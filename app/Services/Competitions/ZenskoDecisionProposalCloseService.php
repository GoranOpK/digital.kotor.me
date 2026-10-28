<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\User;
use App\Models\ZenskoDecisionProposalSnapshot;
use App\Services\CanonicalIndividualScoringService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Zatvara konkurs ženskog preduzetništva tek kada je HTML Predlog sačuvan.
 * Ne dira omladinski konkurs ni MMSP.
 */
class ZenskoDecisionProposalCloseService
{
    public function __construct(
        private readonly ZenskoDecisionProposalHtmlStore $documents,
        private readonly ZpEqualScoreAllocationGuard $equalScores,
        private readonly CanonicalIndividualScoringService $scoring,
    ) {}

    public function close(Competition $competition, ?User $actor): ZenskoDecisionProposalCloseResult
    {
        if ($competition->type !== 'zensko') {
            return ZenskoDecisionProposalCloseResult::abort(
                403,
                'Ova radnja važi samo za konkurs ženskog preduzetništva.'
            );
        }

        if ($actor === null || ! $this->actorIsChairman($competition, $actor)) {
            return ZenskoDecisionProposalCloseResult::abort(
                403,
                'Nemate dozvolu za zatvaranje konkursa. Samo predsjednik komisije može zatvarati konkurse.'
            );
        }

        $attemptPath = null;

        try {
            return DB::transaction(function () use ($competition, $actor, &$attemptPath) {
                $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->firstOrFail();

                if ($locked->zensko_decision_proposal_snapshot_id !== null) {
                    if (in_array($locked->status, ['closed', 'completed'], true)) {
                        return ZenskoDecisionProposalCloseResult::success();
                    }

                    return ZenskoDecisionProposalCloseResult::error(
                        'Konkurs nije zatvoren jer veza prema snimku Predloga već postoji.'
                    );
                }

                if (in_array($locked->status, ['closed', 'completed'], true)) {
                    return ZenskoDecisionProposalCloseResult::success();
                }

                Application::query()
                    ->where('competition_id', $locked->id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if (! $this->actorIsChairman($locked, $actor)) {
                    return ZenskoDecisionProposalCloseResult::abort(
                        403,
                        'Nemate dozvolu za zatvaranje konkursa. Samo predsjednik komisije može zatvarati konkurse.'
                    );
                }

                $blocked = $this->blocked($locked);
                if ($blocked !== null) {
                    return $blocked;
                }

                $file = $this->documents->store($locked);
                $attemptPath = $file->storagePath;
                if ((int) $file->competitionId !== (int) $locked->id) {
                    throw new ZenskoDecisionProposalStoreException(
                        'Snimak ne pripada konkursu koji se zatvara.'
                    );
                }

                $this->beforePersist($file);
                $snapshot = $this->recordSnapshot($locked, $file, $actor);
                $this->completeCompetition($locked, $snapshot);

                return ZenskoDecisionProposalCloseResult::success();
            });
        } catch (Throwable $exception) {
            if (! $exception instanceof ZenskoDecisionProposalStoreException) {
                report($exception);
            }

            $warning = $this->discardUnreferencedAttempt($attemptPath);
            $message = $exception instanceof ZenskoDecisionProposalStoreException
                ? $exception->getMessage()
                : 'Konkurs nije zatvoren jer Predlog odluke nije sačuvan.';

            if ($warning !== null) {
                $message .= ' '.$warning;
            }

            return ZenskoDecisionProposalCloseResult::error($message);
        }
    }

    protected function beforePersist(ZenskoDecisionProposalStoredFile $file): void
    {
    }

    protected function recordSnapshot(
        Competition $competition,
        ZenskoDecisionProposalStoredFile $file,
        User $actor,
    ): ZenskoDecisionProposalSnapshot {
        $snapshot = ZenskoDecisionProposalSnapshot::query()->create([
            'competition_id' => $competition->id,
            'storage_path' => $file->storagePath,
            'content_sha256' => $file->contentSha256,
            'closed_by' => $actor->id,
        ]);

        if ((int) $snapshot->competition_id !== (int) $competition->id) {
            throw new ZenskoDecisionProposalStoreException(
                'Snimak ne pripada konkursu koji se zatvara.'
            );
        }

        return $snapshot;
    }

    protected function completeCompetition(Competition $competition, ZenskoDecisionProposalSnapshot $snapshot): void
    {
        if ((int) $snapshot->competition_id !== (int) $competition->id) {
            throw new ZenskoDecisionProposalStoreException(
                'Snimak ne pripada konkursu koji se zatvara.'
            );
        }

        $competition->forceFill([
            'status' => 'completed',
            'closed_at' => now(),
            'zensko_decision_proposal_snapshot_id' => $snapshot->id,
        ])->save();

        $competition->refresh();

        if ($competition->status !== 'completed'
            || (int) $competition->zensko_decision_proposal_snapshot_id !== (int) $snapshot->id) {
            throw new ZenskoDecisionProposalStoreException(
                'Konkurs nije povezan sa snimkom Predloga odluke.'
            );
        }
    }

    protected function discardUnreferencedAttempt(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        try {
            $referenced = ZenskoDecisionProposalSnapshot::query()
                ->where('storage_path', $path)
                ->exists();
        } catch (Throwable) {
            return 'Stanje sačuvanog Predloga odluke nije moglo da se provjeri. Fajl nije obrisan.';
        }

        if ($referenced) {
            return null;
        }

        try {
            $deleted = Storage::disk('local')->delete($path);
        } catch (Throwable) {
            return 'Fajl neuspjelog pokušaja nije mogao da se ukloni.';
        }

        if ($deleted !== true && Storage::disk('local')->exists($path)) {
            return 'Fajl neuspjelog pokušaja nije mogao da se ukloni.';
        }

        return null;
    }

    private function actorIsChairman(Competition $competition, User $actor): bool
    {
        if (! $competition->commission_id) {
            return false;
        }

        $member = CommissionMember::activeForCommission($actor->id, (int) $competition->commission_id);

        return $member !== null && $member->position === 'predsjednik';
    }

    private function blocked(Competition $competition): ?ZenskoDecisionProposalCloseResult
    {
        $deadline = $competition->deadline;
        if (! $deadline || ! now()->isAfter($deadline)) {
            return ZenskoDecisionProposalCloseResult::error(
                'Ne možete zatvoriti konkurs dok je još otvoren za prijave. Rok za prijave mora prvo isteći.'
            );
        }

        if (! $competition->hasChairmanCompletedDecisions()) {
            return ZenskoDecisionProposalCloseResult::error(
                'Ne možete zatvoriti konkurs prije nego što donesete zaključak za sve prijave i generišete odluku o dodjeli sredstava.'
            );
        }

        $equalScoreBlock = $this->equalScores->blockReason($competition);
        if ($equalScoreBlock !== null) {
            return ZenskoDecisionProposalCloseResult::error($equalScoreBlock);
        }

        $identityBlock = $this->identityBlock($competition);
        if ($identityBlock !== null) {
            return ZenskoDecisionProposalCloseResult::error($identityBlock);
        }

        $evaluationBlock = $this->rejectIncompleteAndBlockUnevaluated($competition);
        if ($evaluationBlock !== null) {
            return $evaluationBlock;
        }

        $competition->load('commission');
        $rankableApplications = $competition->applications()
            ->with(['evaluationScores', 'eliminatoryCheck', 'prigovor'])
            ->get()
            ->filter(function ($application) {
                if ($application->isEliminatedFromScoring()) {
                    return false;
                }

                if ($application->evaluationScores->count() === 0) {
                    return false;
                }

                return $application->meetsMinimumScore();
            });

        $this->scoring->assignAboveLineRankingPositions($rankableApplications);

        return null;
    }

    private function identityBlock(Competition $competition): ?string
    {
        $lines = [];
        foreach (app(CompetitionDecisionDocumentBuilder::class)->articleOneApplications($competition) as $winner) {
            $reason = $winner->decisionIdentityBlockReason();
            if ($reason === null) {
                continue;
            }

            $label = filled($winner->business_plan_name)
                ? (string) $winner->business_plan_name
                : 'Prijava #'.$winner->id;
            $lines[] = $label.': '.$reason;
        }

        return $lines === [] ? null : implode(' ', $lines);
    }

    private function rejectIncompleteAndBlockUnevaluated(Competition $competition): ?ZenskoDecisionProposalCloseResult
    {
        $submittedApplications = $competition->applications()
            ->where('status', 'submitted')
            ->get();

        if ($submittedApplications->isEmpty()) {
            return null;
        }

        $commission = $competition->commission;
        if (! $commission) {
            return null;
        }

        $activeMemberIds = $commission->activeMembers()->pluck('id');

        foreach ($submittedApplications as $application) {
            $hasBusinessPlan = $application->businessPlan !== null;
            $isObrazacComplete = $application->isObrazacComplete();

            if (! $hasBusinessPlan || ! $isObrazacComplete) {
                if ($application->status === 'submitted') {
                    $application->update([
                        'status' => 'rejected',
                        'rejection_reason' => 'Prijava je nepotpuna (nedostaje biznis plan ili obrazac nije kompletan) i rok za prijave je istekao.',
                    ]);
                }

                continue;
            }

            $evaluatedByMemberIds = $application->evaluationScores()
                ->whereIn('commission_member_id', $activeMemberIds)
                ->pluck('commission_member_id');

            if ($activeMemberIds->diff($evaluatedByMemberIds)->isNotEmpty()) {
                return ZenskoDecisionProposalCloseResult::redirect(
                    'evaluation.index',
                    ['competition_id' => $competition->id],
                    'Ne možete zatvoriti konkurs dok postoje prijave koje nisu ocijenjene od svih članova komisije. Molimo prvo ocijenite sve prijave.'
                );
            }
        }

        return null;
    }
}
