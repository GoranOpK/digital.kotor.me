<?php

namespace Tests\Feature\Kn;

use App\Mail\CanonicalComplaintDecisionMail;
use App\Mail\IncompleteDocumentationNoticeMail;
use App\Models\Application;
use App\Models\ApplicationComplaint;
use App\Models\ApplicationCompletenessAssessment;
use App\Models\ApplicationDocument;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\ApplicationEliminatoryNotice;
use App\Models\ApplicationPrigovor;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\FinalApplicationClaim;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\CanonicalReviewState;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance as G;
use App\Services\Competitions\Commission\CanonicalCommissionMembershipService;
use App\Services\Competitions\ComplaintService;
use App\Services\Competitions\CompletenessAssessmentService;
use App\Services\Competitions\Decision\CanonicalAdministrativeGate;
use App\Services\Competitions\Lifecycle\LegacyCommissionFlowGuard;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\EliminatoryProfileConfig;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Kn\Concerns\BuildsCanonicalCommission;
use Tests\TestCase;

/**
 * KN-SYNC-014 — canonical completeness + complaint flow through HTTP/UI: chair-only authority,
 * frozen review basis, stage lock, 3/7-day exact-time boundaries, after-commit notifications,
 * applicant status, legacy isolation and Women/Youth non-regression.
 */
class CanonicalCompletenessComplaintUiTest extends TestCase
{
    use BuildsCanonicalCommission;
    use RefreshDatabase;

    private const TZ = 'Europe/Belgrade';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-09-25 10:00:00', self::TZ));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // A — completeness UI, frozen basis, chair authority

    public function test_chair_sees_frozen_review_basis_and_records_complete_append_only(): void
    {
        [$call, $members] = $this->canonicalCall();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $application = $this->finalApplication($call, ['founder_name' => 'Zamrznuto Ime', 'pib' => '02999111']);
        ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'Rješenje o registraciji',
            'file_path' => 'frozen/registracija.pdf',
            'document_type' => 'ostalo',
        ]);
        $fingerprint = app(\App\Services\Competitions\CanonicalReviewBasis::class)->fingerprint(
            app(\App\Services\Competitions\CanonicalReviewBasis::class)->snapshot($application->fresh())
        );

        $this->actingAs($chair)->get(route('evaluation.canonical-review.index', $call))
            ->assertOk()
            ->assertSee('data-application-id="'.$application->id.'"', false)
            ->assertSee(CanonicalReviewState::LABELS[CanonicalReviewState::PENDING_REVIEW]);

        $this->actingAs($chair)->get(route('evaluation.canonical-review.show', $application))
            ->assertOk()
            ->assertSee('Zamrznuto Ime')
            ->assertSee('02999111')
            ->assertSee('Rješenje o registraciji')
            ->assertSee($fingerprint)
            ->assertSee('data-form="completeness"', false)
            ->assertDontSee('frozen/registracija.pdf');

        $this->actingAs($chair)
            ->from(route('evaluation.canonical-review.show', $application))
            ->post(route('evaluation.completeness', $application), ['outcome' => 'complete', 'notes' => 'Dosije je potpun.'])
            ->assertRedirect(route('evaluation.canonical-review.show', $application))
            ->assertSessionHas('success');

        $assessment = ApplicationCompletenessAssessment::query()->where('application_id', $application->id)->sole();
        $this->assertSame('complete', $assessment->outcome);
        $this->assertSame($chair->id, $assessment->recorded_by_user_id);
        $this->assertSame($fingerprint, $assessment->evidence_fingerprint);

        $this->actingAs($chair)->get(route('evaluation.canonical-review.show', $application))
            ->assertOk()
            ->assertSee('data-assessment-id="'.$assessment->id.'"', false)
            ->assertSee('Dosije je potpun.')
            ->assertDontSee('data-form="completeness"', false);

        // Duplicate action: no second assessment, no mail.
        $this->actingAs($chair)
            ->post(route('evaluation.completeness', $application), ['outcome' => 'incomplete', 'notes' => 'Predomislio sam se.'])
            ->assertSessionHasErrors(['error' => CompletenessAssessmentService::ALREADY_ASSESSED_MESSAGE]);
        $this->assertSame(1, ApplicationCompletenessAssessment::query()->where('application_id', $application->id)->count());
        Mail::assertNothingSent();
    }

    public function test_incomplete_requires_reason(): void
    {
        [$call, $members] = $this->canonicalCall();
        $application = $this->finalApplication($call);

        $this->actingAs($members[G::ROLE_PREDSJEDNIK]->user)
            ->post(route('evaluation.completeness', $application), ['outcome' => 'incomplete', 'notes' => '  '])
            ->assertSessionHasErrors(['error' => CompletenessAssessmentService::REASON_REQUIRED_MESSAGE]);

        $this->assertSame(0, ApplicationCompletenessAssessment::query()->count());
        Mail::assertNothingSent();
    }

    public function test_only_current_canonical_chair_records_completeness(): void
    {
        [$call, $members] = $this->canonicalCall();
        $application = $this->finalApplication($call);
        [$otherCall, $otherMembers] = $this->canonicalCall();
        $service = app(CompletenessAssessmentService::class);

        $secretary = $members[G::ROLE_SEKRETAR]->user;
        $this->actingAs($secretary)
            ->post(route('evaluation.completeness', $application), ['outcome' => 'complete'])
            ->assertForbidden();
        $this->assertSame(G::CHAIR_COMPLETENESS_REQUIRED_MESSAGE, $service->record($application, $secretary, 'complete')->error);

        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)->post(route('evaluation.completeness', $application), ['outcome' => 'complete'])->assertForbidden();
        $this->assertSame(G::NOT_MEMBER_MESSAGE, $service->record($application, $admin, 'complete')->error);

        $konkursAdmin = $this->userWithRole('konkurs_admin');
        $this->actingAs($konkursAdmin)->post(route('evaluation.completeness', $application), ['outcome' => 'complete'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertSame(G::NOT_MEMBER_MESSAGE, $service->record($application, $konkursAdmin, 'complete')->error);

        $superadmin = $this->userWithRole('superadmin');
        $this->actingAs($superadmin)->post(route('evaluation.completeness', $application), ['outcome' => 'complete'])->assertForbidden();
        $this->assertSame(G::NOT_MEMBER_MESSAGE, $service->record($application, $superadmin, 'complete')->error);

        $foreignChair = $otherMembers[G::ROLE_PREDSJEDNIK]->user;
        $this->actingAs($foreignChair)->post(route('evaluation.completeness', $application), ['outcome' => 'complete'])->assertForbidden();
        $this->assertSame(G::NOT_MEMBER_MESSAGE, $service->record($application, $foreignChair, 'complete')->error);

        $this->assertSame(0, ApplicationCompletenessAssessment::query()->count());

        // Replaced chair loses authority; the replacement chair has it.
        $oldChair = $members[G::ROLE_PREDSJEDNIK];
        $newChair = $this->replaceMember($oldChair);
        $this->actingAs($oldChair->user)->post(route('evaluation.completeness', $application), ['outcome' => 'complete'])->assertForbidden();
        $this->actingAs($oldChair->user)->get(route('evaluation.canonical-review.show', $application))->assertForbidden();
        $this->assertSame(0, ApplicationCompletenessAssessment::query()->count());

        $this->actingAs($newChair->user)
            ->post(route('evaluation.completeness', $application), ['outcome' => 'complete'])
            ->assertSessionHas('success');
        $this->assertSame(1, ApplicationCompletenessAssessment::query()->count());
        $this->assertNotNull($otherCall);
    }

    public function test_raw_legacy_position_predsjednik_grants_no_canonical_authority(): void
    {
        [$call, $members] = $this->canonicalCall();
        $application = $this->finalApplication($call);
        $secretary = $members[G::ROLE_SEKRETAR];
        DB::table('commission_members')->where('id', $secretary->id)->update(['position' => 'predsjednik']);

        $this->actingAs($secretary->user)->post(route('evaluation.completeness', $application), ['outcome' => 'complete'])->assertForbidden();
        $this->assertSame(
            G::CHAIR_COMPLETENESS_REQUIRED_MESSAGE,
            app(CompletenessAssessmentService::class)->record($application->fresh(), $secretary->user, 'complete')->error
        );
        $this->assertSame(0, ApplicationCompletenessAssessment::query()->count());
    }

    public function test_vacated_chair_cannot_record_or_view(): void
    {
        [$call, $members] = $this->canonicalCall();
        $application = $this->finalApplication($call);
        $chair = $members[G::ROLE_PREDSJEDNIK];
        $this->assertTrue(app(CanonicalCommissionMembershipService::class)
            ->vacate($chair, 'resigned', now()->subMinute(), $this->canonicalActor())->succeeded);

        $this->actingAs($chair->user)->post(route('evaluation.completeness', $application), ['outcome' => 'complete'])->assertForbidden();
        $this->actingAs($chair->user)->get(route('evaluation.canonical-review.show', $application))->assertForbidden();
        $this->assertSame(0, ApplicationCompletenessAssessment::query()->count());
    }

    public function test_members_view_without_actions_and_admin_is_read_only(): void
    {
        [$call, $members] = $this->canonicalCall();
        [$owner, $application, $complaint] = $this->pendingComplaint($call, $members, 'Moj povjerljivi prigovor.');

        $this->actingAs($members[G::ROLE_SEKRETAR]->user)->get(route('evaluation.canonical-review.show', $application))
            ->assertOk()
            ->assertSee('Moj povjerljivi prigovor.')
            ->assertDontSee('data-form="completeness"', false)
            ->assertDontSee('data-form="complaint-decision"', false)
            ->assertSee(G::CHAIR_REQUIRED_MESSAGE);

        $this->actingAs($members[G::ROLE_PREDSJEDNIK]->user)->get(route('evaluation.canonical-review.show', $application))
            ->assertOk()
            ->assertSee('data-form="complaint-decision"', false);

        foreach (['admin', 'konkurs_admin', 'superadmin'] as $role) {
            $viewer = $this->userWithRole($role);
            $this->actingAs($viewer)->get(route('admin.competitions.canonical-review.index', $call))->assertOk();
            $this->actingAs($viewer)->get(route('admin.competitions.canonical-review.show', [$call, $application]))
                ->assertOk()
                ->assertSee('samo čitanje')
                ->assertDontSee('data-form="completeness"', false)
                ->assertDontSee('data-form="complaint-decision"', false)
                ->assertDontSee('Moj povjerljivi prigovor.');

            $mutation = $this->actingAs($viewer)
                ->post(route('evaluation.complaint-decision', [$application, $complaint]), ['decision' => 'accepted']);
            $role === 'konkurs_admin' ? $mutation->assertRedirect(route('admin.dashboard')) : $mutation->assertForbidden();
            $this->assertSame(G::NOT_MEMBER_MESSAGE, app(ComplaintService::class)->decide($complaint, $viewer, 'accepted', 'Obrazloženje.')->error);
        }

        $this->actingAs($this->komisijaUser('stranac'))->get(route('evaluation.canonical-review.show', $application))->assertForbidden();
        $this->actingAs($this->komisijaUser('stranac2'))->get(route('evaluation.canonical-review.index', $call))->assertForbidden();
        $this->actingAs($owner)->get(route('evaluation.canonical-review.show', $application))->assertForbidden();
        $this->assertNull($complaint->fresh()->decision);
    }

    public function test_cross_competition_ids_are_blocked(): void
    {
        [$call, $members] = $this->canonicalCall();
        [$otherCall, $otherMembers] = $this->canonicalCall();
        [, $application] = $this->pendingComplaint($call, $members);
        [, , $foreignComplaint] = $this->pendingComplaint($otherCall, $otherMembers);

        $this->actingAs($members[G::ROLE_PREDSJEDNIK]->user)
            ->post(route('evaluation.complaint-decision', [$application, $foreignComplaint]), ['decision' => 'accepted'])
            ->assertNotFound();
        $this->actingAs($this->userWithRole('admin'))
            ->get(route('admin.competitions.canonical-review.show', [$otherCall, $application]))
            ->assertNotFound();
        $this->actingAs($members[G::ROLE_PREDSJEDNIK]->user)
            ->get(route('evaluation.canonical-review.index', $otherCall))
            ->assertForbidden();

        $this->assertSame(0, ApplicationComplaint::query()->whereNotNull('decision')->count());
    }

    // B — stage lock

    public function test_completeness_and_complaint_mutation_blocked_after_preliminary_ranking(): void
    {
        [$call, $members] = $this->canonicalCall();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $pending = $this->finalApplication($call);
        [$owner, $incomplete] = $this->incompleteApplication($call, $members);
        [, $withComplaint, $complaint] = $this->pendingComplaint($call, $members);

        DB::table('competitions')->where('id', $call->id)->update(['canonical_ranking_formed_at' => now()]);

        $this->actingAs($chair)
            ->post(route('evaluation.completeness', $pending), ['outcome' => 'complete'])
            ->assertSessionHasErrors(['error' => CompletenessAssessmentService::STAGE_LOCKED_MESSAGE]);
        $this->actingAs($owner)
            ->post(route('applications.complaint', $incomplete), ['body' => 'Prekasno, rang lista formirana.'])
            ->assertSessionHasErrors(['error' => CompletenessAssessmentService::STAGE_LOCKED_MESSAGE]);
        $this->actingAs($chair)
            ->post(route('evaluation.complaint-decision', [$withComplaint, $complaint]), ['decision' => 'accepted', 'decision_notes' => 'Obrazloženje.'])
            ->assertSessionHasErrors(['error' => CompletenessAssessmentService::STAGE_LOCKED_MESSAGE]);

        $this->assertSame(0, ApplicationCompletenessAssessment::query()->where('application_id', $pending->id)->count());
        $this->assertSame(0, ApplicationComplaint::query()->where('application_id', $incomplete->id)->count());
        $this->assertNull($complaint->fresh()->decision);

        $this->actingAs($chair)->get(route('evaluation.canonical-review.show', $withComplaint))
            ->assertOk()
            ->assertSee(CompletenessAssessmentService::STAGE_LOCKED_MESSAGE)
            ->assertDontSee('data-form="complaint-decision"', false);
        $this->actingAs($owner)->get(route('applications.canonical-review.show', $incomplete))
            ->assertOk()
            ->assertDontSee('name="body"', false);
    }

    public function test_completeness_blocked_once_application_has_scores(): void
    {
        [$call, $members] = $this->canonicalCall();
        $application = $this->finalApplication($call);
        EvaluationScore::query()->create([
            'application_id' => $application->id,
            'commission_member_id' => $members[G::ROLE_SEKRETAR]->id,
            'canonical_seat_no' => 2,
        ]);

        $this->assertSame(
            CompletenessAssessmentService::STAGE_LOCKED_MESSAGE,
            app(CompletenessAssessmentService::class)->record($application, $members[G::ROLE_PREDSJEDNIK]->user, 'complete')->error
        );
        $this->assertSame(0, ApplicationCompletenessAssessment::query()->count());
    }

    public function test_post_close_mutation_blocked_and_history_readable(): void
    {
        [$call, $members] = $this->canonicalCall();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $pending = $this->finalApplication($call);
        [$owner, $incomplete] = $this->incompleteApplication($call, $members);
        [, $withComplaint, $complaint] = $this->pendingComplaint($call, $members);

        DB::table('competitions')->where('id', $call->id)->update(['status' => 'closed']);

        $this->actingAs($chair)->post(route('evaluation.completeness', $pending), ['outcome' => 'complete'])
            ->assertSessionHasErrors(['error' => CanonicalAdministrativeGate::CLOSED_MESSAGE]);
        $this->actingAs($owner)->post(route('applications.complaint', $incomplete), ['body' => 'Nakon zatvaranja.'])
            ->assertSessionHasErrors(['error' => CanonicalAdministrativeGate::CLOSED_MESSAGE]);
        $this->actingAs($chair)->post(route('evaluation.complaint-decision', [$withComplaint, $complaint]), ['decision' => 'rejected', 'decision_notes' => 'Obrazloženje.'])
            ->assertSessionHasErrors(['error' => CanonicalAdministrativeGate::CLOSED_MESSAGE]);

        $this->assertSame(0, ApplicationCompletenessAssessment::query()->where('application_id', $pending->id)->count());
        $this->assertNull($complaint->fresh()->decision);
        $this->actingAs($chair)->get(route('evaluation.canonical-review.show', $incomplete))->assertOk()->assertSee('Poziv je zatvoren');
        $this->actingAs($owner)->get(route('applications.canonical-review.show', $incomplete))->assertOk()->assertSee('zatvoren');
    }

    // C — applicant complaint, 3-day exact-time boundary

    public function test_applicant_complaint_page_and_three_day_exact_boundary(): void
    {
        [$call, $members] = $this->canonicalCall();
        $noticeAt = '2026-09-25 10:00:00';
        $cases = [
            'before' => ['2026-09-28 09:59:59', true],
            'exact' => ['2026-09-28 10:00:00', true],
            'after' => ['2026-09-28 10:00:01', false],
        ];

        foreach ($cases as $key => [$at, $allowed]) {
            Carbon::setTestNow(Carbon::parse($noticeAt, self::TZ));
            [$owner, $application] = $this->incompleteApplication($call, $members, 'Nedostaje potvrda poreske uprave.');

            Carbon::setTestNow(Carbon::parse($at, self::TZ));
            $page = $this->actingAs($owner)->get(route('applications.canonical-review.show', $application))->assertOk()
                ->assertSee('Nedostaje potvrda poreske uprave.')
                ->assertSee('28.09.2026 10:00:00');

            if ($allowed) {
                $page->assertSee('data-status="'.ComplaintService::STATUS_COMPLAINT_AVAILABLE.'"', false)->assertSee('name="body"', false);
                $this->actingAs($owner)
                    ->from(route('applications.canonical-review.show', $application))
                    ->post(route('applications.complaint', $application), ['body' => 'Prigovor '.$key])
                    ->assertRedirect(route('applications.canonical-review.show', $application))
                    ->assertSessionHas('success');
                $this->assertSame(1, ApplicationComplaint::query()->where('application_id', $application->id)->count(), $key);
            } else {
                $page->assertSee('data-status="'.ComplaintService::STATUS_INCOMPLETE.'"', false)->assertDontSee('name="body"', false);
                $this->actingAs($owner)
                    ->post(route('applications.complaint', $application), ['body' => 'Prigovor '.$key])
                    ->assertSessionHasErrors(['error' => ComplaintService::WINDOW_CLOSED_MESSAGE]);
                $this->assertSame(0, ApplicationComplaint::query()->where('application_id', $application->id)->count(), $key);
            }
        }
    }

    public function test_complaint_owner_only_single_and_immutable(): void
    {
        [$call, $members] = $this->canonicalCall();
        [$owner, $application] = $this->incompleteApplication($call, $members);
        $stranger = $this->userWithRole('korisnik');

        $this->actingAs($stranger)->get(route('applications.canonical-review.show', $application))->assertForbidden();
        $this->actingAs($stranger)->post(route('applications.complaint', $application), ['body' => 'Tuđi prigovor.'])
            ->assertSessionHasErrors('error');
        $this->assertSame(0, ApplicationComplaint::query()->count());

        $this->actingAs($owner)->post(route('applications.complaint', $application), ['body' => 'Prvi prigovor.'])->assertSessionHas('success');
        $this->actingAs($owner)->post(route('applications.complaint', $application), ['body' => 'Drugi prigovor.'])
            ->assertSessionHasErrors(['error' => ComplaintService::DUPLICATE_MESSAGE]);

        $complaint = ApplicationComplaint::query()->sole();
        $this->assertSame('Prvi prigovor.', $complaint->body);

        $this->assertThrowsImmutable(fn () => $complaint->update(['body' => 'Izmijenjen prigovor.']), ApplicationComplaint::IMMUTABLE_MESSAGE);
        $this->assertThrowsImmutable(fn () => $complaint->delete(), ApplicationComplaint::IMMUTABLE_MESSAGE);
        $this->assertSame('Prvi prigovor.', $complaint->fresh()->body);

        $assessment = ApplicationCompletenessAssessment::query()->sole();
        $this->assertThrowsImmutable(fn () => $assessment->update(['outcome' => 'complete']), ApplicationCompletenessAssessment::IMMUTABLE_MESSAGE);
        $this->assertThrowsImmutable(fn () => $assessment->delete(), ApplicationCompletenessAssessment::IMMUTABLE_MESSAGE);
        $this->assertSame('incomplete', $assessment->fresh()->outcome);
    }

    // D — complaint decision, 7-day exact-time boundary, late warning

    public function test_chair_decision_seven_day_exact_boundary_and_late_warning(): void
    {
        [$call, $members] = $this->canonicalCall();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $cases = [
            'before' => ['2026-10-02 09:59:59', false],
            'exact' => ['2026-10-02 10:00:00', false],
            'after' => ['2026-10-02 10:00:01', true],
        ];

        foreach ($cases as $key => [$at, $late]) {
            Carbon::setTestNow(Carbon::parse('2026-09-24 10:00:00', self::TZ));
            [, $application, $complaint] = $this->pendingComplaint($call, $members, 'Prigovor '.$key, '2026-09-25 10:00:00');
            $this->assertSame('2026-10-02 10:00:00', $complaint->decision_due_at->timezone(self::TZ)->format('Y-m-d H:i:s'));

            Carbon::setTestNow(Carbon::parse($at, self::TZ));
            $page = $this->actingAs($chair)->get(route('evaluation.canonical-review.show', $application))->assertOk()
                ->assertSee('02.10.2026 10:00:00')
                ->assertSee('data-form="complaint-decision"', false);
            $late ? $page->assertSee('data-overdue-warning', false) : $page->assertDontSee('data-overdue-warning', false);

            $response = $this->actingAs($chair)
                ->from(route('evaluation.canonical-review.show', $application))
                ->post(route('evaluation.complaint-decision', [$application, $complaint]), ['decision' => 'rejected', 'decision_notes' => 'Odluka '.$key])
                ->assertSessionHas('success');
            $late
                ? $response->assertSessionHas('warning', ComplaintService::LATE_DECISION_WARNING)
                : $response->assertSessionMissing('warning');

            $complaint->refresh();
            $this->assertSame('rejected', $complaint->decision, $key);
            $this->assertSame($late, (bool) $complaint->decision_late, $key);
            $this->assertSame($at, $complaint->decided_at->timezone(self::TZ)->format('Y-m-d H:i:s'), $key);
            $this->assertSame($chair->id, $complaint->decided_by_user_id);

            $after = $this->actingAs($chair)->get(route('evaluation.canonical-review.show', $application))->assertOk()
                ->assertSee('data-complaint-decision="rejected"', false)
                ->assertDontSee('data-form="complaint-decision"', false);
            $late ? $after->assertSee(ComplaintService::LATE_DECISION_WARNING) : $after->assertDontSee('data-late-warning', false);
        }
    }

    public function test_only_current_chair_decides_and_decision_is_immutable(): void
    {
        [$call, $members] = $this->canonicalCall();
        [, $application, $complaint] = $this->pendingComplaint($call, $members);
        $service = app(ComplaintService::class);

        $secretary = $members[G::ROLE_SEKRETAR]->user;
        $this->actingAs($secretary)
            ->post(route('evaluation.complaint-decision', [$application, $complaint]), ['decision' => 'accepted'])
            ->assertForbidden();
        $this->assertSame(G::CHAIR_REQUIRED_MESSAGE, $service->decide($complaint, $secretary, 'accepted', 'Obrazloženje.')->error);
        $this->assertSame(G::NOT_MEMBER_MESSAGE, $service->decide($complaint, $this->userWithRole('admin'), 'accepted', 'Obrazloženje.')->error);
        $this->assertNull($complaint->fresh()->decision);

        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $this->actingAs($chair)
            ->post(route('evaluation.complaint-decision', [$application, $complaint]), ['decision' => 'rejected'])
            ->assertSessionHasErrors(['decision_notes' => ComplaintService::DECISION_NOTES_REQUIRED_MESSAGE]);
        $this->assertSame(ComplaintService::DECISION_NOTES_REQUIRED_MESSAGE, $service->decide($complaint, $chair, 'rejected', '   ')->error);
        $this->assertNull($complaint->fresh()->decision);
        Mail::assertNotSent(CanonicalComplaintDecisionMail::class);

        $this->actingAs($chair)
            ->post(route('evaluation.complaint-decision', [$application, $complaint]), ['decision' => 'rejected', 'decision_notes' => 'Obrazloženje.'])
            ->assertSessionHas('success');
        $this->actingAs($chair)
            ->post(route('evaluation.complaint-decision', [$application, $complaint]), ['decision' => 'accepted', 'decision_notes' => 'Obrazloženje.'])
            ->assertSessionHasErrors(['error' => ComplaintService::ALREADY_DECIDED_MESSAGE]);

        $complaint->refresh();
        $this->assertSame('rejected', $complaint->decision);
        $this->assertThrowsImmutable(fn () => $complaint->update(['decision' => 'accepted']), ApplicationComplaint::IMMUTABLE_MESSAGE);
        $this->assertSame('rejected', $complaint->fresh()->decision);
        $this->assertSame(1, ApplicationCompletenessAssessment::query()->where('application_id', $application->id)->count());
        Mail::assertSent(CanonicalComplaintDecisionMail::class, 1);
    }

    // E — accepted complaint appends COMPLETE

    public function test_accepted_complaint_appends_complete_and_opens_scoring(): void
    {
        [$call, $members] = $this->canonicalCall();
        [$owner, $application, $complaint] = $this->pendingComplaint($call, $members);
        $original = ApplicationCompletenessAssessment::query()->where('application_id', $application->id)->sole();

        $this->actingAs($members[G::ROLE_PREDSJEDNIK]->user)
            ->post(route('evaluation.complaint-decision', [$application, $complaint]), ['decision' => 'accepted', 'decision_notes' => 'Prilog je bio u dosijeu.'])
            ->assertSessionHas('success');

        $history = ApplicationCompletenessAssessment::query()->where('application_id', $application->id)->orderBy('id')->get();
        $this->assertCount(2, $history);
        $this->assertSame($original->id, $history[0]->id);
        $this->assertSame('incomplete', $history[0]->outcome);
        $this->assertSame('complete', $history[1]->outcome);
        $this->assertSame($history[0]->evidence_fingerprint, $history[1]->evidence_fingerprint);
        $this->assertSame(CanonicalReviewState::COMPLAINT_RESOLVED, CanonicalReviewState::resolve($application->fresh()));
        $this->assertTrue(app(G::class)->scoringStageOpen($application->fresh()));
        $this->assertSame('submitted', $application->fresh()->status);

        $this->actingAs($owner)->get(route('applications.canonical-review.show', $application))
            ->assertOk()
            ->assertSee('data-status="'.ComplaintService::STATUS_COMPLAINT_ACCEPTED.'"', false)
            ->assertSee('nastavlja postupak u fazi bodovanja');
    }

    // F — notifications after commit, rollback, duplicates, canonical wording

    public function test_incomplete_notice_is_sent_only_after_commit_with_complaint_link(): void
    {
        [$call, $members] = $this->canonicalCall();
        $application = $this->finalApplication($call, ['founder_name' => 'Zamrznuti Podnosilac']);
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;

        DB::transaction(function () use ($application, $chair) {
            $result = app(CompletenessAssessmentService::class)->record($application, $chair, 'incomplete', 'Nedostaje biznis plan u PDF formatu.');
            $this->assertTrue($result->succeeded, (string) $result->error);
            Mail::assertNothingSent();
        });

        $url = route('applications.canonical-review.show', $application);
        Mail::assertSent(IncompleteDocumentationNoticeMail::class, 1);
        Mail::assertSent(IncompleteDocumentationNoticeMail::class, function (IncompleteDocumentationNoticeMail $mail) use ($application, $url) {
            $html = $mail->render();

            return $mail->hasTo($application->user->email)
                && $mail->complaintUrl === $url
                && $mail->complaintDeadline?->format('Y-m-d H:i:s') === '2026-09-28 10:00:00'
                && str_contains($html, $url)
                && str_contains($html, '28.09.2026. 10:00:00')
                && str_contains($html, 'Nedostaje biznis plan u PDF formatu.')
                && str_contains($html, 'Zamrznuti Podnosilac')
                && ! preg_match('/žensk|omladin|Obrazac 3/iu', $html);
        });
    }

    public function test_rollback_sends_no_notice_and_duplicate_request_sends_one(): void
    {
        [$call, $members] = $this->canonicalCall();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $application = $this->finalApplication($call);

        try {
            DB::transaction(function () use ($application, $chair) {
                app(CompletenessAssessmentService::class)->record($application, $chair, 'incomplete', 'Razlog.');
                throw new \DomainException('rollback');
            });
        } catch (\DomainException) {
        }

        Mail::assertNothingSent();
        $this->assertSame(0, ApplicationCompletenessAssessment::query()->count());

        foreach ([1, 2] as $attempt) {
            $this->actingAs($chair)->post(route('evaluation.completeness', $application), ['outcome' => 'incomplete', 'notes' => 'Razlog '.$attempt]);
        }

        $this->assertSame(1, ApplicationCompletenessAssessment::query()->count());
        Mail::assertSent(IncompleteDocumentationNoticeMail::class, 1);
    }

    public function test_complaint_decision_mail_after_commit_with_canonical_wording(): void
    {
        [$call, $members] = $this->canonicalCall();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        [$acceptedOwner, $accepted, $acceptedComplaint] = $this->pendingComplaint($call, $members);
        [$rejectedOwner, $rejected, $rejectedComplaint] = $this->pendingComplaint($call, $members);
        [, , $rolledBackComplaint] = $this->pendingComplaint($call, $members);
        Mail::fake();

        try {
            DB::transaction(function () use ($rolledBackComplaint, $chair) {
                $this->assertTrue(app(ComplaintService::class)->decide($rolledBackComplaint, $chair, 'accepted', 'Obrazloženje.')->succeeded);
                throw new \DomainException('rollback');
            });
        } catch (\DomainException) {
        }
        Mail::assertNothingSent();
        $this->assertNull($rolledBackComplaint->fresh()->decision);

        $this->actingAs($chair)->post(route('evaluation.complaint-decision', [$accepted, $acceptedComplaint]), [
            'decision' => 'accepted',
            'decision_notes' => 'Prilog je bio dostavljen u roku.',
        ])->assertSessionHas('success');
        $this->actingAs($chair)->post(route('evaluation.complaint-decision', [$rejected, $rejectedComplaint]), [
            'decision' => 'rejected',
            'decision_notes' => 'Potvrda poreske uprave nije dostavljena.',
        ])->assertSessionHas('success');
        $this->actingAs($chair)->post(route('evaluation.complaint-decision', [$rejected, $rejectedComplaint]), ['decision' => 'accepted', 'decision_notes' => 'Obrazloženje.'])
            ->assertSessionHasErrors('error');

        Mail::assertSent(CanonicalComplaintDecisionMail::class, 2);
        Mail::assertSent(CanonicalComplaintDecisionMail::class, function (CanonicalComplaintDecisionMail $mail) use ($acceptedOwner, $accepted, $call, $chair) {
            $html = $mail->render();

            return $mail->hasTo($acceptedOwner->email)
                && $mail->accepted()
                && str_contains($html, $call->title)
                && str_contains($html, 'redni broj '.$accepted->redni_broj)
                && str_contains($html, 'prihvaćen')
                && str_contains($html, 'nastavlja postupak u fazi bodovanja')
                && str_contains($html, route('applications.canonical-review.show', $accepted))
                && $mail->decisionNotes === 'Prilog je bio dostavljen u roku.'
                && str_contains($html, 'Prilog je bio dostavljen u roku.')
                && ! str_contains($html, $chair->name)
                && ! preg_match('/žensk|omladin|Obrazac 3/iu', $html);
        });
        Mail::assertSent(CanonicalComplaintDecisionMail::class, function (CanonicalComplaintDecisionMail $mail) use ($rejectedOwner) {
            $html = $mail->render();

            return $mail->hasTo($rejectedOwner->email)
                && ! $mail->accepted()
                && str_contains($html, 'odbijen')
                && str_contains($html, 'ne učestvuje u daljem bodovanju')
                && $mail->decisionNotes === 'Potvrda poreske uprave nije dostavljena.'
                && str_contains($html, 'Potvrda poreske uprave nije dostavljena.');
        });
    }

    // G — applicant-facing review status

    public function test_applicant_status_states_without_internal_data(): void
    {
        [$call, $members] = $this->canonicalCall();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $application = $this->finalApplication($call);
        $owner = $application->user;
        $status = fn () => $this->actingAs($owner)->get(route('applications.canonical-review.show', $application))->assertOk();

        $status()->assertSee('data-status="'.ComplaintService::STATUS_AWAITING_REVIEW.'"', false);

        $this->actingAs($chair)->post(route('evaluation.completeness', $application), ['outcome' => 'incomplete', 'notes' => 'Nedostaje prilog A.']);
        $status()->assertSee('data-status="'.ComplaintService::STATUS_COMPLAINT_AVAILABLE.'"', false)->assertSee('Nedostaje prilog A.');

        Carbon::setTestNow(Carbon::parse('2026-09-26 10:00:00', self::TZ));
        $this->actingAs($owner)->post(route('applications.complaint', $application), ['body' => 'Prilog A je predat.']);
        $status()->assertSee('data-status="'.ComplaintService::STATUS_COMPLAINT_SUBMITTED.'"', false)->assertDontSee('name="body"', false);

        $complaint = ApplicationComplaint::query()->sole();
        $status()->assertDontSee('Prilog A nije pronađen u dosijeu.');
        $this->actingAs($chair)->post(route('evaluation.complaint-decision', [$application, $complaint]), [
            'decision' => 'rejected',
            'decision_notes' => 'Prilog A nije pronađen u dosijeu.',
        ]);
        $fingerprint = ApplicationCompletenessAssessment::query()->sole()->evidence_fingerprint;
        $status()
            ->assertSee('data-status="'.ComplaintService::STATUS_COMPLAINT_REJECTED.'"', false)
            ->assertSee('ne učestvuje u daljem bodovanju')
            ->assertSee('Prilog A nije pronađen u dosijeu.')
            ->assertDontSee($chair->name)
            ->assertDontSee($fingerprint);

        $complete = $this->finalApplication($call);
        $this->actingAs($chair)->post(route('evaluation.completeness', $complete), ['outcome' => 'complete', 'notes' => 'Interna bilješka.']);
        $this->actingAs($complete->user)->get(route('applications.canonical-review.show', $complete))->assertOk()
            ->assertSee('data-status="'.ComplaintService::STATUS_COMPLETE.'"', false)
            ->assertSee('nastavlja postupak u fazi bodovanja')
            ->assertDontSee('Interna bilješka.');

        DB::table('competitions')->where('id', $call->id)->update(['canonical_ranking_formed_at' => now()]);
        $this->actingAs($complete->user)->get(route('applications.canonical-review.show', $complete))->assertOk()
            ->assertSee('formirana je preliminarna rang lista');

        $this->actingAs($owner)->get(route('applications.show', $application))->assertOk()
            ->assertSee(route('applications.canonical-review.show', $application));
    }

    // G — shared document view uses canonical governance membership

    public function test_document_view_uses_current_canonical_membership(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('frozen/dokaz.pdf', '%PDF-1.4 test');
        [$call, $members] = $this->canonicalCall();
        $application = $this->finalApplication($call);
        $document = ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'Dokaz',
            'file_path' => 'frozen/dokaz.pdf',
            'document_type' => 'ostalo',
        ]);
        $url = route('applications.document.view', [$application, $document]);

        $this->actingAs($members[G::ROLE_SPOLJASNJI]->user)->get($url)->assertOk();
        $this->actingAs($this->komisijaUser('van-komisije'))->get($url)->assertForbidden();
        [, $foreignMembers] = $this->canonicalCall();
        $this->actingAs($foreignMembers[G::ROLE_PREDSJEDNIK]->user)->get($url)->assertForbidden();

        $replaced = $members[G::ROLE_SEKRETAR];
        $replacement = $this->replaceMember($replaced);
        $this->actingAs($replaced->user)->get($url)->assertForbidden();
        $this->actingAs($replacement->user)->get($url)->assertOk();
        $this->actingAs($application->user)->get($url)->assertOk();
    }

    // H — legacy isolation

    public function test_canonical_cannot_mutate_through_legacy_obrazac3_or_prigovor(): void
    {
        [$call, $members] = $this->canonicalCall();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        [$owner, $application] = $this->incompleteApplication($call, $members);
        Mail::fake();

        $this->actingAs($chair)->post(route('evaluation.eliminatory.store', $application), ['answers' => [1 => 'da']])
            ->assertForbidden();
        $this->actingAs($chair)->post(route('evaluation.eliminatory.confirm', $application), ['answers' => [1 => 'ne'], 'confirmation_acknowledged' => 1])
            ->assertForbidden();
        $this->actingAs($chair)->post(route('evaluation.prigovor.decide', $application), ['odluka' => 'prihvacen', 'decision_note' => 'X'])
            ->assertForbidden();
        $this->actingAs($owner)->post(route('applications.prigovor.store', $application), ['obrazlozenje' => 'Legacy prigovor'])
            ->assertForbidden();

        $this->assertSame(0, ApplicationEliminatoryCheck::query()->count());
        $this->assertSame(0, ApplicationEliminatoryNotice::query()->count());
        $this->assertSame(0, ApplicationPrigovor::query()->count());
        $this->assertSame(0, ApplicationComplaint::query()->count());
        Mail::assertNothingSent();

        try {
            EliminatoryProfileConfig::for(CompetitionProfileResolver::TYPE_CANONICAL);
            $this->fail('Canonical must not fall back to the Women eliminatory config.');
        } catch (\DomainException $exception) {
            $this->assertSame(LegacyCommissionFlowGuard::BLOCKED_MESSAGE, $exception->getMessage());
        }
    }

    // I — Women/Youth unchanged

    public function test_women_and_youth_do_not_use_canonical_completeness_complaint_surface(): void
    {
        foreach ([CompetitionProfileResolver::TYPE_WOMEN, CompetitionProfileResolver::TYPE_YOUTH] as $type) {
            [$chair, $application] = $this->legacyApplication($type);

            $this->actingAs($chair)->post(route('evaluation.completeness', $application), ['outcome' => 'incomplete', 'notes' => 'X'])
                ->assertForbidden();
            $this->actingAs($chair)->get(route('evaluation.canonical-review.show', $application))->assertForbidden();
            $this->actingAs($chair)->get(route('evaluation.canonical-review.index', $application->competition))->assertForbidden();
            $this->actingAs($this->userWithRole('admin'))->get(route('admin.competitions.canonical-review.index', $application->competition))
                ->assertForbidden();
            $this->actingAs($application->user)->get(route('applications.canonical-review.show', $application))->assertNotFound();
            $this->actingAs($application->user)->post(route('applications.complaint', $application), ['body' => 'X'])
                ->assertSessionHasErrors('error');

            $this->assertFalse(CompetitionProfileResolver::resolve($type)->completenessRuntimeActive());
            $this->assertTrue(LegacyCommissionFlowGuard::allows($application->competition));
            $this->assertSame($type, EliminatoryProfileConfig::for($type)->type);
        }

        $this->assertSame(0, ApplicationCompletenessAssessment::query()->count());
        $this->assertSame(0, ApplicationComplaint::query()->count());
        Mail::assertNothingSent();
    }

    // Helpers

    /**
     * @return array{0: Competition, 1: array<string, CommissionMember>}
     */
    private function canonicalCall(?User $chair = null, bool $signDeclarations = true): array
    {
        [$commission, $members] = $this->canonicalCommission($chair, '2026-01-01 00:00:00', $signDeclarations);
        $call = Competition::create([
            'title' => 'Kanonski poziv '.uniqid(),
            'type' => CompetitionProfileResolver::TYPE_CANONICAL,
            'status' => 'published',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'commission_id' => $commission->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-21',
            'published_at' => '2026-09-01 09:00:00',
        ]);
        $this->confirmOrdinarySession($call, Carbon::parse('2026-09-22 09:00:00', self::TZ));

        return [$call, $members];
    }

    private function finalApplication(Competition $call, array $overrides = []): Application
    {
        static $redniBroj = 0;
        $owner = $this->userWithRole('korisnik');
        $application = Application::create(array_merge([
            'competition_id' => $call->id,
            'user_id' => $owner->id,
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'business_plan_name' => 'Plan '.uniqid(),
            'founder_name' => 'Podnosilac',
            'submitted_at' => now(),
            'redni_broj' => ++$redniBroj,
        ], $overrides));
        FinalApplicationClaim::query()->create([
            'user_id' => $owner->id,
            'competition_id' => $call->id,
            'application_id' => $application->id,
            'claimed_at' => now(),
        ]);

        return $application->fresh(['competition', 'finalClaim', 'user']);
    }

    /**
     * @param  array<string, CommissionMember>  $members
     * @return array{0: User, 1: Application}
     */
    private function incompleteApplication(Competition $call, array $members, string $reason = 'Nedostaje prilog.'): array
    {
        $application = $this->finalApplication($call);
        $result = app(CompletenessAssessmentService::class)->record($application, $members[G::ROLE_PREDSJEDNIK]->user, 'incomplete', $reason);
        $this->assertTrue($result->succeeded, (string) $result->error);

        return [$application->user, $application->fresh(['competition', 'finalClaim', 'user'])];
    }

    /**
     * @param  array<string, CommissionMember>  $members
     * @return array{0: User, 1: Application, 2: ApplicationComplaint}
     */
    private function pendingComplaint(Competition $call, array $members, string $body = 'Prigovor.', ?string $submitAt = null): array
    {
        [$owner, $application] = $this->incompleteApplication($call, $members);
        if ($submitAt !== null) {
            Carbon::setTestNow(Carbon::parse($submitAt, self::TZ));
        }
        $result = app(ComplaintService::class)->submit($application, $owner, $body);
        $this->assertTrue($result->succeeded, (string) $result->error);

        return [$owner, $application, ApplicationComplaint::query()->findOrFail($result->recordId)];
    }

    /**
     * @return array{0: User, 1: Application}
     */
    private function legacyApplication(string $type): array
    {
        $chair = $this->komisijaUser('legacy-'.$type);
        $commission = Commission::create([
            'name' => 'Komisija '.$type.' '.uniqid(),
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
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-21',
            'published_at' => '2026-09-01 09:00:00',
        ]);
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return [$chair, $application->fresh(['competition', 'user'])];
    }

    private function replaceMember(CommissionMember $old): CommissionMember
    {
        $service = app(CanonicalCommissionMembershipService::class);
        $this->assertTrue($service->vacate($old, 'resigned', now()->subMinute(), $this->canonicalActor())->succeeded);
        $result = $service->replace($old, $this->komisijaUser('zamjena'), 'Rješenje o zamjeni', now(), $this->canonicalActor());
        $this->assertTrue($result->succeeded, (string) $result->error);
        $replacement = CommissionMember::query()->findOrFail($result->recordId);
        $this->signDeclarations($replacement);

        return $replacement->fresh(['user']);
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function assertThrowsImmutable(\Closure $mutation, string $message): void
    {
        try {
            $mutation();
            $this->fail('Expected immutability guard: '.$message);
        } catch (\LogicException $exception) {
            $this->assertSame($message, $exception->getMessage());
        }
    }
}
