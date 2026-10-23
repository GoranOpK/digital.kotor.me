<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\CanonicalApplicantForm;
use App\Models\CanonicalInvestmentPlan;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalSubsidyType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * KN-SYNC-008A — canonical P2 (čl.17) subsidy type + finansijski okvir ulaganja.
 *
 * Decimal-safe (bcmath, scale 2). The explicit P2 total is the only required-funds
 * basis: no fallback to BusinessPlan required/requested amounts, expense projections
 * or applications.total_budget_needed. No čl.19 cap is enforced on the applicant side.
 */
final class CanonicalInvestmentPlanService
{
    public const SCALE = 2;

    public const MAX_EXPENSE_LINES = 50;

    private const AMOUNT_PATTERN = '/^\d{1,12}(?:[.,]\d{1,2})?$/';

    public function __construct(
        private readonly CanonicalBeneficiaryApplications $beneficiaries,
    ) {
    }

    /**
     * Draft save. Presence is only required at FINAL; values that are present must be consistent.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function save(Application $application, User $actor, array $input): ProcessWriteResult
    {
        if ((int) $application->user_id !== (int) $actor->id) {
            return ProcessWriteResult::failed('Nemate pristup ovoj prijavi.');
        }

        $application->loadMissing('competition');
        if ($application->competition?->type !== CompetitionProfileResolver::TYPE_CANONICAL) {
            return ProcessWriteResult::failed('Plan ulaganja (Obrazac P2) postoji samo za kanonski MMSP profil.');
        }

        if ($application->isApplicantContentWriteLocked()) {
            return ProcessWriteResult::failed('Prijava je zaključana i plan ulaganja više nije moguće mijenjati.');
        }

        [$values, $errors] = $this->normalize($input);
        $errors += $this->consistencyErrors($values, false);

        if ($errors === [] && $values['subsidy_type'] !== null) {
            $p1Type = CanonicalApplicantForm::query()->where('application_id', $application->id)->value('subsidy_type');
            if ($p1Type !== null && $p1Type !== $values['subsidy_type']) {
                $errors['subsidy_type'] = CanonicalApplicantFormService::SUBSIDY_MISMATCH_MESSAGE;
            } elseif ($this->beneficiaries->subsidyTypeAlreadyTaken($application, $values['subsidy_type'])) {
                $errors['subsidy_type'] = CanonicalBeneficiaryApplications::DUPLICATE_TYPE_MESSAGE;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return DB::transaction(function () use ($application, $values) {
            $locked = Application::query()->whereKey($application->id)->lockForUpdate()->first();
            if (! $locked || $locked->isApplicantContentWriteLocked()) {
                return ProcessWriteResult::failed('Prijava je zaključana i plan ulaganja više nije moguće mijenjati.');
            }

            $plan = CanonicalInvestmentPlan::query()
                ->where('application_id', $locked->id)
                ->lockForUpdate()
                ->first() ?? new CanonicalInvestmentPlan(['application_id' => $locked->id]);

            if ($plan->isFrozen()) {
                return ProcessWriteResult::failed(CanonicalInvestmentPlan::FROZEN_MESSAGE);
            }

            $plan->fill($values)->save();

            return ProcessWriteResult::ok((int) $plan->id);
        });
    }

    /**
     * Authoritative FINAL gate: every P2 legal fact present and consistent.
     */
    public function finalBlockReason(Application $application): ?string
    {
        $plan = CanonicalInvestmentPlan::query()->where('application_id', $application->id)->first();
        if (! $plan) {
            return 'Morate popuniti plan ulaganja (Obrazac P2): vrstu subvencije i finansijski okvir ulaganja prije konačne predaje.';
        }

        if ($plan->isFrozen()) {
            return CanonicalInvestmentPlan::FROZEN_MESSAGE;
        }

        $errors = $this->consistencyErrors([
            'subsidy_type' => $plan->subsidy_type,
            'total_investment' => $plan->total_investment,
            'own_contribution' => $plan->own_contribution,
            'expected_subsidy' => $plan->expected_subsidy,
            'expense_lines' => $plan->expenseLines(),
        ], true);

        return $errors === [] ? null : 'Plan ulaganja (Obrazac P2) nije kompletan: '.reset($errors);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{0: array{subsidy_type: ?string, total_investment: ?string, own_contribution: ?string, expected_subsidy: ?string, expense_lines: list<array{cost_type: string, description: string, amount: string}>}, 1: array<string, string>}
     */
    private function normalize(array $input): array
    {
        $errors = [];

        $type = $input['subsidy_type'] ?? null;
        $type = is_string($type) && trim($type) !== '' ? trim($type) : null;

        $amounts = [];
        foreach (['total_investment', 'own_contribution', 'expected_subsidy'] as $field) {
            $raw = $input[$field] ?? null;
            $amounts[$field] = $this->amount($raw);
            if ($amounts[$field] === false) {
                $errors[$field] = 'Iznos mora biti broj sa najviše dvije decimale (npr. 1500 ili 1500,50).';
                $amounts[$field] = null;
            }
        }

        $lines = [];
        $rawLines = $input['expense_lines'] ?? [];
        if (! is_array($rawLines)) {
            $errors['expense_lines'] = 'Stavke troškova nisu u ispravnom formatu.';
            $rawLines = [];
        }
        if (count($rawLines) > self::MAX_EXPENSE_LINES) {
            $errors['expense_lines'] = 'Najviše '.self::MAX_EXPENSE_LINES.' stavki troškova.';
            $rawLines = [];
        }

        foreach (array_values($rawLines) as $index => $rawLine) {
            $rawLine = is_array($rawLine) ? $rawLine : [];
            $costType = trim((string) ($rawLine['cost_type'] ?? ''));
            $description = trim((string) ($rawLine['description'] ?? ''));
            $rawAmount = $rawLine['amount'] ?? null;

            if ($costType === '' && $description === '' && ($rawAmount === null || trim((string) $rawAmount) === '')) {
                continue;
            }

            $key = 'expense_lines.'.$index;
            $amount = $this->amount($rawAmount);
            if ($costType === '' || mb_strlen($costType) > 255) {
                $errors[$key.'.cost_type'] = 'Vrsta troška je obavezna (najviše 255 karaktera).';
            }
            if ($description === '' || mb_strlen($description) > 2000) {
                $errors[$key.'.description'] = 'Opis troška je obavezan (najviše 2000 karaktera).';
            }
            if ($amount === false || $amount === null) {
                $errors[$key.'.amount'] = 'Iznos stavke mora biti broj sa najviše dvije decimale.';
                continue;
            }

            $lines[] = ['cost_type' => $costType, 'description' => $description, 'amount' => $amount];
        }

        return [[
            'subsidy_type' => $type,
            'total_investment' => $amounts['total_investment'],
            'own_contribution' => $amounts['own_contribution'],
            'expected_subsidy' => $amounts['expected_subsidy'],
            'expense_lines' => $lines,
        ], $errors];
    }

    /**
     * @param  array{subsidy_type: ?string, total_investment: ?string, own_contribution: ?string, expected_subsidy: ?string, expense_lines: list<array{cost_type: string, description: string, amount: string}>}  $values
     * @return array<string, string>
     */
    private function consistencyErrors(array $values, bool $requireComplete): array
    {
        $errors = [];
        $total = $values['total_investment'];
        $own = $values['own_contribution'];
        $expected = $values['expected_subsidy'];
        $lines = $values['expense_lines'];

        if ($values['subsidy_type'] === null) {
            if ($requireComplete) {
                $errors['subsidy_type'] = 'Vrsta subvencije je obavezna.';
            }
        } elseif (! CanonicalSubsidyType::isValid($values['subsidy_type'])) {
            $errors['subsidy_type'] = 'Vrsta subvencije mora biti jedna od tri vrste iz Odluke.';
        }

        if ($total === null) {
            if ($requireComplete) {
                $errors['total_investment'] = 'Ukupna vrijednost ulaganja je obavezna.';
            }
        } elseif (bccomp($total, '0', self::SCALE) <= 0) {
            $errors['total_investment'] = 'Ukupna vrijednost ulaganja mora biti veća od 0.';
        }

        if ($own === null) {
            if ($requireComplete) {
                $errors['own_contribution'] = 'Sopstveno učešće je obavezno (unesite 0 ako ga nema).';
            }
        } elseif (bccomp($own, '0', self::SCALE) < 0) {
            $errors['own_contribution'] = 'Sopstveno učešće ne može biti negativno.';
        } elseif ($total !== null && bccomp($own, $total, self::SCALE) > 0) {
            $errors['own_contribution'] = 'Sopstveno učešće ne može biti veće od ukupne vrijednosti ulaganja.';
        }

        if ($expected === null) {
            if ($requireComplete) {
                $errors['expected_subsidy'] = 'Očekivani iznos subvencije je obavezan.';
            }
        } elseif (bccomp($expected, '0', self::SCALE) <= 0) {
            $errors['expected_subsidy'] = 'Očekivani iznos subvencije mora biti veći od 0.';
        } elseif ($total !== null && bccomp($expected, $total, self::SCALE) > 0) {
            $errors['expected_subsidy'] = 'Očekivani iznos subvencije ne može biti veći od ukupne vrijednosti ulaganja.';
        }

        if ($lines === []) {
            if ($requireComplete) {
                $errors['expense_lines'] = 'Unesite najmanje jednu stavku troška (vrsta troška, opis, iznos).';
            }

            return $errors;
        }

        $sum = '0.00';
        foreach ($lines as $index => $line) {
            if (! is_array($line) || ! isset($line['amount']) || ! is_string($line['amount'])
                || ! preg_match('/^\d{1,12}\.\d{2}$/', $line['amount'])
                || trim((string) ($line['cost_type'] ?? '')) === ''
                || trim((string) ($line['description'] ?? '')) === '') {
                $errors['expense_lines'] = 'Stavka troška '.($index + 1).' nije ispravna.';

                return $errors;
            }
            if (bccomp($line['amount'], '0', self::SCALE) <= 0) {
                $errors['expense_lines.'.$index.'.amount'] = 'Iznos stavke troška mora biti veći od 0.';
            }
            $sum = bcadd($sum, $line['amount'], self::SCALE);
        }

        if ($total !== null && ! isset($errors['total_investment']) && bccomp($sum, $total, self::SCALE) !== 0) {
            $errors['expense_lines'] = 'Zbir stavki troškova ('.$sum.' EUR) mora biti jednak ukupnoj vrijednosti ulaganja ('.$total.' EUR).';
        }

        return $errors;
    }

    /**
     * Canonical "12345.67" string, null when empty, false when malformed.
     */
    private function amount(mixed $raw): string|false|null
    {
        if ($raw === null) {
            return null;
        }
        if (is_int($raw)) {
            $raw = (string) $raw;
        }
        if (! is_string($raw)) {
            return false;
        }

        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        if (! preg_match(self::AMOUNT_PATTERN, $raw)) {
            return false;
        }

        return bcadd(str_replace(',', '.', $raw), '0', self::SCALE);
    }
}
