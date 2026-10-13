<?php

namespace App\Services\Competitions;

use App\Mail\CanonicalComplaintDecisionMail;
use App\Models\Application;
use App\Models\ApplicationComplaint;
use App\Models\ApplicationCompletenessAssessment;
use App\Models\User;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use App\Services\Competitions\Decision\CanonicalAdministrativeGate;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Canonical complaint lifecycle: 3 calendar days to submit, 7 calendar days for the
 * Commission decision. A late decision is recorded with its actual timestamp and
 * compliance evidence; history is never rewritten.
 */
class ComplaintService
{
    public const LATE_DECISION_WARNING = 'Odluka je evidentirana nakon roka od 7 dana. Stvarni datum odluke je sačuvan.';

    public const DUPLICATE_MESSAGE = 'Prigovor za ovu prijavu je već evidentiran.';

    public const WINDOW_CLOSED_MESSAGE = 'Rok za prigovor je istekao.';

    public const ALREADY_DECIDED_MESSAGE = 'Odluka o ovom prigovoru je već evidentirana.';

    public const STATUS_NOT_FINAL = 'not_final';

    public const STATUS_AWAITING_REVIEW = 'awaiting_review';

    public const STATUS_COMPLETE = 'complete';

    public const STATUS_INCOMPLETE = 'incomplete';

    public const STATUS_COMPLAINT_AVAILABLE = 'complaint_available';

    public const STATUS_COMPLAINT_SUBMITTED = 'complaint_submitted';

    public const STATUS_COMPLAINT_ACCEPTED = 'complaint_accepted';

    public const STATUS_COMPLAINT_REJECTED = 'complaint_rejected';

    public const STATUS_LABELS = [
        self::STATUS_NOT_FINAL => 'Prijava nije konačno podnesena.',
        self::STATUS_AWAITING_REVIEW => 'Prijava je podnesena i čeka provjeru potpunosti dokumentacije.',
        self::STATUS_COMPLETE => 'Dokumentacija je potpuna. Prijava nastavlja postupak u fazi bodovanja.',
        self::STATUS_INCOMPLETE => 'Dokumentacija je ocijenjena kao nepotpuna. Rok za prigovor je istekao.',
        self::STATUS_COMPLAINT_AVAILABLE => 'Dokumentacija je ocijenjena kao nepotpuna. Prigovor je moguć do isteka roka.',
        self::STATUS_COMPLAINT_SUBMITTED => 'Prigovor je podnesen i čeka odluku Komisije.',
        self::STATUS_COMPLAINT_ACCEPTED => 'Prigovor je prihvaćen. Prijava se smatra potpunom i nastavlja postupak u fazi bodovanja.',
        self::STATUS_COMPLAINT_REJECTED => 'Prigovor je odbijen. Nalaz o nepotpunosti ostaje na snazi; prijava ne učestvuje u daljem bodovanju.',
    ];

    public function __construct(
        private readonly CanonicalReviewBasis $basis,
        private readonly CanonicalCommissionGovernance $governance,
    ) {
    }

    public function submit(Application $application, User $actor, string $body): ProcessWriteResult
    {
        $application->loadMissing(['competition', 'finalClaim']);
        $competition = $application->competition;
        $policy = $competition?->profile();

        if (! $competition
            || $competition->type !== CompetitionProfileResolver::TYPE_CANONICAL
            || ! $policy
            || ! $policy->completenessRuntimeActive()) {
            return ProcessWriteResult::failed('Prigovor nije aktivan za ovaj profil.');
        }

        if (CanonicalAdministrativeGate::isClosed($competition)) {
            return ProcessWriteResult::failed(CanonicalAdministrativeGate::CLOSED_MESSAGE);
        }

        if ((int) $application->user_id !== (int) $actor->id) {
            return ProcessWriteResult::failed('Prigovor može podnijeti samo podnosilac prijave.');
        }

        if ($this->governance->canonicalRankingFormed($competition)) {
            return ProcessWriteResult::failed(CompletenessAssessmentService::STAGE_LOCKED_MESSAGE);
        }

        if (! $application->finalClaim) {
            return ProcessWriteResult::failed('Prigovor se odnosi samo na uspješno podnesenu prijavu.');
        }

        if (ApplicationComplaint::query()->where('application_id', $application->id)->exists()) {
            return ProcessWriteResult::failed(self::DUPLICATE_MESSAGE);
        }

        $assessment = ApplicationCompletenessAssessment::query()
            ->where('application_id', $application->id)
            ->latest('id')
            ->first();

        if (! $assessment
            || $assessment->outcome !== ApplicationCompletenessAssessment::INCOMPLETE
            || ! $assessment->notice_sent_at) {
            return ProcessWriteResult::failed('Prigovor je moguć samo na obavještenje o nepotpunosti.');
        }

        $submittedAt = CarbonImmutable::now($policy->publicationTimezone());
        if (! CanonicalReviewState::complaintWindowOpen($application, $assessment, $submittedAt)) {
            return ProcessWriteResult::failed(self::WINDOW_CLOSED_MESSAGE);
        }

        $due = $policy->complaintDecisionDueAt($submittedAt);
        if (! $due) {
            return ProcessWriteResult::failed('Profil ne definiše rok za odluku o prigovoru.');
        }

        try {
            return DB::transaction(function () use ($application, $actor, $body, $competition, $assessment, $due, $submittedAt) {
                $locked = Application::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
                $guard = $this->basis->dossierGuard($locked);

                $complaint = ApplicationComplaint::query()->create([
                    'application_id' => $locked->id,
                    'competition_id' => $competition->id,
                    'completeness_assessment_id' => $assessment->id,
                    'submitted_by_user_id' => $actor->id,
                    'body' => $body,
                    'submitted_at' => $submittedAt,
                    'decision_due_at' => $due,
                ]);

                $locked->refresh();
                if ($this->basis->dossierGuard($locked) !== $guard) {
                    throw new \RuntimeException('Prigovor ne smije mijenjati podneseni dosije.');
                }

                return ProcessWriteResult::ok($complaint->id);
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return ProcessWriteResult::failed(self::DUPLICATE_MESSAGE);
            }

            throw $exception;
        } catch (\RuntimeException $exception) {
            return ProcessWriteResult::failed($exception->getMessage());
        }
    }

    public function decide(ApplicationComplaint $complaint, User $actor, string $decision, ?string $notes = null): ProcessWriteResult
    {
        if (! in_array($decision, [ApplicationComplaint::ACCEPTED, ApplicationComplaint::REJECTED], true)) {
            return ProcessWriteResult::failed('Odluka o prigovoru nije dozvoljena.');
        }

        $blockReason = $this->decisionBlockReason($complaint, $actor);
        if ($blockReason !== null) {
            return ProcessWriteResult::failed($blockReason);
        }

        $application = $complaint->application;
        $competition = $application->competition;
        $policy = $competition->profile();
        $notes = is_string($notes) && trim($notes) !== '' ? trim($notes) : null;

        try {
            return DB::transaction(function () use ($complaint, $actor, $decision, $notes, $application, $competition, $policy) {
                $lockedComplaint = ApplicationComplaint::query()->whereKey($complaint->id)->lockForUpdate()->firstOrFail();
                if ($lockedComplaint->decision !== null) {
                    return ProcessWriteResult::failed(self::ALREADY_DECIDED_MESSAGE);
                }

                $locked = Application::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
                $guard = $this->basis->dossierGuard($locked);

                $now = CarbonImmutable::now($policy->publicationTimezone());
                $due = $policy->complaintDecisionDueAt($lockedComplaint->submitted_at)
                    ?? CarbonImmutable::parse($lockedComplaint->decision_due_at);
                $late = $now->greaterThan($due);
                $warning = $late ? self::LATE_DECISION_WARNING : null;

                $lockedComplaint->update([
                    'decision' => $decision,
                    'decided_at' => $now,
                    'decided_by_user_id' => $actor->id,
                    'decision_notes' => $notes,
                    'decision_late' => $late,
                    'compliance_warning' => $warning,
                ]);

                if ($decision === ApplicationComplaint::ACCEPTED) {
                    $snapshot = $this->basis->snapshot($locked);
                    ApplicationCompletenessAssessment::query()->create([
                        'application_id' => $locked->id,
                        'competition_id' => $competition->id,
                        'outcome' => ApplicationCompletenessAssessment::COMPLETE,
                        'catalog_key' => $policy->documentCatalogHook(),
                        'recorded_by_user_id' => $actor->id,
                        'notes' => 'Prigovor prihvaćen. Podneseni dosije nije mijenjan.',
                        'evidence_snapshot' => $snapshot,
                        'evidence_fingerprint' => $this->basis->fingerprint($snapshot),
                        'recorded_at' => $now,
                    ]);
                }

                $locked->refresh();
                if ($this->basis->dossierGuard($locked) !== $guard) {
                    throw new \RuntimeException('Odluka o prigovoru ne smije mijenjati podneseni dosije.');
                }

                DB::afterCommit(fn () => $this->deliverDecision($lockedComplaint));

                return ProcessWriteResult::ok($lockedComplaint->id, $warning);
            });
        } catch (\RuntimeException $exception) {
            return ProcessWriteResult::failed($exception->getMessage());
        }
    }

    public function decisionBlockReason(ApplicationComplaint $complaint, User $actor): ?string
    {
        $complaint->loadMissing('application.competition', 'application.finalClaim');
        $application = $complaint->application;
        $competition = $application?->competition;
        $policy = $competition?->profile();

        if (! $application
            || ! $competition
            || $competition->type !== CompetitionProfileResolver::TYPE_CANONICAL
            || ! $policy
            || ! $policy->completenessRuntimeActive()) {
            return 'Odluka o prigovoru nije aktivna za ovaj profil.';
        }

        if (CanonicalAdministrativeGate::isClosed($competition)) {
            return CanonicalAdministrativeGate::CLOSED_MESSAGE;
        }

        $authority = $this->governance->complaintDecisionBlockReason($actor, $application);
        if ($authority !== null) {
            return $authority;
        }

        if ($this->governance->canonicalRankingFormed($competition)) {
            return CompletenessAssessmentService::STAGE_LOCKED_MESSAGE;
        }

        if (ApplicationComplaint::query()->whereKey($complaint->id)->value('decision') !== null) {
            return self::ALREADY_DECIDED_MESSAGE;
        }

        return null;
    }

    /**
     * Applicant-facing review status: derived from structured rows only and limited to what
     * the applicant may see (own finding, own complaint, decision outcome, deadlines).
     *
     * @return array<string, mixed>|null
     */
    public function applicantStatus(Application $application, ?CarbonImmutable $at = null): ?array
    {
        $application->loadMissing(['competition', 'finalClaim']);
        $competition = $application->competition;
        $state = CanonicalReviewState::resolve($application, $at);
        if ($state === null) {
            return null;
        }

        $policy = $competition->profile();
        $complaint = ApplicationComplaint::query()->where('application_id', $application->id)->first();
        $incomplete = ApplicationCompletenessAssessment::query()
            ->where('application_id', $application->id)
            ->where('outcome', ApplicationCompletenessAssessment::INCOMPLETE)
            ->latest('id')
            ->first();

        $status = match ($state) {
            CanonicalReviewState::NOT_FINAL => self::STATUS_NOT_FINAL,
            CanonicalReviewState::PENDING_REVIEW => self::STATUS_AWAITING_REVIEW,
            CanonicalReviewState::COMPLETE => self::STATUS_COMPLETE,
            CanonicalReviewState::COMPLAINT_ELIGIBLE => self::STATUS_COMPLAINT_AVAILABLE,
            CanonicalReviewState::INCOMPLETE => self::STATUS_INCOMPLETE,
            CanonicalReviewState::COMPLAINT_PENDING => self::STATUS_COMPLAINT_SUBMITTED,
            CanonicalReviewState::COMPLAINT_RESOLVED => $complaint?->decision === ApplicationComplaint::ACCEPTED
                ? self::STATUS_COMPLAINT_ACCEPTED
                : self::STATUS_COMPLAINT_REJECTED,
        };

        $closed = CanonicalAdministrativeGate::isClosed($competition);
        $rankingFormed = $this->governance->canonicalRankingFormed($competition);
        $complaintOpen = $status === self::STATUS_COMPLAINT_AVAILABLE && ! $closed && ! $rankingFormed;

        return [
            'status' => $status,
            'label' => self::STATUS_LABELS[$status],
            'incompleteReason' => $incomplete?->notes,
            'noticeSentAt' => $incomplete?->notice_sent_at,
            'complaintDeadline' => $incomplete?->notice_sent_at ? $policy->complaintWindowEndsAt($incomplete->notice_sent_at) : null,
            'complaintOpen' => $complaintOpen,
            'complaintSubmittedAt' => $complaint?->submitted_at,
            'complaintDecision' => $complaint?->decision,
            'complaintDecidedAt' => $complaint?->decided_at,
            'proceedsToScoring' => in_array($status, [self::STATUS_COMPLETE, self::STATUS_COMPLAINT_ACCEPTED], true),
            'rankingFormed' => $rankingFormed,
            'closed' => $closed,
            'timezone' => $policy->publicationTimezone(),
        ];
    }

    /**
     * Email the recorded complaint decision. Runs after the decision committed and may be invoked
     * again after a failure; decided_at stays the legal event.
     */
    public function deliverDecision(ApplicationComplaint $complaint): void
    {
        $complaint = ApplicationComplaint::query()->find($complaint->id);
        if ($complaint === null || $complaint->decision === null || $complaint->mail_sent_at !== null) {
            return;
        }

        $application = Application::query()->with(['user', 'competition'])->find($complaint->application_id);
        $recipient = $application?->user?->email;
        if (! is_string($recipient) || trim($recipient) === '') {
            $complaint->forceFill(['mail_failed_at' => now()])->save();

            return;
        }

        try {
            Mail::to($recipient)->send(new CanonicalComplaintDecisionMail(
                (string) ($application->competition?->title ?? ''),
                $application->redni_broj ? 'redni broj '.$application->redni_broj : 'prijava #'.$application->id,
                $complaint->decision,
                CarbonImmutable::parse($complaint->decided_at)->setTimezone($application->competition->profile()->publicationTimezone()),
                route('applications.canonical-review.show', $application),
            ));
        } catch (\Throwable $exception) {
            Log::error('Canonical complaint decision notice could not be sent.', [
                'application_id' => $application->id,
                'complaint_id' => $complaint->id,
                'error' => $exception->getMessage(),
            ]);
            $complaint->forceFill(['mail_failed_at' => now()])->save();

            return;
        }

        $complaint->forceFill(['mail_sent_at' => now(), 'mail_failed_at' => null])->save();
    }
}
