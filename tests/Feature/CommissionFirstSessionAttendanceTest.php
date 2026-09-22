<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\CommissionSession;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Support\CommissionProfileConfig;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDOException;
use Tests\TestCase;

class CommissionFirstSessionAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_only_assigned_chairman_can_record_and_confirm_first_session_with_quorum_two(): void
    {
        $ctx = $this->makeYouthContext(complete: true, deadlinePassed: true);
        $payload = $this->sessionPayload([$ctx['members'][0]->id, $ctx['members'][1]->id]);

        $this->actingAs($this->userWithRole('konkurs_admin'))
            ->post(route('commission-sessions.first.store', $ctx['competition']), $payload)
            ->assertForbidden();

        $this->actingAs($this->userWithRole('korisnik'))
            ->post(route('commission-sessions.first.store', $ctx['competition']), $payload)
            ->assertForbidden();

        $this->actingAs($ctx['members'][1]->user)
            ->post(route('commission-sessions.first.store', $ctx['competition']), $payload)
            ->assertForbidden();

        $other = $this->makeYouthContext(complete: true, deadlinePassed: true, year: 2027);
        $this->actingAs($other['chairman']->user)
            ->post(route('commission-sessions.first.store', $ctx['competition']), $payload)
            ->assertForbidden();

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.first.store', $ctx['competition']), $this->sessionPayload([$ctx['members'][0]->id]))
            ->assertRedirect(route('commission-sessions.first.edit', $ctx['competition']));

        $session = $ctx['competition']->fresh()->firstCommissionSession();
        $this->assertNotNull($session);
        $this->assertTrue($session->isDraft());
        $this->assertSame(CommissionSession::TYPE_FIRST, $session->session_type);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('commission-sessions.first.edit', $ctx['competition']))
            ->post(route('commission-sessions.first.confirm', $ctx['competition']))
            ->assertRedirect(route('commission-sessions.first.edit', $ctx['competition']))
            ->assertSessionHasErrors('session');
        $this->assertSame(CommissionProfileConfig::SESSION_QUORUM_MESSAGE, session('errors')->first('session'));
        $this->assertTrue($session->fresh()->isDraft());

        $this->actingAs($ctx['chairman']->user)->put(
            route('commission-sessions.first.update', $ctx['competition']),
            $this->sessionPayload([$ctx['members'][0]->id, $ctx['members'][1]->id])
        )->assertSessionHasNoErrors();

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.first.confirm', $ctx['competition']))
            ->assertSessionHasNoErrors();

        $session->refresh();
        $this->assertTrue($session->isConfirmed());
        $this->assertTrue($ctx['competition']->fresh()->hasConfirmedFirstSessionQuorum());
        $this->assertFalse($ctx['competition']->fresh()->isCommissionProcessingBlocked());
        $this->assertSame(0, $ctx['competition']->applications()->whereHas('eliminatoryCheck')->count());

        $this->actingAs($ctx['chairman']->user)->put(
            route('commission-sessions.first.update', $ctx['competition']),
            $this->sessionPayload([$ctx['members'][0]->id, $ctx['members'][1]->id, $ctx['members'][2]->id])
        )->assertSessionHasErrors('session');

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.first.store', $ctx['competition']), $payload)
            ->assertSessionHasErrors('session');
    }

    public function test_inactive_and_foreign_members_are_rejected_and_three_present_are_not_required(): void
    {
        $ctx = $this->makeYouthContext(complete: true, deadlinePassed: true);
        $other = $this->makeYouthContext(complete: true, deadlinePassed: true, year: 2028);
        $inactive = $ctx['members'][2];
        $inactive->update(['status' => 'inactive']);

        $this->actingAs($ctx['chairman']->user)->post(
            route('commission-sessions.first.store', $ctx['competition']),
            $this->sessionPayload([$ctx['members'][0]->id, $other['members'][0]->id])
        )->assertSessionHasErrors('present_member_ids');

        $this->actingAs($ctx['chairman']->user)->post(
            route('commission-sessions.first.store', $ctx['competition']),
            $this->sessionPayload([$ctx['members'][0]->id, $inactive->id])
        )->assertSessionHasErrors('present_member_ids');

        $this->actingAs($ctx['chairman']->user)->post(
            route('commission-sessions.first.store', $ctx['competition']),
            $this->sessionPayload([$ctx['members'][0]->id, $ctx['members'][1]->id])
        )->assertSessionHasNoErrors();

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.first.confirm', $ctx['competition']))
            ->assertSessionHasErrors('session');
        $this->assertSame(
            CommissionProfileConfig::SESSION_INCOMPLETE_COMMISSION_MESSAGE,
            session('errors')->first('session')
        );

        $inactive->update(['status' => 'active']);
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.first.confirm', $ctx['competition']))
            ->assertSessionHasNoErrors();
        $this->assertTrue($ctx['competition']->fresh()->firstCommissionSession()->meetsFirstSessionQuorum($ctx['competition']));
        $this->assertSame(2, $ctx['competition']->fresh()->firstCommissionSession()->validPresentCount($ctx['competition']));
    }

    public function test_processing_is_blocked_without_completeness_or_confirmed_quorum(): void
    {
        $incomplete = $this->makeYouthContext(complete: false, deadlinePassed: true);
        $this->assertTrue($incomplete['competition']->isCommissionProcessingBlocked());
        $this->actingAs($incomplete['chairman']->user)
            ->get(route('evaluation.create', $incomplete['application']))
            ->assertForbidden();

        $complete = $this->makeYouthContext(complete: true, deadlinePassed: true, year: 2029);
        $this->assertTrue($complete['competition']->isCommissionProcessingBlocked());
        $this->actingAs($complete['chairman']->user)
            ->get(route('evaluation.create', $complete['application']))
            ->assertForbidden();

        $this->actingAs($complete['chairman']->user)->post(
            route('commission-sessions.first.store', $complete['competition']),
            $this->sessionPayload([$complete['members'][0]->id, $complete['members'][1]->id])
        )->assertSessionHasNoErrors();
        $this->actingAs($complete['chairman']->user)
            ->post(route('commission-sessions.first.confirm', $complete['competition']))
            ->assertSessionHasNoErrors();

        $this->assertFalse($complete['competition']->fresh()->isCommissionProcessingBlocked());
        $this->actingAs($complete['chairman']->user)
            ->get(route('evaluation.create', $complete['application']))
            ->assertOk();
        $this->assertNull($complete['application']->fresh()->eliminatoryCheck);
    }

    public function test_zensko_processing_does_not_require_a_recorded_session(): void
    {
        $commission = $this->makeZenskoCommission();
        $competition = Competition::create([
            'title' => 'Zensko session '.uniqid(),
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
        UpNumber::create(['competition_id' => $competition->id, 'number' => 'UP-'.uniqid()]);
        $this->assertTrue($competition->hasCompleteValidCommission());
        $this->assertFalse($competition->isCommissionProcessingBlocked());
        $this->assertNull($competition->firstCommissionSession());
    }

    public function test_duplicate_and_unique_first_session_store_returns_controlled_message(): void
    {
        $ctx = $this->makeYouthContext(complete: true, deadlinePassed: true);
        $payload = $this->sessionPayload([$ctx['members'][0]->id, $ctx['members'][1]->id]);
        $payload['notes'] = 'Prvi nacrt ostaje';

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.first.store', $ctx['competition']), $payload)
            ->assertSessionHasNoErrors();

        $duplicate = $this->actingAs($ctx['chairman']->user)
            ->from(route('commission-sessions.first.edit', $ctx['competition']))
            ->post(route('commission-sessions.first.store', $ctx['competition']), $payload);

        $duplicate->assertStatus(302);
        $duplicate->assertSessionHasErrors('session');
        $this->assertNotSame(500, $duplicate->getStatusCode());
        $this->assertSame(1, CommissionSession::query()->where('competition_id', $ctx['competition']->id)->count());
        $this->assertSame('Prvi nacrt ostaje', $ctx['competition']->fresh()->firstCommissionSession()->notes);

        $race = $this->makeYouthContext(complete: true, deadlinePassed: true, year: 2031);
        $dispatcher = CommissionSession::getEventDispatcher();
        $event = 'eloquent.creating: '.CommissionSession::class;
        $dispatcher->listen($event, function () {
            $previous = new PDOException(
                'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry',
                23000
            );
            $previous->errorInfo = ['23000', 1062, 'Duplicate entry'];

            throw new UniqueConstraintViolationException(
                'mysql',
                'insert into `commission_sessions`',
                [],
                $previous
            );
        });

        try {
            $conflict = $this->actingAs($race['chairman']->user)
                ->from(route('commission-sessions.first.edit', $race['competition']))
                ->post(
                    route('commission-sessions.first.store', $race['competition']),
                    $this->sessionPayload([$race['members'][0]->id, $race['members'][1]->id])
                );
            $conflict->assertStatus(302);
            $conflict->assertSessionHasErrors('session');
            $this->assertSame(
                CommissionProfileConfig::SESSION_FIRST_CONFLICT_MESSAGE,
                session('errors')->first('session')
            );
            $this->assertSame(
                0,
                CommissionSession::query()->where('competition_id', $race['competition']->id)->count()
            );
        } finally {
            $dispatcher->forget($event);
        }
    }

    public function test_confirm_first_rechecks_lock_completeness_and_quorum(): void
    {
        $ctx = $this->makeYouthContext(complete: true, deadlinePassed: true, year: 2032);
        $this->actingAs($ctx['chairman']->user)->post(
            route('commission-sessions.first.store', $ctx['competition']),
            $this->sessionPayload([$ctx['members'][0]->id, $ctx['members'][1]->id])
        )->assertSessionHasNoErrors();

        $session = $ctx['competition']->fresh()->firstCommissionSession();
        $this->assertNotNull($session);
        $this->assertNull($session->completed_at);

        $ctx['members'][2]->update(['status' => 'inactive']);
        $this->actingAs($ctx['chairman']->user)
            ->from(route('commission-sessions.first.edit', $ctx['competition']))
            ->post(route('commission-sessions.first.confirm', $ctx['competition']))
            ->assertSessionHasErrors('session');
        $this->assertSame(
            CommissionProfileConfig::SESSION_INCOMPLETE_COMMISSION_MESSAGE,
            session('errors')->first('session')
        );
        $this->assertNull($session->fresh()->completed_at);
        $this->assertSame(3, $session->fresh()->attendances()->count());
        $this->assertSame(2, $session->fresh()->attendances()->where('present', true)->count());

        $ctx['members'][2]->update(['status' => 'active']);
        $session->attendances()->update(['present' => false]);
        $keepPresent = $session->attendances()->orderBy('id')->first();
        $keepPresent->update(['present' => true]);
        $this->actingAs($ctx['chairman']->user)
            ->from(route('commission-sessions.first.edit', $ctx['competition']))
            ->post(route('commission-sessions.first.confirm', $ctx['competition']))
            ->assertSessionHasErrors('session');
        $this->assertSame(CommissionProfileConfig::SESSION_QUORUM_MESSAGE, session('errors')->first('session'));
        $this->assertNull($session->fresh()->completed_at);

        $session->attendances()->update(['present' => true]);
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.first.confirm', $ctx['competition']))
            ->assertSessionHasNoErrors();

        $session->refresh();
        $this->assertNotNull($session->completed_at);
        $confirmedAt = $session->completed_at->toDateTimeString();
        $this->assertSame(
            1,
            CommissionSession::query()
                ->where('competition_id', $ctx['competition']->id)
                ->whereNotNull('completed_at')
                ->count()
        );

        $this->actingAs($ctx['chairman']->user)
            ->from(route('commission-sessions.first.edit', $ctx['competition']))
            ->post(route('commission-sessions.first.confirm', $ctx['competition']))
            ->assertSessionHasErrors('session');
        $this->assertSame(CommissionProfileConfig::SESSION_LOCKED_MESSAGE, session('errors')->first('session'));
        $this->assertSame($confirmedAt, $session->fresh()->completed_at->toDateTimeString());
    }

    public function test_inactive_present_member_before_confirm_keeps_completed_at_null(): void
    {
        $ctx = $this->makeYouthContext(complete: true, deadlinePassed: true, year: 2033);
        $this->actingAs($ctx['chairman']->user)->post(
            route('commission-sessions.first.store', $ctx['competition']),
            $this->sessionPayload([$ctx['members'][0]->id, $ctx['members'][1]->id])
        )->assertSessionHasNoErrors();

        $ctx['members'][1]->update(['status' => 'inactive']);
        $session = $ctx['competition']->fresh()->firstCommissionSession();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.first.confirm', $ctx['competition']))
            ->assertSessionHasErrors('session');
        $this->assertNull($session->fresh()->completed_at);
        $this->assertSame(
            $session->attendances()->count(),
            $session->fresh()->attendances()->count()
        );
    }

    public function test_confirmed_first_session_quorum_survives_later_member_replacement(): void
    {
        $ctx = $this->makeYouthContext(complete: true, deadlinePassed: true, year: 2034);
        $this->actingAs($ctx['chairman']->user)->post(
            route('commission-sessions.first.store', $ctx['competition']),
            $this->sessionPayload([$ctx['members'][0]->id, $ctx['members'][1]->id])
        )->assertSessionHasNoErrors();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.first.confirm', $ctx['competition']))
            ->assertSessionHasNoErrors();

        $session = $ctx['competition']->fresh()->firstCommissionSession();
        $this->assertTrue($session->isConfirmed());
        $this->assertTrue($ctx['competition']->fresh()->hasConfirmedFirstSessionQuorum());
        $this->assertSame(2, $session->historicalConfirmedPresentCount($ctx['competition']));

        $oldSeatTwo = $ctx['members'][1];
        $this->replaceSeat($oldSeatTwo, 2);

        $session = $session->fresh(['attendances.member']);
        $this->assertTrue($oldSeatTwo->fresh()->status === 'inactive');
        $this->assertTrue($session->attendances->contains(
            fn ($row) => (int) $row->commission_member_id === (int) $oldSeatTwo->id && $row->present
        ));
        $this->assertTrue($session->meetsFirstSessionQuorum($ctx['competition']));
        $this->assertTrue($ctx['competition']->fresh()->hasConfirmedFirstSessionQuorum());
        $this->assertFalse($ctx['competition']->fresh()->isCommissionProcessingBlocked());
        $this->assertSame(2, $session->historicalConfirmedPresentCount($ctx['competition']));
        $this->assertSame(1, $session->validPresentCount($ctx['competition']));
        $this->assertTrue($session->historicalConfirmedPresentMembers($ctx['competition'])->contains(
            fn (CommissionMember $member) => (int) $member->id === (int) $oldSeatTwo->id
        ));
    }

    public function test_replacement_before_confirm_invalidates_old_attendance_and_requires_new_member(): void
    {
        $ctx = $this->makeYouthContext(complete: true, deadlinePassed: true, year: 2035);
        $this->actingAs($ctx['chairman']->user)->post(
            route('commission-sessions.first.store', $ctx['competition']),
            $this->sessionPayload([$ctx['members'][0]->id, $ctx['members'][1]->id])
        )->assertSessionHasNoErrors();

        $session = $ctx['competition']->fresh()->firstCommissionSession();
        $oldSeatTwo = $ctx['members'][1];
        $newSeatTwo = $this->replaceSeat($oldSeatTwo, 2);

        $this->assertTrue($ctx['competition']->fresh()->hasCompleteValidCommission());
        $this->actingAs($ctx['chairman']->user)
            ->from(route('commission-sessions.first.edit', $ctx['competition']))
            ->post(route('commission-sessions.first.confirm', $ctx['competition']))
            ->assertSessionHasErrors('session');
        $this->assertSame(CommissionProfileConfig::SESSION_QUORUM_MESSAGE, session('errors')->first('session'));
        $this->assertNull($session->fresh()->completed_at);
        $this->assertSame(0, $session->fresh()->historicalConfirmedPresentCount($ctx['competition']));

        $this->actingAs($ctx['chairman']->user)->put(
            route('commission-sessions.first.update', $ctx['competition']),
            $this->sessionPayload([$ctx['members'][0]->id, $oldSeatTwo->id])
        )->assertSessionHasErrors('present_member_ids');

        $this->actingAs($ctx['chairman']->user)->put(
            route('commission-sessions.first.update', $ctx['competition']),
            $this->sessionPayload([$ctx['members'][0]->id, $newSeatTwo->id])
        )->assertSessionHasNoErrors();

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.first.confirm', $ctx['competition']))
            ->assertSessionHasNoErrors();

        $session = $session->fresh(['attendances.member']);
        $this->assertTrue($session->isConfirmed());
        $this->assertTrue($ctx['competition']->fresh()->hasConfirmedFirstSessionQuorum());
        $this->assertTrue($session->historicalConfirmedPresentMembers($ctx['competition'])->contains(
            fn (CommissionMember $member) => (int) $member->id === (int) $newSeatTwo->id
        ));
        $this->assertFalse($session->historicalConfirmedPresentMembers($ctx['competition'])->contains(
            fn (CommissionMember $member) => (int) $member->id === (int) $oldSeatTwo->id
        ));
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function makeYouthContext(bool $complete, bool $deadlinePassed, int $year = 2026): array
    {
        $commission = Commission::create([
            'name' => 'Mladi '.uniqid(),
            'year' => $year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        $members = [];
        $count = $complete ? 3 : 2;
        for ($seat = 1; $seat <= $count; $seat++) {
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
            'title' => 'Omladinsko sjednica '.$year,
            'description' => 'Opis',
            'start_date' => $deadlinePassed ? now()->subDays(30)->toDateString() : now()->toDateString(),
            'end_date' => $deadlinePassed ? now()->subDays(5)->toDateString() : now()->addDays(20)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => $year,
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => $deadlinePassed ? now()->subDays(30) : now(),
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

    private function makeZenskoCommission(): Commission
    {
        $commission = Commission::create([
            'name' => 'Zenska '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
        $types = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];
        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        foreach ($types as $i => $type) {
            $user = User::factory()->create([
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $i === 0 ? 'predsjednik' : 'clan',
                'member_type' => $type,
                'status' => 'active',
            ]);
        }

        return $commission->fresh(['activeMembers']);
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
    private function sessionPayload(array $presentIds): array
    {
        return [
            'held_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'notes' => 'Prva sjednica',
            'present_member_ids' => $presentIds,
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
