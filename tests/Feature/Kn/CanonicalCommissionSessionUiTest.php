<?php

namespace Tests\Feature\Kn;

use App\Models\Application;
use App\Models\ApplicationCompletenessAssessment;
use App\Models\ApplicationOralPresentation;
use App\Models\CanonicalAdministrativeEvent;
use App\Models\CanonicalFinalRanking;
use App\Models\CanonicalInvestmentPlan;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\CommissionSession;
use App\Models\CommissionSessionAttendance;
use App\Models\Competition;
use App\Models\FinalApplicationClaim;
use App\Models\Role;
use App\Models\User;
use App\Services\CanonicalIndividualScoringService;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance as G;
use App\Services\Competitions\Commission\CanonicalCommissionMembershipService;
use App\Services\Competitions\Commission\CanonicalCommissionSessionService as S;
use App\Services\Competitions\CompletenessAssessmentService;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalSubsidyType as T;
use App\Support\CommissionProfileConfig;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Kn\Concerns\BuildsCanonicalCommission;
use Tests\TestCase;

/**
 * KN-SYNC-013 — canonical Commission session HTTP/UI layer (ordinary, interview, final decision).
 */
class CanonicalCommissionSessionUiTest extends TestCase
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
        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:00', 'Europe/Belgrade'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ---------------------------------------------------------------- A. Session page

    public function test_chair_member_and_admin_reach_session_page_with_correct_capabilities(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $call = $this->canonicalCall($commission);

        $this->actingAs($members[G::ROLE_PREDSJEDNIK]->user)
            ->get(route('evaluation.canonical-sessions.show', $call))
            ->assertOk()
            ->assertSee('Sjednice kanonske Komisije', false)
            ->assertSee('Sjednica intervjua', false)
            ->assertSee('Treća sjednica – konačna rang-lista i predlog', false)
            ->assertSee('Mjesto 1:', false)
            ->assertSee('Evidentiraj sjednicu', false)
            ->assertSee(S::INTERVIEW_BEFORE_FINAL_MESSAGE, false)
            ->assertDontSee('usmenog predstavljanja', false);

        $this->actingAs($members[G::ROLE_SEKRETAR]->user)
            ->get(route('evaluation.canonical-sessions.show', $call))
            ->assertOk()
            ->assertSee(S::CHAIR_ONLY_MESSAGE, false)
            ->assertDontSee('Evidentiraj sjednicu', false);

        foreach (['admin', 'konkurs_admin', 'superadmin'] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('admin.competitions.canonical-sessions.show', $call))
                ->assertOk()
                ->assertSee('samo čitanje', false)
                ->assertDontSee('Evidentiraj sjednicu', false);
        }

        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->get(route('admin.competitions.show', $call))
            ->assertOk()
            ->assertSee(route('admin.competitions.canonical-sessions.show', $call), false);
    }

    public function test_wrong_role_profile_foreign_member_and_admin_cannot_act(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $call = $this->canonicalCall($commission);
        [, $foreign] = $this->canonicalCommission();

        $this->actingAs($this->userWithRole('korisnik'))->get(route('evaluation.canonical-sessions.show', $call))->assertForbidden();
        $this->actingAs($this->userWithRole('korisnik'))->get(route('admin.competitions.canonical-sessions.show', $call))->assertForbidden();
        $this->actingAs($this->userWithRole('admin'))
            ->post(route('evaluation.canonical-sessions.record', $call), $this->recordPayload($members, G::PURPOSE_INTERVIEW))
            ->assertForbidden();
        $this->actingAs($this->userWithRole('superadmin'))
            ->post(route('evaluation.canonical-sessions.record', $call), $this->recordPayload($members, G::PURPOSE_INTERVIEW))
            ->assertSessionHasErrors(['canonical_session' => S::CHAIR_ONLY_MESSAGE]);

        $this->actingAs($foreign[G::ROLE_PREDSJEDNIK]->user)->get(route('evaluation.canonical-sessions.show', $call))->assertForbidden();
        $this->actingAs($foreign[G::ROLE_PREDSJEDNIK]->user)
            ->post(route('evaluation.canonical-sessions.record', $call), $this->recordPayload($members, G::PURPOSE_INTERVIEW))
            ->assertSessionHasErrors(['canonical_session' => S::CHAIR_ONLY_MESSAGE]);

        foreach (['zensko', 'omladinsko'] as $type) {
            $other = $this->canonicalCall($commission, ['type' => $type]);
            $this->actingAs($members[G::ROLE_PREDSJEDNIK]->user)->get(route('evaluation.canonical-sessions.show', $other))->assertForbidden();
            $this->actingAs($members[G::ROLE_PREDSJEDNIK]->user)
                ->post(route('evaluation.canonical-sessions.record', $other), $this->recordPayload($members, G::PURPOSE_INTERVIEW))
                ->assertForbidden();
            $this->actingAs($this->userWithRole('admin'))->get(route('admin.competitions.canonical-sessions.show', $other))->assertForbidden();
        }

        $this->assertSame(0, CommissionSession::query()->count());
    }

    public function test_cross_competition_session_access_is_blocked(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $first = $this->canonicalCall($commission);
        $second = $this->canonicalCall($commission);
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $this->record($chair, $first, $members, G::PURPOSE_INTERVIEW, [G::ROLE_SPOLJASNJI => 'absent'])->assertSessionHasNoErrors();
        $session = $this->sessionFor($first, G::PURPOSE_INTERVIEW);

        $this->actingAs($chair)
            ->post(route('evaluation.canonical-sessions.attendance', [$second, $session]), $this->attendance($members))
            ->assertNotFound();
        $this->actingAs($chair)->post(route('evaluation.canonical-sessions.confirm', [$second, $session]))->assertNotFound();

        $this->assertNull($session->fresh()->completed_at);
        $this->assertFalse(CommissionSessionAttendance::query()->where('commission_member_id', $members[G::ROLE_SPOLJASNJI]->id)->firstOrFail()->present);
    }

    // ---------------------------------------------------------------- B. Interview session

    public function test_interview_is_recorded_confirmed_and_locked(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $call = $this->canonicalCall($commission);
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;

        $this->record($chair, $call, $members, G::PURPOSE_INTERVIEW, [
            G::ROLE_SEKRETAR => G::ATTENDANCE_REMOTE,
            G::ROLE_SPOLJASNJI => G::ATTENDANCE_ELECTRONIC,
        ], '2026-09-10T09:00:00')->assertSessionHasNoErrors();

        $session = $this->sessionFor($call, G::PURPOSE_INTERVIEW);
        $this->assertSame(CommissionSession::TYPE_SECOND, $session->session_type);
        $this->assertSame($chair->id, $session->recorded_by_user_id);
        $this->assertSame('2026-09-10 09:00:00', $session->held_at->timezone('Europe/Belgrade')->format('Y-m-d H:i:s'));
        $this->assertSame(3, $session->attendances()->where('present', true)->count());
        $this->assertSame(0, CanonicalAdministrativeEvent::query()->count());

        $this->actingAs($chair)->post(route('evaluation.canonical-sessions.confirm', [$call, $session]))->assertSessionHasNoErrors();
        $confirmed = $session->fresh();
        $this->assertNotNull($confirmed->completed_at);
        $this->assertNull(app(G::class)->interviewBlockReason($call));

        $event = CanonicalAdministrativeEvent::query()->sole();
        $this->assertSame(CanonicalAdministrativeEvent::COMMISSION_SESSION_CONFIRMED, $event->action);
        $this->assertSame([$chair->id, $call->id, $session->id], [$event->actor_user_id, $event->competition_id, $event->subject_id]);
        $this->assertSame([1, 2, 3], collect($event->payload['present_seat_nos'])->sort()->values()->all());

        $this->actingAs($chair)
            ->get(route('evaluation.canonical-sessions.show', $call))
            ->assertOk()
            ->assertSee('potvrđena i zaključana', false)
            ->assertSee('potvrdio: '.$chair->name, false)
            ->assertSee('Daljinski', false)
            ->assertSee('Elektronski', false);

        $this->actingAs($chair)
            ->post(route('evaluation.canonical-sessions.attendance', [$call, $session]), $this->attendance($members, [G::ROLE_SEKRETAR => 'absent']))
            ->assertSessionHasErrors(['canonical_session' => S::SESSION_LOCKED_MESSAGE]);
        $this->actingAs($chair)
            ->post(route('evaluation.canonical-sessions.confirm', [$call, $session]))
            ->assertSessionHasErrors(['canonical_session' => S::SESSION_LOCKED_MESSAGE]);
        $this->record($chair, $call, $members, G::PURPOSE_INTERVIEW, [], '2026-09-10T09:30:00')
            ->assertSessionHasErrors(['canonical_session' => S::SESSION_EXISTS_MESSAGE]);

        $this->assertEquals($confirmed->completed_at, $session->fresh()->completed_at);
        $this->assertEquals($confirmed->held_at, $session->fresh()->held_at);
        $this->assertSame(3, $session->attendances()->where('present', true)->count());
        $this->assertSame(1, CommissionSession::query()->where('competition_id', $call->id)->count());
    }

    public function test_missing_member_blocks_confirmation_until_attendance_is_completed(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $call = $this->canonicalCall($commission);
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $this->record($chair, $call, $members, G::PURPOSE_INTERVIEW, [G::ROLE_SPOLJASNJI => 'absent'])->assertSessionHasNoErrors();
        $session = $this->sessionFor($call, G::PURPOSE_INTERVIEW);

        $this->actingAs($chair)
            ->post(route('evaluation.canonical-sessions.confirm', [$call, $session]))
            ->assertSessionHasErrors(['canonical_session' => G::FULL_ATTENDANCE_MESSAGE]);
        $this->assertNull($session->fresh()->completed_at);
        $this->actingAs($chair)
            ->get(route('evaluation.canonical-sessions.show', $call))
            ->assertSee('Nedostaje prisustvo za mjesto: 3', false)
            ->assertSee(G::FULL_ATTENDANCE_MESSAGE, false);

        $this->actingAs($members[G::ROLE_SEKRETAR]->user)
            ->post(route('evaluation.canonical-sessions.attendance', [$call, $session]), $this->attendance($members))
            ->assertSessionHasErrors(['canonical_session' => S::CHAIR_ONLY_MESSAGE]);

        $this->actingAs($chair)
            ->post(route('evaluation.canonical-sessions.attendance', [$call, $session]), $this->attendance($members))
            ->assertSessionHasNoErrors();
        $this->assertSame(3, CommissionSessionAttendance::query()->where('commission_session_id', $session->id)->count());
        $this->assertTrue(CanonicalAdministrativeEvent::query()
            ->where('action', CanonicalAdministrativeEvent::COMMISSION_SESSION_ATTENDANCE_UPDATED)
            ->where('actor_user_id', $chair->id)->where('subject_id', $session->id)->exists());

        $this->actingAs($chair)->post(route('evaluation.canonical-sessions.confirm', [$call, $session]))->assertSessionHasNoErrors();
        $this->assertNotNull($session->fresh()->completed_at);
    }

    public function test_attendance_is_limited_to_current_members_and_future_sessions_are_rejected(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $call = $this->canonicalCall($commission);
        [, $foreign] = $this->canonicalCommission();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;

        $payload = $this->recordPayload($members, G::PURPOSE_INTERVIEW);
        $payload['attendance'][$foreign[G::ROLE_SEKRETAR]->id] = G::ATTENDANCE_IN_PERSON;
        $this->actingAs($chair)
            ->post(route('evaluation.canonical-sessions.record', $call), $payload)
            ->assertSessionHasErrors(['canonical_session' => S::ATTENDANCE_MEMBER_INVALID_MESSAGE]);

        $this->record($chair, $call, $members, G::PURPOSE_INTERVIEW, [], '2026-09-11T10:00:00')
            ->assertSessionHasErrors(['canonical_session' => \App\Http\Controllers\CanonicalSessionController::HELD_IN_FUTURE_MESSAGE]);

        $this->actingAs($chair)
            ->post(route('evaluation.canonical-sessions.record', $call), array_merge($this->recordPayload($members, G::PURPOSE_INTERVIEW), [
                'attendance' => [$members[G::ROLE_PREDSJEDNIK]->id => 'telefon'],
            ]))
            ->assertSessionHasErrors('attendance.'.$members[G::ROLE_PREDSJEDNIK]->id);

        $this->assertSame(0, CommissionSession::query()->count());
        $this->assertSame(0, CommissionSessionAttendance::query()->count());
    }

    public function test_replaced_member_cannot_satisfy_attendance_and_replacement_can(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $call = $this->canonicalCall($commission);
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $this->record($chair, $call, $members, G::PURPOSE_INTERVIEW)->assertSessionHasNoErrors();
        $session = $this->sessionFor($call, G::PURPOSE_INTERVIEW);

        $old = $members[G::ROLE_SEKRETAR];
        $replacement = $this->replaceMember($old);

        $this->actingAs($chair)
            ->post(route('evaluation.canonical-sessions.confirm', [$call, $session]))
            ->assertSessionHasErrors(['canonical_session' => S::STALE_ATTENDANCE_MESSAGE]);
        $this->assertNull($session->fresh()->completed_at);
        $this->actingAs($chair)
            ->post(route('evaluation.canonical-sessions.attendance', [$call, $session]), ['attendance' => [$old->id => G::ATTENDANCE_IN_PERSON]])
            ->assertSessionHasErrors(['canonical_session' => S::ATTENDANCE_MEMBER_INVALID_MESSAGE]);
        $this->actingAs($old->user)->get(route('evaluation.canonical-sessions.show', $call))->assertForbidden();

        $this->actingAs($chair)
            ->get(route('evaluation.canonical-sessions.show', $call))
            ->assertSee('ne računa se (član više nije važeći)', false)
            ->assertSee('attendance['.$replacement->id.']', false)
            ->assertDontSee('attendance['.$old->id.']', false);

        $this->actingAs($chair)
            ->post(route('evaluation.canonical-sessions.attendance', [$call, $session]), ['attendance' => [$replacement->id => G::ATTENDANCE_REMOTE]])
            ->assertSessionHasNoErrors();
        $this->actingAs($chair)->post(route('evaluation.canonical-sessions.confirm', [$call, $session]))->assertSessionHasNoErrors();

        $present = app(G::class)->validPresentMembers($session->fresh())->pluck('id')->all();
        $this->assertContains($replacement->id, $present);
        $this->assertNotContains($old->id, $present);
        $this->assertCount(3, $present);
        $oldRow = CommissionSessionAttendance::query()->where('commission_session_id', $session->id)->where('commission_member_id', $old->id)->sole();
        $this->assertFalse($oldRow->present);
        $this->assertSame(G::ATTENDANCE_IN_PERSON, $oldRow->attendance_mode);
        $this->assertSame([$old->id], CanonicalAdministrativeEvent::query()
            ->where('action', CanonicalAdministrativeEvent::COMMISSION_SESSION_ATTENDANCE_UPDATED)->sole()->payload['no_longer_counted_member_ids']);
    }

    public function test_later_replacement_does_not_rewrite_confirmed_attendance(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $call = $this->canonicalCall($commission);
        $session = $this->confirmedInterview($call, $members);
        $before = CommissionSessionAttendance::query()->where('commission_session_id', $session->id)->orderBy('id')->get(['commission_member_id', 'present', 'attendance_mode'])->toArray();

        $this->replaceMember($members[G::ROLE_SEKRETAR]);

        $this->assertNull(app(G::class)->interviewBlockReason($call));
        $this->assertSame($before, CommissionSessionAttendance::query()->where('commission_session_id', $session->id)->orderBy('id')->get(['commission_member_id', 'present', 'attendance_mode'])->toArray());
        $this->actingAs($members[G::ROLE_PREDSJEDNIK]->user)
            ->get(route('evaluation.canonical-sessions.show', $call))
            ->assertSee($members[G::ROLE_SEKRETAR]->name)
            ->assertSee('potvrđena i zaključana', false);
    }

    // ---------------------------------------------------------------- C/D. Final session and 7-day timing

    public function test_final_session_requires_confirmed_interview_and_formed_ranking(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $call = $this->canonicalCall($commission);
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;

        $this->record($chair, $call, $members, G::PURPOSE_FINAL_DECISION)
            ->assertSessionHasErrors(['canonical_session' => S::INTERVIEW_BEFORE_FINAL_MESSAGE]);

        $this->confirmedInterview($call, $members);
        $this->record($chair, $call, $members, G::PURPOSE_FINAL_DECISION)
            ->assertSessionHasErrors(['canonical_session' => S::RANKING_BEFORE_FINAL_MESSAGE]);
        $this->actingAs($chair)->get(route('evaluation.canonical-sessions.show', $call))->assertSee(S::RANKING_BEFORE_FINAL_MESSAGE, false);

        $this->assertNull($this->sessionFor($call, G::PURPOSE_FINAL_DECISION, false));
    }

    public function test_final_session_seven_day_exact_time_boundary_late_warning_and_lock(): void
    {
        $cases = [
            'before-deadline' => ['2026-09-08T14:29:59', false],
            'exact-deadline' => ['2026-09-08T14:30:00', false],
            'after-deadline' => ['2026-09-08T14:30:01', true],
        ];
        [$commission, $members] = $this->canonicalCommission();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;

        foreach ($cases as $key => [$heldAt, $expectLate]) {
            Carbon::setTestNow(Carbon::parse('2026-09-01 14:30:00', 'Europe/Belgrade'));
            $call = $this->canonicalCall($commission);
            $interview = $this->confirmedInterview($call, $members, '2026-09-01T14:00:00');
            $this->assertSame('2026-09-01 14:30:00', $interview->completed_at->timezone('Europe/Belgrade')->format('Y-m-d H:i:s'), $key);
            $call->forceFill(['canonical_ranking_formed_at' => now()])->save();

            Carbon::setTestNow(Carbon::parse('2026-09-20 10:00:00', 'Europe/Belgrade'));
            $this->actingAs($chair)
                ->get(route('evaluation.canonical-sessions.show', $call))
                ->assertSee('08.09.2026 14:30:00', false)
                ->assertSee('Rok je istekao', false);

            $response = $this->record($chair, $call, $members, G::PURPOSE_FINAL_DECISION, [], $heldAt)->assertSessionHasNoErrors();
            if ($expectLate) {
                $response->assertSessionHas('warning', S::LATE_THIRD_SESSION_WARNING);
            } else {
                $response->assertSessionMissing('warning');
            }
            $session = $this->sessionFor($call, G::PURPOSE_FINAL_DECISION);
            $this->assertSame(CommissionSession::TYPE_THIRD, $session->session_type, $key);
            $this->assertSame('2026-09-08 14:30:00', $session->due_at->timezone('Europe/Belgrade')->format('Y-m-d H:i:s'), $key);
            $this->assertSame(str_replace('T', ' ', $heldAt), $session->held_at->timezone('Europe/Belgrade')->format('Y-m-d H:i:s'), $key);
            $this->assertSame($expectLate, (bool) $session->late, $key);

            $page = $this->actingAs($chair)->get(route('evaluation.canonical-sessions.show', $call));
            if ($expectLate) {
                $page->assertSee('Kašnjenje: '.S::LATE_THIRD_SESSION_WARNING, false);
            } else {
                $page->assertDontSee('Kašnjenje:', false);
            }

            $this->assertSame(G::FINAL_DECISION_SESSION_REQUIRED_MESSAGE, app(G::class)->finalDecisionSessionBlockReason($call), $key);
            $confirm = $this->actingAs($chair)->post(route('evaluation.canonical-sessions.confirm', [$call, $session]))->assertSessionHasNoErrors();
            if ($expectLate) {
                $confirm->assertSessionHas('warning', S::LATE_THIRD_SESSION_WARNING);
            }
            $this->assertNull(app(G::class)->finalDecisionSessionBlockReason($call), $key);
            $this->actingAs($chair)->get(route('evaluation.canonical-sessions.show', $call))->assertSee('potvrđena i zaključana', false);
            $this->assertSame($expectLate, (bool) $session->fresh()->late, $key);

            $this->actingAs($chair)
                ->post(route('evaluation.canonical-sessions.confirm', [$call, $session]))
                ->assertSessionHasErrors(['canonical_session' => S::SESSION_LOCKED_MESSAGE]);
            $this->actingAs($chair)
                ->post(route('evaluation.canonical-sessions.attendance', [$call, $session]), $this->attendance($members, [G::ROLE_SPOLJASNJI => 'absent']))
                ->assertSessionHasErrors(['canonical_session' => S::SESSION_LOCKED_MESSAGE]);
            $this->assertSame(3, $session->attendances()->where('present', true)->count(), $key);
        }
    }

    public function test_final_session_requires_full_attendance(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $call = $this->canonicalCall($commission);
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $this->confirmedInterview($call, $members);
        $call->forceFill(['canonical_ranking_formed_at' => now()])->save();

        $this->record($chair, $call, $members, G::PURPOSE_FINAL_DECISION, [G::ROLE_SEKRETAR => 'absent'])->assertSessionHasNoErrors();
        $session = $this->sessionFor($call, G::PURPOSE_FINAL_DECISION);
        $this->actingAs($chair)
            ->post(route('evaluation.canonical-sessions.confirm', [$call, $session]))
            ->assertSessionHasErrors(['canonical_session' => G::FULL_ATTENDANCE_MESSAGE]);
        $this->assertSame(G::FINAL_DECISION_SESSION_REQUIRED_MESSAGE, app(G::class)->finalDecisionSessionBlockReason($call));
    }

    // ---------------------------------------------------------------- E. Outcome handoff

    public function test_governance_to_sessions_to_outcome_handoff(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $call = $this->canonicalCall($commission);
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $application = $this->finalApplication($call, $chair);

        $this->confirmedInterview($call, $members);
        $scoring = app(CanonicalIndividualScoringService::class);
        foreach ([G::ROLE_PREDSJEDNIK, G::ROLE_SEKRETAR, G::ROLE_SPOLJASNJI] as $role) {
            $scoring->recordCanonicalFinalScore(Application::query()->findOrFail($application->id), $members[$role]->user, $this->criteriaSumming(40), null, true);
        }
        $this->assertTrue($scoring->persistCanonicalPreliminaryRankingIfReady($call->fresh()));

        $this->actingAs($chair)
            ->get(route('evaluation.canonical-outcome.show', $call))
            ->assertOk()
            ->assertSee(G::FINAL_DECISION_SESSION_REQUIRED_MESSAGE, false);
        $this->actingAs($chair)
            ->post(route('evaluation.canonical-outcome.confirm', $call))
            ->assertSessionHasErrors(['canonical_outcome' => G::FINAL_DECISION_SESSION_REQUIRED_MESSAGE]);

        $this->record($chair, $call, $members, G::PURPOSE_FINAL_DECISION)->assertSessionHasNoErrors();
        $this->actingAs($chair)
            ->post(route('evaluation.canonical-sessions.confirm', [$call, $this->sessionFor($call, G::PURPOSE_FINAL_DECISION)]))
            ->assertSessionHasNoErrors();

        $this->actingAs($chair)
            ->get(route('evaluation.canonical-outcome.show', $call))
            ->assertOk()
            ->assertDontSee(G::FINAL_DECISION_SESSION_REQUIRED_MESSAGE, false);
        $this->actingAs($chair)
            ->post(route('evaluation.canonical-outcome.draft', [$call, $application]), ['decision' => 'support', 'amount' => '1000'])
            ->assertSessionHasNoErrors();
        $this->actingAs($members[G::ROLE_SEKRETAR]->user)
            ->post(route('evaluation.canonical-outcome.confirm', $call))
            ->assertSessionHasErrors('canonical_outcome');
        $this->actingAs($chair)->post(route('evaluation.canonical-outcome.confirm', $call))->assertSessionHasNoErrors();

        $this->assertTrue(CanonicalFinalRanking::query()->where('competition_id', $call->id)->exists());
    }

    // ---------------------------------------------------------------- F. Legacy isolation

    public function test_legacy_session_and_oral_routes_are_blocked_for_canonical_without_writes(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $call = $this->canonicalCall($commission);
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $application = $this->finalApplication($call, $chair, assessCompleteness: false);
        $payload = [
            'held_at' => '2026-09-10 09:00',
            'present_member_ids' => collect($members)->pluck('id')->all(),
            'scheduled_at' => '2026-09-10 09:00',
            'applicant_attended' => '1',
        ];

        $requests = [
            ['get', route('commission-sessions.first.edit', $call)],
            ['post', route('commission-sessions.first.store', $call)],
            ['put', route('commission-sessions.first.update', $call)],
            ['post', route('commission-sessions.first.confirm', $call)],
            ['get', route('commission-sessions.second.edit', $call)],
            ['post', route('commission-sessions.second.store', $call)],
            ['put', route('commission-sessions.second.update', $call)],
            ['post', route('commission-sessions.second.confirm', $call)],
            ['get', route('commission-sessions.oral.edit', [$call, $application])],
            ['post', route('commission-sessions.oral.store', [$call, $application])],
            ['put', route('commission-sessions.oral.update', [$call, $application])],
            ['post', route('commission-sessions.oral.complete', [$call, $application])],
        ];

        foreach ($requests as [$method, $url]) {
            $this->actingAs($chair)->{$method}($url, $method === 'get' ? [] : $payload)->assertForbidden();
        }

        $this->actingAs($chair)
            ->post(route('commission-sessions.first.store', $call), $payload)
            ->assertSee(CommissionProfileConfig::CANONICAL_SESSION_ROUTE_MESSAGE, false);
        $this->assertSame(0, CommissionSession::query()->count());
        $this->assertSame(0, CommissionSessionAttendance::query()->count());
        $this->assertSame(0, ApplicationOralPresentation::query()->count());
    }

    // ---------------------------------------------------------------- G. Governance safety

    public function test_vacated_seat_conflicted_member_and_non_chair_are_blocked(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $call = $this->canonicalCall($commission);
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;

        $this->record($members[G::ROLE_SPOLJASNJI]->user, $call, $members, G::PURPOSE_INTERVIEW)
            ->assertSessionHasErrors(['canonical_session' => S::CHAIR_ONLY_MESSAGE]);

        app(CanonicalCommissionMembershipService::class)->vacate($members[G::ROLE_SPOLJASNJI], 'resigned', now(), $this->canonicalActor());
        $this->record($chair, $call, $members, G::PURPOSE_INTERVIEW, [G::ROLE_SPOLJASNJI => null])
            ->assertSessionHasErrors(['canonical_session' => G::COMMISSION_INCOMPLETE_MESSAGE]);
        $this->actingAs($chair)
            ->get(route('evaluation.canonical-sessions.show', $call))
            ->assertSee('Mjesto 3:', false)
            ->assertSee('upražnjeno / nepopunjeno', false)
            ->assertSee(G::COMMISSION_INCOMPLETE_MESSAGE, false);

        [$conflicted, $conflictMembers] = $this->canonicalCommission();
        $conflictCall = $this->canonicalCall($conflicted);
        $conflictMembers[G::ROLE_SPOLJASNJI]->update(['represented_company_pib' => '12345678']);
        $this->finalApplication($conflictCall, $conflictMembers[G::ROLE_PREDSJEDNIK]->user, '12345678', assessCompleteness: false);
        $this->record($conflictMembers[G::ROLE_PREDSJEDNIK]->user, $conflictCall, $conflictMembers, G::PURPOSE_INTERVIEW)
            ->assertSessionHasErrors(['canonical_session' => G::PIB_CONFLICT_MESSAGE]);

        $this->assertSame(0, CommissionSession::query()->count());
    }

    // ---------------------------------------------------------------- H. Profile safety

    public function test_women_and_youth_sessions_are_not_routed_through_canonical_ui(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;

        foreach (['zensko', 'omladinsko'] as $type) {
            $call = $this->canonicalCall($commission, ['type' => $type]);
            $this->actingAs($chair)->get(route('evaluation.canonical-sessions.show', $call))->assertForbidden();
            $this->actingAs($this->userWithRole('admin'))->get(route('admin.competitions.canonical-sessions.show', $call))->assertForbidden();
            $this->assertNull(CommissionSession::query()->where('competition_id', $call->id)->first());
        }

        $this->assertNull(CommissionSession::query()->first());
    }

    // ---------------------------------------------------------------- helpers

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'email' => $role.'-'.uniqid().'@example.test',
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function canonicalCall(Commission $commission, array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'title' => 'KN-SYNC-013 '.uniqid(),
            'type' => self::CANONICAL,
            'status' => 'published',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'commission_id' => $commission->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-21',
            'published_at' => '2026-08-01 09:00:00',
        ], $overrides))->fresh();
    }

    /**
     * @param  array<string, CommissionMember>  $members
     * @param  array<string, string|null>  $overrides  role => mode ('absent' or null = absent)
     * @return array{attendance: array<int, string>}
     */
    private function attendance(array $members, array $overrides = []): array
    {
        $attendance = [];
        foreach ($members as $role => $member) {
            if (array_key_exists($role, $overrides) && $overrides[$role] === null) {
                continue;
            }
            $attendance[$member->id] = $overrides[$role] ?? G::ATTENDANCE_IN_PERSON;
        }

        return ['attendance' => $attendance];
    }

    /**
     * @param  array<string, CommissionMember>  $members
     * @param  array<string, string|null>  $overrides
     * @return array<string, mixed>
     */
    private function recordPayload(array $members, string $purpose, array $overrides = [], string $heldAt = '2026-09-10T09:00:00'): array
    {
        return ['purpose' => $purpose, 'held_at' => $heldAt] + $this->attendance($members, $overrides);
    }

    /**
     * @param  array<string, CommissionMember>  $members
     * @param  array<string, string|null>  $overrides
     */
    private function record(User $actor, Competition $call, array $members, string $purpose, array $overrides = [], string $heldAt = '2026-09-10T09:00:00'): TestResponse
    {
        return $this->actingAs($actor)->post(route('evaluation.canonical-sessions.record', $call), $this->recordPayload($members, $purpose, $overrides, $heldAt));
    }

    private function sessionFor(Competition $call, string $purpose, bool $required = true): ?CommissionSession
    {
        $query = CommissionSession::query()->where('competition_id', $call->id)->where('canonical_purpose', $purpose);

        return $required ? $query->firstOrFail() : $query->first();
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function confirmedInterview(Competition $call, array $members, ?string $heldAt = null): CommissionSession
    {
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $heldAt ??= now('Europe/Belgrade')->subHour()->format('Y-m-d\TH:i:s');
        $this->record($chair, $call, $members, G::PURPOSE_INTERVIEW, [], $heldAt)->assertSessionHasNoErrors();
        $session = $this->sessionFor($call, G::PURPOSE_INTERVIEW);
        $this->actingAs($chair)->post(route('evaluation.canonical-sessions.confirm', [$call, $session]))->assertSessionHasNoErrors();

        return $session->fresh();
    }

    private function replaceMember(CommissionMember $old): CommissionMember
    {
        $service = app(CanonicalCommissionMembershipService::class);
        $this->assertTrue($service->vacate($old, 'resigned', now()->subMinute(), $this->canonicalActor())->succeeded);
        $result = $service->replace($old, $this->komisijaUser('zamjena'), 'Rješenje o zamjeni', now(), $this->canonicalActor());
        $this->assertTrue($result->succeeded, (string) $result->error);
        $replacement = CommissionMember::query()->findOrFail($result->recordId);
        $this->signDeclarations($replacement);

        return $replacement->fresh();
    }

    private function finalApplication(Competition $call, User $chair, string $pib = '81000001', bool $assessCompleteness = true): Application
    {
        $owner = $this->userWithRole('korisnik');
        $application = Application::create([
            'competition_id' => $call->id,
            'user_id' => $owner->id,
            'applicant_type' => 'doo',
            'business_stage' => 'razvoj',
            'status' => 'submitted',
            'business_plan_name' => 'Plan '.uniqid(),
            'founder_name' => 'Osnivač '.uniqid(),
            'doo_name' => 'Firma '.uniqid(),
            'pib' => $pib,
            'submitted_at' => now(),
            'redni_broj' => 1,
        ]);

        FinalApplicationClaim::query()->create([
            'user_id' => $owner->id,
            'competition_id' => $call->id,
            'application_id' => $application->id,
            'claimed_at' => now(),
            'subsidy_type' => T::AUTHENTIC_LOCAL,
            'beneficiary_pib' => $pib,
            'beneficiary_slot' => 1,
        ]);

        (new CanonicalInvestmentPlan)->forceFill([
            'application_id' => $application->id,
            'subsidy_type' => T::AUTHENTIC_LOCAL,
            'total_investment' => '5000.00',
            'own_contribution' => '0.00',
            'expected_subsidy' => '5000.00',
            'expense_lines' => [['cost_type' => 'oprema', 'description' => 'Oprema', 'amount' => '5000.00']],
            'frozen_at' => now(),
        ])->save();

        if ($assessCompleteness) {
            $this->confirmOrdinarySession($call);
            $result = app(CompletenessAssessmentService::class)->record($application, $chair, ApplicationCompletenessAssessment::COMPLETE);
            $this->assertTrue($result->succeeded, (string) $result->error);
        }

        return $application->fresh();
    }

    /**
     * @return array<string, int>
     */
    private function criteriaSumming(int $total): array
    {
        $base = intdiv($total, 10);
        $remainder = $total % 10;
        $criteria = [];
        foreach (range(1, 10) as $criterion) {
            $criteria["criterion_{$criterion}"] = $base + ($criterion <= $remainder ? 1 : 0);
        }

        return $criteria;
    }
}
