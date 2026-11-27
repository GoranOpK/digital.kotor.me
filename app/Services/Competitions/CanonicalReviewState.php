<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\ApplicationComplaint;
use App\Models\ApplicationCompletenessAssessment;
use App\Services\Competitions\Profile\CanonicalMmspPolicy;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Canonical completeness/complaint state, derived from structured rows only.
 *
 * Not a second Application status machine: Application.status stays `submitted`
 * throughout review; this resolves the review sub-state from assessment/complaint records.
 */
final class CanonicalReviewState
{
    public const NOT_FINAL = 'not_final';

    public const PENDING_REVIEW = 'pending_review';

    public const COMPLETE = 'complete';

    public const INCOMPLETE = 'incomplete';

    public const COMPLAINT_ELIGIBLE = 'complaint_eligible';

    public const COMPLAINT_PENDING = 'complaint_pending';

    public const COMPLAINT_RESOLVED = 'complaint_resolved';

    public const LABELS = [
        self::NOT_FINAL => 'Nije konačno podnesena',
        self::PENDING_REVIEW => 'Čeka provjeru potpunosti',
        self::COMPLETE => 'Potpuna',
        self::INCOMPLETE => 'Nepotpuna — rok za prigovor istekao',
        self::COMPLAINT_ELIGIBLE => 'Nepotpuna — rok za prigovor teče',
        self::COMPLAINT_PENDING => 'Prigovor podnesen — čeka odluku',
        self::COMPLAINT_RESOLVED => 'Odlučeno o prigovoru',
    ];

    public static function resolve(Application $application, ?CarbonImmutable $at = null): ?string
    {
        $application->loadMissing(['competition', 'finalClaim']);
        $competition = $application->competition;
        if (! $competition || $competition->type !== CompetitionProfileResolver::TYPE_CANONICAL) {
            return null;
        }

        if (! $application->finalClaim || $application->status === 'draft') {
            return self::NOT_FINAL;
        }

        $complaint = ApplicationComplaint::query()->where('application_id', $application->id)->first();
        if ($complaint) {
            return $complaint->decision === null ? self::COMPLAINT_PENDING : self::COMPLAINT_RESOLVED;
        }

        $latest = ApplicationCompletenessAssessment::query()
            ->where('application_id', $application->id)
            ->latest('id')
            ->first();

        if (! $latest) {
            return self::PENDING_REVIEW;
        }

        if ($latest->outcome === ApplicationCompletenessAssessment::COMPLETE) {
            return self::COMPLETE;
        }

        return self::complaintWindowOpen($application, $latest, $at)
            ? self::COMPLAINT_ELIGIBLE
            : self::INCOMPLETE;
    }

    public static function complaintWindowOpen(
        Application $application,
        ApplicationCompletenessAssessment $assessment,
        ?CarbonImmutable $at = null,
    ): bool {
        if ($assessment->outcome !== ApplicationCompletenessAssessment::INCOMPLETE || ! $assessment->notice_sent_at) {
            return false;
        }

        $policy = $application->competition->profile();
        $deadline = $policy->complaintWindowEndsAt($assessment->notice_sent_at);
        if (! $deadline) {
            return false;
        }

        $now = $at ?? CarbonImmutable::now($policy->publicationTimezone());

        return ! $now->greaterThan($deadline);
    }

    /**
     * MMSP elimination is read from the assessment and complaint rows.
     * A rejected complaint eliminates at once. An incomplete finding eliminates only after
     * the complaint window, and that window starts at the successful notice, not at the finding.
     */
    public static function isFinallyEliminated(Application $application, ?CarbonImmutable $at = null): bool
    {
        $application->loadMissing('competition');
        if ($application->competition?->type !== CompetitionProfileResolver::TYPE_CANONICAL) {
            return false;
        }

        $complaint = ApplicationComplaint::query()->where('application_id', $application->id)->first();
        if ($complaint) {
            return $complaint->decision === ApplicationComplaint::REJECTED;
        }

        $latest = ApplicationCompletenessAssessment::query()
            ->where('application_id', $application->id)
            ->latest('id')
            ->first();

        return $latest !== null
            && $latest->outcome === ApplicationCompletenessAssessment::INCOMPLETE
            && $latest->notice_sent_at !== null
            && ! self::complaintWindowOpen($application, $latest, $at);
    }

    /**
     * Ranking stays closed through review and the complaint procedure.
     * An incomplete finding whose notice was never sent is still in that procedure:
     * the complaint window has not started.
     */
    public static function blocksPreliminaryRanking(Application $application, ?CarbonImmutable $at = null): bool
    {
        $state = self::resolve($application, $at);
        if (in_array($state, [
            self::PENDING_REVIEW,
            self::COMPLAINT_ELIGIBLE,
            self::COMPLAINT_PENDING,
        ], true)) {
            return true;
        }

        if ($state !== self::INCOMPLETE) {
            return false;
        }

        $latest = ApplicationCompletenessAssessment::query()
            ->where('application_id', $application->id)
            ->latest('id')
            ->first();

        return $latest !== null
            && $latest->outcome === ApplicationCompletenessAssessment::INCOMPLETE
            && $latest->notice_sent_at === null;
    }

    /**
     * Status text for the commission list while the application is still in review or complaint.
     * Null means scoring is open and the list keeps its scoring status.
     *
     * @return array{label: string, eliminated: bool}|null
     */
    public static function commissionListPresentation(Application $application, ?CarbonImmutable $at = null): ?array
    {
        $state = self::resolve($application, $at);
        if ($state === null || $state === self::COMPLETE) {
            return null;
        }

        if ($state === self::COMPLAINT_RESOLVED) {
            $complaint = ApplicationComplaint::query()->where('application_id', $application->id)->first();
            if ($complaint?->decision === ApplicationComplaint::ACCEPTED) {
                return null;
            }

            return [
                'label' => ComplaintService::STATUS_LABELS[ComplaintService::STATUS_COMPLAINT_REJECTED],
                'eliminated' => true,
            ];
        }

        if ($state === self::INCOMPLETE) {
            $latest = ApplicationCompletenessAssessment::query()
                ->where('application_id', $application->id)
                ->latest('id')
                ->first();
            if ($latest?->notice_sent_at === null) {
                return [
                    'label' => ComplaintService::STATUS_LABELS[ComplaintService::STATUS_NOTICE_PENDING],
                    'eliminated' => false,
                ];
            }

            return [
                'label' => self::LABELS[self::INCOMPLETE],
                'eliminated' => true,
            ];
        }

        return [
            'label' => self::LABELS[$state],
            'eliminated' => false,
        ];
    }

    /**
     * @param  Builder<Application>  $query
     */
    public static function constrainFinallyEliminated(Builder $query, ?CarbonImmutable $at = null): void
    {
        $cutoff = self::complaintWindowCutoff($at);

        $query->whereHas('competition', function ($competition) {
            $competition->where('type', CompetitionProfileResolver::TYPE_CANONICAL);
        })->where(function ($outer) use ($cutoff) {
            $outer->whereHas('complaint', function ($complaint) {
                $complaint->where('decision', ApplicationComplaint::REJECTED);
            })->orWhere(function ($expired) use ($cutoff) {
                $expired->whereDoesntHave('complaint')
                    ->whereExists(function ($sub) use ($cutoff) {
                        self::expiredNoticeSubquery($sub, $cutoff);
                    });
            });
        });
    }

    /**
     * @param  Builder<Application>  $query
     */
    public static function excludeFinallyEliminated(Builder $query, ?CarbonImmutable $at = null): void
    {
        $cutoff = self::complaintWindowCutoff($at);

        $query->where(function ($outer) use ($cutoff) {
            $outer->whereDoesntHave('competition', function ($competition) {
                $competition->where('type', CompetitionProfileResolver::TYPE_CANONICAL);
            })->orWhere(function ($canonical) use ($cutoff) {
                $canonical->whereDoesntHave('complaint', function ($complaint) {
                    $complaint->where('decision', ApplicationComplaint::REJECTED);
                })->where(function ($stillOpen) use ($cutoff) {
                    $stillOpen->whereHas('complaint')
                        ->orWhereNotExists(function ($sub) use ($cutoff) {
                            self::expiredNoticeSubquery($sub, $cutoff);
                        });
                });
            });
        });
    }

    private static function complaintWindowCutoff(?CarbonImmutable $at): CarbonImmutable
    {
        $policy = new CanonicalMmspPolicy();
        $now = ($at ?? CarbonImmutable::now($policy->publicationTimezone()))
            ->timezone($policy->publicationTimezone());

        return $now->subDays((int) $policy->complaintWindowDays());
    }

    private static function expiredNoticeSubquery($sub, CarbonImmutable $cutoff): void
    {
        $sub->selectRaw('1')
            ->from('application_completeness_assessments as latest')
            ->whereColumn('latest.application_id', 'applications.id')
            ->where('latest.outcome', ApplicationCompletenessAssessment::INCOMPLETE)
            ->whereNotNull('latest.notice_sent_at')
            ->where('latest.notice_sent_at', '<', $cutoff)
            ->whereRaw('latest.id = (select max(id) from application_completeness_assessments where application_id = applications.id)');
    }
}
