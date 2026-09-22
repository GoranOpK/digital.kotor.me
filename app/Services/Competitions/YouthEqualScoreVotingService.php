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
use App\Support\KnApplicationClassification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Youth 6.16.3 equal-score voting evidence. Does not confirm the list or change application status.
 */
final class YouthEqualScoreVotingService
{
    public const RANKING_NOT_READY_MESSAGE =
        'Glasanje izjednačenih grupa mladih moguće je tek kada je preliminarna rang-lista trajno formirana.';

    public const CHAIRMAN_ONLY_MESSAGE =
        'Predlog i glasove izjednačenih grupa mladih evidentira samo aktivni predsjednik Komisije konkretnog Poziva.';

    public const NOT_YOUTH_MESSAGE =
        'Glasanje izjednačenih grupa važi samo za omladinski Poziv.';

    public const NO_BOUNDARY_GROUP_MESSAGE =
        'Nema granične izjednačene grupe koja zahtijeva glasanje.';

    public const INCOMPLETE_DRAFTS_MESSAGE =
        'Krug se ne kreira dok sve izjednačene prijave nemaju važeći nacrt raspodjele za mlade.';

    public const REDUCE_PRIORITY_DRAFT_MESSAGE =
        'Krug se ne kreira. Predsjednik prvo mora smanjiti nacrt raspodjele za mlade jedine prioritetne prijave uz obrazloženje.';

    public const ADOPTED_ROUND_EXISTS_MESSAGE =
        'Nakon usvojenog kruga novi krug nije dozvoljen.';

    public const THREE_VOTES_REQUIRED_MESSAGE =
        'Krug se zaključuje tek kada su evidentirana sva tri glasa.';

    public const SEAT_MEMBER_MISMATCH_MESSAGE =
        'Glas starog člana ne važi. Evidentirajte glas trenutnog člana kanonskog mjesta.';

    public const ROUND_LOCKED_MESSAGE =
        'Zaključani krug je nepromjenjiv.';

    public const ROUND_NOT_IN_COMPETITION_MESSAGE =
        'Krug ne pripada ovom Pozivu.';

    public const JUSTIFICATION_REQUIRED_MESSAGE =
        'Obrazloženje predloga je obavezno.';

    public const AMOUNT_EXCEEDS_REMAINING_MESSAGE =
        'Zbir izabranih prijava ne može biti veći od raspoloživog budžeta prije predloga.';

    public const DEVELOPMENT_INSTEAD_OF_STARTUP_MESSAGE =
        'Razvoj se ne bira umjesto prioritetne prijave otpočinjanja.';

    public const COMPLETED_LOCKED_MESSAGE =
        'Rang lista je zaključena. Nakon završetka konkursa izmjene nijesu dozvoljene.';

    public const LIST_CLOSE_BLOCK_MESSAGE =
        'Poziv se ne može zaključiti dok izjednačena grupa nema usvojen zaključani krug.';

    public const LOWER_RANK_UNRESOLVED_GROUP_MESSAGE =
        'Nacrt nižeg ranga nije dozvoljen dok izjednačena grupa višeg ranga nema usvojen zaključani krug.';

    public const RULE_STARTUP_PRIORITY_VOTE = 'startup_priority_vote';

    public const RULE_DEVELOPMENT_AFTER_STARTUP = 'development_after_startup';

    public const RULE_SAME_STAGE_VOTE = 'same_stage_vote';

    public function __construct(
        protected CanonicalIndividualScoringService $canonicalScoring,
    ) {}

    /**
     * Display-only board. Does not persist groups or rounds.
     *
     * @return array{
     *     visible: bool,
     *     can_edit: bool,
     *     ranking_ready: bool,
     *     groups: list<array<string, mixed>>
     * }
     */
    public function board(Competition $competition, User $user): array
    {
        $member = $this->activeMember($competition, $user);
        $chairman = $this->activeChairman($competition, $user);

        if (! $competition->isOmladinskoProfile() || $member === null) {
            return [
                'visible' => false,
                'can_edit' => false,
                'ranking_ready' => false,
                'groups' => [],
            ];
        }

        $rankingReady = $this->canonicalScoring->isYouthPreliminaryRankingReady($competition);
        $detected = $rankingReady ? $this->detectBoundaryGroups($competition) : [];
        $groups = [];

        foreach ($detected as $detectedGroup) {
            $persisted = YouthEqualScoreGroup::query()
                ->where('competition_id', $competition->id)
                ->where('group_key', $detectedGroup['group_key'])
                ->with([
                    'rounds.applications.application.user',
                    'rounds.votes',
                ])
                ->first();

            $activeRound = $persisted
                ? $persisted->rounds->first(fn (YouthEqualScoreRound $round) => ! $round->isLocked())
                : null;
            $history = $persisted
                ? $persisted->rounds
                    ->filter(fn (YouthEqualScoreRound $round) => $round->isLocked())
                    ->sortBy('round_no')
                    ->values()
                : collect();

            $groups[] = [
                ...$detectedGroup,
                'persisted_group_id' => $persisted?->id,
                'active_round' => $activeRound ? $this->roundPayload($activeRound) : null,
                'history' => $history->map(fn (YouthEqualScoreRound $round) => $this->roundPayload($round))->all(),
            ];
        }

        return [
            'visible' => true,
            'can_edit' => $chairman !== null && ! in_array($competition->status, ['closed', 'completed'], true),
            'ranking_ready' => $rankingReady,
            'groups' => $groups,
        ];
    }

    public function blocksListClose(Competition $competition): bool
    {
        return $this->listCloseBlockReason($competition) !== null;
    }

    public function remainingBeforeApplication(Competition $competition, ?Application $application = null): string
    {
        $rank = PHP_INT_MAX;
        if ($application !== null && $application->ranking_position !== null) {
            $rank = (int) $application->ranking_position;
        }

        return $this->remainingBeforeRank($competition, $this->scoredRankedApplications($competition), $rank);
    }

    public function lowerRankDraftBlockReason(Competition $competition, Application $application): ?string
    {
        if (! $competition->isOmladinskoProfile()) {
            return null;
        }

        if (! $this->canonicalScoring->isYouthPreliminaryRankingReady($competition)) {
            return null;
        }

        $rank = (int) ($application->ranking_position ?? 0);
        if ($rank <= 0) {
            return null;
        }

        foreach ($this->detectBoundaryGroups($competition) as $group) {
            if ((int) $group['ranking_position'] >= $rank) {
                continue;
            }
            if ($this->adoptedLockedRound($competition, (string) $group['group_key']) === null) {
                return self::LOWER_RANK_UNRESOLVED_GROUP_MESSAGE;
            }
        }

        return null;
    }

    public function listCloseBlockReason(Competition $competition): ?string
    {
        if (! $competition->isOmladinskoProfile()) {
            return null;
        }

        if (! $this->canonicalScoring->isYouthPreliminaryRankingReady($competition)) {
            return null;
        }

        foreach ($this->detectBoundaryGroups($competition) as $group) {
            $adopted = YouthEqualScoreRound::query()
                ->whereHas('group', function ($query) use ($competition, $group) {
                    $query->where('competition_id', $competition->id)
                        ->where('group_key', $group['group_key']);
                })
                ->whereNotNull('locked_at')
                ->where('outcome', YouthEqualScoreRound::OUTCOME_ADOPTED)
                ->exists();

            if (! $adopted) {
                return self::LIST_CLOSE_BLOCK_MESSAGE;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function saveRound(Competition $competition, User $user, array $input): YouthEqualScoreRound
    {
        $chairman = $this->guardChairmanMutation($competition, $user);
        $this->canonicalScoring->persistYouthPreliminaryRankingIfReady($competition);
        $competition->refresh();

        if (! $this->canonicalScoring->isYouthPreliminaryRankingReady($competition)) {
            abort(403, self::RANKING_NOT_READY_MESSAGE);
        }

        $justification = trim((string) ($input['justification'] ?? ''));
        if ($justification === '') {
            throw ValidationException::withMessages([
                'justification' => self::JUSTIFICATION_REQUIRED_MESSAGE,
            ]);
        }

        $selectedIds = $this->selectedIdsFromInput($input);
        $votesInput = $this->votesFromInput($input);
        $groupKey = isset($input['group_key']) ? (string) $input['group_key'] : null;

        return DB::transaction(function () use ($competition, $chairman, $user, $justification, $selectedIds, $votesInput, $groupKey) {
            Competition::query()->whereKey($competition->id)->lockForUpdate()->first();
            $detected = $this->requireVotableGroup($competition, $groupKey);
            $this->assertSelectionFits($detected, $selectedIds);

            $group = YouthEqualScoreGroup::query()
                ->where('competition_id', $competition->id)
                ->where('group_key', $detected['group_key'])
                ->lockForUpdate()
                ->first();

            if ($group === null) {
                $group = YouthEqualScoreGroup::query()->create([
                    'competition_id' => $competition->id,
                    'group_key' => $detected['group_key'],
                    'full_score' => $detected['full_score'],
                    'ranking_position' => $detected['ranking_position'],
                    'applied_rule' => $detected['applied_rule'],
                ]);
                $group = YouthEqualScoreGroup::query()->whereKey($group->id)->lockForUpdate()->firstOrFail();
            } else {
                $group->forceFill(['applied_rule' => $detected['applied_rule']])->save();
            }

            if ($this->adoptedRound($group) !== null) {
                abort(403, self::ADOPTED_ROUND_EXISTS_MESSAGE);
            }

            $unlocked = YouthEqualScoreRound::query()
                ->where('group_id', $group->id)
                ->whereNull('locked_at')
                ->lockForUpdate()
                ->get();

            if ($unlocked->count() > 1) {
                abort(409, 'Postoji više od jednog aktivnog nacrta kruga.');
            }

            $proposalTotal = $this->proposalTotal($detected, $selectedIds);
            $round = $unlocked->first();
            if ($round === null) {
                $nextNo = (int) YouthEqualScoreRound::query()->where('group_id', $group->id)->max('round_no') + 1;
                $round = YouthEqualScoreRound::query()->create([
                    'group_id' => $group->id,
                    'round_no' => $nextNo,
                    'justification' => $justification,
                    'budget_before' => $detected['remaining_before'],
                    'proposal_total' => $proposalTotal,
                    'created_by_user_id' => $user->id,
                    'created_by_member_id' => $chairman->id,
                    'created_by_name' => $this->memberDisplayName($chairman, $user),
                ]);
            } else {
                $round->forceFill([
                    'justification' => $justification,
                    'budget_before' => $detected['remaining_before'],
                    'proposal_total' => $proposalTotal,
                ])->save();
            }

            $this->syncRoundApplications($round, $detected, $selectedIds);
            $this->syncVotes($round, $competition, $user, $votesInput);

            return $round->fresh(['applications', 'votes']);
        });
    }

    public function lockRound(Competition $competition, User $user, YouthEqualScoreRound $round): YouthEqualScoreRound
    {
        $chairman = $this->guardChairmanMutation($competition, $user);

        return DB::transaction(function () use ($competition, $chairman, $user, $round) {
            Competition::query()->whereKey($competition->id)->lockForUpdate()->first();
            $lockedRound = YouthEqualScoreRound::query()->whereKey($round->id)->lockForUpdate()->firstOrFail();
            $group = YouthEqualScoreGroup::query()->whereKey($lockedRound->group_id)->lockForUpdate()->firstOrFail();

            if ((int) $group->competition_id !== (int) $competition->id) {
                abort(404, self::ROUND_NOT_IN_COMPETITION_MESSAGE);
            }

            if ($lockedRound->isLocked()) {
                abort(403, self::ROUND_LOCKED_MESSAGE);
            }

            $this->canonicalScoring->persistYouthPreliminaryRankingIfReady($competition);
            $competition->refresh();
            if (! $this->canonicalScoring->isYouthPreliminaryRankingReady($competition)) {
                abort(403, self::RANKING_NOT_READY_MESSAGE);
            }

            $detected = $this->requireVotableGroup($competition, $group->group_key);

            $selectedIds = $lockedRound->applications()
                ->where('selected', true)
                ->pluck('application_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $this->assertSelectionFits($detected, $selectedIds);

            $lockedRound->forceFill([
                'budget_before' => $detected['remaining_before'],
                'proposal_total' => $this->proposalTotal($detected, $selectedIds),
            ])->save();
            $this->syncRoundApplications($lockedRound, $detected, $selectedIds);

            $seats = $this->activeCanonicalSeats($competition);
            if ($seats->count() !== 3) {
                throw ValidationException::withMessages([
                    'votes' => self::THREE_VOTES_REQUIRED_MESSAGE,
                ]);
            }

            $votes = YouthEqualScoreVote::query()
                ->where('round_id', $lockedRound->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('canonical_seat_no');

            foreach ([1, 2, 3] as $seatNo) {
                $vote = $votes->get($seatNo);
                $member = $seats->get($seatNo);
                if ($vote === null || $member === null) {
                    throw ValidationException::withMessages([
                        'votes' => self::THREE_VOTES_REQUIRED_MESSAGE,
                    ]);
                }
                if ((int) $vote->commission_member_id !== (int) $member->id) {
                    throw ValidationException::withMessages([
                        'votes' => self::SEAT_MEMBER_MISMATCH_MESSAGE,
                    ]);
                }
            }

            $forCount = $votes->where('vote_value', YouthEqualScoreVote::FOR)->count();
            $outcome = $forCount >= 2
                ? YouthEqualScoreRound::OUTCOME_ADOPTED
                : YouthEqualScoreRound::OUTCOME_REJECTED;

            $lockedRound->forceFill([
                'locked_at' => now(),
                'outcome' => $outcome,
                'locked_by_user_id' => $user->id,
                'locked_by_member_id' => $chairman->id,
                'locked_by_name' => $this->memberDisplayName($chairman, $user),
            ])->save();

            return $lockedRound->fresh(['applications', 'votes']);
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function detectBoundaryGroups(Competition $competition): array
    {
        if (! $competition->isOmladinskoProfile()) {
            return [];
        }

        if (! $this->canonicalScoring->isYouthPreliminaryRankingReady($competition)) {
            return [];
        }

        $scored = $this->scoredRankedApplications($competition);
        $budget = $this->money((string) ($competition->budget ?? 0));
        $used = '0.00';
        $groups = [];

        foreach ($scored->groupBy('rank')->sortKeys() as $rank => $rows) {
            foreach ($rows->groupBy('full_score') as $fullScore => $sameScore) {
                $remaining = bcsub($budget, $used, 2);
                if ($sameScore->count() >= 2) {
                    $detected = $this->analyzeTiedSet((int) $rank, (string) $fullScore, $sameScore, $remaining);
                    if ($detected !== null) {
                        $groups[] = $detected;
                        $adopted = $this->adoptedLockedRound($competition, (string) $detected['group_key']);
                        if ($adopted === null) {
                            return $groups;
                        }
                        $used = bcadd($used, $this->selectedProposalTotal($adopted), 2);

                        continue;
                    }
                }

                $used = bcadd($used, $this->sumDrafts($this->competingFromRows($sameScore)), 2);
            }
        }

        return $groups;
    }

    /**
     * @param  Collection<int, array{application: Application, full_score: string, rank: int}>  $sameScore
     * @return array<string, mixed>|null
     */
    private function analyzeTiedSet(
        int $rank,
        string $fullScore,
        Collection $sameScore,
        string $remaining,
    ): ?array {
        $applications = $sameScore->map(fn (array $row) => $row['application'])->values();
        foreach ($applications as $application) {
            if (! $this->hasCompleteDraft($application)) {
                return [
                    'group_key' => $this->groupKey($rank, $fullScore),
                    'full_score' => $fullScore,
                    'ranking_position' => $rank,
                    'applied_rule' => self::RULE_SAME_STAGE_VOTE,
                    'remaining_before' => $remaining,
                    'block_reason' => self::INCOMPLETE_DRAFTS_MESSAGE,
                    'can_create_round' => false,
                    'applications' => $this->liveSnapshots($applications, []),
                ];
            }
        }

        $competing = $applications
            ->filter(fn (Application $application) => $this->isCompetingDraft($application))
            ->values();
        if ($competing->count() < 2) {
            return null;
        }

        $startups = $competing
            ->filter(fn (Application $application) => $this->isStartup($application))
            ->values();
        $development = $competing
            ->reject(fn (Application $application) => $this->isStartup($application))
            ->values();

        $startupSum = $this->sumDrafts($startups);
        $developmentSum = $this->sumDrafts($development);
        $allSum = $this->sumDrafts($competing);

        if (bccomp($remaining, $allSum, 2) >= 0) {
            return null;
        }

        if ($startups->isNotEmpty()) {
            if (bccomp($remaining, $startupSum, 2) >= 0) {
                $leftover = bcsub($remaining, $startupSum, 2);
                if ($development->isEmpty() || bccomp($leftover, $developmentSum, 2) >= 0) {
                    return null;
                }
                if ($development->count() < 2) {
                    return null;
                }

                return $this->votablePayload(
                    $rank,
                    $fullScore,
                    self::RULE_DEVELOPMENT_AFTER_STARTUP,
                    $leftover,
                    $development
                );
            }

            if ($startups->count() === 1) {
                return [
                    'group_key' => $this->groupKey($rank, $fullScore),
                    'full_score' => $fullScore,
                    'ranking_position' => $rank,
                    'applied_rule' => self::RULE_STARTUP_PRIORITY_VOTE,
                    'remaining_before' => $remaining,
                    'block_reason' => self::REDUCE_PRIORITY_DRAFT_MESSAGE,
                    'can_create_round' => false,
                    'applications' => $this->liveSnapshots($startups, []),
                ];
            }

            return $this->votablePayload(
                $rank,
                $fullScore,
                self::RULE_STARTUP_PRIORITY_VOTE,
                $remaining,
                $startups
            );
        }

        if ($development->count() === 1) {
            return [
                'group_key' => $this->groupKey($rank, $fullScore),
                'full_score' => $fullScore,
                'ranking_position' => $rank,
                'applied_rule' => self::RULE_SAME_STAGE_VOTE,
                'remaining_before' => $remaining,
                'block_reason' => self::REDUCE_PRIORITY_DRAFT_MESSAGE,
                'can_create_round' => false,
                'applications' => $this->liveSnapshots($development, []),
            ];
        }

        return $this->votablePayload(
            $rank,
            $fullScore,
            self::RULE_SAME_STAGE_VOTE,
            $remaining,
            $development
        );
    }

    /**
     * @param  Collection<int, Application>  $relevant
     * @return array<string, mixed>
     */
    private function votablePayload(
        int $rank,
        string $fullScore,
        string $rule,
        string $remaining,
        Collection $relevant,
    ): array {
        return [
            'group_key' => $this->groupKey($rank, $fullScore),
            'full_score' => $fullScore,
            'ranking_position' => $rank,
            'applied_rule' => $rule,
            'remaining_before' => $remaining,
            'block_reason' => null,
            'can_create_round' => true,
            'applications' => $this->liveSnapshots($relevant, []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function requireVotableGroup(Competition $competition, ?string $groupKey = null): array
    {
        $detected = $this->detectBoundaryGroups($competition);
        $votable = collect($detected)->filter(fn (array $group) => $group['can_create_round'] === true);
        if ($groupKey !== null && $groupKey !== '') {
            $votable = $votable->first(fn (array $group) => $group['group_key'] === $groupKey);
        } else {
            $votable = $votable->first();
        }

        if ($votable === null) {
            $blocked = collect($detected)->first(function (array $group) use ($groupKey) {
                if ($groupKey !== null && $groupKey !== '') {
                    return $group['group_key'] === $groupKey;
                }

                return true;
            });
            if ($blocked !== null && ($blocked['block_reason'] ?? null) === self::REDUCE_PRIORITY_DRAFT_MESSAGE) {
                abort(403, self::REDUCE_PRIORITY_DRAFT_MESSAGE);
            }
            if ($blocked !== null && ($blocked['block_reason'] ?? null) === self::INCOMPLETE_DRAFTS_MESSAGE) {
                abort(403, self::INCOMPLETE_DRAFTS_MESSAGE);
            }
            abort(403, self::NO_BOUNDARY_GROUP_MESSAGE);
        }

        return $votable;
    }

    /**
     * @param  array<string, mixed>  $detected
     * @param  list<int>  $selectedIds
     */
    private function assertSelectionFits(array $detected, array $selectedIds): void
    {
        $relevantIds = collect($detected['applications'])->pluck('application_id')->map(fn ($id) => (int) $id);
        $selected = collect($selectedIds)->unique()->values();
        $unknown = $selected->diff($relevantIds);
        if ($unknown->isNotEmpty()) {
            throw ValidationException::withMessages([
                'selected' => self::DEVELOPMENT_INSTEAD_OF_STARTUP_MESSAGE,
            ]);
        }

        if ($detected['applied_rule'] === self::RULE_STARTUP_PRIORITY_VOTE) {
            foreach ($detected['applications'] as $snapshot) {
                if (in_array((int) $snapshot['application_id'], $selectedIds, true)
                    && ! $this->isStartupStage((string) $snapshot['business_stage'])) {
                    throw ValidationException::withMessages([
                        'selected' => self::DEVELOPMENT_INSTEAD_OF_STARTUP_MESSAGE,
                    ]);
                }
            }
        }

        $proposalTotal = $this->proposalTotal($detected, $selectedIds);
        if (bccomp($proposalTotal, $detected['remaining_before'], 2) === 1) {
            throw ValidationException::withMessages([
                'selected' => self::AMOUNT_EXCEEDS_REMAINING_MESSAGE,
            ]);
        }

        foreach ($detected['applications'] as $snapshot) {
            $amount = $this->money((string) $snapshot['draft_amount']);
            $requested = $snapshot['requested_amount'];
            if ($requested !== null && bccomp($amount, $this->money((string) $requested), 2) === 1) {
                throw ValidationException::withMessages([
                    'selected' => YouthAllocationDraftService::AMOUNT_EXCEEDS_REQUESTED_MESSAGE,
                ]);
            }
            $percent = $snapshot['applied_cap_percent'];
            if ($percent !== null) {
                $application = Application::query()->find((int) $snapshot['application_id']);
                $application?->loadMissing('competition');
                $cap = $this->percentOfBudget(
                    $this->money((string) ($application?->competition?->budget ?? 0)),
                    (int) $percent
                );
                if (bccomp($amount, $cap, 2) === 1) {
                    throw ValidationException::withMessages([
                        'selected' => YouthAllocationDraftService::AMOUNT_EXCEEDS_PERCENT_CAP_MESSAGE,
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $detected
     * @param  list<int>  $selectedIds
     */
    private function proposalTotal(array $detected, array $selectedIds): string
    {
        $total = '0.00';
        foreach ($detected['applications'] as $snapshot) {
            if (! in_array((int) $snapshot['application_id'], $selectedIds, true)) {
                continue;
            }
            $total = bcadd($total, $this->money((string) $snapshot['draft_amount']), 2);
        }

        return $total;
    }

    /**
     * @param  array<string, mixed>  $detected
     * @param  list<int>  $selectedIds
     */
    private function syncRoundApplications(YouthEqualScoreRound $round, array $detected, array $selectedIds): void
    {
        YouthEqualScoreRoundApplication::query()->where('round_id', $round->id)->delete();
        foreach ($detected['applications'] as $snapshot) {
            YouthEqualScoreRoundApplication::query()->create([
                'round_id' => $round->id,
                'application_id' => $snapshot['application_id'],
                'business_stage' => $snapshot['business_stage'],
                'requested_amount' => $snapshot['requested_amount'],
                'draft_amount' => $snapshot['draft_amount'],
                'applied_cap_percent' => $snapshot['applied_cap_percent'],
                'selected' => in_array((int) $snapshot['application_id'], $selectedIds, true),
            ]);
        }
    }

    /**
     * @param  array<int, string>  $votesInput
     */
    private function syncVotes(YouthEqualScoreRound $round, Competition $competition, User $user, array $votesInput): void
    {
        if ($votesInput === []) {
            return;
        }

        $seats = $this->activeCanonicalSeats($competition);
        foreach ($votesInput as $seatNo => $value) {
            $member = $seats->get((int) $seatNo);
            if ($member === null) {
                continue;
            }
            YouthEqualScoreVote::query()->updateOrCreate(
                [
                    'round_id' => $round->id,
                    'canonical_seat_no' => (int) $seatNo,
                ],
                [
                    'commission_member_id' => $member->id,
                    'user_id' => $member->user_id,
                    'member_name' => $this->memberDisplayName($member, $member->user),
                    'vote_value' => $value,
                    'recorded_by_user_id' => $user->id,
                    'voted_at' => now(),
                ]
            );
        }
    }

    /**
     * @return Collection<int, CommissionMember>
     */
    private function activeCanonicalSeats(Competition $competition): Collection
    {
        if (! $competition->commission_id) {
            return collect();
        }

        return CommissionMember::query()
            ->where('commission_id', $competition->commission_id)
            ->where('status', 'active')
            ->whereIn('canonical_seat_no', [1, 2, 3])
            ->with('user')
            ->get()
            ->keyBy(fn (CommissionMember $member) => (int) $member->canonical_seat_no);
    }

    /**
     * @param  Collection<int, Application>  $applications
     * @param  list<int>  $selectedIds
     * @return list<array<string, mixed>>
     */
    private function liveSnapshots(Collection $applications, array $selectedIds): array
    {
        return $applications->map(function (Application $application) use ($selectedIds) {
            return [
                'application_id' => (int) $application->id,
                'business_plan_name' => $application->business_plan_name,
                'applicant_name' => $application->user->name ?? '',
                'business_stage' => (string) $application->business_stage,
                'requested_amount' => $application->requested_amount !== null
                    ? $this->money((string) $application->requested_amount)
                    : null,
                'draft_amount' => $this->money((string) ($application->approved_amount ?? 0)),
                'applied_cap_percent' => $application->youth_applied_cap_percent !== null
                    ? (int) $application->youth_applied_cap_percent
                    : null,
                'selected' => in_array((int) $application->id, $selectedIds, true),
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function roundPayload(YouthEqualScoreRound $round): array
    {
        $round->loadMissing(['applications.application.user', 'votes']);

        return [
            'id' => $round->id,
            'round_no' => (int) $round->round_no,
            'justification' => $round->justification,
            'budget_before' => $this->money((string) $round->budget_before),
            'proposal_total' => $this->money((string) $round->proposal_total),
            'locked_at' => $round->locked_at,
            'outcome' => $round->outcome,
            'created_by_name' => $round->created_by_name,
            'locked_by_name' => $round->locked_by_name,
            'applications' => $round->applications->map(function (YouthEqualScoreRoundApplication $item) {
                return [
                    'application_id' => (int) $item->application_id,
                    'business_plan_name' => $item->application?->business_plan_name,
                    'applicant_name' => $item->application?->user?->name,
                    'business_stage' => $item->business_stage,
                    'requested_amount' => $item->requested_amount !== null
                        ? $this->money((string) $item->requested_amount)
                        : null,
                    'draft_amount' => $this->money((string) $item->draft_amount),
                    'applied_cap_percent' => $item->applied_cap_percent,
                    'selected' => (bool) $item->selected,
                ];
            })->all(),
            'votes' => $round->votes->sortBy('canonical_seat_no')->map(function (YouthEqualScoreVote $vote) {
                return [
                    'canonical_seat_no' => (int) $vote->canonical_seat_no,
                    'member_name' => $vote->member_name,
                    'vote_value' => $vote->vote_value,
                    'voted_at' => $vote->voted_at,
                ];
            })->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, array{application: Application, full_score: string, rank: int}>  $allScored
     */
    private function remainingBeforeRank(Competition $competition, Collection $allScored, int $rank): string
    {
        $budget = $this->money((string) ($competition->budget ?? 0));
        $used = '0.00';

        foreach ($allScored->groupBy('rank')->sortKeys() as $groupRank => $rows) {
            if ((int) $groupRank >= $rank) {
                break;
            }

            foreach ($rows->groupBy('full_score') as $fullScore => $sameScore) {
                $remainingAt = bcsub($budget, $used, 2);
                $used = bcadd(
                    $used,
                    $this->consumptionForSet($competition, (int) $groupRank, (string) $fullScore, $sameScore, $remainingAt),
                    2
                );
            }
        }

        return bcsub($budget, $used, 2);
    }

    /**
     * @param  Collection<int, array{application: Application, full_score: string, rank: int}>  $sameScore
     */
    private function consumptionForSet(
        Competition $competition,
        int $rank,
        string $fullScore,
        Collection $sameScore,
        string $remainingAt,
    ): string {
        $adopted = $this->adoptedLockedRound($competition, $this->groupKey($rank, $fullScore));
        if ($adopted !== null) {
            return $this->selectedProposalTotal($adopted);
        }

        if ($sameScore->count() >= 2) {
            $detected = $this->analyzeTiedSet($rank, $fullScore, $sameScore, $remainingAt);
            if ($detected !== null) {
                return '0.00';
            }
        }

        return $this->sumDrafts($this->competingFromRows($sameScore));
    }

    /**
     * @return Collection<int, array{application: Application, full_score: string, rank: int}>
     */
    private function scoredRankedApplications(Competition $competition): Collection
    {
        return Application::query()
            ->where('competition_id', $competition->id)
            ->whereNotNull('ranking_position')
            ->with('user')
            ->orderBy('ranking_position')
            ->orderBy('id')
            ->get()
            ->map(function (Application $application) {
                $aggregate = $this->canonicalScoring->aggregateYouthApplication($application);

                return [
                    'application' => $application,
                    'full_score' => $this->fullScore($aggregate['final_score_full'] ?? '0'),
                    'rank' => (int) $application->ranking_position,
                ];
            });
    }

    /**
     * @param  Collection<int, array{application: Application, full_score: string, rank: int}>  $rows
     * @return Collection<int, Application>
     */
    private function competingFromRows(Collection $rows): Collection
    {
        return $rows
            ->map(fn (array $row) => $row['application'])
            ->filter(fn (Application $application) => $this->isCompetingDraft($application))
            ->values();
    }

    private function adoptedLockedRound(Competition $competition, string $groupKey): ?YouthEqualScoreRound
    {
        return YouthEqualScoreRound::query()
            ->whereNotNull('locked_at')
            ->where('outcome', YouthEqualScoreRound::OUTCOME_ADOPTED)
            ->whereHas('group', function ($query) use ($competition, $groupKey) {
                $query->where('competition_id', $competition->id)
                    ->where('group_key', $groupKey);
            })
            ->with('applications')
            ->first();
    }

    private function selectedProposalTotal(YouthEqualScoreRound $round): string
    {
        $round->loadMissing('applications');
        $sum = '0.00';
        foreach ($round->applications as $item) {
            if (! $item->selected) {
                continue;
            }
            $sum = bcadd($sum, $this->money((string) $item->draft_amount), 2);
        }

        return $sum;
    }

    private function hasCompleteDraft(Application $application): bool
    {
        if ($application->commission_decision === 'podrzava_potpuno') {
            return $application->approved_amount !== null
                && bccomp($this->money((string) $application->approved_amount), '0', 2) === 1;
        }

        if ($application->commission_decision === 'odbija') {
            return trim((string) $application->commission_justification) !== '';
        }

        return false;
    }

    private function isCompetingDraft(Application $application): bool
    {
        return $application->commission_decision === 'podrzava_potpuno'
            && $application->approved_amount !== null
            && bccomp($this->money((string) $application->approved_amount), '0', 2) === 1;
    }

    /**
     * @param  Collection<int, Application>  $applications
     */
    private function sumDrafts(Collection $applications): string
    {
        $sum = '0.00';
        foreach ($applications as $application) {
            $sum = bcadd($sum, $this->money((string) ($application->approved_amount ?? 0)), 2);
        }

        return $sum;
    }

    private function isStartup(Application $application): bool
    {
        return $this->isStartupStage((string) $application->business_stage);
    }

    private function isStartupStage(string $stage): bool
    {
        return $stage === KnApplicationClassification::STAGE_ZAPOCINJANJE;
    }

    private function groupKey(int $rank, string $fullScore): string
    {
        return $rank.':'.$fullScore;
    }

    private function fullScore(string $value): string
    {
        return bcadd($value, '0', 10);
    }

    private function adoptedRound(YouthEqualScoreGroup $group): ?YouthEqualScoreRound
    {
        return YouthEqualScoreRound::query()
            ->where('group_id', $group->id)
            ->whereNotNull('locked_at')
            ->where('outcome', YouthEqualScoreRound::OUTCOME_ADOPTED)
            ->first();
    }

    private function guardChairmanMutation(Competition $competition, User $user): CommissionMember
    {
        if (! $competition->isOmladinskoProfile()) {
            abort(403, self::NOT_YOUTH_MESSAGE);
        }

        if (in_array($competition->status, ['closed', 'completed'], true)) {
            abort(403, self::COMPLETED_LOCKED_MESSAGE);
        }

        $chairman = $this->activeChairman($competition, $user);
        if ($chairman === null) {
            abort(403, self::CHAIRMAN_ONLY_MESSAGE);
        }

        return $chairman;
    }

    private function activeChairman(Competition $competition, User $user): ?CommissionMember
    {
        $member = $this->activeMember($competition, $user);
        if ($member === null || $member->position !== 'predsjednik') {
            return null;
        }

        return $member;
    }

    private function activeMember(Competition $competition, User $user): ?CommissionMember
    {
        if (! $competition->commission_id) {
            return null;
        }

        return CommissionMember::activeForCommission((int) $user->id, (int) $competition->commission_id);
    }

    private function memberDisplayName(CommissionMember $member, ?User $user): string
    {
        $name = trim((string) $member->name);
        if ($name !== '') {
            return $name;
        }

        return trim((string) ($user?->name ?? ''));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<int>
     */
    private function selectedIdsFromInput(array $input): array
    {
        $selected = $input['selected'] ?? [];
        if (! is_array($selected)) {
            return [];
        }

        return collect($selected)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, string>
     */
    private function votesFromInput(array $input): array
    {
        $votes = $input['votes'] ?? [];
        if (! is_array($votes)) {
            return [];
        }

        $clean = [];
        foreach ([1, 2, 3] as $seat) {
            $value = $votes[$seat] ?? $votes[(string) $seat] ?? null;
            if ($value === YouthEqualScoreVote::FOR || $value === YouthEqualScoreVote::AGAINST) {
                $clean[$seat] = $value;
            }
        }

        return $clean;
    }

    private function percentOfBudget(string $budget, int $percent): string
    {
        return bcmul($budget, bcdiv((string) $percent, '100', 2), 2);
    }

    private function money(string $value): string
    {
        return bcadd($value, '0', 2);
    }
}
