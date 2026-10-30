<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationOralPresentation;
use App\Models\ApplicationScore;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\CommissionSession;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\ApplicationEliminatoryCheckService;
use App\Support\CommissionProfileConfig;
use App\Support\ScoringProfileConfig;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PDOException;
use Tests\TestCase;

class YouthSecondSessionAndOralPresentationTest extends TestCase
{
    use RefreshDatabase;

    private int $yearSerial = 2040;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_youth_second_session_routes_do_not_record_session_or_attendance(): void
    {
        $ctx = $this->youthWithPassedM3();
        $competition = $ctx['competition'];
        $payload = $this->secondPayload($this->allMemberIds($ctx));

        $this->actingAs($ctx['chairman']->user)
            ->get(route('commission-sessions.second.edit', $competition))
            ->assertRedirect(route('admin.competitions.show', $competition));

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.second.store', $competition), $payload)
            ->assertRedirect(route('admin.competitions.show', $competition));
        $this->actingAs($ctx['chairman']->user)
            ->put(route('commission-sessions.second.update', $competition), $payload)
            ->assertRedirect(route('admin.competitions.show', $competition));
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.second.confirm', $competition), $payload)
            ->assertRedirect(route('admin.competitions.show', $competition));

        $this->assertNull($competition->fresh()->firstCommissionSession());
        $this->assertNull($competition->fresh()->secondCommissionSession());
        $this->assertSame(0, CommissionSession::query()->where('competition_id', $competition->id)->count());
    }

    public function test_m3_pass_opens_scoring_while_sibling_appeal_window_is_open(): void
    {
        $ctx = $this->youthWithPassedM3();
        $failed = $this->addSubmittedApplication($ctx['competition']);
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $failed), $this->youthFailPayload())
            ->assertRedirect();

        $gate = app(\App\Services\YouthSecondSessionGate::class);
        $this->assertTrue($gate->appealWindowIsOpen($failed->fresh()));
        $this->assertFalse($gate->secondSessionMayStart($ctx['competition']->fresh()));
        $this->assertSame(
            CommissionProfileConfig::SESSION_SECOND_APPEAL_GATE_MESSAGE,
            $gate->secondSessionBlockMessage($ctx['competition']->fresh())
        );
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($failed->fresh()));
        $this->assertFalse($gate->oralPresentationIsAllowed($failed->fresh()));
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($ctx['application']->fresh()));
        $this->assertFalse($gate->oralPresentationIsAllowed($ctx['application']->fresh()));

        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk()
            ->assertDontSee(ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE, false)
            ->assertDontSee(ApplicationEliminatoryCheckService::CONFIRMED_FAIL_SCORING_MESSAGE, false);
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('evaluation.index', ['filter' => 'evaluated']));
        $this->assertNotNull(
            EvaluationScore::query()
                ->where('application_id', $ctx['application']->id)
                ->where('commission_member_id', $ctx['chairman']->id)
                ->whereNotNull('completed_at')
                ->first()
        );

        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $failed))
            ->assertOk()
            ->assertSee(ApplicationEliminatoryCheckService::CONFIRMED_FAIL_SCORING_MESSAGE, false);
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $failed))
            ->post(route('evaluation.store', $failed), $this->draftPayload())
            ->assertForbidden();

        $this->actingAs($ctx['chairman']->user)
            ->from(route('commission-sessions.oral.edit', [$ctx['competition'], $ctx['application']]))
            ->post(route('commission-sessions.oral.store', [$ctx['competition'], $ctx['application']]), [
                'scheduled_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']));
        $this->assertSame(0, ApplicationOralPresentation::query()->where('application_id', $ctx['application']->id)->count());

        $this->actingAs($ctx['chairman']->user)
            ->get(route('commission-sessions.second.edit', $ctx['competition']))
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']));
    }

    public function test_phase_opens_after_appeal_window_expires_without_prigovor(): void
    {
        $ctx = $this->youthWithPassedM3();
        $failed = $this->addSubmittedApplication($ctx['competition']);
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $failed), $this->youthFailPayload())
            ->assertRedirect();
        $failed->fresh()->eliminatoryNotice->update(['sent_at' => now()->subDays(4)]);

        $gate = app(\App\Services\YouthSecondSessionGate::class);
        $this->assertFalse($gate->appealWindowIsOpen($failed->fresh()));
        $this->assertTrue($gate->secondSessionMayStart($ctx['competition']->fresh()));
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($ctx['application']->fresh()));
        $this->assertTrue($gate->oralPresentationIsAllowed($ctx['application']->fresh()));

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$ctx['competition'], $ctx['application']]), [
                'scheduled_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']));
        $this->assertNull($ctx['application']->fresh()->oralPresentation);
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($ctx['application']->fresh()));
    }

    public function test_unresolved_prigovor_on_sibling_does_not_block_draft_but_blocks_oral(): void
    {
        $ctx = $this->youthWithPassedM3();
        $failed = $this->addSubmittedApplication($ctx['competition']);
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $failed), $this->youthFailPayload())
            ->assertRedirect();
        $this->actingAs($failed->user)->post(route('applications.prigovor.store', $failed), [
            'contested' => [1, 2],
            'criterion_obrazlozenja' => [1 => 'Obrazloženje 1.', 2 => 'Obrazloženje 2.'],
        ])->assertRedirect();
        $failed->fresh()->eliminatoryNotice->update(['sent_at' => now()->subDays(4)]);
        $this->assertTrue($failed->fresh()->prigovor->isPodnesen());

        $gate = app(\App\Services\YouthSecondSessionGate::class);
        $this->assertFalse($gate->secondSessionMayStart($ctx['competition']->fresh()));
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($failed->fresh()));
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($ctx['application']->fresh()));
        $this->assertFalse($gate->oralPresentationIsAllowed($ctx['application']->fresh()));

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $ctx['application']))
            ->post(route('evaluation.store', $ctx['application']), $this->finalPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('evaluation.index', ['filter' => 'evaluated']));
        $this->assertNotNull(
            EvaluationScore::query()
                ->where('application_id', $ctx['application']->id)
                ->whereNotNull('completed_at')
                ->first()
        );

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$ctx['competition'], $ctx['application']]), [
                'scheduled_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']));
        $this->assertSame(0, ApplicationOralPresentation::query()->where('application_id', $ctx['application']->id)->count());
    }

    public function test_active_eliminatory_reason_blocks_scoring_until_accepted_prigovor_lifts_all_reasons(): void
    {
        $ctx = $this->confirmYouthFail();
        $application = $ctx['application']->fresh();
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application));
        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->assertSee(ApplicationEliminatoryCheckService::CONFIRMED_FAIL_SCORING_MESSAGE, false);
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.store', $application), $this->draftPayload())
            ->assertForbidden();

        $this->actingAs($application->user)->post(route('applications.prigovor.store', $application), [
            'contested' => [1, 2],
            'criterion_obrazlozenja' => [1 => 'Obrazloženje 1.', 2 => 'Obrazloženje 2.'],
        ])->assertRedirect();
        $this->assertTrue($application->fresh()->prigovor->isPodnesen());
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application->fresh()));
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $application), $this->draftPayload())
            ->assertForbidden();

        $this->actingAs($ctx['chairman']->user)->post(route('evaluation.prigovor.decide', $application), [
            'decision_note' => 'Svi osporeni razlozi su otklonjeni.',
            'odluka' => 'prihvacen',
        ])->assertRedirect();

        $application = $application->fresh();
        $this->assertTrue($application->prigovor->liftsEliminatoryBar());
        $this->assertTrue(app(\App\Services\YouthSecondSessionGate::class)->appealWindowIsOpen($application));
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application));
        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->assertDontSee(ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE, false);
        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.store', $application), $this->finalPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('evaluation.index', ['filter' => 'evaluated']));
        $this->assertNotNull(
            EvaluationScore::query()
                ->where('application_id', $application->id)
                ->whereNotNull('completed_at')
                ->first()
        );
    }

    public function test_favorable_final_prigovor_opens_scoring_and_oral_without_session_record(): void
    {
        $ctx = $this->confirmYouthFail();
        $this->actingAs($ctx['application']->user)->post(route('applications.prigovor.store', $ctx['application']), [
            'contested' => [1, 2],
            'criterion_obrazlozenja' => [1 => 'Obrazloženje 1.', 2 => 'Obrazloženje 2.'],
        ])->assertRedirect();
        $ctx['application']->eliminatoryNotice->update(['sent_at' => now()->subDays(4)]);
        $this->actingAs($ctx['chairman']->user)->post(route('evaluation.prigovor.decide', $ctx['application']), [
            'decision_note' => 'Svi osporeni razlozi su otklonjeni.',
            'odluka' => 'prihvacen',
        ])->assertRedirect();

        $application = $ctx['application']->fresh();
        $this->assertTrue($application->prigovor->liftsEliminatoryBar());
        $this->assertSame('submitted', $application->status);
        $this->assertTrue(app(\App\Services\YouthSecondSessionGate::class)->secondSessionMayStart($ctx['competition']->fresh()));
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application));

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.complete', [$ctx['competition'], $application]), [
                'scheduled_at' => now()->format('Y-m-d H:i:s'),
                'applicant_attended' => '1',
                'held_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']));
        $this->assertNull($application->fresh()->oralPresentation);
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application->fresh()));
        $this->assertSame(0, \App\Models\CommissionSessionAttendance::query()->count());
    }

    public function test_oral_completes_without_recorded_second_session_and_allows_multiple_orals(): void
    {
        $ctx = $this->youthWithPassedM3();
        $secondApp = $this->addSubmittedApplication($ctx['competition']);
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $secondApp), $this->youthPassPayload())
            ->assertRedirect();

        $this->assertNull($ctx['competition']->fresh()->secondCommissionSession());

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$ctx['competition'], $ctx['application']]), [
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'notes' => 'Plan',
            ])
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']));

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.complete', [$ctx['competition'], $ctx['application']]), $this->oralCompletePayload(true))
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']));

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$ctx['competition'], $secondApp]), [
                'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']));
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.complete', [$ctx['competition'], $secondApp]), $this->oralCompletePayload(true))
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']));

        $this->assertSame(0, ApplicationOralPresentation::query()->count());
        $this->assertNull($ctx['application']->fresh()->oralPresentation);
        $this->assertNull($secondApp->fresh()->oralPresentation);
        $this->assertSame(0, \App\Models\CommissionSessionAttendance::query()->count());
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($ctx['application']->fresh()));
    }

    public function test_ordinary_admin_other_commission_inactive_chairman_and_zensko_cannot_open_oral_hub(): void
    {
        $ctx = $this->youthWithPassedM3();
        $payload = $this->secondPayload($this->allMemberIds($ctx));

        $this->actingAs($this->userWithRole('admin'))
            ->post(route('commission-sessions.second.store', $ctx['competition']), $payload)
            ->assertForbidden();
        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->get(route('commission-sessions.second.edit', $ctx['competition']))
            ->assertForbidden();
        $this->actingAs($ctx['members'][1]->user)
            ->post(route('commission-sessions.second.store', $ctx['competition']), $payload)
            ->assertForbidden();

        $other = $this->youthWithPassedM3();
        $this->actingAs($other['chairman']->user)
            ->post(route('commission-sessions.second.store', $ctx['competition']), $payload)
            ->assertForbidden();

        $inactive = $this->youthWithPassedM3();
        $inactive['chairman']->update(['status' => 'inactive']);
        $this->actingAs($inactive['chairman']->fresh('user')->user)
            ->post(route('commission-sessions.second.store', $inactive['competition']), $this->secondPayload($this->allMemberIds($inactive)))
            ->assertForbidden();

        $zensko = $this->makeZenskoCompetition();
        $this->actingAs($zensko['chairman']->user)
            ->post(route('commission-sessions.second.store', $zensko['competition']), [
                'held_at' => now()->format('Y-m-d H:i:s'),
                'notes' => 'Ženski',
                'present_member_ids' => $zensko['members']->pluck('id')->all(),
            ])
            ->assertForbidden();
        $this->actingAs($zensko['chairman']->user)
            ->get(route('commission-sessions.second.edit', $zensko['competition']))
            ->assertForbidden();

        $this->assertSame(0, CommissionSession::query()->where('session_type', CommissionSession::TYPE_SECOND)->count());
        $this->assertNull($zensko['competition']->fresh()->firstCommissionSession());
    }

    public function test_dual_membership_is_isolated_by_competition_commission_id(): void
    {
        $a = $this->youthWithPassedM3();
        $b = $this->youthWithPassedM3();
        $b['members'][1]->update([
            'user_id' => $a['chairman']->user_id,
            'name' => $a['chairman']->name,
        ]);

        $this->actingAs($a['chairman']->user)
            ->get(route('commission-sessions.second.edit', $b['competition']))
            ->assertForbidden();
        $this->actingAs($a['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$b['competition'], $b['application']]), ['notes' => 'idor'])
            ->assertForbidden();

        $this->actingAs($a['chairman']->user)
            ->get(route('commission-sessions.second.edit', $a['competition']))
            ->assertRedirect(route('admin.competitions.show', $a['competition']));
        $this->actingAs($a['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$a['competition'], $a['application']]), ['notes' => 'svoje'])
            ->assertRedirect(route('admin.competitions.show', $a['competition']));
        $this->assertNull($a['application']->fresh()->oralPresentation);
        $this->assertNull($b['application']->fresh()->oralPresentation);
        $this->assertSame(0, ApplicationOralPresentation::query()->count());
    }

    public function test_chairman_on_seats_one_two_or_three_can_open_oral_hub(): void
    {
        foreach ([1, 2, 3] as $chairSeat) {
            $ctx = $this->makeYouthContext();
            foreach ($ctx['members'] as $index => $member) {
                $member->update(['position' => ($index + 1) === $chairSeat ? 'predsjednik' : 'clan']);
            }
            $chairman = $ctx['members'][$chairSeat - 1]->fresh('user');
            $this->actingAs($chairman->user)
                ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $this->youthPassPayload())
                ->assertRedirect();

            $this->actingAs($chairman->user)
                ->get(route('commission-sessions.second.edit', $ctx['competition']))
                ->assertRedirect(route('admin.competitions.show', $ctx['competition']));
            $this->actingAs($chairman->user)
                ->post(route('commission-sessions.oral.store', [$ctx['competition'], $ctx['application']]), [
                    'scheduled_at' => now()->format('Y-m-d H:i:s'),
                ])
                ->assertRedirect(route('admin.competitions.show', $ctx['competition']));
            $this->assertSame(0, ApplicationOralPresentation::query()->where('application_id', $ctx['application']->id)->count());
        }
    }

    public function test_attendance_and_no_show_do_not_reject_or_score_and_lock_completed_record(): void
    {
        $ctx = $this->youthWithConfirmedSecond();
        $ctx['application']->update(['interview_scheduled_at' => '2026-04-01 10:00:00']);
        $interview = $ctx['application']->fresh()->interview_scheduled_at?->toDateTimeString();

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.complete', [$ctx['competition'], $ctx['application']]), $this->oralCompletePayload(true))
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']));
        $this->assertNull($ctx['application']->fresh()->oralPresentation);
        $this->assertSame('submitted', $ctx['application']->fresh()->status);
        $this->assertSame($interview, $ctx['application']->fresh()->interview_scheduled_at?->toDateTimeString());
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($ctx['application']->fresh()));

        $noShow = $this->youthWithConfirmedSecond();
        $noShow['application']->update(['interview_scheduled_at' => '2026-05-01 09:00:00']);
        $this->actingAs($noShow['chairman']->user)
            ->post(route('commission-sessions.oral.complete', [$noShow['competition'], $noShow['application']]), $this->oralCompletePayload(false))
            ->assertRedirect(route('admin.competitions.show', $noShow['competition']));
        $this->assertNull($noShow['application']->fresh()->oralPresentation);
        $this->assertSame('submitted', $noShow['application']->fresh()->status);
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $noShow['application']->id)->count());
        $this->assertSame(0, ApplicationScore::query()->where('application_id', $noShow['application']->id)->count());
        $this->assertSame(
            '2026-05-01 09:00:00',
            $noShow['application']->fresh()->interview_scheduled_at?->format('Y-m-d H:i:s')
        );
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($noShow['application']->fresh()));

        $this->actingAs($noShow['chairman']->user)->put(
            route('commission-sessions.oral.update', [$noShow['competition'], $noShow['application']]),
            ['notes' => 'kasnije']
        )->assertRedirect(route('admin.competitions.show', $noShow['competition']));
        $this->actingAs($noShow['chairman']->user)
            ->post(route('commission-sessions.oral.complete', [$noShow['competition'], $noShow['application']]), $this->oralCompletePayload(false))
            ->assertRedirect(route('admin.competitions.show', $noShow['competition']));
        $this->assertSame(0, ApplicationOralPresentation::query()->count());
    }

    public function test_duplicate_oral_idor_and_rejected_application_are_blocked(): void
    {
        $ctx = $this->youthWithConfirmedSecond();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$ctx['competition'], $ctx['application']]), ['notes' => 'prvi'])
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']));
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$ctx['competition'], $ctx['application']]), ['notes' => 'drugi'])
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']));
        $this->assertSame(0, ApplicationOralPresentation::query()->where('application_id', $ctx['application']->id)->count());

        $foreign = $this->youthWithConfirmedSecond();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$ctx['competition'], $foreign['application']]), ['notes' => 'idor'])
            ->assertNotFound();
        $this->actingAs($ctx['chairman']->user)
            ->get(route('commission-sessions.oral.edit', [$foreign['competition'], $ctx['application']]))
            ->assertNotFound();

        $rejected = $this->youthWithConfirmedSecond();
        $rejected['application']->update(['status' => 'rejected']);
        $this->actingAs($rejected['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$rejected['competition'], $rejected['application']]), ['notes' => 'odbijena'])
            ->assertRedirect(route('admin.competitions.show', $rejected['competition']));
        $this->assertSame(0, ApplicationOralPresentation::query()->count());
    }

    public function test_oral_routes_do_not_write_records_even_when_a_creating_listener_would_fail(): void
    {
        $otherError = $this->youthWithConfirmedSecond();
        $dispatcher = ApplicationOralPresentation::getEventDispatcher();
        $event = 'eloquent.creating: '.ApplicationOralPresentation::class;
        $otherListener = function () {
            $previous = new PDOException('SQLSTATE[HY000]: General error: 1205 Lock wait timeout', 1205);
            $previous->errorInfo = ['HY000', 1205, 'Lock wait timeout'];
            throw new QueryException('mysql', 'insert into `application_oral_presentations`', [], $previous);
        };
        $dispatcher->listen($event, $otherListener);
        try {
            $this->actingAs($otherError['chairman']->user)
                ->post(route('commission-sessions.oral.store', [$otherError['competition'], $otherError['application']]), ['notes' => 'db'])
                ->assertRedirect(route('admin.competitions.show', $otherError['competition']));
            $this->assertSame(0, ApplicationOralPresentation::query()->count());
        } finally {
            $dispatcher->forget($event);
        }
    }

    public function test_historical_first_session_is_kept_and_does_not_open_session_form(): void
    {
        $ctx = $this->youthWithPassedM3();
        CommissionSession::create([
            'competition_id' => $ctx['competition']->id,
            'commission_id' => $ctx['competition']->commission_id,
            'session_type' => CommissionSession::TYPE_FIRST,
            'held_at' => now()->subDays(10),
            'completed_at' => now()->subDays(10),
            'recorded_by_user_id' => $ctx['chairman']->user_id,
            'notes' => 'Istorijska prva sjednica',
        ]);

        $this->actingAs($ctx['chairman']->user)
            ->get(route('commission-sessions.second.edit', $ctx['competition']))
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']));

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.second.store', $ctx['competition']), $this->secondPayload($this->allMemberIds($ctx)))
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']));
        $this->assertNull($ctx['competition']->fresh()->secondCommissionSession());
        $this->assertNotNull($ctx['competition']->fresh()->firstCommissionSession());
    }

    public function test_youth_call_show_exposes_oral_hub_and_zensko_show_does_not(): void
    {
        $ctx = $this->youthWithPassedM3();
        $response = $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.show', $ctx['competition']))
            ->assertOk();
        $response->assertDontSee('Usmeno predstavljanje', false);
        $response->assertDontSee('Evidencija usmenog', false);
        $response->assertDontSee('Druga sjednica: nije evidentirana', false);
        $response->assertDontSee('Evidentiraj drugu sjednicu', false);
        $response->assertDontSee('Evidentiraj prvu sjednicu Komisije', false);

        $zensko = $this->makeZenskoCompetition();
        $this->actingAs($zensko['chairman']->user)
            ->get(route('admin.competitions.show', $zensko['competition']))
            ->assertOk()
            ->assertDontSee('Usmeno predstavljanje', false)
            ->assertDontSee('Druga sjednica', false);
    }

    public function test_zensko_first_session_recording_is_unchanged(): void
    {
        $zensko = $this->makeZenskoCompetition();
        foreach ($zensko['members']->values() as $index => $member) {
            $member->update(['canonical_seat_no' => $index + 1]);
        }
        $payload = $this->firstPayload($zensko['members']->pluck('id')->all());

        $this->actingAs($zensko['chairman']->user)
            ->get(route('commission-sessions.first.edit', $zensko['competition']))
            ->assertOk()
            ->assertSee('Evidencija prve sjednice', false);

        $this->actingAs($zensko['chairman']->user)
            ->post(route('commission-sessions.first.store', $zensko['competition']), $payload)
            ->assertRedirect(route('commission-sessions.first.edit', $zensko['competition']))
            ->assertSessionHasNoErrors();
        $this->actingAs($zensko['chairman']->user)
            ->post(route('commission-sessions.first.confirm', $zensko['competition']))
            ->assertRedirect(route('commission-sessions.first.edit', $zensko['competition']))
            ->assertSessionHasNoErrors();

        $session = $zensko['competition']->fresh()->firstCommissionSession();
        $this->assertNotNull($session);
        $this->assertTrue($session->isConfirmed());
        $this->assertSame(0, ApplicationOralPresentation::query()->count());
        $this->assertNull($zensko['competition']->fresh()->secondCommissionSession());
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function youthWithConfirmedSecond(): array
    {
        return $this->youthWithPassedM3();
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function youthWithPassedM3(): array
    {
        $ctx = $this->makeYouthReadyContext();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $this->youthPassPayload())
            ->assertRedirect();

        return $ctx;
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function confirmYouthFail(): array
    {
        $ctx = $this->makeYouthReadyContext();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $this->youthFailPayload())
            ->assertRedirect();

        return $ctx;
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function makeYouthReadyContext(): array
    {
        return $this->makeYouthContext();
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function makeYouthContext(): array
    {
        $year = $this->yearSerial++;
        $commission = Commission::create([
            'name' => 'Mladi druga '.$year,
            'year' => $year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'omladinsko',
        ]);
        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        $members = [];
        for ($seat = 1; $seat <= 3; $seat++) {
            $user = User::factory()->create([
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
            $members[] = CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $seat === 1 ? 'predsjednik' : 'clan',
                'member_type' => null,
                'canonical_seat_no' => $seat,
                'status' => 'active',
            ]);
        }

        $competition = Competition::create([
            'title' => 'Omladinsko druga '.$year,
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => $year,
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
            'competition_number' => 'UP-S-'.$year,
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => $competition->competition_number]);

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Plan '.$year,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);

        return [
            'competition' => $competition->fresh(['commission.activeMembers.user']),
            'chairman' => $members[0]->fresh('user'),
            'members' => $members,
            'application' => $application,
        ];
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: \Illuminate\Support\Collection<int, CommissionMember>}
     */
    private function makeZenskoCompetition(): array
    {
        $commission = Commission::create([
            'name' => 'Zenska druga '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'zensko',
        ]);
        $types = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];
        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        $members = collect();
        foreach ($types as $i => $type) {
            $user = User::factory()->create([
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
            $members->push(CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $i === 0 ? 'predsjednik' : 'clan',
                'member_type' => $type,
                'status' => 'active',
            ]));
        }
        $competition = Competition::create([
            'title' => 'Zensko druga '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => 'UP-Z-'.uniqid()]);

        return [
            'competition' => $competition->fresh(['commission.activeMembers.user']),
            'chairman' => $members[0]->fresh('user'),
            'members' => $members,
        ];
    }

    private function addSubmittedApplication(Competition $competition): Application
    {
        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Drugi plan',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);
    }

    /**
     * @param  list<CommissionMember>  $ctx
     * @return list<int>
     */
    private function allMemberIds(array $ctx): array
    {
        return array_map(fn (CommissionMember $member) => $member->id, $ctx['members']);
    }

    private function replaceSeat(CommissionMember $old, int $seat): CommissionMember
    {
        $old->update(['status' => 'inactive']);
        $user = $this->userWithRole('komisija');

        return CommissionMember::create([
            'commission_id' => $old->commission_id,
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => 'clan',
            'member_type' => null,
            'canonical_seat_no' => $seat,
            'status' => 'active',
        ]);
    }

    /**
     * @param  list<int>  $presentIds
     * @return array<string, mixed>
     */
    private function firstPayload(array $presentIds): array
    {
        return [
            'held_at' => now()->subDays(2)->format('Y-m-d H:i:s'),
            'notes' => 'Prva sjednica',
            'present_member_ids' => $presentIds,
        ];
    }

    /**
     * @param  list<int>  $presentIds
     * @return array<string, mixed>
     */
    private function secondPayload(array $presentIds): array
    {
        return [
            'held_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'notes' => 'Druga sjednica',
            'present_member_ids' => $presentIds,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function oralCompletePayload(bool $attended): array
    {
        $payload = [
            'scheduled_at' => now()->format('Y-m-d H:i:s'),
            'applicant_attended' => $attended ? '1' : '0',
            'notes' => $attended ? 'Prisutan' : 'Usmeno locked',
        ];
        if ($attended) {
            $payload['held_at'] = now()->format('Y-m-d H:i:s');
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function draftPayload(array $overrides = []): array
    {
        $payload = ['save_as_draft' => '1', 'notes' => 'Nacrt'];
        for ($i = 1; $i <= 10; $i++) {
            $payload["criterion_{$i}"] = 3;
        }

        return array_merge($payload, $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function finalPayload(array $overrides = []): array
    {
        $payload = ['scoring_confirmed' => '1', 'notes' => null];
        for ($i = 1; $i <= 10; $i++) {
            $payload["criterion_{$i}"] = 3;
        }

        return array_merge($payload, $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function youthPassPayload(): array
    {
        return [
            'criterion_1' => '1',
            'criterion_2' => '1',
            'criterion_3' => '1',
            'criterion_notes' => [1 => '', 2 => '', 3 => ''],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function youthFailPayload(): array
    {
        return [
            'criterion_1' => '0',
            'criterion_2' => '0',
            'criterion_3' => '1',
            'criterion_notes' => [
                1 => 'Nedostaje izvod',
                2 => 'Nema M4',
                3 => '',
            ],
            'confirmation_acknowledged' => '1',
        ];
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
        ]);
    }
}
