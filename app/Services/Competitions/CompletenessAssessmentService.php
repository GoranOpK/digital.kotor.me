<?php

namespace App\Services\Competitions;

use App\Mail\IncompleteDocumentationNoticeMail;
use App\Models\Application;
use App\Models\ApplicationComplaint;
use App\Models\ApplicationCompletenessAssessment;
use App\Models\CommissionSession;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\User;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use App\Services\Competitions\Decision\CanonicalAdministrativeGate;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Canonical completeness / eliminatory review against frozen FINAL evidence.
 * In canonical V1 an INCOMPLETE outcome is the eliminatory finding; Women/Youth
 * Obrazac 3 criteria are not used.
 *
 * Only the current valid chair of the Poziv's canonical Commission records the assessment.
 * One Commission assessment per application; a later COMPLETE row is appended only by an
 * accepted complaint. The notice mail is dispatched after the transaction commits.
 */
class CompletenessAssessmentService
{
    public const REASON_REQUIRED_MESSAGE = 'Obrazloženje je obavezno kada je dokumentacija nepotpuna.';

    public const ALREADY_ASSESSED_MESSAGE = 'Potpunost ove prijave je već evidentirana. Ocjena se ne ponavlja; nalaz o nepotpunosti osporava se isključivo prigovorom.';

    public const STAGE_LOCKED_MESSAGE = 'Faza provjere potpunosti i prigovora je završena (bodovanje je počelo ili je formirana preliminarna rang lista). Nova evidencija nije moguća.';

    public function __construct(
        private readonly CanonicalReviewBasis $basis,
        private readonly CanonicalCommissionGovernance $governance,
    ) {
    }

    public function record(Application $application, User $actor, string $outcome, ?string $notes = null): ProcessWriteResult
    {
        if (! in_array($outcome, [ApplicationCompletenessAssessment::COMPLETE, ApplicationCompletenessAssessment::INCOMPLETE], true)) {
            return ProcessWriteResult::failed('Rezultat potpunosti nije dozvoljen.');
        }

        $notes = is_string($notes) ? trim($notes) : null;
        if ($outcome === ApplicationCompletenessAssessment::INCOMPLETE && ($notes === null || $notes === '')) {
            return ProcessWriteResult::failed(self::REASON_REQUIRED_MESSAGE);
        }

        $blockReason = $this->recordBlockReason($application, $actor);
        if ($blockReason !== null) {
            return ProcessWriteResult::failed($blockReason);
        }

        $competition = $application->competition;
        $policy = $competition->profile();

        try {
            $result = DB::transaction(function () use ($application, $actor, $outcome, $notes, $competition, $policy) {
                $locked = Application::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
                if (ApplicationCompletenessAssessment::query()->where('application_id', $locked->id)->exists()) {
                    return ProcessWriteResult::failed(self::ALREADY_ASSESSED_MESSAGE);
                }

                $locked->load(['businessPlan', 'finalClaim', 'user', 'competition']);
                $guard = $this->basis->dossierGuard($locked);
                $snapshot = $this->basis->snapshot($locked);

                $assessment = ApplicationCompletenessAssessment::query()->create([
                    'application_id' => $locked->id,
                    'competition_id' => $competition->id,
                    'outcome' => $outcome,
                    'catalog_key' => $policy->documentCatalogHook(),
                    'recorded_by_user_id' => $actor->id,
                    'notes' => $notes,
                    'evidence_snapshot' => $snapshot,
                    'evidence_fingerprint' => $this->basis->fingerprint($snapshot),
                    'recorded_at' => now(),
                    'notice_sent_at' => null,
                ]);

                $locked->refresh();
                if ($this->basis->dossierGuard($locked) !== $guard) {
                    throw new \RuntimeException('Provjera potpunosti ne smije mijenjati podneseni dosije.');
                }

                return ProcessWriteResult::ok($assessment->id);
            });
        } catch (\RuntimeException $exception) {
            return ProcessWriteResult::failed($exception->getMessage());
        }

        if ($result->succeeded) {
            $this->releaseAdministrativeReview($competition, $actor);
        }

        return $result;
    }

    /**
     * When every finally submitted application has an assessment, record the first-session
     * date once and queue each unsent incomplete notice. The complaint clock starts only
     * after that notice is actually delivered. Later steps do not move the session date.
     *
     * @return list<int> assessment ids queued for delivery in this call
     */
    public function releaseAdministrativeReview(Competition $competition, User $actor): array
    {
        $pendingIds = DB::transaction(function () use ($competition, $actor): array {
            $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->first();
            if ($locked === null || ! $this->governance->isCanonicalCompetition($locked)) {
                return [];
            }

            $applicationIds = Application::query()
                ->where('competition_id', $locked->id)
                ->where('status', '!=', 'draft')
                ->whereHas('finalClaim')
                ->lockForUpdate()
                ->pluck('id');
            if ($applicationIds->isEmpty()) {
                return [];
            }

            $latestIds = ApplicationCompletenessAssessment::query()
                ->selectRaw('max(id) as id')
                ->whereIn('application_id', $applicationIds)
                ->groupBy('application_id')
                ->pluck('id');
            if ($latestIds->count() !== $applicationIds->count()) {
                return [];
            }

            $this->recordFirstSessionDateOnce($locked, $actor);

            return ApplicationCompletenessAssessment::query()
                ->whereIn('id', $latestIds)
                ->where('outcome', ApplicationCompletenessAssessment::INCOMPLETE)
                ->whereNull('mail_sent_at')
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        });

        DB::afterCommit(function () use ($pendingIds): void {
            foreach ($pendingIds as $assessmentId) {
                $assessment = ApplicationCompletenessAssessment::query()->find($assessmentId);
                if ($assessment !== null) {
                    $this->deliverNotice($assessment);
                }
            }
        });

        return $pendingIds;
    }

    private function recordFirstSessionDateOnce(Competition $competition, User $actor): void
    {
        $exists = CommissionSession::query()
            ->where('competition_id', $competition->id)
            ->where('session_type', CommissionSession::TYPE_FIRST)
            ->exists();
        if ($exists || $competition->commission_id === null) {
            return;
        }

        CommissionSession::query()->create([
            'competition_id' => $competition->id,
            'commission_id' => $competition->commission_id,
            'session_type' => CommissionSession::TYPE_FIRST,
            'canonical_purpose' => CanonicalCommissionGovernance::PURPOSE_ORDINARY,
            'held_at' => now(),
            'recorded_by_user_id' => $actor->id,
            'notes' => 'Datum prve sjednice evidentiran završetkom administrativne provjere svih podnesenih prijava.',
        ]);
    }

    public function recordBlockReason(Application $application, User $actor): ?string
    {
        $application->loadMissing(['competition', 'user', 'finalClaim']);
        $competition = $application->competition;
        $policy = $competition?->profile();

        if (! $competition
            || $competition->type !== CompetitionProfileResolver::TYPE_CANONICAL
            || ! $policy
            || ! $policy->completenessRuntimeActive()) {
            return 'Provjera potpunosti nije aktivna za ovaj profil.';
        }

        if (CanonicalAdministrativeGate::isClosed($competition)) {
            return CanonicalAdministrativeGate::CLOSED_MESSAGE;
        }

        $authority = $this->governance->completenessRecordBlockReason($actor, $application);
        if ($authority !== null) {
            return $authority;
        }

        if ($this->stageLocked($application)) {
            return self::STAGE_LOCKED_MESSAGE;
        }

        $phase = $this->governance->administrativePhaseBlockReason($competition);
        if ($phase !== null) {
            return $phase;
        }

        if (! $application->finalClaim || $application->status === 'draft') {
            return 'Potpunost se utvrđuje samo za uspješno podnesenu prijavu.';
        }

        if (ApplicationComplaint::query()->where('application_id', $application->id)->exists()) {
            return 'Po prigovoru odlučuje Komisija; nova ocjena potpunosti nije moguća.';
        }

        if (ApplicationCompletenessAssessment::query()->where('application_id', $application->id)->exists()) {
            return self::ALREADY_ASSESSED_MESSAGE;
        }

        return null;
    }

    /**
     * Frozen review basis, append-only history and complaint for one application.
     * A null viewer is the read-only administrator view.
     *
     * @return array<string, mixed>
     */
    public function reviewOverview(Application $application, ?User $viewer, ComplaintService $complaints): array
    {
        $application->loadMissing(['competition', 'finalClaim', 'businessPlan']);
        $policy = $application->competition->profile();
        $history = ApplicationCompletenessAssessment::query()
            ->where('application_id', $application->id)
            ->with('recordedBy:id,name')
            ->orderBy('id')
            ->get();
        $first = $history->first();
        $liveFingerprint = $this->basis->fingerprint($this->basis->snapshot($application));
        $complaint = ApplicationComplaint::query()
            ->where('application_id', $application->id)
            ->with('decidedBy:id,name')
            ->first();
        $incomplete = $history->where('outcome', ApplicationCompletenessAssessment::INCOMPLETE)->last();
        $decisionDueAt = $complaint ? $policy->complaintDecisionDueAt($complaint->submitted_at) : null;

        return [
            'application' => $application,
            'state' => CanonicalReviewState::resolve($application),
            'snapshot' => $first?->evidence_snapshot ?? $this->basis->snapshot($application),
            'fingerprint' => $first?->evidence_fingerprint ?? $liveFingerprint,
            'basisMatches' => $first === null || hash_equals((string) $first->evidence_fingerprint, $liveFingerprint),
            'history' => $history,
            'complaint' => $complaint,
            'complaintDeadline' => $incomplete?->notice_sent_at ? $policy->complaintWindowEndsAt($incomplete->notice_sent_at) : null,
            'decisionDueAt' => $decisionDueAt,
            'decisionOverdueNow' => $complaint !== null && $complaint->decision === null && $decisionDueAt !== null
                && now()->greaterThan($decisionDueAt),
            'recordBlock' => $viewer ? $this->recordBlockReason($application, $viewer) : 'Pregled za administratora: samo čitanje.',
            'decideBlock' => $viewer && $complaint ? $complaints->decisionBlockReason($complaint, $viewer) : null,
            'stageLocked' => $this->stageLocked($application),
            'closed' => CanonicalAdministrativeGate::isClosed($application->competition),
            'timezone' => $policy->publicationTimezone(),
        ];
    }

    /**
     * FINAL applications of a canonical Poziv with their review state (no Commission-internal data).
     *
     * @return Collection<int, array{application: Application, state: ?string}>
     */
    public function competitionReview(Competition $competition): Collection
    {
        return Application::query()
            ->where('competition_id', $competition->id)
            ->whereHas('finalClaim')
            ->where('status', '!=', 'draft')
            ->with(['competition', 'finalClaim'])
            ->orderBy('redni_broj')
            ->orderBy('id')
            ->get()
            ->map(fn (Application $application) => [
                'application' => $application,
                'state' => CanonicalReviewState::resolve($application),
            ]);
    }

    /**
     * Completeness may no longer be recorded once this application has scores or the
     * preliminary ranking is formed (outcome, Predlog and formal Odluka all follow it).
     */
    public function stageLocked(Application $application): bool
    {
        return $this->governance->canonicalRankingFormed($application->competition)
            || EvaluationScore::query()->where('application_id', $application->id)->exists();
    }

    /**
     * True while the frozen FINAL evidence still matches what this assessment was recorded against.
     */
    public function reviewBasisMatches(ApplicationCompletenessAssessment $assessment): bool
    {
        $application = Application::query()->with(['businessPlan', 'finalClaim'])->findOrFail($assessment->application_id);

        return hash_equals(
            (string) $assessment->evidence_fingerprint,
            $this->basis->fingerprint($this->basis->snapshot($application))
        );
    }

    /**
     * Email one incompleteness notice after the assessment transaction has committed.
     * notice_sent_at is the successful send time and the start of the complaint window.
     * A failure leaves that clock unset. A later call retries once; a sent notice is not sent again.
     */
    public function deliverNotice(ApplicationCompletenessAssessment $assessment): void
    {
        $lockName = 'mmsp-notice-'.$assessment->id;
        $locked = DB::selectOne('SELECT GET_LOCK(?, 10) AS locked', [$lockName]);
        if ((int) ($locked->locked ?? 0) !== 1) {
            return;
        }

        try {
            $this->deliverNoticeWhileLocked($assessment);
        } finally {
            DB::select('SELECT RELEASE_LOCK(?)', [$lockName]);
        }
    }

    private function deliverNoticeWhileLocked(ApplicationCompletenessAssessment $assessment): void
    {
        $assessment = ApplicationCompletenessAssessment::query()->find($assessment->id);
        if ($assessment === null
            || $assessment->outcome !== ApplicationCompletenessAssessment::INCOMPLETE
            || $assessment->mail_sent_at !== null) {
            return;
        }

        $application = Application::query()->with(['user', 'competition'])->find($assessment->application_id);
        $recipient = $application?->user?->email;
        if (! is_string($recipient) || trim($recipient) === '') {
            $this->markNoticeFailed($assessment);

            return;
        }

        $policy = $application->competition->profile();
        $sentAt = now();
        $legalStart = $assessment->notice_sent_at ?? $sentAt;

        try {
            $snapshot = $assessment->evidence_snapshot ?: $this->basis->snapshot($application);
            $p1 = (array) ($snapshot['canonical_p1'] ?? []);
            $identity = (array) ($snapshot['application'] ?? []);
            $applicationNumber = (string) ($application->competition?->competition_number ?? $application->competition?->upNumber?->number ?? '—')
                .'/'.(string) ($identity['redni_broj'] ?? $application->redni_broj ?? '—');

            Mail::to($recipient)->send(new IncompleteDocumentationNoticeMail(
                $application,
                (string) ($application->user?->name ?? 'podnosioče'),
                $this->frozenApplicantName($application, $snapshot),
                (string) ($application->competition?->title ?? '—'),
                $applicationNumber,
                (string) ($p1['subsidy_type'] ?? '—'),
                $policy->complaintWindowDays() ?? 0,
                'Komisija za sprovođenje javnog konkursa za podršku preduzetnicima i mikro, malim i srednjim preduzećima',
                route('applications.canonical-review.show', $application),
                $policy->complaintWindowEndsAt($legalStart),
                (string) $assessment->notes,
            ));
        } catch (\Throwable $exception) {
            Log::error('Canonical incomplete-documentation notice could not be sent.', [
                'application_id' => $application->id,
                'assessment_id' => $assessment->id,
                'error' => $exception->getMessage(),
            ]);
            $this->markNoticeFailed($assessment);

            return;
        }

        $fresh = ApplicationCompletenessAssessment::query()->find($assessment->id);
        if ($fresh === null || $fresh->mail_sent_at !== null) {
            return;
        }

        $payload = ['mail_sent_at' => $sentAt, 'mail_failed_at' => null];
        if ($fresh->notice_sent_at === null) {
            $payload['notice_sent_at'] = $sentAt;
        }
        $fresh->forceFill($payload)->save();
    }

    private function markNoticeFailed(ApplicationCompletenessAssessment $assessment): void
    {
        $fresh = ApplicationCompletenessAssessment::query()->find($assessment->id);
        if ($fresh === null || $fresh->mail_sent_at !== null) {
            return;
        }

        $fresh->forceFill(['mail_failed_at' => now()])->save();
    }

    private function frozenApplicantName(Application $application, ?array $snapshot = null): string
    {
        $snapshot ??= $this->basis->snapshot($application);
        $p1 = (array) ($snapshot['canonical_p1'] ?? []);

        foreach ([
            $p1['company_name'] ?? null,
            $p1['person_name'] ?? null,
            $application->founder_name,
            $application->physical_person_name,
            $application->director_name,
            $application->user?->name,
        ] as $name) {
            if (is_string($name) && trim($name) !== '') {
                return trim($name);
            }
        }

        return 'Podnosilac';
    }
}
