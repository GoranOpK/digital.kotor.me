<?php

namespace Tests\Feature\Kn;

use App\Models\Application;
use App\Models\ApplicationComplaint;
use App\Models\ApplicationCompletenessAssessment;
use App\Models\CanonicalAdministrativeEvent;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\CommissionSession;
use App\Models\Competition;
use App\Models\CompetitionPublicationClaim;
use App\Models\EvaluationScore;
use App\Models\FinalApplicationClaim;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\CanonicalIndividualScoringService as S;
use App\Services\Competitions\CanonicalTestApplicationPhaseService as Phase;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance as G;
use App\Services\Competitions\Commission\CanonicalCommissionSessionService;
use App\Services\Competitions\CompletenessAssessmentService;
use App\Services\Competitions\Decision\CanonicalCloseService;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalLegalForm;
use App\Support\CompetitionProgramCatalog;
use App\Support\UserType;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Kn\Concerns\BuildsCanonicalCommission;
use Tests\TestCase;

/**
 * Irreversible close of the MMSP test application window.
 * Ranking still waits for a complete scoring cycle. Other programmes stay unchanged.
 */
class CanonicalTestApplicationPhaseTest extends TestCase
{
    use BuildsCanonicalCommission;
    use RefreshDatabase;

    private const CANONICAL = CompetitionProfileResolver::TYPE_CANONICAL;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-10-23 12:00:00', 'Europe/Belgrade'));
        CompetitionProgramCatalog::overrideStatusForTests(self::CANONICAL, CompetitionProgramCatalog::STATUS_ACTIVE);
    }

    protected function tearDown(): void
    {
        CompetitionProgramCatalog::clearTestOverrides();
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_and_chair_see_the_warning_and_can_close_once(): void
    {
        [$members, $competition] = $this->openPreview();
        $admin = $this->canonicalActor();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;

        $this->actingAs($admin)
            ->get(route('admin.competitions.show', $competition))
            ->assertOk()
            ->assertSee('Upozorenje.')
            ->assertSee('Zatvaranje je nepovratno')
            ->assertSee('Zatvori testnu prijavnu fazu');

        $this->actingAs($chair)
            ->get(route('admin.competitions.show', $competition))
            ->assertOk()
            ->assertSee('Zatvori testnu prijavnu fazu');

        $this->actingAs($admin)
            ->from(route('admin.competitions.show', $competition))
            ->post(route('admin.competitions.test-application-phase.close', $competition), [])
            ->assertRedirect(route('admin.competitions.show', $competition))
            ->assertSessionHasErrors('confirm_test_application_phase_close');
        $this->assertNull($competition->fresh()->canonical_test_applications_closed_at);

        $this->actingAs($chair)
            ->post(route('admin.competitions.test-application-phase.close', $competition), [
                'confirm_test_application_phase_close' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $closed = $competition->fresh();
        $this->assertTrue($closed->isCanonicalTestApplicationPhaseClosed());
        $this->assertFalse($closed->is_open);
        $this->assertSame('draft', $closed->status);
        $this->assertTrue($closed->is_test_preview);
        $this->assertSame('2026-10-22', $closed->start_date->toDateString());
        $this->assertSame(20, (int) $closed->deadline_days);
        $this->assertSame('11.11.2026 23:59', $closed->deadline->format('d.m.Y H:i'));
        $this->assertSame($chair->id, CanonicalAdministrativeEvent::query()
            ->where('competition_id', $closed->id)
            ->where('action', CanonicalAdministrativeEvent::TEST_APPLICATIONS_CLOSED)
            ->sole()->actor_user_id);

        $this->actingAs($admin)
            ->get(route('admin.competitions.show', $closed))
            ->assertOk()
            ->assertSee($chair->name)
            ->assertDontSee('Zatvori testnu prijavnu fazu');

        $again = app(Phase::class)->close($closed, $admin, true);
        $this->assertFalse($again->succeeded);
        $this->assertSame(Phase::ALREADY_CLOSED_MESSAGE, $again->error);
        $this->assertSame(1, CanonicalAdministrativeEvent::query()->where('competition_id', $closed->id)->where('action', CanonicalAdministrativeEvent::TEST_APPLICATIONS_CLOSED)->count());
        $this->assertSame(
            $closed->canonical_test_applications_closed_at->toIso8601String(),
            $closed->fresh()->canonical_test_applications_closed_at->toIso8601String(),
        );
    }

    public function test_secretary_applicant_and_other_programmes_cannot_close_the_phase(): void
    {
        [$members, $competition] = $this->openPreview();
        $secretary = $members[G::ROLE_SEKRETAR]->user;
        $applicant = $this->owner();

        $this->actingAs($secretary)
            ->from(route('admin.competitions.show', $competition))
            ->post(route('admin.competitions.test-application-phase.close', $competition), [
                'confirm_test_application_phase_close' => '1',
            ])
            ->assertSessionHasErrors(['test_application_phase' => Phase::UNAUTHORIZED_MESSAGE]);
        $this->actingAs($secretary)
            ->get(route('admin.competitions.show', $competition))
            ->assertOk()
            ->assertSee(Phase::UNAUTHORIZED_MESSAGE)
            ->assertDontSee('Zatvori testnu prijavnu fazu');
        $this->actingAs($applicant)
            ->post(route('admin.competitions.test-application-phase.close', $competition), [
                'confirm_test_application_phase_close' => '1',
            ])
            ->assertForbidden();

        $admin = $this->canonicalActor();
        foreach ([
            $this->plainCall('zensko', 'published', false),
            $this->plainCall('omladinsko', 'published', false),
            $this->plainCall(self::CANONICAL, 'published', false),
            $this->plainCall(self::CANONICAL, 'published', true),
            $this->plainCall(self::CANONICAL, 'draft', false),
        ] as $other) {
            $openBefore = $other->is_open;
            $result = app(Phase::class)->close($other, $admin, true);
            $this->assertFalse($result->succeeded);
            $this->assertSame(Phase::NOT_PREVIEW_MESSAGE, $result->error);
            $fresh = $other->fresh();
            $this->assertNull($fresh->canonical_test_applications_closed_at);
            $this->assertSame($openBefore, $fresh->is_open);
            $this->assertSame($other->status, $fresh->status);
            $this->assertSame((bool) $other->is_test_preview, (bool) $fresh->is_test_preview);
        }

        $this->assertNull($competition->fresh()->canonical_test_applications_closed_at);
        $this->assertTrue($competition->fresh()->is_open);
        $this->assertSame(0, CompetitionPublicationClaim::query()->count());
    }

    public function test_http_cannot_start_edit_or_submit_a_draft_after_close(): void
    {
        [, $competition] = $this->openPreview();
        $owner = $this->owner();
        $this->actingAs($owner)
            ->post(route('applications.canonical.start', $competition), ['legal_form' => CanonicalLegalForm::PREDUZETNIK])
            ->assertSessionHasNoErrors();
        $draft = Application::query()->where('user_id', $owner->id)->sole();
        $this->assertSame('draft', $draft->status);

        $this->closeAsAdmin($competition);

        $this->actingAs($owner)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->assertSee('Testna prijavna faza je zatvorena.')
            ->assertSee('11.11.2026 23:59')
            ->assertDontSee('rok je istekao');

        $other = $this->owner();
        $this->actingAs($other)
            ->from(route('competitions.show', $competition))
            ->post(route('applications.canonical.start', $competition), ['legal_form' => CanonicalLegalForm::PREDUZETNIK])
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHasErrors(['error' => Phase::WINDOW_CLOSED_MESSAGE]);
        $this->actingAs($other)
            ->from(route('competitions.show', $competition))
            ->post(route('applications.start', $competition))
            ->assertSessionHasErrors(['error' => Phase::WINDOW_CLOSED_MESSAGE]);
        $this->assertSame(1, Application::query()->where('competition_id', $competition->id)->count());

        $before = $draft->fresh()->getAttributes();
        $this->actingAs($owner)
            ->from(route('applications.canonical-p1.edit', $draft))
            ->put(route('applications.canonical-p1.update', $draft), ['person_name' => 'Izmjena poslije zatvaranja'])
            ->assertSessionHasErrors(['error' => 'Prijava je zaključana i Obrazac P1 više nije moguće mijenjati.']);
        $this->actingAs($owner)
            ->from(route('applications.show', $draft))
            ->post(route('applications.final-submit', $draft))
            ->assertSessionHasErrors(['error' => Phase::WINDOW_CLOSED_MESSAGE]);
        $this->assertSame($before, $draft->fresh()->getAttributes());
        $this->assertSame('draft', $draft->fresh()->status);
    }

    public function test_closing_does_not_change_submitted_work_and_ranking_waits_for_the_full_cycle(): void
    {
        [$members, $competition, $application] = $this->scoredPreview(complete: false);
        $complaint = $this->complaint($application, $members[G::ROLE_PREDSJEDNIK]->user);
        $interview = CommissionSession::query()
            ->where('competition_id', $competition->id)
            ->where('canonical_purpose', G::PURPOSE_INTERVIEW)
            ->sole();
        $dueBefore = $competition->profile()->thirdSessionDueAt($interview->completed_at)->format('Y-m-d H:i:s');
        $scoresBefore = $this->scoreRows($application->id);
        $applicationBefore = $this->applicationSnapshot($application->fresh());
        $complaintBefore = $complaint->fresh()->getAttributes();
        $sessionBefore = $this->sessionSnapshot($interview->fresh());

        $this->assertNotNull(app(S::class)->canonicalCycleBlockReason($competition->fresh()));
        $this->closeAsAdmin($competition);

        $this->assertSame($scoresBefore, $this->scoreRows(null));
        $this->assertSame($applicationBefore, $this->applicationSnapshot($application->fresh()));
        $this->assertSame($complaintBefore, $complaint->fresh()->getAttributes());
        $this->assertSame($sessionBefore, $this->sessionSnapshot($interview->fresh()));
        $this->assertSame($dueBefore, $competition->fresh()->profile()->thirdSessionDueAt($interview->fresh()->completed_at)->format('Y-m-d H:i:s'));
        $this->assertNull($competition->fresh()->canonical_ranking_formed_at);
        $this->assertFalse(app(S::class)->persistCanonicalPreliminaryRankingIfReady($competition->fresh()));
        $this->assertNotNull(app(S::class)->canonicalCycleBlockReason($competition->fresh()));
        $this->assertSame(CanonicalCloseService::DEADLINE_MESSAGE, app(CanonicalCloseService::class)->blockReason($competition->fresh()));

        [$readyMembers, $readyCompetition, $readyApplication] = $this->scoredPreview(complete: true);
        $readyScores = $this->scoreRows($readyApplication->id);
        $this->assertNotNull(app(S::class)->canonicalCycleBlockReason($readyCompetition->fresh()));
        $this->assertNull($readyCompetition->fresh()->canonical_ranking_formed_at);
        $this->closeAsAdmin($readyCompetition);
        $this->assertSame($readyScores, $this->scoreRows($readyApplication->id));
        $this->assertNull($readyCompetition->fresh()->canonical_ranking_formed_at);
        $this->assertNull(app(S::class)->canonicalCycleBlockReason($readyCompetition->fresh()));
        $this->assertTrue(app(S::class)->persistCanonicalPreliminaryRankingIfReady($readyCompetition->fresh()));
        $this->assertNotNull($readyCompetition->fresh()->canonical_ranking_formed_at);
        $this->assertNotNull($readyApplication->fresh()->ranking_position);
        $this->assertSame($readyScores, $this->scoreRows($readyApplication->id));
        $this->assertSame('submitted', $readyApplication->fresh()->status);
    }

    public function test_admin_save_cannot_erase_the_mark_or_reopen_applications(): void
    {
        [, $competition] = $this->openPreview();
        UpNumber::query()->create(['competition_id' => $competition->id, 'number' => 'UP-TEST-3']);
        $this->closeAsAdmin($competition);
        $closedAt = $competition->fresh()->canonical_test_applications_closed_at->toIso8601String();
        $admin = $this->canonicalActor();

        $this->actingAs($admin)
            ->from(route('admin.competitions.edit', $competition))
            ->put(route('admin.competitions.update', $competition), $this->editPayload($competition, [
                'is_test_preview' => '0',
            ]))
            ->assertSessionHasErrors('is_test_preview');

        $kept = $competition->fresh();
        $this->assertSame('draft', $kept->status);
        $this->assertTrue((bool) $kept->is_test_preview);
        $this->assertSame('2026-10-22', $kept->start_date->toDateString());
        $this->assertSame(20, (int) $kept->deadline_days);
        $this->assertSame($closedAt, $kept->canonical_test_applications_closed_at->toIso8601String());
        $this->assertFalse($kept->is_open);

        $this->actingAs($admin)
            ->put(route('admin.competitions.update', $competition), $this->editPayload($competition, [
                'title' => 'MMSP TEST 3 i dalje nacrt',
                'is_test_preview' => '1',
            ]))
            ->assertRedirect(route('admin.competitions.show', $competition));

        $saved = $competition->fresh();
        $this->assertSame('MMSP TEST 3 i dalje nacrt', $saved->title);
        $this->assertSame('draft', $saved->status);
        $this->assertTrue((bool) $saved->is_test_preview);
        $this->assertSame($closedAt, $saved->canonical_test_applications_closed_at->toIso8601String());
        $this->assertSame('2026-10-22', $saved->start_date->toDateString());
        $this->assertSame(20, (int) $saved->deadline_days);
        $this->assertFalse($saved->is_open);
        $this->assertSame('11.11.2026 23:59', $saved->deadline->format('d.m.Y H:i'));

        $this->actingAs($admin)
            ->from(route('admin.competitions.show', $competition))
            ->post(route('admin.competitions.publish', $competition))
            ->assertSessionHasErrors(['error' => Phase::PUBLICATION_BLOCKED_MESSAGE]);
        $this->assertSame('draft', $competition->fresh()->status);
        $this->assertSame(0, CompetitionPublicationClaim::query()->where('competition_id', $competition->id)->count());
    }

    public function test_removing_preview_or_publishing_in_the_record_does_not_reopen_the_window_or_unlock_ranking(): void
    {
        [, $competition, $application] = $this->scoredPreview(complete: true);
        $this->closeAsAdmin($competition);
        $this->assertNull(app(S::class)->canonicalCycleBlockReason($competition->fresh()));

        $competition->forceFill(['is_test_preview' => false])->save();
        $dropped = $competition->fresh();
        $this->assertFalse($dropped->isCanonicalTestPreview());
        $this->assertFalse($dropped->isCanonicalTestApplicationPhaseClosed());
        $this->assertTrue($dropped->canonicalTestApplicationWindowIsClosed());
        $this->assertFalse($dropped->is_open);
        $this->assertNotNull(app(S::class)->canonicalCycleBlockReason($dropped));
        $this->assertNull($dropped->canonical_ranking_formed_at);

        $dropped->forceFill([
            'status' => 'published',
            'published_at' => now(),
        ])->save();
        $published = $dropped->fresh();
        $this->assertFalse($published->is_open);
        $this->assertNotNull(app(S::class)->canonicalCycleBlockReason($published));
        $owner = User::query()->findOrFail($application->user_id);
        $this->actingAs($owner)
            ->from(route('competitions.show', $published))
            ->post(route('applications.canonical.start', $published), ['legal_form' => CanonicalLegalForm::PREDUZETNIK])
            ->assertSessionHasErrors(['error' => Phase::WINDOW_CLOSED_MESSAGE]);
        $this->assertSame(0, CompetitionPublicationClaim::query()->where('competition_id', $published->id)->count());

        $untouched = $this->plainCall(self::CANONICAL, 'draft', true);
        $this->assertTrue($untouched->is_open);
        $this->assertNull($untouched->canonical_test_applications_closed_at);
    }

    public function test_closed_phase_with_a_future_start_is_shown_as_closed_not_upcoming(): void
    {
        [$members, $closed] = $this->openPreview();
        $closed->forceFill([
            'title' => 'MMSP zatvorena buduca faza',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-21',
        ])->save();
        $this->assertTrue($closed->fresh()->is_upcoming);
        $this->closeAsAdmin($closed);
        $closed = $closed->fresh();
        $this->assertFalse($closed->is_upcoming);
        $this->assertFalse($closed->is_open);
        $this->assertTrue($closed->isCanonicalTestApplicationPhaseClosed());

        [, $stillOpen] = $this->openPreview();
        $stillOpen->forceFill([
            'title' => 'MMSP jos otvoren buduci',
            'start_date' => '2026-12-01',
        ])->save();
        $futureWomen = $this->plainCall('zensko', 'published', false);
        $futureWomen->forceFill(['title' => 'ZP buduci poziv', 'start_date' => '2026-12-01'])->save();
        $futureYouth = $this->plainCall('omladinsko', 'published', false);
        $futureYouth->forceFill(['title' => 'ML buduci poziv', 'start_date' => '2026-12-01'])->save();
        $regular = $this->plainCall(self::CANONICAL, 'published', false);
        $regular->forceFill(['title' => 'MMSP redovni buduci', 'start_date' => '2026-12-01'])->save();
        $openWomen = $this->plainCall('zensko', 'published', false);
        $openWomen->forceFill(['title' => 'ZP otvoren poziv'])->save();

        $this->assertTrue($stillOpen->fresh()->is_upcoming);
        $this->assertTrue($futureWomen->fresh()->is_upcoming);
        $this->assertTrue($futureYouth->fresh()->is_upcoming);
        $this->assertTrue($regular->fresh()->is_upcoming);
        $this->assertFalse($openWomen->fresh()->is_upcoming);

        $visitor = $this->owner();
        $this->actingAs($visitor)
            ->get(route('competitions.index'))
            ->assertOk()
            ->assertSee('MMSP zatvorena buduca faza')
            ->assertSee('Zatvoren za prijave')
            ->assertSee('Testna prijavna faza je zatvorena.')
            ->assertSee('ZP otvoren poziv')
            ->assertSee('Otvoren')
            ->assertDontSee('Uskoro počinje')
            ->assertDontSee('MMSP jos otvoren buduci')
            ->assertDontSee('ZP buduci poziv')
            ->assertDontSee('ML buduci poziv')
            ->assertDontSee('MMSP redovni buduci');

        $this->actingAs($visitor)
            ->get(route('competitions.show', $closed))
            ->assertOk()
            ->assertSee('Testna prijavna faza je zatvorena.')
            ->assertSee('21.12.2026 23:59')
            ->assertSee('Zatvoren za prijave')
            ->assertDontSee('Konkurs uskoro počinje')
            ->assertDontSee('Prijave će biti moguće od')
            ->assertDontSee('rok je istekao');
        $this->actingAs($visitor)->get(route('competitions.show', $stillOpen))->assertNotFound();
        $this->actingAs($visitor)->get(route('competitions.show', $futureWomen))->assertNotFound();
        $this->actingAs($visitor)->get(route('competitions.show', $futureYouth))->assertNotFound();
        $this->actingAs($visitor)->get(route('competitions.show', $regular))->assertNotFound();
        $this->actingAs($visitor)
            ->get(route('competitions.show', $openWomen))
            ->assertOk()
            ->assertDontSee('Konkurs uskoro počinje');

        $this->actingAs($members[G::ROLE_PREDSJEDNIK]->user)
            ->get(route('admin.competitions.show', $closed))
            ->assertOk()
            ->assertSee('Zatvoren za prijave')
            ->assertSee('21.12.2026 23:59')
            ->assertDontSee('Uskoro počinje');
    }

    public function test_closed_phase_keeps_start_date_and_deadline_days_on_admin_save(): void
    {
        [, $competition] = $this->openPreview();
        $competition->forceFill([
            'deadline_days' => 15,
            'end_date' => '2026-11-06',
        ])->save();
        UpNumber::query()->create(['competition_id' => $competition->id, 'number' => 'UP-TEST-3']);
        $originalTitle = $competition->title;
        $this->closeAsAdmin($competition);
        $admin = $this->canonicalActor();

        $this->actingAs($admin)
            ->get(route('admin.competitions.edit', $competition))
            ->assertOk()
            ->assertSee('Datum početka je zaključan')
            ->assertSee('Rok za prijave je zaključan');

        $this->actingAs($admin)
            ->from(route('admin.competitions.edit', $competition))
            ->put(route('admin.competitions.update', $competition), $this->editPayload($competition, [
                'title' => 'Pokusaj pomjeranja roka',
                'start_date' => '2026-12-01',
            ]))
            ->assertSessionHasErrors(['start_date' => Phase::DATES_LOCKED_MESSAGE]);

        $kept = $competition->fresh();
        $this->assertSame($originalTitle, $kept->title);
        $this->assertSame('draft', $kept->status);
        $this->assertTrue((bool) $kept->is_test_preview);
        $this->assertSame('2026-10-22', $kept->start_date->toDateString());
        $this->assertSame('2026-11-06', $kept->end_date->toDateString());
        $this->assertSame(15, (int) $kept->deadline_days);
        $this->assertSame('06.11.2026 23:59', $kept->deadline->format('d.m.Y H:i'));

        $this->actingAs($admin)
            ->put(route('admin.competitions.update', $competition), $this->editPayload($competition, [
                'title' => 'Naslov bez pomjeranja roka',
                'start_date' => '2026-10-22',
            ]))
            ->assertRedirect(route('admin.competitions.show', $competition));

        $saved = $competition->fresh();
        $this->assertSame('Naslov bez pomjeranja roka', $saved->title);
        $this->assertSame('2026-10-22', $saved->start_date->toDateString());
        $this->assertSame('2026-11-06', $saved->end_date->toDateString());
        $this->assertSame(15, (int) $saved->deadline_days);
        $this->assertFalse($saved->is_open);

        $women = $this->plainCall('zensko', 'draft', false);
        $women->forceFill([
            'start_date' => '2026-10-22',
            'end_date' => '2026-11-06',
            'deadline_days' => 15,
        ])->save();
        UpNumber::query()->create(['competition_id' => $women->id, 'number' => 'UP-ZP-1']);
        $this->actingAs($admin)
            ->put(route('admin.competitions.update', $women), $this->editPayload($women, [
                'up_number' => 'UP-ZP-1',
                'start_date' => '2026-11-01',
                'is_test_preview' => '0',
            ]))
            ->assertRedirect(route('admin.competitions.show', $women));
        $moved = $women->fresh();
        $this->assertSame('2026-11-01', $moved->start_date->toDateString());
        $this->assertSame('2026-11-21', $moved->end_date->toDateString());
        $this->assertSame(20, (int) $moved->deadline_days);
        $this->assertNull($moved->canonical_test_applications_closed_at);
    }

    /**
     * @return array{0: array<string, CommissionMember>, 1: Competition}
     */
    private function openPreview(): array
    {
        [, $members] = $this->canonicalCommission();

        return [$members, $this->previewCompetition($members[G::ROLE_PREDSJEDNIK]->commission)];
    }

    private function previewCompetition(Commission $commission): Competition
    {
        return Competition::query()->create([
            'title' => 'MMSP TEST '.uniqid(),
            'type' => self::CANONICAL,
            'status' => 'draft',
            'is_test_preview' => true,
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'commission_id' => $commission->id,
            'start_date' => '2026-10-22',
            'end_date' => '2026-11-11',
        ])->fresh();
    }

    private function plainCall(string $type, string $status, bool $testPreview): Competition
    {
        return Competition::query()->create([
            'title' => $type.' '.$status.' '.uniqid(),
            'type' => $type,
            'status' => $status,
            'is_test_preview' => $testPreview,
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'call_number' => $type === 'omladinsko' ? 1 : null,
            'start_date' => '2026-10-22',
            'end_date' => '2026-11-11',
            'published_at' => $status === 'published' ? '2026-10-22 09:00:00' : null,
        ])->fresh();
    }

    /**
     * @return array{0: array<string, CommissionMember>, 1: Competition, 2: Application}
     */
    private function scoredPreview(bool $complete): array
    {
        [$members, $competition] = $this->openPreview();
        $application = $this->submittedApplication($competition);
        $this->confirmOrdinarySession($competition);
        $recorded = app(CompletenessAssessmentService::class)->record(
            $application,
            $members[G::ROLE_PREDSJEDNIK]->user,
            ApplicationCompletenessAssessment::COMPLETE,
        );
        $this->assertTrue($recorded->succeeded, (string) $recorded->error);
        $this->confirmInterview($competition, $members);

        $roles = $complete
            ? [G::ROLE_PREDSJEDNIK, G::ROLE_SEKRETAR, G::ROLE_SPOLJASNJI]
            : [G::ROLE_PREDSJEDNIK];
        foreach ($roles as $role) {
            $this->actingAs($members[$role]->user)
                ->post(route('evaluation.store', $application), $this->scorePayload())
                ->assertRedirect(route('evaluation.create', $application));
        }

        return [$members, $competition->fresh(), $application->fresh()];
    }

    private function submittedApplication(Competition $competition): Application
    {
        $owner = $this->owner();
        $application = Application::query()->create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'applicant_type' => 'preduzetnik',
            'status' => 'submitted',
            'business_plan_name' => 'Plan '.uniqid(),
            'founder_name' => 'Podnosilac',
            'submitted_at' => now(),
            'redni_broj' => 1,
        ]);
        FinalApplicationClaim::query()->create([
            'user_id' => $owner->id,
            'competition_id' => $competition->id,
            'application_id' => $application->id,
            'claimed_at' => now(),
        ]);

        return $application->fresh(['competition', 'finalClaim']);
    }

    private function complaint(Application $application, User $chair): ApplicationComplaint
    {
        $assessmentId = ApplicationCompletenessAssessment::query()->where('application_id', $application->id)->value('id');

        return ApplicationComplaint::query()->create([
            'application_id' => $application->id,
            'competition_id' => $application->competition_id,
            'completeness_assessment_id' => $assessmentId,
            'submitted_by_user_id' => $application->user_id,
            'body' => 'Prigovor ostaje neizmijenjen.',
            'submitted_at' => now()->subDay(),
            'decision_due_at' => now()->addDays(6),
            'decision' => ApplicationComplaint::REJECTED,
            'decided_at' => now()->subHour(),
            'decided_by_user_id' => $chair->id,
            'decision_late' => false,
        ]);
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function confirmInterview(Competition $competition, array $members): void
    {
        $service = app(CanonicalCommissionSessionService::class);
        $result = $service->record($competition, $members[G::ROLE_PREDSJEDNIK]->user, G::PURPOSE_INTERVIEW, now(), [
            $members[G::ROLE_PREDSJEDNIK]->id => G::ATTENDANCE_IN_PERSON,
            $members[G::ROLE_SEKRETAR]->id => G::ATTENDANCE_IN_PERSON,
            $members[G::ROLE_SPOLJASNJI]->id => G::ATTENDANCE_REMOTE,
        ]);
        $this->assertTrue($result->succeeded, (string) $result->error);
        $confirmed = $service->confirm(CommissionSession::query()->findOrFail($result->recordId), $members[G::ROLE_PREDSJEDNIK]->user);
        $this->assertTrue($confirmed->succeeded, (string) $confirmed->error);
    }

    private function closeAsAdmin(Competition $competition): void
    {
        $this->actingAs($this->canonicalActor())
            ->post(route('admin.competitions.test-application-phase.close', $competition), [
                'confirm_test_application_phase_close' => '1',
            ])
            ->assertSessionHas('success');
    }

    private function owner(): User
    {
        return User::factory()->create([
            'email' => 'podnosilac-'.uniqid().'@example.test',
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'name' => 'Test Podnosilac',
            'jmb' => '0101990'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
            'phone' => '067000000',
            'user_type' => UserType::ENTREPRENEUR,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function editPayload(Competition $competition, array $overrides = []): array
    {
        return array_merge([
            'title' => $competition->title,
            'description' => $competition->description,
            'type' => $competition->type,
            'up_number' => 'UP-TEST-3',
            'year' => $competition->year,
            'budget' => $competition->budget,
            'start_date' => $competition->start_date?->toDateString(),
            'status' => 'draft',
            'commission_id' => $competition->commission_id,
            'is_test_preview' => '1',
        ], $overrides);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function scoreRows(?int $applicationId): array
    {
        $query = EvaluationScore::query()->orderBy('id');
        if ($applicationId !== null) {
            $query->where('application_id', $applicationId);
        }

        return $query->get()->map(fn (EvaluationScore $score) => $score->getAttributes())->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function applicationSnapshot(Application $application): array
    {
        return [
            'status' => $application->status,
            'final_score' => $application->final_score,
            'final_score_calc' => $application->final_score_calc,
            'ranking_position' => $application->ranking_position,
            'submitted_at' => $application->submitted_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionSnapshot(CommissionSession $session): array
    {
        return [
            'completed_at' => $session->completed_at?->toIso8601String(),
            'canonical_purpose' => $session->canonical_purpose,
            'held_at' => $session->held_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function scorePayload(): array
    {
        $payload = ['scoring_confirmed' => '1'];
        foreach (range(1, 10) as $criterion) {
            $payload['criterion_'.$criterion] = 4;
        }

        return $payload;
    }
}
