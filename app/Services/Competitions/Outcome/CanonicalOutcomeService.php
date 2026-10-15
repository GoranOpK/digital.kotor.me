<?php

namespace App\Services\Competitions\Outcome;

use App\Models\Application;
use App\Models\CanonicalFinalRanking;
use App\Models\CanonicalFinalRankingSignature;
use App\Models\CanonicalInvestmentPlan;
use App\Models\CanonicalOutcome;
use App\Models\CanonicalTieResolution;
use App\Models\CommissionMember;
use App\Models\CommissionSession;
use App\Models\Competition;
use App\Models\User;
use App\Services\CanonicalIndividualScoringService;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use App\Services\Competitions\ProcessWriteResult;
use App\Support\CompetitionRanking;
use App\Support\Pib;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Canonical MMSP third-session outcome (KN-SYNC-008).
 *
 * DRAFT: the chair records support/reject, amount, reason, justification and remarks per ranked
 * application. Drafts never change application status.
 * FINAL CONFIRMATION: one atomic transaction over the complete outcome set.
 *   Lock order: competition → applications (id) → P2 → outcomes → tie resolutions → Commission members.
 *   Re-checks ranking, score/rank integrity, below-30, caps, budget, one-support, ties, Commission, session.
 *   Then supported → approved, unsupported → rejected; everything becomes immutable.
 * SIGNATURES: 3/3 valid seats finalize the ranking and prepare the private Predlog snapshot.
 */
final class CanonicalOutcomeService
{
    public const CHAIR_ONLY_MESSAGE = 'Ishod na trećoj sjednici evidentira i potvrđuje predsjednik kanonske Komisije.';

    public const RANKING_REQUIRED_MESSAGE = 'Ishod se utvrđuje tek nakon formirane i zaključane preliminarne rang-liste.';

    public const NOT_RANKED_MESSAGE = 'Prijava nije na preliminarnoj rang-listi ovog Poziva.';

    public const ALREADY_CONFIRMED_MESSAGE = 'Ishod je konačno potvrđen. Izmjene nijesu dozvoljene.';

    public const RANKING_CHANGED_MESSAGE = 'Preliminarna rang-lista ili konačne ocjene ne odgovaraju zaključanim ocjenama. Potvrda je zaustavljena.';

    public const ORPHAN_OUTCOME_MESSAGE = 'Postoji ishod za prijavu koja nije na rang-listi. Potvrda je zaustavljena.';

    public const NOT_CONFIRMED_MESSAGE = 'Konačna rang lista još nije potvrđena.';

    public const ALREADY_FINAL_MESSAGE = 'Konačna rang lista je već potpisana od sva 3 člana Komisije.';

    public const DUPLICATE_SIGNATURE_MESSAGE = 'Ovo mjesto Komisije je već potpisalo konačnu rang listu.';

    public const TEXT_TOO_LONG_MESSAGE = 'Obrazloženje i napomene mogu imati najviše 5000 karaktera.';

    public const MAX_TEXT_LENGTH = 5000;

    public function __construct(
        private readonly CanonicalCommissionGovernance $governance,
        private readonly CanonicalIndividualScoringService $scoring,
        private readonly CanonicalAmountCapPolicy $caps,
        private readonly CanonicalAllocationPolicy $allocation,
        private readonly CanonicalTieResolutionService $ties,
        private readonly CanonicalPredlogService $predlog,
    ) {
    }

    /**
     * Frozen ranking facts ordered by rank (id only orders display inside a shared rank).
     *
     * @param  Collection<int, Application>|null  $ranked
     * @return list<CanonicalRankedRow>
     */
    public function rankedRows(Competition $competition, ?Collection $ranked = null): array
    {
        $ranked ??= Application::query()
            ->where('competition_id', $competition->id)
            ->whereNotNull('ranking_position')
            ->get();
        $ranked = (new EloquentCollection($ranked->all()))->loadMissing(['finalClaim', 'canonicalInvestmentPlan']);

        $budget = $this->budget($competition);
        $businessStart = $this->governance->policy()->businessStartStoredValue();

        return $ranked
            ->sort(fn (Application $a, Application $b) => [(int) $a->ranking_position, (int) $a->id] <=> [(int) $b->ranking_position, (int) $b->id])
            ->map(function (Application $application) use ($budget, $businessStart) {
                $plan = $application->canonicalInvestmentPlan;
                $frozen = $plan !== null && $plan->isFrozen();
                $subsidyType = $frozen ? $plan->subsidy_type : null;
                $total = $frozen ? (string) $plan->total_investment : null;

                return new CanonicalRankedRow(
                    application: $application,
                    rank: (int) $application->ranking_position,
                    calc: (string) $application->final_score_calc,
                    display: (string) $application->final_score,
                    pib: Pib::normalize($application->finalClaim?->beneficiary_pib) ?? Pib::normalize($application->pib),
                    subsidyType: $subsidyType,
                    totalInvestment: $total,
                    businessStart: $businessStart !== null && $application->business_stage === $businessStart,
                    legalMaximum: $this->caps->legalMaximum($budget, $subsidyType, $total),
                );
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $input  decision, amount, reason, justification, remarks
     */
    public function recordDraft(Competition $competition, User $actor, int $applicationId, array $input): ProcessWriteResult
    {
        $gate = $this->stageBlockReason($competition);
        if ($gate !== null) {
            return ProcessWriteResult::failed($gate);
        }
        [$chair, $chairReason] = $this->chair($actor, $competition);
        if ($chairReason !== null) {
            return ProcessWriteResult::failed($chairReason);
        }

        $outcome = $this->allocation->normalizeOutcome($input);
        if (mb_strlen((string) $outcome['justification']) > self::MAX_TEXT_LENGTH || mb_strlen((string) $outcome['remarks']) > self::MAX_TEXT_LENGTH) {
            return ProcessWriteResult::failed(self::TEXT_TOO_LONG_MESSAGE);
        }

        return DB::transaction(function () use ($competition, $chair, $applicationId, $outcome) {
            $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->firstOrFail();
            if (CanonicalFinalRanking::query()->where('competition_id', $locked->id)->exists()) {
                return ProcessWriteResult::failed(self::ALREADY_CONFIRMED_MESSAGE);
            }

            $application = Application::query()
                ->where('competition_id', $locked->id)
                ->whereKey($applicationId)
                ->whereNotNull('ranking_position')
                ->first();
            if ($application === null) {
                return ProcessWriteResult::failed(self::NOT_RANKED_MESSAGE);
            }

            $drafts = CanonicalOutcome::query()->where('competition_id', $locked->id)->orderBy('id')->lockForUpdate()->get();
            $otherSupported = '0.00';
            foreach ($drafts as $draft) {
                if ((int) $draft->application_id !== (int) $application->id && $draft->isSupport()) {
                    $otherSupported = bcadd($otherSupported, (string) $draft->approved_amount, CanonicalAmountCapPolicy::SCALE);
                }
            }
            $remaining = bcsub($this->budget($locked), $otherSupported, CanonicalAmountCapPolicy::SCALE);

            $row = $this->rankedRows($locked, collect([$application]))[0];
            $reason = $this->allocation->rowBlockReason($row, $outcome, $remaining);
            if ($reason !== null) {
                return ProcessWriteResult::failed($reason);
            }

            $record = CanonicalOutcome::query()->updateOrCreate(
                ['application_id' => $application->id],
                [
                    'competition_id' => $locked->id,
                    'decision' => $outcome['decision'],
                    'approved_amount' => $outcome['decision'] === CanonicalOutcome::SUPPORT ? $outcome['amount'] : null,
                    'rejection_reason' => $outcome['decision'] === CanonicalOutcome::SUPPORT ? null : $outcome['reason'],
                    'justification' => $outcome['justification'],
                    'remarks' => $outcome['remarks'],
                    'recorded_by_member_id' => $chair->id,
                ],
            );

            return ProcessWriteResult::ok($record->id);
        });
    }

    public function confirm(Competition $competition, User $actor): ProcessWriteResult
    {
        $gate = $this->stageBlockReason($competition);
        if ($gate !== null) {
            return ProcessWriteResult::failed($gate);
        }
        [, $chairReason] = $this->chair($actor, $competition);
        if ($chairReason !== null) {
            return ProcessWriteResult::failed($chairReason);
        }

        return DB::transaction(function () use ($competition, $actor) {
            $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->firstOrFail();
            if ($locked->canonical_ranking_formed_at === null) {
                return ProcessWriteResult::failed(self::RANKING_REQUIRED_MESSAGE);
            }
            if (CanonicalFinalRanking::query()->where('competition_id', $locked->id)->exists()) {
                return ProcessWriteResult::failed(self::ALREADY_CONFIRMED_MESSAGE);
            }

            $applications = Application::query()->where('competition_id', $locked->id)->orderBy('id')->lockForUpdate()->get();
            $ranked = $applications->filter(fn (Application $application) => $application->ranking_position !== null)->values();
            CanonicalInvestmentPlan::query()->whereIn('application_id', $ranked->pluck('id'))->orderBy('id')->lockForUpdate()->get();
            $outcomes = CanonicalOutcome::query()->where('competition_id', $locked->id)->orderBy('id')->lockForUpdate()->get();
            $resolutions = CanonicalTieResolution::query()->where('competition_id', $locked->id)->orderBy('id')->lockForUpdate()->get();
            CommissionMember::query()->where('commission_id', $locked->commission_id)->orderBy('id')->lockForUpdate()->get();

            $gate = $this->stageBlockReason($locked);
            if ($gate !== null) {
                return ProcessWriteResult::failed($gate);
            }
            [$chair, $chairReason] = $this->chair($actor, $locked);
            if ($chairReason !== null) {
                return ProcessWriteResult::failed($chairReason);
            }

            if (! $this->rankingIsIntact($ranked)) {
                return ProcessWriteResult::failed(self::RANKING_CHANGED_MESSAGE);
            }
            $rankedIds = $ranked->pluck('id')->map(fn ($id) => (int) $id)->all();
            if ($outcomes->contains(fn (CanonicalOutcome $outcome) => ! in_array((int) $outcome->application_id, $rankedIds, true))) {
                return ProcessWriteResult::failed(self::ORPHAN_OUTCOME_MESSAGE);
            }

            $rows = $this->rankedRows($locked, $ranked);
            $inputs = $outcomes->mapWithKeys(fn (CanonicalOutcome $outcome) => [(int) $outcome->application_id => $this->outcomeInput($outcome)])->all();
            $adopted = $resolutions
                ->filter(fn (CanonicalTieResolution $resolution) => $this->ties->adoptedResolutionBlockReason($resolution, $locked) === null)
                ->values();

            $budget = $this->budget($locked);
            $reason = $this->allocation->setBlockReason($budget, $rows, $inputs, $adopted);
            if ($reason !== null) {
                return ProcessWriteResult::failed($reason);
            }

            $session = $this->finalDecisionSession($locked);
            $ranking = CanonicalFinalRanking::query()->create([
                'competition_id' => $locked->id,
                'commission_id' => $locked->commission_id,
                'final_decision_session_id' => $session->id,
                'confirmed_by_member_id' => $chair->id,
                'budget' => $budget,
                'total_approved' => $this->allocation->totalSupported($rows, $inputs),
                'confirmed_at' => now(),
            ]);

            $byApplication = $outcomes->keyBy(fn (CanonicalOutcome $outcome) => (int) $outcome->application_id);
            foreach ($rows as $row) {
                $outcome = $byApplication[$row->id()];
                $row->application->forceFill($outcome->isSupport()
                    ? ['status' => 'approved', 'approved_amount' => (string) $outcome->approved_amount, 'rejection_reason' => null]
                    : ['status' => 'rejected', 'approved_amount' => null, 'rejection_reason' => CanonicalOutcome::REASON_LABELS[$outcome->rejection_reason]]
                )->save();
            }

            foreach ($outcomes as $outcome) {
                $outcome->forceFill(['final_ranking_id' => $ranking->id, 'confirmed_at' => now()])->save();
            }

            foreach ($adopted as $resolution) {
                $resolution->forceFill(['locked_at' => now()])->save();
            }

            return ProcessWriteResult::ok($ranking->id, $session->compliance_warning);
        });
    }

    public function sign(Competition $competition, User $actor): ProcessWriteResult
    {
        if (! $this->governance->isCanonicalCompetition($competition)) {
            return ProcessWriteResult::failed(CanonicalCommissionGovernance::NOT_CANONICAL_MESSAGE);
        }

        $member = $this->governance->activeMemberFor($actor, $competition);
        if ($member === null) {
            return ProcessWriteResult::failed(CanonicalCommissionGovernance::NOT_MEMBER_MESSAGE);
        }
        $reason = $this->governance->memberActionBlockReason($member, $competition);
        if ($reason !== null) {
            return ProcessWriteResult::failed($reason);
        }

        try {
            return DB::transaction(function () use ($competition, $member) {
                $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->firstOrFail();
                $ranking = CanonicalFinalRanking::query()->where('competition_id', $locked->id)->lockForUpdate()->first();
                if ($ranking === null) {
                    return ProcessWriteResult::failed(self::NOT_CONFIRMED_MESSAGE);
                }
                if ($ranking->isFinal()) {
                    return ProcessWriteResult::failed(self::ALREADY_FINAL_MESSAGE);
                }
                if ((int) $member->commission_id !== (int) $ranking->commission_id) {
                    return ProcessWriteResult::failed(CanonicalCommissionGovernance::NOT_MEMBER_MESSAGE);
                }

                $signatures = CanonicalFinalRankingSignature::query()->where('final_ranking_id', $ranking->id)->orderBy('id')->lockForUpdate()->get();
                if ($signatures->contains(fn (CanonicalFinalRankingSignature $signature) => (int) $signature->canonical_seat_no === (int) $member->canonical_seat_no
                    || (int) $signature->commission_member_id === (int) $member->id)) {
                    return ProcessWriteResult::failed(self::DUPLICATE_SIGNATURE_MESSAGE);
                }

                $signature = CanonicalFinalRankingSignature::query()->create([
                    'final_ranking_id' => $ranking->id,
                    'commission_member_id' => $member->id,
                    'user_id' => $member->user_id,
                    'canonical_seat_no' => $member->canonical_seat_no,
                    'canonical_seat' => $member->canonical_seat,
                    'signer_name' => (string) ($member->name ?: $member->user?->name),
                    'signed_at' => now(),
                ]);

                $signerIds = $signatures->pluck('commission_member_id')->push($member->id);
                if ($signerIds->count() >= $this->governance->commissionConfig()->allMembersRequiredCount
                    && $this->governance->finalRankSignatureBlockReason($locked, $signerIds) === null) {
                    $ranking->forceFill(['finalized_at' => now()])->save();
                    $this->predlog->prepare($ranking->fresh());
                }

                return ProcessWriteResult::ok($signature->id);
            });
        } catch (UniqueConstraintViolationException) {
            return ProcessWriteResult::failed(self::DUPLICATE_SIGNATURE_MESSAGE);
        }
    }

    /**
     * @return Collection<int, CanonicalOutcome> application id => outcome
     */
    public function outcomesFor(Competition $competition): Collection
    {
        return CanonicalOutcome::query()
            ->where('competition_id', $competition->id)
            ->get()
            ->keyBy(fn (CanonicalOutcome $outcome) => (int) $outcome->application_id);
    }

    public function finalRankingFor(Competition $competition): ?CanonicalFinalRanking
    {
        return CanonicalFinalRanking::query()->where('competition_id', $competition->id)->with('signatures')->first();
    }

    /**
     * @return array{decision: string, amount: ?string, amount_raw: null, reason: ?string, justification: ?string}
     */
    private function outcomeInput(CanonicalOutcome $outcome): array
    {
        return [
            'decision' => $outcome->decision,
            'amount' => $outcome->approved_amount === null ? null : (string) $outcome->approved_amount,
            'amount_raw' => null,
            'reason' => $outcome->rejection_reason,
            'justification' => $outcome->justification,
        ];
    }

    /**
     * @param  Collection<int, Application>  $ranked
     */
    private function rankingIsIntact(Collection $ranked): bool
    {
        if ($ranked->isEmpty()) {
            return false;
        }

        $full = [];
        foreach ($ranked as $application) {
            $aggregate = $this->scoring->aggregateCanonicalApplication($application);
            if ($aggregate === null
                || $application->final_score_calc === null
                || bccomp((string) $application->final_score_calc, $aggregate['final_score_full'], CompetitionRanking::SCALE) !== 0
                || bccomp((string) $application->final_score, $aggregate['final_score_display'], 2) !== 0) {
                return false;
            }
            $full[(int) $application->id] = $aggregate['final_score_full'];
        }

        $positions = CompetitionRanking::positions($full);
        foreach ($ranked as $application) {
            if ((int) $application->ranking_position !== (int) $positions[(int) $application->id]) {
                return false;
            }
        }

        return true;
    }

    private function stageBlockReason(Competition $competition): ?string
    {
        if (! $this->governance->isCanonicalCompetition($competition)) {
            return CanonicalCommissionGovernance::NOT_CANONICAL_MESSAGE;
        }
        if (! $this->governance->canonicalRankingFormed($competition)) {
            return self::RANKING_REQUIRED_MESSAGE;
        }

        return $this->governance->finalDecisionSessionBlockReason($competition);
    }

    /**
     * @return array{0: ?CommissionMember, 1: ?string}
     */
    private function chair(User $actor, Competition $competition): array
    {
        $member = $this->governance->activeMemberFor($actor, $competition);
        if ($member === null || $member->canonical_seat !== CanonicalCommissionGovernance::ROLE_PREDSJEDNIK) {
            return [null, self::CHAIR_ONLY_MESSAGE];
        }

        $reason = $this->governance->memberActionBlockReason($member, $competition);

        return [$reason === null ? $member : null, $reason];
    }

    private function finalDecisionSession(Competition $competition): CommissionSession
    {
        return CommissionSession::query()
            ->where('competition_id', $competition->id)
            ->where('session_type', CommissionSession::TYPE_THIRD)
            ->where('canonical_purpose', CanonicalCommissionGovernance::PURPOSE_FINAL_DECISION)
            ->whereNotNull('completed_at')
            ->firstOrFail();
    }

    private function budget(Competition $competition): string
    {
        return bcadd((string) ($competition->budget ?? '0'), '0', CanonicalAmountCapPolicy::SCALE);
    }
}
