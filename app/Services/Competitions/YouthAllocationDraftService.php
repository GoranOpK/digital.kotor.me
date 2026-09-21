<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\User;
use App\Services\CanonicalIndividualScoringService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Youth 6.15.3 allocation draft: chairman records facts and applies 30/20/15.
 * Does not confirm ranking, resolve equal-score, or change application status.
 */
final class YouthAllocationDraftService
{
    public const RANKING_NOT_READY_MESSAGE =
        'Nacrt raspodjele mladih može se unijeti tek kada je preliminarna rang-lista trajno formirana.';

    public const CHAIRMAN_ONLY_MESSAGE =
        'Nacrt raspodjele mladih unosi samo aktivni predsjednik Komisije konkretnog Poziva.';

    public const BELOW_THRESHOLD_MESSAGE =
        'Prijava ispod praga od 30 bodova ne ulazi u raspodjelu.';

    public const NOT_YOUTH_MESSAGE =
        'Ovaj unos raspodjele važi samo za konkurs mladih.';

    public const WOMEN_STORE_DECISION_CLOSED_MESSAGE =
        'Zaključak raspodjele mladih čuva se kao nacrt na preliminarnom rangu Komisije, ne ženskim ulazom.';

    public const WOMEN_SELECT_WINNERS_CLOSED_MESSAGE =
        'Odabir dobitnika nije dostupan za konkurs mladih.';

    public const WOMEN_PREDLOG_CLOSED_MESSAGE =
        'Predlog odluke nije dostupan za konkurs mladih.';

    public const COMPLETED_LOCKED_MESSAGE =
        'Rang lista je zaključena. Nakon završetka konkursa izmjene nijesu dozvoljene.';

    public const AMOUNT_REQUIRED_MESSAGE =
        'Za zaključak Podržava predloženi iznos podrške je obavezan i mora biti veći od nule.';

    public const AMOUNT_EXCEEDS_REQUESTED_MESSAGE =
        'Odobreni iznos ne može biti veći od traženog iznosa.';

    public const AMOUNT_EXCEEDS_REMAINING_MESSAGE =
        'Odobreni iznos ne može biti veći od preostalih sredstava konkursa.';

    public const AMOUNT_EXCEEDS_PERCENT_CAP_MESSAGE =
        'Odobreni iznos ne može biti veći od primjenjivog maksimuma za ovu prijavu.';

    public const FACTS_REQUIRED_FOR_SUPPORT_MESSAGE =
        'Za zaključak Podržava moraju biti potvrđene činjenice o inovativnom tehnološkom start-upu i ranijem youth finansiranju.';

    public const CAP_IS_NOT_AUTOMATIC_AWARD_MESSAGE =
        'Primijenjeni procenat je maksimum, nije automatska dodjela.';

    public const REJECT_JUSTIFICATION_REQUIRED_MESSAGE =
        'Za zaključak Odbija obrazloženje je obavezno.';

    public const REDUCED_AMOUNT_JUSTIFICATION_REQUIRED_MESSAGE =
        'Kada je iznos manji od traženog, obrazloženje je obavezno.';

    public function __construct(
        protected CanonicalIndividualScoringService $canonicalScoring,
    ) {}

    public function canEditDraft(Competition $competition, User $user): bool
    {
        if (! $competition->isOmladinskoProfile()) {
            return false;
        }

        if (in_array($competition->status, ['closed', 'completed'], true)) {
            return false;
        }

        if (! $this->canonicalScoring->isYouthPreliminaryRankingReady($competition)) {
            return false;
        }

        return $this->activeChairman($competition, $user) !== null;
    }

    public function remainingBudget(Competition $competition, ?int $exceptApplicationId = null): string
    {
        $budget = $this->money((string) ($competition->budget ?? 0));
        $query = Application::query()
            ->where('competition_id', $competition->id)
            ->where('commission_decision', 'podrzava_potpuno')
            ->whereNotNull('approved_amount')
            ->where('approved_amount', '>', 0);

        if ($exceptApplicationId !== null) {
            $query->where('id', '!=', $exceptApplicationId);
        }

        $used = '0.00';
        foreach ($query->pluck('approved_amount') as $amount) {
            $used = bcadd($used, $this->money((string) $amount), 2);
        }

        return bcsub($budget, $used, 2);
    }

    /**
     * @return array{
     *     facts_confirmed: bool,
     *     startup: bool|null,
     *     prior_funding: bool|null,
     *     percent: int|null,
     *     percent_max: string|null,
     *     remaining: string,
     *     confirmed_by_name: string|null,
     *     confirmed_at: string|null
     * }
     */
    public function capSnapshot(Application $application): array
    {
        $application->loadMissing([
            'competition',
            'youthInnovativeTechStartupConfirmedByUser',
            'youthPriorMunicipalYouthFundingConfirmedByUser',
        ]);

        $startup = $this->storedNullableBool($application->getAttributes()['youth_innovative_tech_startup'] ?? null);
        $prior = $this->storedNullableBool($application->getAttributes()['youth_prior_municipal_youth_funding'] ?? null);
        $factsConfirmed = $startup !== null
            && $prior !== null
            && $application->youth_innovative_tech_startup_confirmed_at !== null
            && $application->youth_prior_municipal_youth_funding_confirmed_at !== null;

        $percent = $factsConfirmed ? $this->appliedCapPercent($startup, $prior) : null;
        $budget = $this->money((string) ($application->competition->budget ?? 0));
        $percentMax = $percent !== null
            ? $this->percentOfBudget($budget, $percent)
            : null;

        $confirmedByUser = $application->youthInnovativeTechStartupConfirmedByUser
            ?? $application->youthPriorMunicipalYouthFundingConfirmedByUser;
        $confirmedByName = $confirmedByUser?->name;
        $commissionId = $application->competition?->commission_id;
        if ($confirmedByUser !== null && $commissionId) {
            $member = CommissionMember::activeForCommission((int) $confirmedByUser->id, (int) $commissionId);
            if ($member !== null && trim((string) $member->name) !== '') {
                $confirmedByName = $member->name;
            }
        }

        $confirmedAt = $application->youth_innovative_tech_startup_confirmed_at
            ?? $application->youth_prior_municipal_youth_funding_confirmed_at;

        return [
            'facts_confirmed' => $factsConfirmed,
            'startup' => $startup,
            'prior_funding' => $prior,
            'percent' => $percent,
            'percent_max' => $percentMax,
            'remaining' => $this->remainingBudget($application->competition, $application->id),
            'confirmed_by_name' => $confirmedByName,
            'confirmed_at' => $confirmedAt?->format('d.m.Y. H:i'),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function saveDraft(Application $application, User $user, array $input): void
    {
        $application->loadMissing('competition');
        $competition = $application->competition;

        if ($competition === null || ! $competition->isOmladinskoProfile()) {
            abort(403, self::NOT_YOUTH_MESSAGE);
        }

        if (in_array($competition->status, ['closed', 'completed'], true)) {
            abort(403, self::COMPLETED_LOCKED_MESSAGE);
        }

        $chairman = $this->activeChairman($competition, $user);
        if ($chairman === null) {
            abort(403, self::CHAIRMAN_ONLY_MESSAGE);
        }

        $this->canonicalScoring->persistYouthPreliminaryRankingIfReady($competition);
        $competition->refresh();

        if (! $this->canonicalScoring->isYouthPreliminaryRankingReady($competition)) {
            abort(403, self::RANKING_NOT_READY_MESSAGE);
        }

        DB::transaction(function () use ($application, $input, $competition, $user) {
            $locked = Application::query()->whereKey($application->id)->lockForUpdate()->first();
            if ($locked === null) {
                abort(404);
            }

            $locked->loadMissing('competition');
            $lockedCompetition = $locked->competition ?? $competition->fresh();

            if (! $this->canonicalScoring->youthMeetsMinimumScore($locked)) {
                abort(403, self::BELOW_THRESHOLD_MESSAGE);
            }

            $decision = (string) ($input['commission_decision'] ?? '');
            $justification = trim((string) ($input['commission_justification'] ?? ''));
            $approvedAmount = array_key_exists('approved_amount', $input) ? $input['approved_amount'] : null;
            $startup = $this->nullableBoolFromInput($input, 'youth_innovative_tech_startup');
            $prior = $this->nullableBoolFromInput($input, 'youth_prior_municipal_youth_funding');
            $confirmedAt = now();

            if ($decision === 'podrzava_potpuno') {
                if ($startup === null || $prior === null) {
                    throw ValidationException::withMessages([
                        'youth_innovative_tech_startup' => self::FACTS_REQUIRED_FOR_SUPPORT_MESSAGE,
                        'youth_prior_municipal_youth_funding' => self::FACTS_REQUIRED_FOR_SUPPORT_MESSAGE,
                    ]);
                }

                if ($approvedAmount === null || $approvedAmount === '' || bccomp($this->money((string) $approvedAmount), '0', 2) <= 0) {
                    throw ValidationException::withMessages([
                        'approved_amount' => self::AMOUNT_REQUIRED_MESSAGE,
                    ]);
                }

                $amount = $this->money((string) $approvedAmount);
                $requested = $locked->requested_amount;
                if ($requested !== null && bccomp($amount, $this->money((string) $requested), 2) === 1) {
                    throw ValidationException::withMessages([
                        'approved_amount' => self::AMOUNT_EXCEEDS_REQUESTED_MESSAGE,
                    ]);
                }

                if (
                    $requested !== null
                    && bccomp($amount, $this->money((string) $requested), 2) === -1
                    && $justification === ''
                ) {
                    throw ValidationException::withMessages([
                        'commission_justification' => self::REDUCED_AMOUNT_JUSTIFICATION_REQUIRED_MESSAGE,
                    ]);
                }

                $percent = $this->appliedCapPercent($startup, $prior);
                $percentMax = $this->percentOfBudget(
                    $this->money((string) ($lockedCompetition->budget ?? 0)),
                    $percent
                );
                if (bccomp($amount, $percentMax, 2) === 1) {
                    throw ValidationException::withMessages([
                        'approved_amount' => self::AMOUNT_EXCEEDS_PERCENT_CAP_MESSAGE,
                    ]);
                }

                $remaining = $this->remainingBudget($lockedCompetition, $locked->id);
                if (bccomp($amount, $remaining, 2) === 1) {
                    throw ValidationException::withMessages([
                        'approved_amount' => self::AMOUNT_EXCEEDS_REMAINING_MESSAGE,
                    ]);
                }

                $locked->forceFill([
                    'commission_decision' => 'podrzava_potpuno',
                    'approved_amount' => $amount,
                    'commission_justification' => $justification !== '' ? $justification : null,
                    'commission_decision_date' => now(),
                    'youth_innovative_tech_startup' => $startup,
                    'youth_innovative_tech_startup_confirmed_at' => $confirmedAt,
                    'youth_innovative_tech_startup_confirmed_by_user_id' => $user->id,
                    'youth_prior_municipal_youth_funding' => $prior,
                    'youth_prior_municipal_youth_funding_confirmed_at' => $confirmedAt,
                    'youth_prior_municipal_youth_funding_confirmed_by_user_id' => $user->id,
                    'youth_applied_cap_percent' => $percent,
                ])->save();
            } elseif ($decision === 'odbija') {
                if ($justification === '') {
                    throw ValidationException::withMessages([
                        'commission_justification' => self::REJECT_JUSTIFICATION_REQUIRED_MESSAGE,
                    ]);
                }

                $rejectFill = [
                    'commission_decision' => 'odbija',
                    'approved_amount' => null,
                    'commission_justification' => $justification,
                    'commission_decision_date' => now(),
                    'youth_applied_cap_percent' => null,
                ];

                if ($startup !== null && $prior !== null) {
                    $rejectFill['youth_innovative_tech_startup'] = $startup;
                    $rejectFill['youth_innovative_tech_startup_confirmed_at'] = $confirmedAt;
                    $rejectFill['youth_innovative_tech_startup_confirmed_by_user_id'] = $user->id;
                    $rejectFill['youth_prior_municipal_youth_funding'] = $prior;
                    $rejectFill['youth_prior_municipal_youth_funding_confirmed_at'] = $confirmedAt;
                    $rejectFill['youth_prior_municipal_youth_funding_confirmed_by_user_id'] = $user->id;
                }

                $locked->forceFill($rejectFill)->save();
            } else {
                throw ValidationException::withMessages([
                    'commission_decision' => 'Morate odabrati zaključak komisije.',
                ]);
            }

            $locked->refresh();
            if ($locked->status !== 'evaluated') {
                $locked->forceFill(['status' => 'evaluated'])->save();
            }
        });
    }

    public function appliedCapPercent(bool $innovativeTechStartup, bool $priorMunicipalYouthFunding): int
    {
        if ($innovativeTechStartup) {
            return 30;
        }

        if (! $priorMunicipalYouthFunding) {
            return 20;
        }

        return 15;
    }

    private function percentOfBudget(string $budget, int $percent): string
    {
        return bcmul($budget, bcdiv((string) $percent, '100', 2), 2);
    }

    private function activeChairman(Competition $competition, User $user): ?CommissionMember
    {
        if (! $competition->commission_id) {
            return null;
        }

        $member = CommissionMember::activeForCommission((int) $user->id, (int) $competition->commission_id);
        if ($member === null || $member->position !== 'predsjednik') {
            return null;
        }

        return $member;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function nullableBoolFromInput(array $input, string $key): ?bool
    {
        if (! array_key_exists($key, $input) || $input[$key] === null || $input[$key] === '') {
            return null;
        }

        $value = $input[$key];
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
    }

    private function storedNullableBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        return (int) $value === 1;
    }

    private function money(string $value): string
    {
        return bcadd($value, '0', 2);
    }
}
