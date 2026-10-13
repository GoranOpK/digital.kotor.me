<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\CanonicalApplicantForm;
use App\Models\CanonicalInvestmentPlan;
use App\Models\Competition;
use App\Models\User;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use App\Services\Competitions\Decision\CanonicalAdministrativeGate;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalLegalForm;
use App\Support\CanonicalSubsidyType;
use App\Support\KotorAddress;
use App\Support\Pib;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * KN-SYNC-015 — canonical Obrazac P1a / P1b (čl.16): explicit legal form, draft save, FINAL gate.
 *
 * Draft save accepts partial data; values that are present must be valid. FINAL requires every
 * čl.16 fact for the chosen form. Vrsta subvencije must agree with Obrazac P2 whenever both are set.
 */
final class CanonicalApplicantFormService
{
    public const LEGAL_FORM_REQUIRED_MESSAGE = 'Izaberite pravnu formu podnosioca: preduzetnik (Obrazac P1a) ili društvo (Obrazac P1b).';

    public const NOT_CANONICAL_MESSAGE = 'Obrazac P1a/P1b postoji samo za kanonski MMSP profil.';

    public const OPEN_DRAFT_EXISTS_MESSAGE = 'Već imate započetu prijavu na ovaj javni poziv. Završite je ili obrišite prije nove.';

    public const START_LIMIT_MESSAGE = 'Na ovaj javni poziv ne možete započeti novu prijavu: dostignut je maksimum od dvije konačne prijave.';

    public const SUBSIDY_MISMATCH_MESSAGE = 'Vrsta subvencije u Obrascu P1 mora biti ista kao vrsta subvencije u Planu ulaganja (Obrazac P2).';

    public const ACCURACY_STATEMENT_P1A = 'Za tačnost datih podataka odgovara preduzetnik.';

    public const ACCURACY_STATEMENT_P1B = 'Za tačnost datih podataka odgovara podnosilac prijave.';

    private const ACTIVITY_CODE_PATTERN = '/^(\d{2})\.?(\d{2})$/';

    private const BANK_ACCOUNT_PATTERN = '/^\d[\d\- ]{5,29}$/';

    private const PHONE_PATTERN = '/^[0-9+\/\-\s()]{6,32}$/';

    public function __construct(
        private readonly CanonicalBeneficiaryApplications $beneficiaries,
        private readonly CanonicalCommissionGovernance $governance,
    ) {
    }

    /**
     * Applicant-side start gate (published, open, not closed, not an excluded role, one open draft, max two FINAL).
     */
    public function startBlockReason(Competition $competition, User $user): ?string
    {
        if ($competition->type !== CompetitionProfileResolver::TYPE_CANONICAL) {
            return self::NOT_CANONICAL_MESSAGE;
        }
        if ($competition->status !== 'published') {
            return 'Javni poziv nije objavljen.';
        }
        if (CanonicalAdministrativeGate::isClosed($competition)) {
            return CanonicalAdministrativeGate::CLOSED_MESSAGE;
        }
        if (! $competition->is_open) {
            return $competition->is_upcoming
                ? 'Javni poziv još nije počeo. Prijave će biti moguće od datuma početka.'
                : 'Rok za prijave je istekao.';
        }

        $roleName = $user->role?->name;
        if ($roleName === 'konkurs_admin') {
            return 'Administrator konkursa ne može se prijaviti na konkurse.';
        }
        if ($this->governance->activeMemberFor($user, $competition) !== null) {
            return 'Članovi komisije ne mogu se prijaviti na konkurse za koje su imenovani kao članovi komisije.';
        }

        $existing = $this->beneficiaries->userApplicationForCompetition($competition, (int) $user->id);
        if ($existing !== null) {
            return $existing->status === 'draft' ? self::OPEN_DRAFT_EXISTS_MESSAGE : self::START_LIMIT_MESSAGE;
        }

        return null;
    }

    public function start(Competition $competition, User $user, mixed $legalForm): ProcessWriteResult
    {
        if (! CanonicalLegalForm::isValid($legalForm)) {
            return ProcessWriteResult::failed(self::LEGAL_FORM_REQUIRED_MESSAGE);
        }

        return DB::transaction(function () use ($competition, $user, $legalForm) {
            $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->first();
            if (! $locked) {
                return ProcessWriteResult::failed('Javni poziv nije pronađen.');
            }

            $block = $this->startBlockReason($locked, $user);
            if ($block !== null) {
                return ProcessWriteResult::failed($block);
            }

            $application = Application::query()->create([
                'competition_id' => $locked->id,
                'user_id' => $user->id,
                'applicant_type' => CanonicalLegalForm::applicantType($legalForm),
                'status' => 'draft',
            ]);

            CanonicalApplicantForm::query()->create([
                'application_id' => $application->id,
                'legal_form' => $legalForm,
            ]);

            return ProcessWriteResult::ok((int) $application->id);
        });
    }

    /**
     * Draft save of Obrazac P1a / P1b.
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
            return ProcessWriteResult::failed(self::NOT_CANONICAL_MESSAGE);
        }

        $form = CanonicalApplicantForm::query()->where('application_id', $application->id)->first();
        if (! $form || ! CanonicalLegalForm::isValid($form->legal_form)) {
            return ProcessWriteResult::failed(self::LEGAL_FORM_REQUIRED_MESSAGE);
        }
        if ($form->isFrozen()) {
            return ProcessWriteResult::failed(CanonicalApplicantForm::FROZEN_MESSAGE);
        }
        if ($application->isApplicantContentWriteLocked()) {
            return ProcessWriteResult::failed('Prijava je zaključana i Obrazac P1 više nije moguće mijenjati.');
        }

        [$values, $pib, $errors] = $this->normalize($form->legal_form, $input);

        if (! isset($errors['subsidy_type']) && $values['subsidy_type'] !== null) {
            $p2Type = CanonicalInvestmentPlan::query()->where('application_id', $application->id)->value('subsidy_type');
            if ($p2Type !== null && $p2Type !== $values['subsidy_type']) {
                $errors['subsidy_type'] = self::SUBSIDY_MISMATCH_MESSAGE;
            } else {
                $probe = clone $application;
                $probe->pib = $pib ?? $application->pib;
                if ($this->beneficiaries->subsidyTypeAlreadyTaken($probe, $values['subsidy_type'])) {
                    $errors['subsidy_type'] = CanonicalBeneficiaryApplications::DUPLICATE_TYPE_MESSAGE;
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return DB::transaction(function () use ($application, $values, $pib) {
            $locked = Application::query()->whereKey($application->id)->lockForUpdate()->first();
            if (! $locked || $locked->isApplicantContentWriteLocked()) {
                return ProcessWriteResult::failed('Prijava je zaključana i Obrazac P1 više nije moguće mijenjati.');
            }

            $form = CanonicalApplicantForm::query()
                ->where('application_id', $locked->id)
                ->lockForUpdate()
                ->first();
            if (! $form) {
                return ProcessWriteResult::failed(self::LEGAL_FORM_REQUIRED_MESSAGE);
            }
            if ($form->isFrozen()) {
                return ProcessWriteResult::failed(CanonicalApplicantForm::FROZEN_MESSAGE);
            }

            $confirmed = $values['accuracy_confirmed'];
            unset($values['accuracy_confirmed']);
            $values['accuracy_confirmed_at'] = $confirmed ? ($form->accuracy_confirmed_at ?? now()) : null;

            $form->fill($values)->save();
            $locked->forceFill(['pib' => $pib])->save();

            return ProcessWriteResult::ok((int) $form->id);
        });
    }

    /**
     * Authoritative FINAL gate for Obrazac P1 (legal form, every čl.16 fact, PIB, P1/P2 agreement).
     */
    public function finalBlockReason(Application $application): ?string
    {
        $form = CanonicalApplicantForm::query()->where('application_id', $application->id)->first();
        if (! $form || ! CanonicalLegalForm::isValid($form->legal_form)) {
            return self::LEGAL_FORM_REQUIRED_MESSAGE;
        }
        if ($form->isFrozen()) {
            return CanonicalApplicantForm::FROZEN_MESSAGE;
        }

        $code = $form->formCode();
        $missing = [];
        foreach ($this->requiredFields($form->legal_form) as $field => $label) {
            $value = $form->{$field};
            if ($value === null || (is_string($value) && trim($value) === '')) {
                $missing[] = $label;
            }
        }
        if ($missing !== []) {
            return 'Obrazac '.$code.' nije kompletan. Nedostaje: '.implode(', ', $missing).'.';
        }
        if (Pib::normalize($application->pib) === null) {
            return CanonicalBeneficiaryApplications::PIB_REQUIRED_MESSAGE;
        }

        if (! CanonicalSubsidyType::isValid($form->subsidy_type)) {
            return 'Obrazac '.$code.': vrsta subvencije mora biti jedna od tri vrste iz Odluke.';
        }
        if ($form->accuracy_confirmed_at === null) {
            return 'Obrazac '.$code.': potvrdite izjavu o tačnosti podataka i elektronski potpis.';
        }

        $p2Type = CanonicalInvestmentPlan::query()->where('application_id', $application->id)->value('subsidy_type');
        if ($p2Type !== null && $p2Type !== $form->subsidy_type) {
            return self::SUBSIDY_MISMATCH_MESSAGE;
        }

        return null;
    }

    public function kotorAddressError(Application $application): ?string
    {
        $form = CanonicalApplicantForm::query()->where('application_id', $application->id)->first();
        if (! $form) {
            return self::LEGAL_FORM_REQUIRED_MESSAGE;
        }
        if (KotorAddress::isInKotorMunicipality($form->address)) {
            return null;
        }

        return $form->isEntrepreneur()
            ? KotorAddress::validationMessage()
            : 'Sjedište društva mora biti na teritoriji Opštine Kotor.';
    }

    /**
     * čl.16 "naznaka tačnosti podataka" for the legal form.
     */
    public static function accuracyStatement(string $legalForm): string
    {
        return $legalForm === CanonicalLegalForm::PREDUZETNIK
            ? self::ACCURACY_STATEMENT_P1A
            : self::ACCURACY_STATEMENT_P1B;
    }

    /**
     * @return array<string, string>
     */
    public function requiredFields(string $legalForm): array
    {
        if ($legalForm === CanonicalLegalForm::PREDUZETNIK) {
            return [
                'subsidy_type' => 'vrsta subvencije',
                'person_name' => 'ime i prezime preduzetnika',
                'address' => 'adresa',
                'activity_code' => 'šifra djelatnosti',
                'employee_count' => 'broj zaposlenih',
                'bank_account' => 'broj žiro računa',
                'phone' => 'kontakt telefon',
                'email' => 'e-mail',
                'registration_date' => 'datum registracije u CRPS',
            ];
        }

        return [
            'subsidy_type' => 'vrsta subvencije',
            'company_name' => 'naziv društva',
            'person_name' => 'ime i prezime odgovornog lica',
            'address' => 'sjedište',
            'registration_date' => 'datum osnivanja',
            'activity_code' => 'šifra djelatnosti',
            'employee_count' => 'broj zaposlenih',
            'bank_account' => 'broj žiro računa',
            'phone' => 'kontakt telefon odgovornog lica',
            'email' => 'e-mail odgovornog lica',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{0: array<string, mixed>, 1: ?string, 2: array<string, string>}
     */
    private function normalize(string $legalForm, array $input): array
    {
        $errors = [];
        $text = function (string $field, int $max = 255) use ($input, &$errors): ?string {
            $raw = $input[$field] ?? null;
            if ($raw === null) {
                return null;
            }
            if (! is_scalar($raw)) {
                $errors[$field] = 'Neispravan unos.';

                return null;
            }
            $value = trim((string) $raw);
            if ($value === '') {
                return null;
            }
            if (mb_strlen($value) > $max) {
                $errors[$field] = 'Najviše '.$max.' karaktera.';

                return null;
            }

            return $value;
        };

        $values = [
            'subsidy_type' => $text('subsidy_type', 64),
            'person_name' => $text('person_name'),
            'company_name' => $legalForm === CanonicalLegalForm::DRUSTVO ? $text('company_name') : null,
            'address' => $text('address'),
            'activity_code' => $text('activity_code', 16),
            'employee_count' => null,
            'bank_account' => $text('bank_account', 64),
            'phone' => $text('phone', 64),
            'email' => $text('email'),
            'website' => $text('website'),
            'registration_date' => null,
            'accuracy_confirmed' => in_array((string) ($input['accuracy_confirmed'] ?? ''), ['1', 'on', 'true'], true),
        ];

        if ($values['subsidy_type'] !== null && ! CanonicalSubsidyType::isValid($values['subsidy_type'])) {
            $errors['subsidy_type'] = 'Vrsta subvencije mora biti jedna od tri vrste iz Odluke.';
        }

        if ($values['address'] !== null && ! KotorAddress::isInKotorMunicipality($values['address'])) {
            $errors['address'] = $legalForm === CanonicalLegalForm::PREDUZETNIK
                ? KotorAddress::validationMessage()
                : 'Sjedište društva mora biti na teritoriji Opštine Kotor.';
        }

        if ($values['activity_code'] !== null) {
            if (preg_match(self::ACTIVITY_CODE_PATTERN, $values['activity_code'], $match)) {
                $values['activity_code'] = $match[1].'.'.$match[2];
            } else {
                $errors['activity_code'] = 'Šifra djelatnosti unosi se u obliku 00.00 (npr. 56.10).';
            }
        }

        $employees = $text('employee_count', 9);
        if ($employees !== null) {
            if (ctype_digit($employees) && (int) $employees <= 100000) {
                $values['employee_count'] = (int) $employees;
            } else {
                $errors['employee_count'] = 'Broj zaposlenih mora biti cijeli broj (0 ako nema zaposlenih).';
            }
        }

        if ($values['bank_account'] !== null && ! preg_match(self::BANK_ACCOUNT_PATTERN, $values['bank_account'])) {
            $errors['bank_account'] = 'Broj žiro računa sadrži samo cifre i crtice (npr. 510-0000000000000-00).';
        }
        if ($values['phone'] !== null && ! preg_match(self::PHONE_PATTERN, $values['phone'])) {
            $errors['phone'] = 'Kontakt telefon nije u ispravnom formatu.';
        }
        if ($values['email'] !== null && filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'E-mail adresa nije ispravna.';
        }
        if ($values['website'] !== null) {
            $candidate = preg_match('#^https?://#i', $values['website']) ? $values['website'] : 'https://'.$values['website'];
            if (filter_var($candidate, FILTER_VALIDATE_URL) === false) {
                $errors['website'] = 'Website adresa nije ispravna.';
            }
        }

        $date = $text('registration_date', 10);
        if ($date !== null) {
            $parsed = CanonicalDocumentService::strictDate($date);
            if ($parsed === null) {
                $errors['registration_date'] = 'Datum unesite u obliku GGGG-MM-DD.';
            } elseif ($parsed->greaterThan(now()->startOfDay())) {
                $errors['registration_date'] = 'Datum ne može biti u budućnosti.';
            } else {
                $values['registration_date'] = $date;
            }
        }

        $pib = null;
        $rawPib = $text('pib', 32);
        if ($rawPib !== null) {
            $pib = Pib::normalize($rawPib);
            if ($pib === null) {
                $errors['pib'] = 'PIB mora imati tačno 8 cifara.';
            }
        }

        return [$values, $pib, $errors];
    }
}
