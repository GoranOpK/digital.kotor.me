<?php

namespace App\Services\Competitions\Outcome;

use App\Models\Application;
use App\Models\CanonicalFinalRanking;
use App\Models\CanonicalTieResolution;
use App\Models\CanonicalTieVote;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\User;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use App\Services\Competitions\ProcessWriteResult;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Canonical shared-rank scarcity resolution at the funding boundary. The chair proposes which
 * applications of the exact tied set are supported; all 3 valid Commission seats vote.
 * 2–1 or 3–0 adopts, otherwise the proposal is rejected. Ranks are never changed.
 * Canonical-only: does not reuse the Youth equal-score voting rules.
 */
final class CanonicalTieResolutionService
{
    public const CHAIR_ONLY_MESSAGE = 'Odluku o jednakom rangu predlaže predsjednik kanonske Komisije.';

    public const TIED_SET_INVALID_MESSAGE = 'Skup prijava mora sadržati najmanje dvije prijave istog ranga sa konačnom ocjenom od najmanje 30 bodova.';

    public const SELECTION_INVALID_MESSAGE = 'Izbor mora sadržati najmanje jednu, ali ne sve prijave iz skupa istog ranga.';

    public const PENDING_EXISTS_MESSAGE = 'Za ovaj rang već postoji prijedlog o kojem se glasa.';

    public const ADOPTED_EXISTS_MESSAGE = 'Za ovaj skup prijava istog ranga odluka je već usvojena.';

    public const NOT_PROPOSED_MESSAGE = 'Glasanje nije otvoreno: odluka je već donesena.';

    public const DUPLICATE_VOTE_MESSAGE = 'Ovo mjesto Komisije je već glasalo.';

    public const INVALID_VOTES_MESSAGE = 'Odluka o jednakom rangu mora imati glasove sva 3 važeća mjesta Komisije i većinu (2–1 ili 3–0).';

    public function __construct(
        private readonly CanonicalCommissionGovernance $governance,
    ) {
    }

    /**
     * @param  list<int>  $tiedApplicationIds
     * @param  list<int>  $selectedApplicationIds
     */
    public function propose(Competition $competition, User $actor, int $sharedRank, array $tiedApplicationIds, array $selectedApplicationIds): ProcessWriteResult
    {
        $gate = $this->stageBlockReason($competition);
        if ($gate !== null) {
            return ProcessWriteResult::failed($gate);
        }

        $chair = $this->governance->activeMemberFor($actor, $competition);
        if ($chair === null || $chair->canonical_seat !== CanonicalCommissionGovernance::ROLE_PREDSJEDNIK) {
            return ProcessWriteResult::failed(self::CHAIR_ONLY_MESSAGE);
        }
        $reason = $this->governance->memberActionBlockReason($chair, $competition);
        if ($reason !== null) {
            return ProcessWriteResult::failed($reason);
        }

        $tied = CanonicalTieResolution::normalizeIds($tiedApplicationIds);
        $selected = CanonicalTieResolution::normalizeIds($selectedApplicationIds);
        if (count($tied) < 2 || ! $this->tiedSetIsValid($competition, $sharedRank, $tied)) {
            return ProcessWriteResult::failed(self::TIED_SET_INVALID_MESSAGE);
        }
        if ($selected === [] || array_diff($selected, $tied) !== [] || count($selected) >= count($tied)) {
            return ProcessWriteResult::failed(self::SELECTION_INVALID_MESSAGE);
        }

        return DB::transaction(function () use ($competition, $chair, $sharedRank, $tied, $selected) {
            Competition::query()->whereKey($competition->id)->lockForUpdate()->first();
            if (CanonicalFinalRanking::query()->where('competition_id', $competition->id)->exists()) {
                return ProcessWriteResult::failed(CanonicalOutcomeService::ALREADY_CONFIRMED_MESSAGE);
            }

            $existing = CanonicalTieResolution::query()
                ->where('competition_id', $competition->id)
                ->where('shared_rank', $sharedRank)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            if ($existing->contains(fn (CanonicalTieResolution $row) => $row->status === CanonicalTieResolution::PROPOSED)) {
                return ProcessWriteResult::failed(self::PENDING_EXISTS_MESSAGE);
            }
            if ($existing->contains(fn (CanonicalTieResolution $row) => $row->status === CanonicalTieResolution::ADOPTED && $row->tiedIds() === $tied)) {
                return ProcessWriteResult::failed(self::ADOPTED_EXISTS_MESSAGE);
            }

            $resolution = CanonicalTieResolution::query()->create([
                'competition_id' => $competition->id,
                'shared_rank' => $sharedRank,
                'tied_application_ids' => $tied,
                'selected_application_ids' => $selected,
                'status' => CanonicalTieResolution::PROPOSED,
                'proposed_by_member_id' => $chair->id,
            ]);

            return ProcessWriteResult::ok($resolution->id);
        });
    }

    public function vote(CanonicalTieResolution $resolution, User $actor, bool $inFavour): ProcessWriteResult
    {
        $competition = Competition::query()->findOrFail($resolution->competition_id);
        $gate = $this->stageBlockReason($competition);
        if ($gate !== null) {
            return ProcessWriteResult::failed($gate);
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
            return DB::transaction(function () use ($resolution, $member, $inFavour, $competition) {
                Competition::query()->whereKey($competition->id)->lockForUpdate()->first();
                if (CanonicalFinalRanking::query()->where('competition_id', $competition->id)->exists()) {
                    return ProcessWriteResult::failed(CanonicalOutcomeService::ALREADY_CONFIRMED_MESSAGE);
                }

                $locked = CanonicalTieResolution::query()->whereKey($resolution->id)->lockForUpdate()->firstOrFail();
                if ($locked->status !== CanonicalTieResolution::PROPOSED) {
                    return ProcessWriteResult::failed(self::NOT_PROPOSED_MESSAGE);
                }

                $votes = CanonicalTieVote::query()->where('tie_resolution_id', $locked->id)->orderBy('id')->lockForUpdate()->get();
                if ($votes->contains(fn (CanonicalTieVote $vote) => (int) $vote->canonical_seat_no === (int) $member->canonical_seat_no
                    || (int) $vote->commission_member_id === (int) $member->id)) {
                    return ProcessWriteResult::failed(self::DUPLICATE_VOTE_MESSAGE);
                }

                CanonicalTieVote::query()->create([
                    'tie_resolution_id' => $locked->id,
                    'commission_member_id' => $member->id,
                    'user_id' => $member->user_id,
                    'canonical_seat_no' => $member->canonical_seat_no,
                    'in_favour' => $inFavour,
                    'voted_at' => now(),
                ]);

                $votes = CanonicalTieVote::query()->where('tie_resolution_id', $locked->id)->get();
                $quorum = (int) $this->governance->policy()->commissionFullDecisionQuorum();
                if ($votes->count() >= $quorum) {
                    $inFavourCount = $votes->where('in_favour', true)->count();
                    $locked->forceFill([
                        'status' => $inFavourCount * 2 > $votes->count() ? CanonicalTieResolution::ADOPTED : CanonicalTieResolution::REJECTED,
                        'decided_at' => now(),
                    ])->save();
                }

                return ProcessWriteResult::ok($locked->id);
            });
        } catch (UniqueConstraintViolationException) {
            return ProcessWriteResult::failed(self::DUPLICATE_VOTE_MESSAGE);
        }
    }

    /**
     * Confirmation-time re-check of an adopted resolution: exactly 3 votes from 3 distinct seats
     * of currently valid members of this Commission, with a 2–1 or 3–0 majority.
     */
    public function adoptedResolutionBlockReason(CanonicalTieResolution $resolution, Competition $competition): ?string
    {
        if ($resolution->status !== CanonicalTieResolution::ADOPTED) {
            return self::INVALID_VOTES_MESSAGE;
        }

        $votes = CanonicalTieVote::query()->where('tie_resolution_id', $resolution->id)->get();
        $quorum = (int) $this->governance->policy()->commissionFullDecisionQuorum();
        if ($votes->count() !== $quorum || $votes->pluck('canonical_seat_no')->unique()->count() !== $quorum) {
            return self::INVALID_VOTES_MESSAGE;
        }

        $membersMatch = CommissionMember::query()
            ->whereIn('id', $votes->pluck('commission_member_id'))
            ->get()
            ->every(fn (CommissionMember $member) => $votes->firstWhere('commission_member_id', $member->id)?->canonical_seat_no === (int) $member->canonical_seat_no);
        if (! $membersMatch || $this->governance->finalRankSignatureBlockReason($competition, $votes->pluck('commission_member_id')) !== null) {
            return self::INVALID_VOTES_MESSAGE;
        }

        return $votes->where('in_favour', true)->count() * 2 > $quorum ? null : self::INVALID_VOTES_MESSAGE;
    }

    private function stageBlockReason(Competition $competition): ?string
    {
        if (! $this->governance->isCanonicalCompetition($competition)) {
            return CanonicalCommissionGovernance::NOT_CANONICAL_MESSAGE;
        }
        if (! $this->governance->canonicalRankingFormed($competition)) {
            return CanonicalOutcomeService::RANKING_REQUIRED_MESSAGE;
        }

        return $this->governance->finalDecisionSessionBlockReason($competition);
    }

    /**
     * @param  list<int>  $tied
     */
    private function tiedSetIsValid(Competition $competition, int $sharedRank, array $tied): bool
    {
        $applications = Application::query()
            ->where('competition_id', $competition->id)
            ->whereIn('id', $tied)
            ->get();

        return $applications->count() === count($tied)
            && $applications->every(fn (Application $application) => (int) $application->ranking_position === $sharedRank
                && $application->final_score_calc !== null
                && ! CanonicalAllocationPolicy::belowThreshold((string) $application->final_score_calc));
    }
}
