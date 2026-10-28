<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\CommissionMember;
use App\Models\Competition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Builds the view data for the official competition decision document.
 * Shared by admin decision rendering and public Notice content delivery.
 */
class CompetitionDecisionDocumentBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Competition $competition): array
    {
        $winners = $competition->type === 'zensko'
            ? $this->articleOneApplications($competition)
            : $this->positiveApprovedAmountApplications($competition);

        $chairmanName = null;
        $commissionMembersCount = 0;
        $competition->load('commission');
        if ($competition->commission_id && $competition->commission) {
            $chairman = CommissionMember::where('commission_id', $competition->commission_id)
                ->where('status', 'active')
                ->where('position', 'predsjednik')
                ->with('user')
                ->first();
            $chairmanName = $chairman?->name ?: $chairman?->user?->name;
            $commissionMembersCount = $competition->commission->activeMembers()->count();
        }

        $allApplications = $competition->applications()
            ->whereIn('status', ['submitted', 'evaluated', 'rejected', 'approved'])
            ->get();
        $totalApplications = $allApplications->count();
        $incompleteCount = $allApplications->filter(
            fn ($a) => $a->rejection_reason && str_contains($a->rejection_reason, 'Nedostaju potrebna dokumenta')
        )->count();
        $eligibleCount = $totalApplications - $incompleteCount;
        $pubStart = $competition->start_date ?? $competition->published_at;
        $pubEnd = $competition->deadline;
        $deadlineDay = $competition->deadline
            ? $competition->deadline->copy()->addDay()->startOfDay()
            : null;
        $oralDate = $allApplications->min('interview_scheduled_at')
            ?? ($competition->deadline ? $competition->deadline->copy()->addDays(4) : null);
        $rankingDate = $competition->closed_at ?? now();
        $firstSessionDate = $pubEnd ? $pubEnd->copy()->addDay() : null;
        $winnersCount = $winners->count();
        $totalApprovedAmount = (float) $winners->sum('approved_amount');
        $competitionYear = $competition->year ?? (int) date('Y');

        $isCurrentWomenEntrepreneurshipCompetition =
            $competition->type === 'zensko'
            && (int) $competition->year === 2026;

        if ($isCurrentWomenEntrepreneurshipCompetition) {
            $decisionDate = Carbon::create(2026, 7, 31);
            $rankingDate = $decisionDate->copy();
        } else {
            $decisionDate = $competition->closed_at ?? now();
        }

        return compact(
            'competition',
            'winners',
            'chairmanName',
            'commissionMembersCount',
            'totalApplications',
            'incompleteCount',
            'eligibleCount',
            'pubStart',
            'pubEnd',
            'deadlineDay',
            'oralDate',
            'rankingDate',
            'firstSessionDate',
            'winnersCount',
            'totalApprovedAmount',
            'competitionYear',
            'decisionDate'
        );
    }

    /**
     * Član 1 ženskog Predloga: podržane prijave iznad postojećeg praga, bez eliminisanih i odbijenih.
     *
     * @return Collection<int, Application>
     */
    public function articleOneApplications(Competition $competition): Collection
    {
        return Application::query()
            ->where('competition_id', $competition->id)
            ->where('commission_decision', 'podrzava_potpuno')
            ->where('status', '!=', 'rejected')
            ->whereNotNull('approved_amount')
            ->where('approved_amount', '>', 0)
            ->with(['user.identity.legalEntity', 'businessPlan', 'eliminatoryCheck', 'prigovor'])
            ->get()
            ->filter(function (Application $application): bool {
                if ($application->isEliminatedFromScoring()) {
                    return false;
                }

                return $application->meetsMinimumScore();
            })
            ->sort(function (Application $a, Application $b): int {
                $aPos = $a->ranking_position ?? PHP_INT_MAX;
                $bPos = $b->ranking_position ?? PHP_INT_MAX;

                if ($aPos !== $bPos) {
                    return $aPos <=> $bPos;
                }

                $aScore = (float) ($a->final_score ?? 0);
                $bScore = (float) ($b->final_score ?? 0);
                if ($aScore !== $bScore) {
                    return $bScore <=> $aScore;
                }

                return $a->id <=> $b->id;
            })
            ->values();
    }

    /**
     * @return Collection<int, Application>
     */
    private function positiveApprovedAmountApplications(Competition $competition): Collection
    {
        return Application::query()
            ->where('competition_id', $competition->id)
            ->whereNotNull('approved_amount')
            ->where('approved_amount', '>', 0)
            ->with(['user', 'businessPlan'])
            ->orderBy('ranking_position')
            ->orderBy('id')
            ->get();
    }
}
