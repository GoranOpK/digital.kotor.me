<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\Competition;
use Illuminate\Support\Collection;

/**
 * Minimal §13.6 / §14.9 equal-score financing check for ŽP.
 *
 * Final-allocation guard only — no persistence subsystem, no score/rank changes,
 * no application-ID tie-break. Does not block mid-flow storeDecision for stage priority.
 */
final class ZpEqualScoreAllocationGuard
{
    public const STAGE_PRIORITY_VIOLATION_MESSAGE =
        'Raspodjela kod jednakog broja bodova ne poštuje prednost prijave za započinjanje poslovanja.';

    public const SAME_STAGE_JUSTIFICATION_MESSAGE =
        'Kod jednakog broja bodova i istog poslovnog stadijuma, kada sredstva nijesu dovoljna za sve, obrazloženje odluke Komisije je obavezno.';

    /**
     * Non-null when Predlog / finalization must be blocked for ŽP §13.6.
     */
    public function blockReason(Competition $competition): ?string
    {
        if ($competition->type !== 'zensko') {
            return null;
        }

        if (! $competition->hasChairmanCompletedDecisions()) {
            return null;
        }

        $ranking = $this->rankingApplications($competition);
        if ($ranking->count() < 2) {
            return null;
        }

        foreach ($this->equalScoreGroups($ranking) as $group) {
            if (! $this->priorityRuleActive($competition, $ranking, $group)) {
                continue;
            }

            if ($this->stagePriorityViolated($group)) {
                return self::STAGE_PRIORITY_VIOLATION_MESSAGE;
            }

            if ($this->sameStageChoiceLacksJustification($group)) {
                return self::SAME_STAGE_JUSTIFICATION_MESSAGE;
            }
        }

        return null;
    }

    /**
     * Whether storeDecision must require commission_justification for this application.
     * Only for activated equal-score / same-stage budget competition — not every Podržava.
     *
     * @param  array{commission_decision?: string|null}  $pending
     */
    public function requiresCommissionJustification(
        Competition $competition,
        Application $application,
        array $pending = []
    ): bool {
        if ($competition->type !== 'zensko') {
            return false;
        }

        $ranking = $this->rankingApplications($competition);
        if (! $ranking->contains(fn (Application $app) => (int) $app->id === (int) $application->id)) {
            return false;
        }

        $decision = $pending['commission_decision'] ?? $application->commission_decision;
        if (! in_array($decision, ['podrzava_potpuno', 'odbija'], true)) {
            return false;
        }

        $scoreMoney = $this->money((float) $application->final_score);
        $stage = $application->business_stage;

        $group = $ranking->filter(function (Application $app) use ($scoreMoney, $stage) {
            return $this->money((float) $app->final_score) === $scoreMoney
                && $app->business_stage === $stage;
        })->values();

        if ($group->count() < 2) {
            return false;
        }

        if (! $this->priorityRuleActive($competition, $ranking, $group)) {
            return false;
        }

        return true;
    }

    /**
     * @return Collection<int, Application>
     */
    public function rankingApplications(Competition $competition): Collection
    {
        $allApplications = $competition->applications()
            ->whereIn('status', ['submitted', 'evaluated', 'rejected', 'approved'])
            ->with(['evaluationScores', 'eliminatoryCheck', 'prigovor'])
            ->get();

        return $allApplications
            ->filter(function (Application $application) {
                if ($application->isEliminatedFromScoring()) {
                    return false;
                }
                if ($application->final_score === null || $application->ranking_position === null) {
                    return false;
                }
                if (! $application->meetsMinimumScore()) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    /**
     * @param  Collection<int, Application>  $ranking
     * @return list<Collection<int, Application>>
     */
    private function equalScoreGroups(Collection $ranking): array
    {
        $groups = [];

        foreach ($ranking->groupBy(fn (Application $app) => $this->money((float) $app->final_score)) as $members) {
            if ($members->count() < 2) {
                continue;
            }
            $groups[] = $members->values();
        }

        return $groups;
    }

    /**
     * @param  Collection<int, Application>  $ranking
     * @param  Collection<int, Application>  $group
     */
    private function priorityRuleActive(
        Competition $competition,
        Collection $ranking,
        Collection $group
    ): bool {
        $groupRank = (int) $group->first()->ranking_position;
        $remaining = $this->remainingBeforeRank($competition, $ranking, $groupRank);
        $costAll = $this->groupRequestedCost($group);

        if (bccomp($remaining, $costAll, 2) >= 0) {
            return false;
        }

        // Ako su sve u grupi podržane, sredstva jesu raspoređena svima — §13.6 se ne pali.
        $allSupported = $group->every(
            fn (Application $app) => $app->commission_decision === 'podrzava_potpuno'
        );

        return ! $allSupported;
    }

    /**
     * @param  Collection<int, Application>  $ranking
     */
    private function remainingBeforeRank(
        Competition $competition,
        Collection $ranking,
        int $groupRank
    ): string {
        $budget = $this->money((float) ($competition->budget ?? 0));
        $spent = '0.00';

        foreach ($ranking as $application) {
            if ((int) $application->ranking_position >= $groupRank) {
                continue;
            }
            if ($application->commission_decision !== 'podrzava_potpuno') {
                continue;
            }
            if ($application->approved_amount === null) {
                continue;
            }
            $spent = bcadd($spent, $this->money((float) $application->approved_amount), 2);
        }

        return bcsub($budget, $spent, 2);
    }

    /**
     * @param  Collection<int, Application>  $group
     */
    private function groupRequestedCost(Collection $group): string
    {
        $sum = '0.00';
        foreach ($group as $application) {
            $requested = $application->requested_amount;
            if ($requested === null) {
                continue;
            }
            $sum = bcadd($sum, $this->money((float) $requested), 2);
        }

        return $sum;
    }

    /**
     * @param  Collection<int, Application>  $group
     */
    private function stagePriorityViolated(Collection $group): bool
    {
        $stages = $group->pluck('business_stage')->unique()->filter()->values();
        if (! $stages->contains('započinjanje') || ! $stages->contains('razvoj')) {
            return false;
        }

        $razvojSupported = $group->contains(
            fn (Application $app) => $app->business_stage === 'razvoj'
                && $app->commission_decision === 'podrzava_potpuno'
        );

        $zapocinjanjeRejected = $group->contains(
            fn (Application $app) => $app->business_stage === 'započinjanje'
                && $app->commission_decision === 'odbija'
        );

        return $razvojSupported && $zapocinjanjeRejected;
    }

    /**
     * @param  Collection<int, Application>  $group
     */
    private function sameStageChoiceLacksJustification(Collection $group): bool
    {
        $stages = $group->pluck('business_stage')->unique()->filter()->values();
        if ($stages->count() !== 1) {
            return false;
        }

        $supported = $group->contains(fn (Application $app) => $app->commission_decision === 'podrzava_potpuno');
        $rejected = $group->contains(fn (Application $app) => $app->commission_decision === 'odbija');
        if (! $supported || ! $rejected) {
            return false;
        }

        return $group->contains(
            fn (Application $app) => trim((string) $app->commission_justification) === ''
        );
    }

    private function money(float|int|string $value): string
    {
        return bcadd((string) $value, '0', 2);
    }
}
