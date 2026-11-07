<?php

namespace Tests\Feature\Kn;

use App\Mail\CanonicalComplaintDecisionMail;
use App\Mail\IncompleteDocumentationNoticeMail;
use App\Models\Application;
use App\Models\ApplicationComplaint;
use App\Models\ApplicationCompletenessAssessment;
use App\Models\ApplicationDocument;
use App\Models\ApplicationEliminatoryNotice;
use App\Models\ApplicationPrigovor;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\FinalApplicationClaim;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\CanonicalReviewState;
use App\Services\Competitions\ComplaintService;
use App\Services\Competitions\CompletenessAssessmentService;
use App\Services\Competitions\Profile\CompetitionProfilePolicy;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CompetitionProgramCatalog;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Testing\Fakes\MailFake;
use Tests\Feature\Kn\Concerns\BuildsCanonicalCommission;
use Tests\TestCase;

/**
 * KN-SYNC-005 — canonical completeness + complaint (3/7 day rules).
 */
class CompletenessComplaintTest extends TestCase
{
    use BuildsCanonicalCommission;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_draft_cannot_enter_completeness_review(): void
    {
        $owner = $this->user('korisnik', 'draft@example.test');
        $chair = $this->user('komisija', 'draft-chair@example.test');
        $application = $this->draftApplication($owner, $chair);

        $this->actingAs($chair)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.completeness', $application), ['outcome' => 'complete'])
            ->assertSessionHasErrors('error');

        $this->assertSame(0, ApplicationCompletenessAssessment::query()->count());
    }

    public function test_canonical_final_enters_completeness_and_complete_persists(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:00', 'Europe/Belgrade'));
        $owner = $this->user('korisnik', 'complete@example.test', 'Zivo Ime');
        $chair = $this->user('komisija', 'complete-chair@example.test');
        $application = $this->finalApplication($owner, $chair, [
            'founder_name' => 'Zamrznuto Ime',
            'business_plan_name' => 'Zamrznuti plan',
        ]);

        $this->actingAs($chair)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.completeness', $application), [
                'outcome' => 'complete',
                'notes' => 'Dosije je potpun.',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('application_completeness_assessments', [
            'application_id' => $application->id,
            'outcome' => 'complete',
            'catalog_key' => 'canonical_mmsp_document_catalog',
            'notes' => 'Dosije je potpun.',
        ]);
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertNotNull($application->fresh()->finalClaim);
    }

    public function test_incomplete_persists_notice_uses_frozen_facts_not_live_name(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:00', 'Europe/Belgrade'));
        $owner = $this->user('korisnik', 'live-name@example.test', 'Zivo Ime');
        $chair = $this->user('komisija', 'chair@example.test');
        $application = $this->finalApplication($owner, $chair, [
            'founder_name' => 'Zamrznuto Ime',
            'business_plan_name' => 'Zamrznuti plan',
        ]);
        $document = ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'Prilog',
            'file_path' => 'frozen/prilog.pdf',
            'document_type' => 'ostalo',
        ]);

        $this->actingAs($chair)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.completeness', $application), [
                'outcome' => 'incomplete',
                'notes' => 'Nedostaje prilog u podnesenom dosijeu.',
            ])
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame('submitted', $application->status);
        $this->assertNull($application->rejection_reason);
        $this->assertSame('Zamrznuto Ime', $application->founder_name);
        $this->assertSame('frozen/prilog.pdf', $document->fresh()->file_path);
        $this->assertNotNull($application->finalClaim);
        $this->assertDatabaseHas('application_completeness_assessments', [
            'application_id' => $application->id,
            'outcome' => 'incomplete',
            'catalog_key' => 'canonical_mmsp_document_catalog',
        ]);

        Mail::assertSent(IncompleteDocumentationNoticeMail::class, function (IncompleteDocumentationNoticeMail $mail) use ($owner) {
            return $mail->hasTo($owner->email)
                && $mail->frozenApplicantName === 'Zamrznuto Ime'
                && $mail->complaintWindowDays === 3
                && $mail->catalogKey === 'canonical_mmsp_document_catalog'
                && ! str_contains($mail->frozenApplicantName, 'Zivo Ime');
        });
    }

    public function test_complaint_within_window_and_last_valid_day_accepted(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-01 10:00:00', 'Europe/Belgrade'));
        $owner = $this->user('korisnik', 'window@example.test');
        $chair = $this->user('komisija', 'window-chair@example.test');
        $application = $this->finalApplication($owner, $chair);
        $this->recordIncomplete($chair, $application);

        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00', 'Europe/Belgrade'));
        $this->actingAs($owner)
            ->from(route('applications.show', $application))
            ->post(route('applications.complaint', $application), ['body' => 'Unutar roka.'])
            ->assertSessionHas('success');

        $complaint = ApplicationComplaint::query()->where('application_id', $application->id)->firstOrFail();
        $submittedAt = $complaint->submitted_at->copy();
        $this->assertNull($complaint->decision);
        $this->assertSame(
            '2026-09-09 10:00:00',
            $complaint->decision_due_at->timezone('Europe/Belgrade')->format('Y-m-d H:i:s')
        );

        // Idempotent timestamp: later mutation of body must not exist — unique one complaint.
        $this->actingAs($owner)
            ->from(route('applications.show', $application))
            ->post(route('applications.complaint', $application), ['body' => 'Duplikat.'])
            ->assertSessionHasErrors('error');
        $this->assertSame(1, ApplicationComplaint::query()->count());
        $this->assertSame(
            $submittedAt->timezone('Europe/Belgrade')->format('Y-m-d H:i:s'),
            $complaint->fresh()->submitted_at->timezone('Europe/Belgrade')->format('Y-m-d H:i:s')
        );
    }

    public function test_complaint_window_is_exact_notice_time_plus_three_days_inclusive(): void
    {
        $noticeAt = '2026-09-01 10:00:00';

        [$ownerA, $appA] = $this->incompleteAt($noticeAt, 'exact-a');
        $this->assertSame(
            '2026-09-04 10:00:00.000000',
            $this->windowEnd($appA)->format('Y-m-d H:i:s.u')
        );
        $this->complaintAt('2026-09-04 09:59:59', $ownerA, $appA)->assertSessionHas('success');

        [$ownerB, $appB] = $this->incompleteAt($noticeAt, 'exact-b');
        $this->complaintAt('2026-09-04 10:00:00', $ownerB, $appB)->assertSessionHas('success');

        [$ownerC, $appC] = $this->incompleteAt($noticeAt, 'exact-c');
        $this->complaintAt('2026-09-04 10:00:00.000001', $ownerC, $appC)->assertSessionHasErrors('error');

        [$ownerD, $appD] = $this->incompleteAt($noticeAt, 'exact-d');
        $this->complaintAt('2026-09-04 10:00:01', $ownerD, $appD)->assertSessionHasErrors('error');

        $this->assertSame(1, ApplicationComplaint::query()->where('application_id', $appA->id)->count());
        $this->assertSame(1, ApplicationComplaint::query()->where('application_id', $appB->id)->count());
        $this->assertSame(0, ApplicationComplaint::query()->where('application_id', $appC->id)->count());
        $this->assertSame(0, ApplicationComplaint::query()->where('application_id', $appD->id)->count());
    }

    public function test_complaint_window_near_midnight_is_exact_time_in_belgrade(): void
    {
        [$lateOwner, $late] = $this->incompleteAt('2026-09-01 23:59:30', 'midnight-late');
        $this->assertSame(
            '2026-09-04 23:59:30',
            $this->windowEnd($late)->format('Y-m-d H:i:s')
        );
        $this->complaintAt('2026-09-04 23:59:30', $lateOwner, $late)->assertSessionHas('success');

        [$lateOwner2, $late2] = $this->incompleteAt('2026-09-01 23:59:30', 'midnight-late-2');
        $this->complaintAt('2026-09-04 23:59:31', $lateOwner2, $late2)->assertSessionHasErrors('error');

        [$earlyOwner, $early] = $this->incompleteAt('2026-09-02 00:00:05', 'midnight-early');
        $this->assertSame(
            '2026-09-05 00:00:05',
            $this->windowEnd($early)->format('Y-m-d H:i:s')
        );
        $this->complaintAt('2026-09-05 00:00:05', $earlyOwner, $early)->assertSessionHas('success');
    }

    public function test_complaint_and_decision_deadlines_are_dst_safe(): void
    {
        $policy = CompetitionProfileResolver::resolve(CompetitionProfileResolver::TYPE_CANONICAL);

        // Europe/Belgrade leaves DST on 2026-10-25 (CEST → CET); the wall-clock time is kept.
        $window = $policy->complaintWindowEndsAt(Carbon::parse('2026-10-23 10:00:00', 'Europe/Belgrade'));
        $this->assertSame('2026-10-26 10:00:00', $window->format('Y-m-d H:i:s'));
        $this->assertSame('+01:00', $window->format('P'));

        $decision = $policy->complaintDecisionDueAt(Carbon::parse('2026-10-20 10:00:00', 'Europe/Belgrade'));
        $this->assertSame('2026-10-27 10:00:00', $decision->format('Y-m-d H:i:s'));
        $this->assertSame('+01:00', $decision->format('P'));

        // Europe/Belgrade enters DST on 2027-03-28 (CET → CEST).
        $spring = $policy->complaintWindowEndsAt(Carbon::parse('2027-03-26 10:00:00', 'Europe/Belgrade'));
        $this->assertSame('2027-03-29 10:00:00', $spring->format('Y-m-d H:i:s'));
        $this->assertSame('+02:00', $spring->format('P'));

        // A UTC start instant is converted to Europe/Belgrade first.
        $utc = $policy->complaintWindowEndsAt(Carbon::parse('2026-09-01 22:30:00', 'UTC'));
        $this->assertSame('2026-09-05 00:30:00', $utc->format('Y-m-d H:i:s'));
        $this->assertSame('Europe/Belgrade', $utc->getTimezone()->getName());

        // Same wall-clock result as the shared Women/Youth Obrazac 3 notice.
        $notice = new ApplicationEliminatoryNotice(['sent_at' => SupportCarbon::parse('2026-10-23 10:00:00', 'Europe/Belgrade')]);
        $this->assertSame($notice->prigovorDeadlineAt()->format('Y-m-d H:i:s P'), $window->format('Y-m-d H:i:s P'));

        [$owner, $application] = $this->incompleteAt('2026-10-23 10:00:00', 'dst');
        $this->complaintAt('2026-10-26 10:00:00', $owner, $application)->assertSessionHas('success');
        [$owner2, $application2] = $this->incompleteAt('2026-10-23 10:00:00', 'dst-2');
        $this->complaintAt('2026-10-26 10:00:01', $owner2, $application2)->assertSessionHasErrors('error');
    }

    public function test_decision_deadline_is_exact_submission_time_plus_seven_days_inclusive(): void
    {
        $cases = [
            'on-time-before' => ['2026-09-08 09:59:59', false, '2026-09-08 09:59:59'],
            'on-time-exact' => ['2026-09-08 10:00:00', false, '2026-09-08 10:00:00'],
            'late-next-instant' => ['2026-09-08 10:00:00.000001', true, '2026-09-08 10:00:00'],
            'late-next-second' => ['2026-09-08 10:00:01', true, '2026-09-08 10:00:01'],
        ];

        foreach ($cases as $key => [$decidedAt, $expectLate, $storedDecidedAt]) {
            [$owner, $application, $chair] = $this->incompleteAt('2026-08-31 10:00:00', 'decision-'.$key);
            $this->complaintAt('2026-09-01 10:00:00', $owner, $application)->assertSessionHas('success');
            $complaint = ApplicationComplaint::query()->where('application_id', $application->id)->firstOrFail();
            $this->assertSame(
                '2026-09-08 10:00:00',
                $complaint->decision_due_at->timezone('Europe/Belgrade')->format('Y-m-d H:i:s')
            );
            $assessment = ApplicationCompletenessAssessment::query()->where('application_id', $application->id)->firstOrFail();
            $originalSubmittedAt = DB::table('application_complaints')->where('id', $complaint->id)->value('submitted_at');
            $originalDueAt = DB::table('application_complaints')->where('id', $complaint->id)->value('decision_due_at');

            Carbon::setTestNow(Carbon::parse($decidedAt, 'Europe/Belgrade'));
            $response = $this->actingAs($chair)
                ->from(route('evaluation.create', $application))
                ->post(route('evaluation.complaint-decision', [$application, $complaint]), [
                    'decision' => 'rejected',
                    'decision_notes' => 'Odluka '.$key,
                ])
                ->assertSessionHas('success');

            $complaint->refresh();
            $this->assertSame($originalSubmittedAt, DB::table('application_complaints')->where('id', $complaint->id)->value('submitted_at'), $key.' original submission time changed');
            $this->assertSame($originalDueAt, DB::table('application_complaints')->where('id', $complaint->id)->value('decision_due_at'), $key.' original decision deadline changed');
            $this->assertSame('rejected', $complaint->decision, $key);
            $this->assertSame($expectLate, (bool) $complaint->decision_late, $key);
            $this->assertSame($storedDecidedAt, $complaint->decided_at->timezone('Europe/Belgrade')->format('Y-m-d H:i:s'), $key);

            if ($expectLate) {
                $response->assertSessionHas('warning', ComplaintService::LATE_DECISION_WARNING);
                $this->assertSame(ComplaintService::LATE_DECISION_WARNING, $complaint->compliance_warning);
            } else {
                $response->assertSessionMissing('warning');
                $this->assertNull($complaint->compliance_warning);
            }

            $this->assertTrue(app(CompletenessAssessmentService::class)->reviewBasisMatches($assessment->fresh()), $key);
            $this->assertSame('submitted', $application->fresh()->status, $key);
            $this->assertNotNull($application->fresh()->finalClaim, $key);
        }
    }

    public function test_women_youth_and_canonical_complaint_deadlines_share_exact_time(): void
    {
        $start = Carbon::parse('2026-09-01 10:00:00', 'Europe/Belgrade');

        $women = CompetitionProfileResolver::resolve(CompetitionProfileResolver::TYPE_WOMEN);
        $youth = CompetitionProfileResolver::resolve(CompetitionProfileResolver::TYPE_YOUTH);
        $canonical = CompetitionProfileResolver::resolve(CompetitionProfileResolver::TYPE_CANONICAL);

        $this->assertSame(CompetitionProfilePolicy::PROCESS_DEADLINE_EXACT_TIME, $women->processDeadlineSemantics());
        $this->assertSame(CompetitionProfilePolicy::PROCESS_DEADLINE_EXACT_TIME, $youth->processDeadlineSemantics());
        $this->assertSame(CompetitionProfilePolicy::PROCESS_DEADLINE_EXACT_TIME, $canonical->processDeadlineSemantics());

        $this->assertNull($women->complaintWindowEndsAt($start));
        $this->assertSame('2026-09-04 10:00:00', $youth->complaintWindowEndsAt($start)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-08 10:00:00', $youth->complaintDecisionDueAt($start)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-04 10:00:00', $canonical->complaintWindowEndsAt($start)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-08 10:00:00', $canonical->complaintDecisionDueAt($start)->format('Y-m-d H:i:s'));

        $this->assertSame(CompetitionProfilePolicy::PROCESS_DEADLINE_EXACT_TIME, $canonical->commissionGovernanceDeadlineSemantics());
        $this->assertSame(CompetitionProfilePolicy::PROCESS_DEADLINE_EXACT_TIME, $women->commissionGovernanceDeadlineSemantics());
        $this->assertSame(CompetitionProfilePolicy::PROCESS_DEADLINE_EXACT_TIME, $youth->commissionGovernanceDeadlineSemantics());

        $notice = new ApplicationEliminatoryNotice(['sent_at' => $start]);
        $this->assertSame('2026-09-04 10:00:00', $notice->prigovorDeadlineAt()->format('Y-m-d H:i:s'));
        $this->assertTrue($notice->prigovorWindowIsOpen(SupportCarbon::parse('2026-09-04 10:00:00', 'Europe/Belgrade')));
        $this->assertFalse($notice->prigovorWindowIsOpen(SupportCarbon::parse('2026-09-04 10:00:01', 'Europe/Belgrade')));

        $prigovor = new ApplicationPrigovor(['submitted_at' => $start]);
        $this->assertSame('2026-09-08 10:00:00', $prigovor->komisijaDeadlineAt()->format('Y-m-d H:i:s'));
        $this->assertFalse($prigovor->komisijaDeadlineHasPassed(SupportCarbon::parse('2026-09-08 10:00:00', 'Europe/Belgrade')));
        $this->assertTrue($prigovor->komisijaDeadlineHasPassed(SupportCarbon::parse('2026-09-08 10:00:01', 'Europe/Belgrade')));
    }

    /**
     * @return array{0: User, 1: Application, 2: User}
     */
    private function incompleteAt(string $noticeAt, string $key): array
    {
        Carbon::setTestNow(Carbon::parse($noticeAt, 'Europe/Belgrade'));
        $owner = $this->user('korisnik', $key.'@example.test');
        $chair = $this->user('komisija', $key.'-chair@example.test');
        $application = $this->finalApplication($owner, $chair);
        $this->recordIncomplete($chair, $application);

        return [$owner, $application->fresh(['competition', 'finalClaim']), $chair];
    }

    private function windowEnd(Application $application): \Carbon\CarbonImmutable
    {
        $noticeSentAt = ApplicationCompletenessAssessment::query()
            ->where('application_id', $application->id)
            ->latest('id')
            ->value('notice_sent_at');

        return $application->competition->profile()->complaintWindowEndsAt(Carbon::parse($noticeSentAt));
    }

    private function complaintAt(string $at, User $owner, Application $application): \Illuminate\Testing\TestResponse
    {
        Carbon::setTestNow(Carbon::parse($at, 'Europe/Belgrade'));

        return $this->actingAs($owner)
            ->from(route('applications.show', $application))
            ->post(route('applications.complaint', $application), ['body' => 'Prigovor '.$at]);
    }

    public function test_decision_within_and_exact_7_day_boundary_and_late_preserves_timestamp(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:00', 'Europe/Belgrade'));
        $owner = $this->user('korisnik', 'decide@example.test');
        $chair = $this->user('komisija', 'decide-chair@example.test');
        $application = $this->finalApplication($owner, $chair, ['founder_name' => 'Podnosilac']);
        $document = ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'Prilog',
            'file_path' => 'frozen/same.pdf',
            'document_type' => 'ostalo',
        ]);
        $this->recordIncomplete($chair, $application);

        Carbon::setTestNow(Carbon::parse('2026-09-11 10:00:00', 'Europe/Belgrade'));
        $this->actingAs($owner)
            ->post(route('applications.complaint', $application), ['body' => 'Dokument je bio u dosijeu.'])
            ->assertSessionHas('success');
        $complaint = ApplicationComplaint::query()->where('application_id', $application->id)->firstOrFail();

        // Exact 7-day boundary (submitted 11th 10:00 → due 18th 10:00)
        Carbon::setTestNow(Carbon::parse('2026-09-18 10:00:00', 'Europe/Belgrade'));
        $this->actingAs($chair)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.complaint-decision', [$application, $complaint]), [
                'decision' => 'accepted',
                'decision_notes' => 'Prilog je bio predan.',
            ])
            ->assertSessionHas('success');

        $complaint->refresh();
        $this->assertSame('accepted', $complaint->decision);
        $this->assertFalse((bool) $complaint->decision_late);
        $this->assertNull($complaint->compliance_warning);
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertSame('frozen/same.pdf', $document->fresh()->file_path);
        $this->assertSame('Podnosilac', $application->fresh()->founder_name);
        $this->assertSame(
            'complete',
            ApplicationCompletenessAssessment::query()->where('application_id', $application->id)->latest('id')->value('outcome')
        );

        // Late decision on second application — preserve actual timestamp + warning
        $ownerLate = $this->user('korisnik', 'late-decide@example.test');
        $appLate = $this->finalApplication($ownerLate, $chair);
        Carbon::setTestNow(Carbon::parse('2026-09-01 10:00:00', 'Europe/Belgrade'));
        $this->recordIncomplete($chair, $appLate);
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00', 'Europe/Belgrade'));
        $this->actingAs($ownerLate)
            ->post(route('applications.complaint', $appLate), ['body' => 'Prigovor.'])
            ->assertSessionHas('success');
        $lateComplaint = ApplicationComplaint::query()->where('application_id', $appLate->id)->firstOrFail();

        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:01', 'Europe/Belgrade'));
        $this->actingAs($chair)
            ->from(route('evaluation.create', $appLate))
            ->post(route('evaluation.complaint-decision', [$appLate, $lateComplaint]), [
                'decision' => 'rejected',
                'decision_notes' => 'Kasna odluka.',
            ])
            ->assertSessionHas('success')
            ->assertSessionHas('warning');

        $lateComplaint->refresh();
        $this->assertTrue((bool) $lateComplaint->decision_late);
        $this->assertSame(ComplaintService::LATE_DECISION_WARNING, $lateComplaint->compliance_warning);
        $this->assertSame(
            '2026-09-10 10:00:01',
            $lateComplaint->decided_at->timezone('Europe/Belgrade')->format('Y-m-d H:i:s')
        );
        $this->assertSame('submitted', $appLate->fresh()->status);
        $this->assertNotNull($appLate->fresh()->finalClaim);
    }

    public function test_women_and_youth_profiles_do_not_use_canonical_completeness_tables(): void
    {
        $owner = $this->user('korisnik', 'women@example.test');
        $chair = $this->user('komisija', 'women-chair@example.test');

        foreach ([CompetitionProfileResolver::TYPE_WOMEN, CompetitionProfileResolver::TYPE_YOUTH] as $type) {
            $application = $this->submittedWithoutClaim($owner, $chair, $type);
            $result = app(CompletenessAssessmentService::class)->record($application, $chair, 'incomplete', 'Razlog.');
            $this->assertFalse($result->succeeded);
            $this->assertNull(CanonicalReviewState::resolve($application));
            $complaint = app(ComplaintService::class)->submit($application, $owner, 'Prigovor.');
            $this->assertFalse($complaint->succeeded);
        }

        $this->assertSame(0, ApplicationCompletenessAssessment::query()->count());
        $this->assertSame(0, ApplicationComplaint::query()->count());
        $this->assertFalse(CompetitionProfileResolver::resolve(CompetitionProfileResolver::TYPE_WOMEN)->completenessRuntimeActive());
        $this->assertFalse(CompetitionProfileResolver::resolve(CompetitionProfileResolver::TYPE_YOUTH)->completenessRuntimeActive());
    }

    public function test_canonical_remains_development_and_policy_windows(): void
    {
        $canonical = CompetitionProfileResolver::resolve(CompetitionProfileResolver::TYPE_CANONICAL);
        $this->assertTrue($canonical->completenessRuntimeActive());
        $this->assertSame(3, $canonical->complaintWindowDays());
        $this->assertSame(7, $canonical->complaintDecisionWindowDays());
        $this->assertSame('canonical_mmsp_document_catalog', $canonical->documentCatalogHook());

        $entry = CompetitionProgramCatalog::definitions()[CompetitionProfileResolver::TYPE_CANONICAL];
        $this->assertSame(CompetitionProgramCatalog::STATUS_DEVELOPMENT, $entry['status']);
    }

    public function test_frozen_evidence_unchanged_after_completeness_and_complaint_cycle(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:00', 'Europe/Belgrade'));
        $owner = $this->user('korisnik', 'freeze@example.test', 'Live');
        $chair = $this->user('komisija', 'freeze-chair@example.test');
        $application = $this->finalApplication($owner, $chair, [
            'founder_name' => 'Frozen',
            'business_plan_name' => 'Frozen plan',
        ]);
        $document = ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'Dok',
            'file_path' => 'frozen/a.pdf',
            'document_type' => 'ostalo',
        ]);
        $claimId = $application->finalClaim->id;

        $this->recordIncomplete($chair, $application);
        $owner->update(['name' => 'Changed Live', 'email' => 'changed-routing@example.test']);

        Carbon::setTestNow(Carbon::parse('2026-09-11 10:00:00', 'Europe/Belgrade'));
        $this->actingAs($owner)->post(route('applications.complaint', $application), ['body' => 'OK'])->assertSessionHas('success');
        $complaint = ApplicationComplaint::query()->firstOrFail();

        Carbon::setTestNow(Carbon::parse('2026-09-12 10:00:00', 'Europe/Belgrade'));
        $this->actingAs($chair)
            ->post(route('evaluation.complaint-decision', [$application, $complaint]), ['decision' => 'rejected', 'decision_notes' => 'Obrazloženje.'])
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame('Frozen', $application->founder_name);
        $this->assertSame('Frozen plan', $application->business_plan_name);
        $this->assertSame('frozen/a.pdf', $document->fresh()->file_path);
        $this->assertSame($claimId, $application->finalClaim->id);
        $this->assertSame('submitted', $application->status);
        $this->assertSame('changed-routing@example.test', $owner->fresh()->email);
    }

    public function test_incomplete_requires_reason_and_reason_persists(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:00', 'Europe/Belgrade'));
        $owner = $this->user('korisnik', 'reason@example.test');
        $chair = $this->user('komisija', 'reason-chair@example.test');
        $application = $this->finalApplication($owner, $chair);

        $this->actingAs($chair)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.completeness', $application), ['outcome' => 'incomplete', 'notes' => '   '])
            ->assertSessionHasErrors('error');
        $this->assertSame(0, ApplicationCompletenessAssessment::query()->count());
        Mail::assertNothingSent();

        $this->recordIncomplete($chair, $application, 'Nedostaje dokaz o registraciji.');

        $assessment = ApplicationCompletenessAssessment::query()->firstOrFail();
        $this->assertSame('Nedostaje dokaz o registraciji.', $assessment->notes);
        $this->assertSame($chair->id, $assessment->recorded_by_user_id);
        $this->assertNotNull($assessment->recorded_at);
        $this->assertNotNull($assessment->notice_sent_at);
    }

    public function test_failed_notice_mail_is_recorded_and_retry_delivers_exactly_once(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-01 10:00:00', 'Europe/Belgrade'));
        $owner = $this->user('korisnik', 'smtp-down@example.test');
        $chair = $this->user('komisija', 'smtp-down-chair@example.test');
        $application = $this->finalApplication($owner, $chair);
        $fake = $this->failMailTransportOnce();

        $this->recordIncomplete($chair, $application);

        $assessment = ApplicationCompletenessAssessment::query()->where('application_id', $application->id)->sole();
        $this->assertSame(ApplicationCompletenessAssessment::INCOMPLETE, $assessment->outcome);
        $this->assertSame('2026-09-01 10:00:00', $this->belgrade($assessment->notice_sent_at));
        $this->assertSame('2026-09-04 10:00:00.000000', $this->windowEnd($application)->format('Y-m-d H:i:s.u'));
        $this->assertNull($assessment->mail_sent_at);
        $this->assertSame('2026-09-01 10:00:00', $this->belgrade($assessment->mail_failed_at));

        Carbon::setTestNow(Carbon::parse('2026-09-01 12:00:00', 'Europe/Belgrade'));
        Mail::swap($fake);
        $service = app(CompletenessAssessmentService::class);
        $service->deliverNotice($assessment);

        $delivered = $assessment->fresh();
        $this->assertSame(1, ApplicationCompletenessAssessment::query()->where('application_id', $application->id)->count());
        $this->assertSame('2026-09-01 12:00:00', $this->belgrade($delivered->mail_sent_at));
        $this->assertNull($delivered->mail_failed_at);
        $this->assertSame('2026-09-01 10:00:00', $this->belgrade($delivered->notice_sent_at));
        $this->assertSame($this->belgrade($assessment->recorded_at), $this->belgrade($delivered->recorded_at));
        $this->assertSame([$assessment->outcome, $assessment->notes, $assessment->evidence_fingerprint], [$delivered->outcome, $delivered->notes, $delivered->evidence_fingerprint]);
        $this->assertSame('2026-09-04 10:00:00.000000', $this->windowEnd($application)->format('Y-m-d H:i:s.u'));
        Mail::assertSent(IncompleteDocumentationNoticeMail::class, function (IncompleteDocumentationNoticeMail $mail) use ($owner) {
            return $mail->hasTo($owner->email)
                && $mail->complaintDeadline?->format('Y-m-d H:i:s') === '2026-09-04 10:00:00';
        });

        Carbon::setTestNow(Carbon::parse('2026-09-01 13:00:00', 'Europe/Belgrade'));
        $service->deliverNotice($assessment);
        $this->actingAs($chair)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.completeness', $application), ['outcome' => 'incomplete', 'notes' => 'Ponovo.'])
            ->assertSessionHasErrors('error');

        Mail::assertSent(IncompleteDocumentationNoticeMail::class, 1);
        $this->assertSame(1, ApplicationCompletenessAssessment::query()->where('application_id', $application->id)->count());
        $this->assertSame('2026-09-01 12:00:00', $this->belgrade($assessment->fresh()->mail_sent_at));
    }

    public function test_failed_decision_mail_is_recorded_and_retry_delivers_exactly_once(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-01 10:00:00', 'Europe/Belgrade'));
        $owner = $this->user('korisnik', 'smtp-decision@example.test');
        $chair = $this->user('komisija', 'smtp-decision-chair@example.test');
        $application = $this->finalApplication($owner, $chair);
        $this->recordIncomplete($chair, $application);

        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00', 'Europe/Belgrade'));
        $this->actingAs($owner)
            ->from(route('applications.show', $application))
            ->post(route('applications.complaint', $application), ['body' => 'Prigovor.'])
            ->assertSessionHas('success');
        $complaint = ApplicationComplaint::query()->where('application_id', $application->id)->sole();

        Carbon::setTestNow(Carbon::parse('2026-09-03 10:00:00', 'Europe/Belgrade'));
        $fake = $this->failMailTransportOnce();
        $this->actingAs($chair)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.complaint-decision', [$application, $complaint]), ['decision' => 'rejected', 'decision_notes' => 'Obrazloženje.'])
            ->assertSessionHas('success');

        $failed = $complaint->fresh();
        $this->assertSame(ApplicationComplaint::REJECTED, $failed->decision);
        $this->assertSame('2026-09-03 10:00:00', $this->belgrade($failed->decided_at));
        $this->assertSame('2026-09-02 10:00:00', $this->belgrade($failed->submitted_at));
        $this->assertSame('2026-09-09 10:00:00', $this->belgrade($failed->decision_due_at));
        $this->assertNull($failed->mail_sent_at);
        $this->assertSame('2026-09-03 10:00:00', $this->belgrade($failed->mail_failed_at));

        Carbon::setTestNow(Carbon::parse('2026-09-03 11:00:00', 'Europe/Belgrade'));
        Mail::swap($fake);
        $service = app(ComplaintService::class);
        $service->deliverDecision($complaint);

        $delivered = $complaint->fresh();
        $this->assertSame('2026-09-03 11:00:00', $this->belgrade($delivered->mail_sent_at));
        $this->assertNull($delivered->mail_failed_at);
        $this->assertSame(
            [$failed->decision, $failed->decision_notes, $failed->decision_late, $failed->decided_by_user_id, $this->belgrade($failed->decided_at), $this->belgrade($failed->decision_due_at)],
            [$delivered->decision, $delivered->decision_notes, $delivered->decision_late, $delivered->decided_by_user_id, $this->belgrade($delivered->decided_at), $this->belgrade($delivered->decision_due_at)]
        );
        Mail::assertSent(CanonicalComplaintDecisionMail::class, function (CanonicalComplaintDecisionMail $mail) use ($owner) {
            return $mail->hasTo($owner->email)
                && ! $mail->accepted()
                && $mail->decidedAt->timezone('Europe/Belgrade')->format('Y-m-d H:i:s') === '2026-09-03 10:00:00';
        });

        Carbon::setTestNow(Carbon::parse('2026-09-03 12:00:00', 'Europe/Belgrade'));
        $service->deliverDecision($complaint);
        $this->actingAs($chair)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.complaint-decision', [$application, $complaint]), ['decision' => 'accepted', 'decision_notes' => 'Obrazloženje.'])
            ->assertSessionHasErrors('error');

        Mail::assertSent(CanonicalComplaintDecisionMail::class, 1);
        $this->assertSame(ApplicationComplaint::REJECTED, $complaint->fresh()->decision);
        $this->assertSame('2026-09-03 11:00:00', $this->belgrade($complaint->fresh()->mail_sent_at));
    }

    public function test_delivery_bookkeeping_never_opens_legal_fields(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-01 10:00:00', 'Europe/Belgrade'));
        $owner = $this->user('korisnik', 'immutable-mail@example.test');
        $chair = $this->user('komisija', 'immutable-mail-chair@example.test');
        $application = $this->finalApplication($owner, $chair);
        $fake = $this->failMailTransportOnce();
        $this->recordIncomplete($chair, $application);
        $assessmentId = ApplicationCompletenessAssessment::query()->where('application_id', $application->id)->value('id');
        $assessment = fn () => ApplicationCompletenessAssessment::query()->findOrFail($assessmentId);

        foreach ([
            ['notes' => 'Izmijenjeno.'],
            ['notice_sent_at' => now()->addDay()],
            ['outcome' => ApplicationCompletenessAssessment::COMPLETE],
            ['notes' => 'Izmijenjeno.', 'mail_failed_at' => now()],
            ['recorded_at' => now()->subDay(), 'mail_sent_at' => now()],
        ] as $changes) {
            $this->assertLegalRecordLocked(fn () => $assessment()->forceFill($changes)->save(), ApplicationCompletenessAssessment::IMMUTABLE_MESSAGE);
        }
        Carbon::setTestNow(Carbon::parse('2026-09-01 11:00:00', 'Europe/Belgrade'));
        $this->assertLegalRecordLocked(fn () => $assessment()->touch(), ApplicationCompletenessAssessment::IMMUTABLE_MESSAGE);

        Mail::swap($fake);
        app(CompletenessAssessmentService::class)->deliverNotice($assessment());
        $this->assertNotNull($assessment()->mail_sent_at);
        foreach ([['mail_sent_at' => null], ['mail_failed_at' => now()], ['mail_sent_at' => now()->addHour()]] as $changes) {
            $this->assertLegalRecordLocked(fn () => $assessment()->forceFill($changes)->save(), ApplicationCompletenessAssessment::IMMUTABLE_MESSAGE);
        }
        $this->assertLegalRecordLocked(fn () => $assessment()->delete(), ApplicationCompletenessAssessment::IMMUTABLE_MESSAGE);

        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00', 'Europe/Belgrade'));
        $this->actingAs($owner)
            ->from(route('applications.show', $application))
            ->post(route('applications.complaint', $application), ['body' => 'Prigovor.'])
            ->assertSessionHas('success');
        $complaintId = ApplicationComplaint::query()->where('application_id', $application->id)->value('id');
        $complaint = fn () => ApplicationComplaint::query()->findOrFail($complaintId);
        $this->assertLegalRecordLocked(fn () => $complaint()->forceFill(['mail_failed_at' => now()])->save(), ApplicationComplaint::IMMUTABLE_MESSAGE);

        Carbon::setTestNow(Carbon::parse('2026-09-03 10:00:00', 'Europe/Belgrade'));
        $fake = $this->failMailTransportOnce();
        $this->actingAs($chair)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.complaint-decision', [$application, $complaint()]), ['decision' => 'accepted', 'decision_notes' => 'Obrazloženje.'])
            ->assertSessionHas('success');
        $this->assertNotNull($complaint()->mail_failed_at);

        $appended = ApplicationCompletenessAssessment::query()->where('application_id', $application->id)
            ->where('outcome', ApplicationCompletenessAssessment::COMPLETE)->sole();
        $this->assertNull($appended->notice_sent_at);
        $this->assertLegalRecordLocked(fn () => $appended->forceFill(['mail_sent_at' => now()])->save(), ApplicationCompletenessAssessment::IMMUTABLE_MESSAGE);

        foreach ([
            ['decision' => ApplicationComplaint::REJECTED],
            ['body' => 'Izmijenjen prigovor.'],
            ['decision_due_at' => now()->addDays(30)],
            ['decision_notes' => 'Naknadno.', 'mail_failed_at' => now()],
            ['decided_at' => now()->subDay(), 'mail_sent_at' => now()],
        ] as $changes) {
            $this->assertLegalRecordLocked(fn () => $complaint()->forceFill($changes)->save(), ApplicationComplaint::IMMUTABLE_MESSAGE);
        }
        Carbon::setTestNow(Carbon::parse('2026-09-03 11:00:00', 'Europe/Belgrade'));
        $this->assertLegalRecordLocked(fn () => $complaint()->touch(), ApplicationComplaint::IMMUTABLE_MESSAGE);

        Mail::swap($fake);
        app(ComplaintService::class)->deliverDecision($complaint());
        $this->assertNotNull($complaint()->mail_sent_at);
        foreach ([['mail_sent_at' => null], ['mail_failed_at' => now()]] as $changes) {
            $this->assertLegalRecordLocked(fn () => $complaint()->forceFill($changes)->save(), ApplicationComplaint::IMMUTABLE_MESSAGE);
        }
        $this->assertLegalRecordLocked(fn () => $complaint()->delete(), ApplicationComplaint::IMMUTABLE_MESSAGE);

        $this->assertSame('Prigovor.', $complaint()->body);
        $this->assertSame(ApplicationComplaint::ACCEPTED, $complaint()->decision);
        $this->assertSame('2026-09-01 10:00:00', $this->belgrade($assessment()->notice_sent_at));
        $this->assertSame(ApplicationCompletenessAssessment::INCOMPLETE, $assessment()->outcome);
    }

    public function test_review_basis_snapshot_is_frozen_evidence_and_audit_persists(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:00', 'Europe/Belgrade'));
        $owner = $this->user('korisnik', 'basis@example.test', 'Zivo Ime');
        $chair = $this->user('komisija', 'basis-chair@example.test');
        $application = $this->finalApplication($owner, $chair, [
            'founder_name' => 'Zamrznuto Ime',
            'business_plan_name' => 'Zamrznuti plan',
            'redni_broj' => 7,
        ]);
        $document = ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'Rjesenje',
            'file_path' => 'frozen/rjesenje.pdf',
            'document_type' => 'ostalo',
        ]);

        $this->recordIncomplete($chair, $application);
        $assessment = ApplicationCompletenessAssessment::query()->firstOrFail();
        $snapshot = $assessment->evidence_snapshot;

        $this->assertSame('Zamrznuto Ime', $snapshot['application']['founder_name']);
        $this->assertSame('Zamrznuti plan', $snapshot['application']['business_plan_name']);
        $this->assertSame(7, (int) $snapshot['application']['redni_broj']);
        $this->assertSame('frozen/rjesenje.pdf', $snapshot['documents'][0]['file_path']);
        $this->assertSame($application->finalClaim->id, $snapshot['final_claim_id']);
        $this->assertSame(64, strlen($assessment->evidence_fingerprint));
        $this->assertStringNotContainsString('Zivo Ime', json_encode($snapshot));
        $this->assertStringNotContainsString('basis@example.test', json_encode($snapshot));

        $service = app(CompletenessAssessmentService::class);
        $this->assertTrue($service->reviewBasisMatches($assessment));

        // Live User changes stay operational and do not touch the review basis.
        $owner->update(['name' => 'Novo Zivo Ime', 'email' => 'routing-new@example.test']);
        $this->assertTrue($service->reviewBasisMatches($assessment->fresh()));

        // Out-of-band document replacement is detected; the recorded basis is unchanged.
        ApplicationDocument::query()->whereKey($document->id)->update(['file_path' => 'tampered/zamjena.pdf']);
        $fresh = $assessment->fresh();
        $this->assertFalse($service->reviewBasisMatches($fresh));
        $this->assertSame($snapshot, $fresh->evidence_snapshot);
        $this->assertSame($assessment->evidence_fingerprint, $fresh->evidence_fingerprint);
        $this->assertArrayNotHasKey('evidence_snapshot', $fresh->toArray());
    }

    public function test_review_basis_snapshot_contains_no_jmb(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:00', 'Europe/Belgrade'));
        $owner = $this->user('korisnik', 'jmb@example.test');
        $chair = $this->user('komisija', 'jmb-chair@example.test');
        $application = $this->finalApplication($owner, $chair);
        DB::table('applications')->where('id', $application->id)->update(['applicant_jmbg' => '0101990123456']);

        $this->recordIncomplete($chair, $application);

        $raw = (string) DB::table('application_completeness_assessments')->value('evidence_snapshot');
        $this->assertStringNotContainsString('0101990123456', $raw);
        $this->assertStringNotContainsStringIgnoringCase('jmb', $raw);
    }

    public function test_structured_review_states_across_lifecycle(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-01 10:00:00', 'Europe/Belgrade'));
        $owner = $this->user('korisnik', 'states@example.test');
        $chair = $this->user('komisija', 'states-chair@example.test');

        $draft = $this->draftApplication($this->user('korisnik', 'states-draft@example.test'), $chair);
        $this->assertSame(CanonicalReviewState::NOT_FINAL, CanonicalReviewState::resolve($draft));

        $application = $this->finalApplication($owner, $chair);
        $this->assertSame(CanonicalReviewState::PENDING_REVIEW, CanonicalReviewState::resolve($application));

        $this->recordIncomplete($chair, $application);
        $this->assertSame(CanonicalReviewState::COMPLAINT_ELIGIBLE, CanonicalReviewState::resolve($application->fresh()));

        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00', 'Europe/Belgrade'));
        $this->actingAs($owner)
            ->post(route('applications.complaint', $application), ['body' => 'Prigovor.'])
            ->assertSessionHas('success');
        $this->assertSame(CanonicalReviewState::COMPLAINT_PENDING, CanonicalReviewState::resolve($application->fresh()));

        // Inside the 7-day decision window.
        Carbon::setTestNow(Carbon::parse('2026-09-05 12:00:00', 'Europe/Belgrade'));
        $complaint = ApplicationComplaint::query()->firstOrFail();
        $this->actingAs($chair)
            ->post(route('evaluation.complaint-decision', [$application, $complaint]), [
                'decision' => 'rejected',
                'decision_notes' => 'Nedostatak potvrđen.',
            ])
            ->assertSessionHas('success')
            ->assertSessionMissing('warning');
        $this->assertFalse((bool) $complaint->fresh()->decision_late);
        $this->assertSame('Nedostatak potvrđen.', $complaint->fresh()->decision_notes);
        $this->assertSame(CanonicalReviewState::COMPLAINT_RESOLVED, CanonicalReviewState::resolve($application->fresh()));

        // Incomplete whose complaint window lapsed without a complaint.
        $lapsedOwner = $this->user('korisnik', 'states-lapsed@example.test');
        Carbon::setTestNow(Carbon::parse('2026-09-01 10:00:00', 'Europe/Belgrade'));
        $lapsed = $this->finalApplication($lapsedOwner, $chair);
        $this->recordIncomplete($chair, $lapsed);
        Carbon::setTestNow(Carbon::parse('2026-09-05 10:00:00', 'Europe/Belgrade'));
        $this->assertSame(CanonicalReviewState::INCOMPLETE, CanonicalReviewState::resolve($lapsed->fresh()));

        $completeOwner = $this->user('korisnik', 'states-complete@example.test');
        $complete = $this->finalApplication($completeOwner, $chair);
        $this->actingAs($chair)
            ->post(route('evaluation.completeness', $complete), ['outcome' => 'complete'])
            ->assertSessionHas('success');
        $this->assertSame(CanonicalReviewState::COMPLETE, CanonicalReviewState::resolve($complete->fresh()));

        $women = $this->submittedWithoutClaim($owner, $chair, CompetitionProfileResolver::TYPE_WOMEN);
        $this->assertNull(CanonicalReviewState::resolve($women));
    }

    public function test_complaint_allowed_immediately_after_decision_and_first_late_day_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-01 10:00:00', 'Europe/Belgrade'));
        $chair = $this->user('komisija', 'immediate-chair@example.test');
        $owner = $this->user('korisnik', 'immediate@example.test');
        $application = $this->finalApplication($owner, $chair);
        $this->recordIncomplete($chair, $application);

        $this->actingAs($owner)
            ->post(route('applications.complaint', $application), ['body' => 'Odmah nakon obavještenja.'])
            ->assertSessionHas('success');
        $this->assertSame(1, ApplicationComplaint::query()->where('application_id', $application->id)->count());

        $lateOwner = $this->user('korisnik', 'first-late-day@example.test');
        $late = $this->finalApplication($lateOwner, $chair);
        $this->recordIncomplete($chair, $late);

        Carbon::setTestNow(Carbon::parse('2026-09-05 00:00:00', 'Europe/Belgrade'));
        $result = app(ComplaintService::class)->submit($late->fresh(), $lateOwner, 'Prvi dan nakon roka.');
        $this->assertFalse($result->succeeded);
        $this->assertSame(ComplaintService::WINDOW_CLOSED_MESSAGE, $result->error);
        $this->assertSame(0, ApplicationComplaint::query()->where('application_id', $late->id)->count());
    }

    public function test_complaint_without_eligible_decision_is_blocked(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:00', 'Europe/Belgrade'));
        $chair = $this->user('komisija', 'no-decision-chair@example.test');
        $owner = $this->user('korisnik', 'no-decision@example.test');
        $pending = $this->finalApplication($owner, $chair);

        $this->actingAs($owner)
            ->post(route('applications.complaint', $pending), ['body' => 'Bez odluke.'])
            ->assertSessionHasErrors('error');

        $this->actingAs($chair)
            ->post(route('evaluation.completeness', $pending), ['outcome' => 'complete'])
            ->assertSessionHas('success');
        $this->actingAs($owner)
            ->post(route('applications.complaint', $pending), ['body' => 'Na potpunu prijavu.'])
            ->assertSessionHasErrors('error');

        $this->assertSame(0, ApplicationComplaint::query()->count());
    }

    public function test_complaint_evidence_private_and_owner_only(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:00', 'Europe/Belgrade'));
        $chair = $this->user('komisija', 'private-chair@example.test');
        $owner = $this->user('korisnik', 'private@example.test');
        $stranger = $this->user('korisnik', 'stranger@example.test');
        $application = $this->finalApplication($owner, $chair);
        $this->recordIncomplete($chair, $application);

        $this->actingAs($stranger)
            ->post(route('applications.complaint', $application), ['body' => 'Tudji prigovor.'])
            ->assertSessionHasErrors('error');
        $this->assertSame(0, ApplicationComplaint::query()->count());

        $this->actingAs($owner)
            ->post(route('applications.complaint', $application), ['body' => 'Povjerljivo obrazloženje.'])
            ->assertSessionHas('success');

        $complaint = ApplicationComplaint::query()->firstOrFail();
        $this->assertSame('Povjerljivo obrazloženje.', $complaint->body);
        $serialized = $complaint->toArray();
        $this->assertArrayNotHasKey('body', $serialized);
        $this->assertArrayNotHasKey('decision_notes', $serialized);
        $this->assertStringNotContainsString('Povjerljivo', $complaint->toJson());
    }

    public function test_original_review_decision_remains_auditable_after_accepted_complaint(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:00', 'Europe/Belgrade'));
        $chair = $this->user('komisija', 'audit-chair@example.test');
        $owner = $this->user('korisnik', 'audit@example.test');
        $application = $this->finalApplication($owner, $chair);
        $this->recordIncomplete($chair, $application, 'Originalni nalaz.');
        $original = ApplicationCompletenessAssessment::query()->firstOrFail();

        Carbon::setTestNow(Carbon::parse('2026-09-11 10:00:00', 'Europe/Belgrade'));
        $this->actingAs($owner)
            ->post(route('applications.complaint', $application), ['body' => 'Prigovor.'])
            ->assertSessionHas('success');
        $complaint = ApplicationComplaint::query()->firstOrFail();

        Carbon::setTestNow(Carbon::parse('2026-09-12 10:00:00', 'Europe/Belgrade'));
        $this->actingAs($chair)
            ->post(route('evaluation.complaint-decision', [$application, $complaint]), ['decision' => 'accepted', 'decision_notes' => 'Obrazloženje.'])
            ->assertSessionHas('success');

        $this->actingAs($chair)
            ->post(route('evaluation.complaint-decision', [$application, $complaint]), ['decision' => 'rejected', 'decision_notes' => 'Obrazloženje.'])
            ->assertSessionHasErrors('error');

        $history = ApplicationCompletenessAssessment::query()->where('application_id', $application->id)->orderBy('id')->get();
        $this->assertCount(2, $history);
        $this->assertSame('incomplete', $history[0]->outcome);
        $this->assertSame('Originalni nalaz.', $history[0]->notes);
        $this->assertSame($original->evidence_fingerprint, $history[0]->evidence_fingerprint);
        $this->assertSame($original->notice_sent_at->toIso8601String(), $history[0]->notice_sent_at->toIso8601String());
        $this->assertSame('complete', $history[1]->outcome);
        $this->assertSame($history[0]->evidence_fingerprint, $history[1]->evidence_fingerprint);
        $this->assertSame($original->id, $complaint->fresh()->completeness_assessment_id);
        $this->assertSame('accepted', $complaint->fresh()->decision);

        $this->actingAs($chair)
            ->post(route('evaluation.completeness', $application), ['outcome' => 'complete'])
            ->assertSessionHasErrors('error');
        $this->assertSame(2, ApplicationCompletenessAssessment::query()->where('application_id', $application->id)->count());
    }

    public function test_final_claim_submitted_at_and_redni_broj_survive_review_and_complaint(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:00', 'Europe/Belgrade'));
        $chair = $this->user('komisija', 'survive-chair@example.test');
        $owner = $this->user('korisnik', 'survive@example.test');
        $application = $this->finalApplication($owner, $chair, [
            'redni_broj' => 12,
            'submitted_at' => Carbon::parse('2026-09-05 09:30:00', 'Europe/Belgrade'),
        ]);
        $claim = $application->finalClaim;
        $submittedAt = $application->submitted_at->toIso8601String();

        $this->recordIncomplete($chair, $application);
        $afterReview = $application->fresh(['finalClaim']);
        $this->assertSame($claim->id, $afterReview->finalClaim->id);
        $this->assertSame(12, (int) $afterReview->redni_broj);
        $this->assertSame($submittedAt, $afterReview->submitted_at->toIso8601String());

        Carbon::setTestNow(Carbon::parse('2026-09-11 10:00:00', 'Europe/Belgrade'));
        $this->actingAs($owner)
            ->post(route('applications.complaint', $application), ['body' => 'Prigovor.'])
            ->assertSessionHas('success');
        $complaint = ApplicationComplaint::query()->firstOrFail();
        Carbon::setTestNow(Carbon::parse('2026-09-13 10:00:00', 'Europe/Belgrade'));
        $this->actingAs($chair)
            ->post(route('evaluation.complaint-decision', [$application, $complaint]), ['decision' => 'rejected', 'decision_notes' => 'Obrazloženje.'])
            ->assertSessionHas('success');

        $afterComplaint = $application->fresh(['finalClaim']);
        $this->assertSame($claim->id, $afterComplaint->finalClaim->id);
        $this->assertSame($claim->claimed_at->toIso8601String(), $afterComplaint->finalClaim->claimed_at->toIso8601String());
        $this->assertSame(12, (int) $afterComplaint->redni_broj);
        $this->assertSame($submittedAt, $afterComplaint->submitted_at->toIso8601String());
        $this->assertSame('submitted', $afterComplaint->status);
        $this->assertSame(1, FinalApplicationClaim::query()->where('application_id', $application->id)->count());
    }

    /**
     * Swap in a transport that throws on the next send; returns the suite's mail fake for the retry.
     */
    private function failMailTransportOnce(): MailFake
    {
        $fake = Mail::getFacadeRoot();
        $this->assertInstanceOf(MailFake::class, $fake);
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('SMTP unavailable'));

        return $fake;
    }

    private function belgrade(?\DateTimeInterface $at): ?string
    {
        return $at ? Carbon::instance($at)->timezone('Europe/Belgrade')->format('Y-m-d H:i:s') : null;
    }

    private function assertLegalRecordLocked(callable $write, string $message): void
    {
        try {
            $write();
        } catch (\LogicException $exception) {
            $this->assertSame($message, $exception->getMessage());

            return;
        }

        $this->fail('Legal record write was not blocked.');
    }

    private function recordIncomplete(User $chair, Application $application, string $notes = 'Nedostaje obavezni prilog.'): void
    {
        $this->actingAs($chair)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.completeness', $application), ['outcome' => 'incomplete', 'notes' => $notes])
            ->assertSessionHas('success');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function finalApplication(User $owner, User $chair, array $overrides = []): Application
    {
        [$commission] = $this->canonicalCommission($chair);
        $competition = Competition::create([
            'title' => 'Kanonski konkurs '.uniqid(),
            'type' => CompetitionProfileResolver::TYPE_CANONICAL,
            'status' => 'published',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'commission_id' => $commission->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-21',
            'published_at' => '2026-08-01 09:00:00',
        ]);
        $this->confirmOrdinarySession($competition);
        $application = Application::create(array_merge([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'business_plan_name' => 'Plan',
            'founder_name' => 'Podnosilac',
            'submitted_at' => now(),
            'redni_broj' => 1,
        ], $overrides));
        FinalApplicationClaim::query()->create([
            'user_id' => $owner->id,
            'competition_id' => $competition->id,
            'application_id' => $application->id,
            'claimed_at' => now(),
        ]);

        return $application->fresh(['competition.commission', 'finalClaim', 'user']);
    }

    private function draftApplication(User $owner, User $chair): Application
    {
        $application = $this->finalApplication($owner, $chair);
        FinalApplicationClaim::query()->where('application_id', $application->id)->delete();
        $application->update(['status' => 'draft', 'submitted_at' => null, 'redni_broj' => null]);

        return $application->fresh(['competition.commission', 'finalClaim']);
    }

    private function submittedWithoutClaim(User $owner, User $chair, string $type): Application
    {
        $commission = Commission::create([
            'name' => 'Other '.$type,
            'year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
            'status' => 'active',
        ]);
        CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $chair->id,
            'name' => 'Predsjednik',
            'position' => 'predsjednik',
            'member_type' => 'opstina',
            'status' => 'active',
        ]);
        $competition = Competition::create([
            'title' => $type.' '.uniqid(),
            'type' => $type,
            'status' => 'published',
            'year' => 2026,
            'budget' => 1000,
            'deadline_days' => 20,
            'commission_id' => $commission->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'published_at' => now()->subDay(),
        ]);

        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now(),
        ])->fresh(['competition']);
    }

    private function user(string $role, string $email, string $name = 'Korisnik'): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => $email,
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
