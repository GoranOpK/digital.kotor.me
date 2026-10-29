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
 * Youth allocation decision: chairman records one final decision and applies 30/20/15.
 * The first successful save locks the outcome, amount and justification.
 * Does not confirm the whole list, resolve equal-score voting, or close the competition.
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
        'Za zaključak Podržava moraju biti potvrđene činjenice o inovativnom tehnološkom start-upu i finansiranju podrške preduzetništvu mladih.';

    public const CAP_IS_NOT_AUTOMATIC_AWARD_MESSAGE =
        'Primijenjeni procenat je maksimum, nije automatska dodjela.';

    public const REJECT_JUSTIFICATION_REQUIRED_MESSAGE =
        'Za zaključak Odbija obrazloženje je obavezno.';

    public const REDUCED_AMOUNT_JUSTIFICATION_REQUIRED_MESSAGE =
        'Kada je iznos manji od traženog, obrazloženje je obavezno.';

    public const DECISION_LOCKED_MESSAGE =
        'Odluka je konačna i ne može se mijenjati.';

    public const DECISION_LOCK_NOTICE =
        'Nakon čuvanja odluka je konačna i ne može se mijenjati.';

    public function __construct(
        protected CanonicalIndividualScoringService $canonicalScoring,
        protected YouthEqualScoreVotingService $youthEqualScoreVoting,
        protected AllocationCompletionGuard $completion,
    ) {}

    public function canEditDraft(Competition $competition, User $user): bool
    {
        if (! $competition->isOmladinskoProfile()) {
            return false;
        }

        if (in_array($competition->status, ['closed', 'completed'], true)) {
            return false;
        }

        if ($competition->youth_allocation_list_confirmed_at !== null) {
            return false;
        }

        if (! $this->canonicalScoring->isYouthPreliminaryRankingReady($competition)) {
            return false;
        }

        return $this->activeChairman($competition, $user) !== null;
    }

    public function remainingBudget(Competition $competition, ?int $exceptApplicationId = null): string
    {
        if (! $competition->isOmladinskoProfile()) {
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

        $application = $exceptApplicationId !== null
            ? Application::query()->find($exceptApplicationId)
            : null;

        if ($application !== null) {
            return $this->youthEqualScoreVoting->liveSupportRoom($competition, $application);
        }

        return $this->youthEqualScoreVoting->remainingBeforeApplication($competition, null);
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

        if ($competition->youth_allocation_list_confirmed_at !== null) {
            abort(403, YouthAllocationListConfirmationService::LIST_CONFIRMED_LOCKED_MESSAGE);
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
            $lockedCompetition = Competition::query()->whereKey($competition->id)->lockForUpdate()->first();
            if ($lockedCompetition === null || ! $lockedCompetition->isOmladinskoProfile()) {
                abort(403, self::NOT_YOUTH_MESSAGE);
            }

            if (in_array($lockedCompetition->status, ['closed', 'completed'], true)) {
                abort(403, self::COMPLETED_LOCKED_MESSAGE);
            }

            if ($lockedCompetition->youth_allocation_list_confirmed_at !== null) {
                abort(403, YouthAllocationListConfirmationService::LIST_CONFIRMED_LOCKED_MESSAGE);
            }

            $lockedRows = Application::query()
                ->where('competition_id', $lockedCompetition->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $locked = $lockedRows->firstWhere('id', $application->id);
            if ($locked === null) {
                abort(404);
            }

            $locked->setRelation('competition', $lockedCompetition);

            if (! $this->canonicalScoring->youthMeetsMinimumScore($locked)) {
                abort(403, self::BELOW_THRESHOLD_MESSAGE);
            }

            if ($this->decisionIsLocked($locked)) {
                abort(403, self::DECISION_LOCKED_MESSAGE);
            }

            $decision = (string) ($input['commission_decision'] ?? '');
            $pool = $lockedRows
                ->filter(fn (Application $row) => $row->ranking_position !== null
                    && $this->canonicalScoring->youthMeetsMinimumScore($row))
                ->each(fn (Application $row) => $row->setRelation('competition', $lockedCompetition))
                ->values();
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

                if ($this->completion->developmentSupportBlocked($pool, $locked)) {
                    abort(403, AllocationCompletionGuard::DEVELOPMENT_INSTEAD_OF_STARTUP_MESSAGE);
                }

                $budget = $this->money((string) ($lockedCompetition->budget ?? 0));
                $remaining = $this->completion->supportRoom(
                    $budget,
                    $pool,
                    $locked,
                    fn (Application $other) => $this->completion->youthMaxClaim($other, $budget),
                );
                if (bccomp($amount, $remaining, 2) === 1) {
                    throw ValidationException::withMessages([
                        'approved_amount' => self::AMOUNT_EXCEEDS_REMAINING_MESSAGE,
                    ]);
                }

                $locked->forceFill([
                    'status' => 'approved',
                    'commission_decision' => 'podrzava_potpuno',
                    'approved_amount' => $amount,
                    'commission_justification' => $justification !== '' ? $justification : null,
                    'rejection_reason' => null,
                    'commission_decision_date' => now(),
                    'signed_by_chairman' => true,
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

                if ($this->completion->startupRejectionBlocked($pool, $locked)) {
                    abort(403, AllocationCompletionGuard::DEVELOPMENT_INSTEAD_OF_STARTUP_MESSAGE);
                }

                $rejectFill = [
                    'status' => 'rejected',
                    'commission_decision' => 'odbija',
                    'approved_amount' => '0.00',
                    'commission_justification' => $justification,
                    'rejection_reason' => $justification,
                    'commission_decision_date' => now(),
                    'signed_by_chairman' => true,
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
        });
    }

    public function decisionIsLocked(Application $application): bool
    {
        return in_array($application->commission_decision, ['podrzava_potpuno', 'odbija'], true);
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
