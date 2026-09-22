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
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
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

    public function test_second_session_confirms_with_three_present_and_rejects_two(): void
    {
        $ctx = $this->youthWithPassedM3();
        $competition = $ctx['competition'];
        $two = $this->secondPayload([$ctx['members'][0]->id, $ctx['members'][1]->id]);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.second.store', $competition), $two)
            ->assertRedirect(route('commission-sessions.second.edit', $competition));

        $session = $competition->fresh()->secondCommissionSession();
        $this->assertNotNull($session);
        $this->assertTrue($session->isDraft());
        $this->assertSame(CommissionSession::TYPE_SECOND, $session->session_type);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('commission-sessions.second.edit', $competition))
            ->post(route('commission-sessions.second.confirm', $competition))
            ->assertSessionHasErrors('session');
        $this->assertSame(CommissionProfileConfig::SESSION_SECOND_QUORUM_MESSAGE, session('errors')->first('session'));
        $this->assertTrue($session->fresh()->isDraft());

        $this->actingAs($ctx['chairman']->user)->put(
            route('commission-sessions.second.update', $competition),
            $this->secondPayload($this->allMemberIds($ctx))
        )->assertSessionHasNoErrors();

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.second.confirm', $competition))
            ->assertSessionHasNoErrors();

        $session->refresh();
        $this->assertTrue($session->isConfirmed());
        $this->assertTrue($session->meetsSecondSessionAttendance($competition));
        $this->assertSame(3, $session->validPresentCount($competition));
        $this->assertSame(1, CommissionSession::query()->where('competition_id', $competition->id)->where('session_type', CommissionSession::TYPE_SECOND)->count());

        $this->actingAs($ctx['chairman']->user)->put(
            route('commission-sessions.second.update', $competition),
            $this->secondPayload($this->allMemberIds($ctx))
        )->assertSessionHasErrors('session');
        $this->assertSame(CommissionProfileConfig::SESSION_LOCKED_MESSAGE, session('errors')->first('session'));
    }

    public function test_confirmed_second_session_survives_later_member_replacement(): void
    {
        $ctx = $this->youthWithConfirmedSecond();
        $session = $ctx['competition']->fresh()->secondCommissionSession();
        $this->assertTrue($session->isConfirmed());
        $this->assertTrue($session->meetsSecondSessionAttendance($ctx['competition']));
        $this->assertSame(3, $session->historicalConfirmedPresentCount($ctx['competition']));

        $oldSeatTwo = $ctx['members'][1];
        $this->replaceSeat($oldSeatTwo, 2);

        $session = $session->fresh(['attendances.member']);
        $this->assertTrue($oldSeatTwo->fresh()->status === 'inactive');
        $this->assertTrue($session->meetsSecondSessionAttendance($ctx['competition']));
        $this->assertTrue($session->chairmanIsPresent($ctx['competition']));
        $this->assertSame(3, $session->historicalConfirmedPresentCount($ctx['competition']));
        $this->assertSame(2, $session->validPresentCount($ctx['competition']));
        $this->assertTrue($session->historicalConfirmedPresentMembers($ctx['competition'])->contains(
            fn (CommissionMember $member) => (int) $member->id === (int) $oldSeatTwo->id
        ));

        $this->storeAndCompleteOral($ctx, attended: true);
        $this->assertTrue($ctx['application']->fresh()->oralPresentation->isCompleted());
    }

    public function test_unconfirmed_second_session_cannot_use_inactive_replaced_member(): void
    {
        $ctx = $this->youthWithPassedM3();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.second.store', $ctx['competition']), $this->secondPayload($this->allMemberIds($ctx)))
            ->assertSessionHasNoErrors();

        $session = $ctx['competition']->fresh()->secondCommissionSession();
        $this->assertTrue($session->isDraft());

        $oldSeatTwo = $ctx['members'][1];
        $newSeatTwo = $this->replaceSeat($oldSeatTwo, 2);

        $this->assertTrue($ctx['competition']->fresh()->hasCompleteValidCommission());
        $this->actingAs($ctx['chairman']->user)
            ->from(route('commission-sessions.second.edit', $ctx['competition']))
            ->post(route('commission-sessions.second.confirm', $ctx['competition']))
            ->assertSessionHasErrors('session');
        $this->assertSame(CommissionProfileConfig::SESSION_SECOND_QUORUM_MESSAGE, session('errors')->first('session'));
        $this->assertTrue($session->fresh()->isDraft());
        $this->assertFalse($session->fresh()->meetsSecondSessionAttendance($ctx['competition']));

        $this->actingAs($ctx['chairman']->user)->put(
            route('commission-sessions.second.update', $ctx['competition']),
            $this->secondPayload([$ctx['members'][0]->id, $oldSeatTwo->id, $ctx['members'][2]->id])
        )->assertSessionHasErrors('present_member_ids');

        $this->actingAs($ctx['chairman']->user)->put(
            route('commission-sessions.second.update', $ctx['competition']),
            $this->secondPayload([$ctx['members'][0]->id, $newSeatTwo->id, $ctx['members'][2]->id])
        )->assertSessionHasNoErrors();

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.second.confirm', $ctx['competition']))
            ->assertSessionHasNoErrors();

        $session = $session->fresh(['attendances.member']);
        $this->assertTrue($session->isConfirmed());
        $this->assertTrue($session->meetsSecondSessionAttendance($ctx['competition']));
        $this->assertTrue($session->historicalConfirmedPresentMembers($ctx['competition'])->contains(
            fn (CommissionMember $member) => (int) $member->id === (int) $newSeatTwo->id
        ));
        $this->assertFalse($session->historicalConfirmedPresentMembers($ctx['competition'])->contains(
            fn (CommissionMember $member) => (int) $member->id === (int) $oldSeatTwo->id
        ));
    }

    public function test_one_second_session_per_call_and_controlled_duplicate_message(): void
    {
        $ctx = $this->youthWithPassedM3();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.second.store', $ctx['competition']), $this->secondPayload($this->allMemberIds($ctx)))
            ->assertSessionHasNoErrors();

        $duplicate = $this->actingAs($ctx['chairman']->user)
            ->from(route('commission-sessions.second.edit', $ctx['competition']))
            ->post(route('commission-sessions.second.store', $ctx['competition']), $this->secondPayload($this->allMemberIds($ctx)));
        $duplicate->assertSessionHasErrors('session');
        $this->assertSame(CommissionProfileConfig::SESSION_SECOND_EXISTS_MESSAGE, session('errors')->first('session'));
        $this->assertSame(1, CommissionSession::query()->where('competition_id', $ctx['competition']->id)->where('session_type', CommissionSession::TYPE_SECOND)->count());

        $race = $this->youthWithPassedM3();
        $dispatcher = CommissionSession::getEventDispatcher();
        $event = 'eloquent.creating: '.CommissionSession::class;
        $listener = function () {
            $previous = new PDOException(
                'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry for key \'commission_sessions_competition_type_unique\'',
                23000
            );
            $previous->errorInfo = ['23000', 1062, 'Duplicate entry for key \'commission_sessions_competition_type_unique\''];
            throw new UniqueConstraintViolationException('mysql', 'insert into `commission_sessions`', [], $previous);
        };
        $dispatcher->listen($event, $listener);
        try {
            $conflict = $this->actingAs($race['chairman']->user)
                ->from(route('commission-sessions.second.edit', $race['competition']))
                ->post(route('commission-sessions.second.store', $race['competition']), $this->secondPayload($this->allMemberIds($race)));
            $conflict->assertStatus(302);
            $conflict->assertSessionHasErrors('session');
            $this->assertSame(CommissionProfileConfig::SESSION_SECOND_CONFLICT_MESSAGE, session('errors')->first('session'));
            $this->assertSame(0, CommissionSession::query()->where('competition_id', $race['competition']->id)->where('session_type', CommissionSession::TYPE_SECOND)->count());
        } finally {
            $dispatcher->forget($event);
        }
    }

    public function test_chairman_on_seats_one_two_or_three_can_confirm_second_session(): void
    {
        foreach ([1, 2, 3] as $chairSeat) {
            $ctx = $this->makeYouthContext();
            foreach ($ctx['members'] as $index => $member) {
                $member->update(['position' => ($index + 1) === $chairSeat ? 'predsjednik' : 'clan']);
            }
            $chairman = $ctx['members'][$chairSeat - 1]->fresh('user');
            $this->actingAs($chairman->user)->post(
                route('commission-sessions.first.store', $ctx['competition']),
                $this->firstPayload([$ctx['members'][0]->id, $ctx['members'][1]->id])
            )->assertSessionHasNoErrors();
            $this->actingAs($chairman->user)
                ->post(route('commission-sessions.first.confirm', $ctx['competition']))
                ->assertSessionHasNoErrors();
            $this->actingAs($chairman->user)
                ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $this->youthPassPayload())
                ->assertRedirect();

            $this->actingAs($chairman->user)
                ->post(route('commission-sessions.second.store', $ctx['competition']), $this->secondPayload($this->allMemberIds($ctx)))
                ->assertSessionHasNoErrors();
            $this->actingAs($chairman->user)
                ->post(route('commission-sessions.second.confirm', $ctx['competition']))
                ->assertSessionHasNoErrors();
            $this->assertTrue($ctx['competition']->fresh()->secondCommissionSession()->isConfirmed());
        }
    }

    public function test_ordinary_admin_other_commission_inactive_chairman_and_zensko_cannot_mutate_second_session(): void
    {
        $ctx = $this->youthWithPassedM3();
        $payload = $this->secondPayload($this->allMemberIds($ctx));

        $this->actingAs($this->userWithRole('admin'))
            ->post(route('commission-sessions.second.store', $ctx['competition']), $payload)
            ->assertForbidden();
        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->post(route('commission-sessions.second.store', $ctx['competition']), $payload)
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
            ->post(route('commission-sessions.second.store', $b['competition']), $this->secondPayload($this->allMemberIds($b)))
            ->assertForbidden();
        $this->assertNull($b['competition']->fresh()->secondCommissionSession());

        $this->actingAs($a['chairman']->user)
            ->post(route('commission-sessions.second.store', $a['competition']), $this->secondPayload($this->allMemberIds($a)))
            ->assertSessionHasNoErrors();
        $this->assertNotNull($a['competition']->fresh()->secondCommissionSession());
    }

    public function test_open_appeal_window_and_podnesen_prigovor_block_second_session(): void
    {
        $open = $this->confirmYouthFail();
        $this->assertTrue(app(\App\Services\YouthSecondSessionGate::class)->appealWindowIsOpen($open['application']->fresh()));
        $this->actingAs($open['chairman']->user)
            ->from(route('commission-sessions.second.edit', $open['competition']))
            ->post(route('commission-sessions.second.store', $open['competition']), $this->secondPayload($this->allMemberIds($open)))
            ->assertSessionHasErrors('session');
        $this->assertSame(CommissionProfileConfig::SESSION_SECOND_APPEAL_GATE_MESSAGE, session('errors')->first('session'));
        $this->assertNull($open['competition']->fresh()->secondCommissionSession());

        $podnesen = $this->confirmYouthFail();
        $this->actingAs($podnesen['application']->user)->post(route('applications.prigovor.store', $podnesen['application']), [
            'contested' => [1, 2],
            'criterion_obrazlozenja' => [1 => 'Obrazloženje 1.', 2 => 'Obrazloženje 2.'],
        ])->assertRedirect();
        $podnesen['application']->eliminatoryNotice->update(['sent_at' => now()->subDays(4)]);
        $this->assertTrue($podnesen['application']->fresh()->prigovor->isPodnesen());
        $this->actingAs($podnesen['chairman']->user)
            ->post(route('commission-sessions.second.store', $podnesen['competition']), $this->secondPayload($this->allMemberIds($podnesen)))
            ->assertSessionHasErrors('session');
        $this->assertSame(CommissionProfileConfig::SESSION_SECOND_APPEAL_GATE_MESSAGE, session('errors')->first('session'));
    }

    public function test_favorable_final_prigovor_enables_oral_after_confirmed_second_session(): void
    {
        $ctx = $this->confirmYouthFail();
        $this->actingAs($ctx['application']->user)->post(route('applications.prigovor.store', $ctx['application']), [
            'contested' => [1, 2],
            'criterion_obrazlozenja' => [1 => 'Obrazloženje 1.', 2 => 'Obrazloženje 2.'],
        ])->assertRedirect();
        $ctx['application']->eliminatoryNotice->update(['sent_at' => now()->subDays(4)]);
        $this->actingAs($ctx['chairman']->user)->post(route('evaluation.prigovor.decide', $ctx['application']), [
            'decision_note' => 'Svi osporeni razlozi su otklonjeni.',
            'criterion_outcomes' => [1 => 'otklonjen', 2 => 'otklonjen'],
        ])->assertRedirect();

        $application = $ctx['application']->fresh();
        $this->assertTrue($application->prigovor->liftsEliminatoryBar());
        $this->assertSame('submitted', $application->status);

        $this->confirmSecond($ctx);
        $this->storeAndCompleteOral($ctx, attended: true);
        $this->assertTrue($application->fresh()->oralPresentation->isCompleted());
        $this->assertTrue($application->fresh()->oralPresentation->applicantAttended());
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($application->fresh()));
    }

    public function test_oral_requires_confirmed_second_session_and_allows_multiple_orals(): void
    {
        $ctx = $this->youthWithPassedM3();
        $secondApp = $this->addSubmittedApplication($ctx['competition']);
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $secondApp), $this->youthPassPayload())
            ->assertRedirect();

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.second.store', $ctx['competition']), $this->secondPayload($this->allMemberIds($ctx)))
            ->assertSessionHasNoErrors();

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$ctx['competition'], $ctx['application']]), [
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'notes' => 'Plan',
            ])->assertSessionHasNoErrors();

        $this->actingAs($ctx['chairman']->user)
            ->from(route('commission-sessions.oral.edit', [$ctx['competition'], $ctx['application']]))
            ->post(route('commission-sessions.oral.complete', [$ctx['competition'], $ctx['application']]), $this->oralCompletePayload(true))
            ->assertSessionHasErrors('oral');
        $this->assertSame(CommissionProfileConfig::ORAL_SESSION_NOT_CONFIRMED_MESSAGE, session('errors')->first('oral'));
        $this->assertTrue($ctx['application']->fresh()->oralPresentation->isDraft());

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.second.confirm', $ctx['competition']))
            ->assertSessionHasNoErrors();
        $this->assertTrue($ctx['competition']->fresh()->secondCommissionSession()->isConfirmed());
        $this->assertTrue($ctx['application']->fresh()->oralPresentation->isDraft());

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.complete', [$ctx['competition'], $ctx['application']]), $this->oralCompletePayload(true))
            ->assertSessionHasNoErrors();

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$ctx['competition'], $secondApp]), [
                'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            ])->assertSessionHasNoErrors();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.complete', [$ctx['competition'], $secondApp]), $this->oralCompletePayload(true))
            ->assertSessionHasNoErrors();

        $sessionId = $ctx['competition']->fresh()->secondCommissionSession()->id;
        $this->assertSame(2, ApplicationOralPresentation::query()->where('commission_session_id', $sessionId)->count());
        $this->assertTrue($ctx['application']->fresh()->oralPresentation->isCompleted());
        $this->assertTrue($secondApp->fresh()->oralPresentation->isCompleted());
    }

    public function test_attendance_and_no_show_do_not_reject_or_score_and_lock_completed_record(): void
    {
        $ctx = $this->youthWithConfirmedSecond();
        $ctx['application']->update(['interview_scheduled_at' => '2026-04-01 10:00:00']);
        $interview = $ctx['application']->fresh()->interview_scheduled_at?->toDateTimeString();

        $this->storeAndCompleteOral($ctx, attended: true);
        $oral = $ctx['application']->fresh()->oralPresentation;
        $this->assertTrue($oral->applicantAttended());
        $this->assertNotNull($oral->held_at);
        $this->assertSame('submitted', $ctx['application']->fresh()->status);
        $this->assertSame($interview, $ctx['application']->fresh()->interview_scheduled_at?->toDateTimeString());
        $this->assertTrue(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($ctx['application']->fresh()));

        $noShow = $this->youthWithConfirmedSecond();
        $noShow['application']->update(['interview_scheduled_at' => '2026-05-01 09:00:00']);
        $this->storeAndCompleteOral($noShow, attended: false);
        $record = $noShow['application']->fresh()->oralPresentation;
        $this->assertTrue($record->isNoShow());
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
        )->assertSessionHasErrors('oral');
        $this->assertSame(CommissionProfileConfig::ORAL_LOCKED_MESSAGE, session('errors')->first('oral'));
        $this->actingAs($noShow['chairman']->user)
            ->post(route('commission-sessions.oral.complete', [$noShow['competition'], $noShow['application']]), $this->oralCompletePayload(false))
            ->assertSessionHasErrors('oral');
        $this->assertSame(CommissionProfileConfig::ORAL_LOCKED_MESSAGE, session('errors')->first('oral'));
        $this->assertSame('Usmeno locked', $noShow['application']->fresh()->oralPresentation->notes);
        $this->assertTrue($noShow['application']->fresh()->oralPresentation->isCompleted());
    }

    public function test_duplicate_oral_idor_and_rejected_application_are_blocked(): void
    {
        $ctx = $this->youthWithConfirmedSecond();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$ctx['competition'], $ctx['application']]), ['notes' => 'prvi'])
            ->assertSessionHasNoErrors();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$ctx['competition'], $ctx['application']]), ['notes' => 'drugi'])
            ->assertSessionHasErrors('oral');
        $this->assertSame(CommissionProfileConfig::ORAL_EXISTS_MESSAGE, session('errors')->first('oral'));
        $this->assertSame(1, ApplicationOralPresentation::query()->where('application_id', $ctx['application']->id)->count());

        $race = $this->youthWithConfirmedSecond();
        $dispatcher = ApplicationOralPresentation::getEventDispatcher();
        $event = 'eloquent.creating: '.ApplicationOralPresentation::class;
        $listener = function () {
            $previous = new PDOException(
                'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry for key \'application_oral_presentations_application_id_unique\'',
                23000
            );
            $previous->errorInfo = ['23000', 1062, 'Duplicate entry for key \'application_oral_presentations_application_id_unique\''];
            throw new UniqueConstraintViolationException('mysql', 'insert into `application_oral_presentations`', [], $previous);
        };
        $dispatcher->listen($event, $listener);
        try {
            $conflict = $this->actingAs($race['chairman']->user)
                ->from(route('commission-sessions.oral.edit', [$race['competition'], $race['application']]))
                ->post(route('commission-sessions.oral.store', [$race['competition'], $race['application']]), ['notes' => 'trka']);
            $conflict->assertSessionHasErrors('oral');
            $this->assertSame(CommissionProfileConfig::ORAL_CONFLICT_MESSAGE, session('errors')->first('oral'));
        } finally {
            $dispatcher->forget($event);
        }

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
            ->assertSessionHasErrors('oral');
        $this->assertSame(0, ApplicationOralPresentation::query()->where('application_id', $rejected['application']->id)->count());
    }

    public function test_unrecognized_database_errors_are_not_swallowed_for_oral_insert(): void
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
            $this->withoutExceptionHandling();
            $this->expectException(QueryException::class);
            $this->actingAs($otherError['chairman']->user)
                ->post(route('commission-sessions.oral.store', [$otherError['competition'], $otherError['application']]), ['notes' => 'db']);
        } finally {
            $dispatcher->forget($event);
        }
    }

    public function test_seven_day_deadline_is_displayed_and_late_entry_is_allowed(): void
    {
        $ctx = $this->youthWithPassedM3();
        $first = $ctx['competition']->firstCommissionSession();
        $first->update(['held_at' => now()->subDays(10)]);
        $deadline = $first->fresh()->held_at->copy()->addDays(7)->format('d.m.Y H:i');

        $this->actingAs($ctx['chairman']->user)
            ->get(route('commission-sessions.second.edit', $ctx['competition']))
            ->assertOk()
            ->assertSee('Rok od 7 dana od prve sjednice', false)
            ->assertSee($deadline, false)
            ->assertSee('prekoračen', false);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.second.store', $ctx['competition']), $this->secondPayload($this->allMemberIds($ctx)))
            ->assertSessionHasNoErrors();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.second.confirm', $ctx['competition']))
            ->assertSessionHasNoErrors();
        $this->assertTrue($ctx['competition']->fresh()->secondCommissionSession()->isConfirmed());
    }

    public function test_youth_call_show_exposes_second_session_status_and_zensko_show_does_not(): void
    {
        $ctx = $this->youthWithPassedM3();
        $response = $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.show', $ctx['competition']))
            ->assertOk();
        $response->assertSeeText('Druga sjednica: nije evidentirana');
        $response->assertSee('Evidentiraj drugu sjednicu i usmeno predstavljanje', false);

        $zensko = $this->makeZenskoCompetition();
        $this->actingAs($zensko['chairman']->user)
            ->get(route('admin.competitions.show', $zensko['competition']))
            ->assertOk()
            ->assertDontSee('usmeno predstavljanje', false)
            ->assertDontSee('Druga sjednica', false);
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function youthWithConfirmedSecond(): array
    {
        $ctx = $this->youthWithPassedM3();
        $this->confirmSecond($ctx);

        return $ctx;
    }

    /**
     * @param  array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>}  $ctx
     */
    private function confirmSecond(array $ctx): void
    {
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.second.store', $ctx['competition']), $this->secondPayload($this->allMemberIds($ctx)))
            ->assertSessionHasNoErrors();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.second.confirm', $ctx['competition']))
            ->assertSessionHasNoErrors();
    }

    /**
     * @param  array{competition: Competition, chairman: CommissionMember, application: Application}  $ctx
     */
    private function storeAndCompleteOral(array $ctx, bool $attended): void
    {
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.store', [$ctx['competition'], $ctx['application']]), [
                'scheduled_at' => now()->format('Y-m-d H:i:s'),
                'notes' => $attended ? 'Prisutan' : 'Usmeno locked',
            ])->assertSessionHasNoErrors();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.oral.complete', [$ctx['competition'], $ctx['application']]), $this->oralCompletePayload($attended))
            ->assertSessionHasNoErrors();
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
        $ctx = $this->makeYouthContext();
        $this->actingAs($ctx['chairman']->user)->post(
            route('commission-sessions.first.store', $ctx['competition']),
            $this->firstPayload([$ctx['members'][0]->id, $ctx['members'][1]->id])
        )->assertSessionHasNoErrors();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.first.confirm', $ctx['competition']))
            ->assertSessionHasNoErrors();

        return $ctx;
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
