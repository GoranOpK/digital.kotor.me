<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\User;
use App\Models\YouthEqualScoreGroup;
use App\Models\YouthEqualScoreRound;
use App\Models\YouthEqualScoreRoundApplication;
use App\Models\YouthEqualScoreVote;
use App\Services\CanonicalIndividualScoringService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Youth 6.17.1 final list confirmation. Confirms the whole list in one transaction.
 * Does not generate Predlog odluke, PDF, contracts, archive, or a second Call.
 */
final class YouthAllocationListConfirmationService
{
    public const NOT_YOUTH_MESSAGE =
        'Potvrda konačne liste važi samo za omladinski Poziv.';

    public const CHAIRMAN_ONLY_MESSAGE =
        'Konačnu listu raspodjele za mlade potvrđuje samo aktivni predsjednik Komisije konkretnog Poziva.';

    public const COMPLETED_LOCKED_MESSAGE =
        'Poziv je zaključen. Potvrda konačne liste nije dozvoljena.';

    public const RANKING_NOT_READY_MESSAGE =
        'Konačna lista raspodjele za mlade može se potvrditi tek kada je preliminarna rang-lista trajno formirana.';

    public const INCOMPLETE_DRAFTS_MESSAGE =
        'Konačna lista se ne potvrđuje dok sve prijave sa najmanje 30 bodova nemaju važeći nacrt raspodjele za mlade.';

    public const FACTS_REQUIRED_MESSAGE =
        'Konačna lista se ne potvrđuje dok nijesu potvrđene činjenice o limitu 30/20/15 za svaku podržanu prijavu.';

    public const AMOUNT_INVALID_MESSAGE =
        'Konačna lista se ne potvrđuje dok iznos prelazi traženi iznos, procentualni maksimum ili raspoloživi budžet Poziva.';

    public const TOTAL_EXCEEDS_BUDGET_MESSAGE =
        'Zbir konačne raspodjele za mlade ne može biti veći od budžeta Poziva.';

    public const UNRESOLVED_GROUP_MESSAGE =
        'Konačna lista se ne potvrđuje dok granična izjednačena grupa nema usvojen zaključani krug.';

    public const THREE_VOTES_REQUIRED_MESSAGE =
        'Konačna lista se ne potvrđuje dok usvojeni krug nema tačno tri glasa.';

    public const REJECTED_WITHOUT_ADOPTED_MESSAGE =
        'Konačna lista se ne potvrđuje dok zaključani neusvojeni krug nema kasniji usvojeni krug.';

    public const ACTIVE_DRAFT_ROUND_MESSAGE =
        'Konačna lista se ne potvrđuje dok postoji samo aktivni nacrt kruga.';

    public const SNAPSHOT_CHANGED_MESSAGE =
        'Konačna lista se ne potvrđuje jer se nacrt, rezultat, pozicija, faza ili limit izmijenio nakon usvojenog kruga.';

    public const ADOPTED_BUDGET_INVALID_MESSAGE =
        'Konačna lista se ne potvrđuje jer usvojeni predlog više nije budžetski validan.';

    public const ALREADY_CONFIRMED_MESSAGE =
        'Konačna lista raspodjele za mlade je već potvrđena.';

    public const LIST_CONFIRMED_LOCKED_MESSAGE =
        'Konačna lista raspodjele za mlade je potvrđena i zaključana.';

    public const BELOW_THRESHOLD_REASON =
        'Prijava nije ostvarila minimalni prag od 30 bodova.';

    public const INSUFFICIENT_FUNDS_REASON =
        'Nedovoljno raspoloživih sredstava / prijava nije odabrana usvojenim predlogom raspodjele.';

    public const COMMISSION_REJECT_REASON =
        'Komisija je odbila prijavu u raspodjeli za mlade.';

    public function __construct(
        protected CanonicalIndividualScoringService $canonicalScoring,
        protected YouthEqualScoreVotingService $youthEqualScoreVoting,
        protected YouthAllocationDraftService $youthAllocationDrafts,
    ) {}

    public function isConfirmed(Competition $competition): bool
    {
        return $competition->isOmladinskoProfile()
            && $competition->youth_allocation_list_confirmed_at !== null;
    }

    public function confirmedListIsIntact(Competition $competition): bool
    {
        if (! $this->isConfirmed($competition)) {
            return false;
        }

        if (trim((string) $competition->youth_allocation_list_confirmed_by_name) === '') {
            return false;
        }

        if ($competition->youth_allocation_list_confirmed_by_user_id === null
            || $competition->youth_allocation_list_confirmed_by_commission_member_id === null) {
            return false;
        }

        foreach ($this->confirmableApplications($competition) as $application) {
            if (! in_array($application->status, ['approved', 'rejected'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{
     *     visible: bool,
     *     ranking_ready: bool,
     *     confirmed: bool,
     *     can_confirm: bool,
     *     block_reason: ?string,
     *     supported: list<array<string, mixed>>,
     *     rejected: list<array<string, mixed>>,
     *     total_allocation: string,
     *     remaining_budget: string,
     *     resolved_groups: int,
     *     confirmed_at: ?string,
     *     confirmed_by_name: ?string
     * }
     */
    public function board(Competition $competition, User $user): array
    {
        $empty = [
            'visible' => false,
            'ranking_ready' => false,
            'confirmed' => false,
            'can_confirm' => false,
            'block_reason' => null,
            'supported' => [],
            'rejected' => [],
            'total_allocation' => '0.00',
            'remaining_budget' => '0.00',
            'resolved_groups' => 0,
            'confirmed_at' => null,
            'confirmed_by_name' => null,
        ];

        if (! $competition->isOmladinskoProfile()) {
            return $empty;
        }

        $member = CommissionMember::activeForCommission((int) $user->id, (int) $competition->commission_id);
        if ($member === null) {
            return $empty;
        }

        $rankingReady = $this->canonicalScoring->isYouthPreliminaryRankingReady($competition);
        $confirmed = $this->isConfirmed($competition);
        $plan = $rankingReady ? $this->plannedOutcomes($competition) : null;
        $blockReason = null;
        if ($rankingReady && ! $confirmed) {
            $blockReason = $this->validationBlockReason($competition);
        }

        $supported = [];
        $rejected = [];
        $total = '0.00';
        if ($plan !== null) {
            foreach ($plan as $row) {
                $item = [
                    'application_id' => (int) $row['application']->id,
                    'business_plan_name' => $row['application']->business_plan_name,
                    'amount' => $row['approved_amount'],
                    'ranking_position' => $row['application']->ranking_position,
                ];
                if ($row['status'] === 'approved') {
                    $supported[] = $item;
                    $total = bcadd($total, $this->money((string) ($row['approved_amount'] ?? '0')), 2);
                } else {
                    $rejected[] = $item + ['reason' => $row['rejection_reason']];
                }
            }
        }

        $budget = $this->money((string) ($competition->budget ?? 0));
        $resolvedGroups = 0;
        if ($rankingReady) {
            foreach ($this->youthEqualScoreVoting->detectBoundaryGroups($competition) as $group) {
                if ($this->adoptedLockedRound($competition, (string) $group['group_key']) !== null) {
                    $resolvedGroups++;
                }
            }
        }

        $chairman = $this->activeChairman($competition, $user);

        return [
            'visible' => true,
            'ranking_ready' => $rankingReady,
            'confirmed' => $confirmed,
            'can_confirm' => $chairman !== null
                && $rankingReady
                && ! $confirmed
                && $blockReason === null
                && ! in_array($competition->status, ['closed', 'completed'], true),
            'block_reason' => $blockReason,
            'supported' => $supported,
            'rejected' => $rejected,
            'total_allocation' => $total,
            'remaining_budget' => bcsub($budget, $total, 2),
            'resolved_groups' => $resolvedGroups,
            'confirmed_at' => $competition->youth_allocation_list_confirmed_at?->format('d.m.Y. H:i'),
            'confirmed_by_name' => $competition->youth_allocation_list_confirmed_by_name,
        ];
    }

    public function confirm(Competition $competition, User $user, array $ignoredPayload = []): void
    {
        unset($ignoredPayload);
        $this->assertOmladinsko($competition);

        if (in_array($competition->status, ['closed', 'completed'], true)) {
            abort(403, self::COMPLETED_LOCKED_MESSAGE);
        }

        $chairman = $this->activeChairman($competition, $user);
        if ($chairman === null) {
            abort(403, self::CHAIRMAN_ONLY_MESSAGE);
        }

        DB::transaction(function () use ($competition, $user, $chairman) {
            $lockedCompetition = Competition::query()
                ->whereKey($competition->id)
                ->lockForUpdate()
                ->first();
            if ($lockedCompetition === null) {
                abort(404);
            }

            $this->assertOmladinsko($lockedCompetition);

            if (in_array($lockedCompetition->status, ['closed', 'completed'], true)) {
                abort(403, self::COMPLETED_LOCKED_MESSAGE);
            }

            if ($lockedCompetition->youth_allocation_list_confirmed_at !== null) {
                abort(403, self::ALREADY_CONFIRMED_MESSAGE);
            }

            Application::query()
                ->where('competition_id', $lockedCompetition->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $groups = YouthEqualScoreGroup::query()
                ->where('competition_id', $lockedCompetition->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $groupIds = $groups->pluck('id')->all();

            $rounds = $groupIds === []
                ? collect()
                : YouthEqualScoreRound::query()
                    ->whereIn('group_id', $groupIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
            $roundIds = $rounds->pluck('id')->all();

            if ($roundIds !== []) {
                YouthEqualScoreRoundApplication::query()
                    ->whereIn('round_id', $roundIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                YouthEqualScoreVote::query()
                    ->whereIn('round_id', $roundIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
            }

            $lockedCompetition = $lockedCompetition->fresh();
            $block = $this->validationBlockReason($lockedCompetition);
            if ($block !== null) {
                abort(403, $block);
            }

            $plan = $this->plannedOutcomes($lockedCompetition);
            $total = '0.00';
            foreach ($plan as $row) {
                if ($row['status'] === 'approved') {
                    $total = bcadd($total, $this->money((string) $row['approved_amount']), 2);
                }
            }
            $budget = $this->money((string) ($lockedCompetition->budget ?? 0));
            if (bccomp($total, $budget, 2) === 1) {
                abort(403, self::TOTAL_EXCEEDS_BUDGET_MESSAGE);
            }

            $now = now();
            $chairmanName = $this->memberDisplayName($chairman, $user);

            foreach ($plan as $row) {
                /** @var Application $application */
                $application = Application::query()->whereKey($row['application']->id)->first();
                if ($application === null) {
                    continue;
                }
                $application->forceFill([
                    'status' => $row['status'],
                    'commission_decision' => $row['commission_decision'],
                    'approved_amount' => $row['approved_amount'],
                    'commission_justification' => $row['commission_justification'],
                    'rejection_reason' => $row['rejection_reason'],
                    'commission_decision_date' => $now,
                ])->save();
            }

            $lockedCompetition->forceFill([
                'youth_allocation_list_confirmed_at' => $now,
                'youth_allocation_list_confirmed_by_user_id' => $user->id,
                'youth_allocation_list_confirmed_by_commission_member_id' => $chairman->id,
                'youth_allocation_list_confirmed_by_name' => $chairmanName,
            ])->save();
        });
    }

    public function validationBlockReason(Competition $competition): ?string
    {
        if (! $this->canonicalScoring->isYouthPreliminaryRankingReady($competition)) {
            return self::RANKING_NOT_READY_MESSAGE;
        }

        $rankingBlock = $this->canonicalScoring->youthPreliminaryRankingBlockReason($competition);
        if ($rankingBlock !== null) {
            return $rankingBlock;
        }

        foreach ($this->confirmableApplications($competition) as $application) {
            if ($application->isEliminatedFromScoring()) {
                continue;
            }

            if (! $this->canonicalScoring->youthMeetsMinimumScore($application)) {
                continue;
            }

            if (! $this->hasCompleteDraft($application)) {
                return self::INCOMPLETE_DRAFTS_MESSAGE;
            }

            if ($application->commission_decision === 'podrzava_potpuno') {
                $facts = $this->youthAllocationDrafts->capSnapshot($application);
                if (! $facts['facts_confirmed']) {
                    return self::FACTS_REQUIRED_MESSAGE;
                }

                $amount = $this->money((string) $application->approved_amount);
                if ($this->amountExceedsLimits($application, $amount, $facts)) {
                    return self::AMOUNT_INVALID_MESSAGE;
                }
            }
        }

        $groupBlock = $this->boundaryGroupsBlockReason($competition);
        if ($groupBlock !== null) {
            return $groupBlock;
        }

        $plan = $this->plannedOutcomes($competition);
        $total = '0.00';
        foreach ($plan as $row) {
            if ($row['status'] === 'approved') {
                $total = bcadd($total, $this->money((string) $row['approved_amount']), 2);
            }
        }
        if (bccomp($total, $this->money((string) ($competition->budget ?? 0)), 2) === 1) {
            return self::TOTAL_EXCEEDS_BUDGET_MESSAGE;
        }

        return null;
    }

    /**
     * @return list<array{
     *     application: Application,
     *     status: string,
     *     commission_decision: ?string,
     *     approved_amount: ?string,
     *     commission_justification: ?string,
     *     rejection_reason: ?string
     * }>
     */
    private function plannedOutcomes(Competition $competition): array
    {
        $membership = $this->adoptedMembership($competition);
        $plan = [];

        foreach ($this->confirmableApplications($competition) as $application) {
            if ($application->isEliminatedFromScoring()) {
                continue;
            }

            if (! $this->canonicalScoring->youthMeetsMinimumScore($application)) {
                $plan[] = [
                    'application' => $application,
                    'status' => 'rejected',
                    'commission_decision' => $application->commission_decision,
                    'approved_amount' => null,
                    'commission_justification' => $application->commission_justification,
                    'rejection_reason' => self::BELOW_THRESHOLD_REASON,
                ];

                continue;
            }

            $member = $membership[(int) $application->id] ?? null;
            if ($member !== null) {
                if ($member['selected']) {
                    $plan[] = [
                        'application' => $application,
                        'status' => 'approved',
                        'commission_decision' => 'podrzava_potpuno',
                        'approved_amount' => $this->money((string) $application->approved_amount),
                        'commission_justification' => $application->commission_justification,
                        'rejection_reason' => null,
                    ];
                } else {
                    $plan[] = [
                        'application' => $application,
                        'status' => 'rejected',
                        'commission_decision' => 'odbija',
                        'approved_amount' => null,
                        'commission_justification' => $application->commission_justification,
                        'rejection_reason' => self::INSUFFICIENT_FUNDS_REASON,
                    ];
                }

                continue;
            }

            if ($application->commission_decision === 'odbija') {
                $plan[] = [
                    'application' => $application,
                    'status' => 'rejected',
                    'commission_decision' => 'odbija',
                    'approved_amount' => null,
                    'commission_justification' => $application->commission_justification,
                    'rejection_reason' => self::COMMISSION_REJECT_REASON,
                ];

                continue;
            }

            $plan[] = [
                'application' => $application,
                'status' => 'approved',
                'commission_decision' => 'podrzava_potpuno',
                'approved_amount' => $this->money((string) $application->approved_amount),
                'commission_justification' => $application->commission_justification,
                'rejection_reason' => null,
            ];
        }

        return $plan;
    }

    /**
     * @return array<int, array{selected: bool, draft_amount: string, business_stage: string, applied_cap_percent: ?int}>
     */
    private function adoptedMembership(Competition $competition): array
    {
        $map = [];
        foreach ($this->youthEqualScoreVoting->detectBoundaryGroups($competition) as $group) {
            $round = $this->adoptedLockedRound($competition, (string) $group['group_key']);
            if ($round === null) {
                continue;
            }
            $apps = YouthEqualScoreRoundApplication::query()
                ->where('round_id', $round->id)
                ->orderBy('application_id')
                ->get();
            foreach ($apps as $row) {
                $map[(int) $row->application_id] = [
                    'selected' => (bool) $row->selected,
                    'draft_amount' => $this->money((string) $row->draft_amount),
                    'business_stage' => (string) $row->business_stage,
                    'applied_cap_percent' => $row->applied_cap_percent !== null ? (int) $row->applied_cap_percent : null,
                ];
            }
        }

        return $map;
    }

    private function boundaryGroupsBlockReason(Competition $competition): ?string
    {
        foreach ($this->youthEqualScoreVoting->detectBoundaryGroups($competition) as $group) {
            $reason = $this->singleGroupBlockReason($competition, $group);
            if ($reason !== null) {
                return $reason;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $group
     */
    private function singleGroupBlockReason(Competition $competition, array $group): ?string
    {
        if (($group['block_reason'] ?? null) === YouthEqualScoreVotingService::INCOMPLETE_DRAFTS_MESSAGE) {
            return self::INCOMPLETE_DRAFTS_MESSAGE;
        }

        $groupKey = (string) $group['group_key'];
        $persisted = YouthEqualScoreGroup::query()
            ->where('competition_id', $competition->id)
            ->where('group_key', $groupKey)
            ->first();

        if ($persisted === null) {
            return self::UNRESOLVED_GROUP_MESSAGE;
        }

        $rounds = YouthEqualScoreRound::query()
            ->where('group_id', $persisted->id)
            ->orderBy('round_no')
            ->get();

        if ($rounds->contains(fn (YouthEqualScoreRound $round) => $round->locked_at === null)) {
            return self::ACTIVE_DRAFT_ROUND_MESSAGE;
        }

        $adopted = $rounds->first(
            fn (YouthEqualScoreRound $round) => $round->locked_at !== null
                && $round->outcome === YouthEqualScoreRound::OUTCOME_ADOPTED
        );
        if ($adopted === null) {
            if ($rounds->contains(fn (YouthEqualScoreRound $round) => $round->outcome === YouthEqualScoreRound::OUTCOME_REJECTED)) {
                return self::REJECTED_WITHOUT_ADOPTED_MESSAGE;
            }

            return self::UNRESOLVED_GROUP_MESSAGE;
        }

        $votes = YouthEqualScoreVote::query()
            ->where('round_id', $adopted->id)
            ->orderBy('canonical_seat_no')
            ->get();
        if ($votes->count() !== 3) {
            return self::THREE_VOTES_REQUIRED_MESSAGE;
        }
        foreach ([1, 2, 3] as $seat) {
            $vote = $votes->firstWhere('canonical_seat_no', $seat);
            if ($vote === null
                || $vote->commission_member_id === null
                || $vote->user_id === null
                || ! in_array($vote->vote_value, [YouthEqualScoreVote::FOR, YouthEqualScoreVote::AGAINST], true)
            ) {
                return self::THREE_VOTES_REQUIRED_MESSAGE;
            }
        }
        if ($votes->where('vote_value', YouthEqualScoreVote::FOR)->count() < 2) {
            return self::THREE_VOTES_REQUIRED_MESSAGE;
        }

        $roundApps = YouthEqualScoreRoundApplication::query()
            ->where('round_id', $adopted->id)
            ->orderBy('application_id')
            ->get();
        $liveIds = collect($group['applications'] ?? [])
            ->map(function ($row) {
                if (is_array($row) && isset($row['application_id'])) {
                    return (int) $row['application_id'];
                }
                if (is_array($row) && isset($row['id'])) {
                    return (int) $row['id'];
                }

                return (int) ($row->id ?? 0);
            })
            ->filter()
            ->sort()
            ->values();
        $snapshotIds = $roundApps->pluck('application_id')->map(fn ($id) => (int) $id)->sort()->values();
        if ($liveIds->all() !== $snapshotIds->all()) {
            return self::SNAPSHOT_CHANGED_MESSAGE;
        }

        if ((int) $persisted->ranking_position !== (int) $group['ranking_position']
            || $this->fullScore((string) $persisted->full_score) !== $this->fullScore((string) $group['full_score'])
        ) {
            return self::SNAPSHOT_CHANGED_MESSAGE;
        }

        foreach ($roundApps as $row) {
            $live = Application::query()->whereKey($row->application_id)->first();
            if ($live === null) {
                return self::SNAPSHOT_CHANGED_MESSAGE;
            }
            if ((string) $live->business_stage !== (string) $row->business_stage) {
                return self::SNAPSHOT_CHANGED_MESSAGE;
            }
            $liveCap = $live->youth_applied_cap_percent !== null ? (int) $live->youth_applied_cap_percent : null;
            $snapCap = $row->applied_cap_percent !== null ? (int) $row->applied_cap_percent : null;
            if ($liveCap !== $snapCap) {
                return self::SNAPSHOT_CHANGED_MESSAGE;
            }
            $liveAmount = $live->approved_amount !== null ? $this->money((string) $live->approved_amount) : '0.00';
            if ($liveAmount !== $this->money((string) $row->draft_amount)) {
                return self::SNAPSHOT_CHANGED_MESSAGE;
            }
            $aggregate = $this->canonicalScoring->aggregateYouthApplication($live);
            $liveScore = $this->fullScore((string) ($aggregate['final_score_full'] ?? $live->final_score ?? '0'));
            if ($liveScore !== $this->fullScore((string) $group['full_score'])) {
                return self::SNAPSHOT_CHANGED_MESSAGE;
            }
            if ((int) $live->ranking_position !== (int) $group['ranking_position']) {
                return self::SNAPSHOT_CHANGED_MESSAGE;
            }
        }

        $remaining = $this->youthEqualScoreVoting->remainingBeforeApplication(
            $competition,
            Application::query()->whereKey($roundApps->first()?->application_id)->first()
        );
        $proposal = $this->money((string) $adopted->proposal_total);
        if (bccomp($proposal, $remaining, 2) === 1) {
            return self::ADOPTED_BUDGET_INVALID_MESSAGE;
        }

        return null;
    }

    /**
     * @return Collection<int, Application>
     */
    private function confirmableApplications(Competition $competition): Collection
    {
        return $this->canonicalScoring
            ->youthScoringCycleApplications($competition)
            ->sortBy('id')
            ->values();
    }

    private function hasCompleteDraft(Application $application): bool
    {
        if ($application->commission_decision === 'podrzava_potpuno') {
            return $application->approved_amount !== null
                && bccomp($this->money((string) $application->approved_amount), '0', 2) === 1;
        }

        return $application->commission_decision === 'odbija'
            && trim((string) $application->commission_justification) !== '';
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private function amountExceedsLimits(Application $application, string $amount, array $facts): bool
    {
        $requested = $application->requested_amount;
        if ($requested !== null && bccomp($amount, $this->money((string) $requested), 2) === 1) {
            return true;
        }

        $percentMax = $facts['percent_max'] ?? null;
        if ($percentMax !== null && bccomp($amount, $this->money((string) $percentMax), 2) === 1) {
            return true;
        }

        $expectedPercent = $this->youthAllocationDrafts->appliedCapPercent(
            (bool) $facts['startup'],
            (bool) $facts['prior_funding']
        );
        if ((int) ($application->youth_applied_cap_percent ?? 0) !== $expectedPercent) {
            return true;
        }

        return false;
    }

    private function adoptedLockedRound(Competition $competition, string $groupKey): ?YouthEqualScoreRound
    {
        return YouthEqualScoreRound::query()
            ->whereHas('group', function ($query) use ($competition, $groupKey) {
                $query->where('competition_id', $competition->id)
                    ->where('group_key', $groupKey);
            })
            ->whereNotNull('locked_at')
            ->where('outcome', YouthEqualScoreRound::OUTCOME_ADOPTED)
            ->first();
    }

    private function assertOmladinsko(Competition $competition): void
    {
        if (! $competition->isOmladinskoProfile()) {
            abort(403, self::NOT_YOUTH_MESSAGE);
        }
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

    private function memberDisplayName(CommissionMember $member, ?User $user): string
    {
        $name = trim((string) $member->name);
        if ($name !== '') {
            return $name;
        }

        return trim((string) ($user?->name ?? ''));
    }

    private function money(string $value): string
    {
        return bcadd($value, '0', 2);
    }

    private function fullScore(string $value): string
    {
        return bcadd($value, '0', 10);
    }
}
