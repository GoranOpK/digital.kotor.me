<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\ApplicationComplaint;
use App\Models\ApplicationCompletenessAssessment;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Carbon\CarbonImmutable;

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
}
