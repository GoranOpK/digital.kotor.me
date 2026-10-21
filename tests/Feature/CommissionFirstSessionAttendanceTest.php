<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\CommissionSession;
use App\Models\CommissionSessionAttendance;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_youth_first_session_form_is_no_longer_used_and_legacy_get_redirects(): void
    {
        $ctx = $this->makeYouthContext(complete: true, deadlinePassed: true);
        $payload = $this->sessionPayload([$ctx['members'][0]->id, $ctx['members'][1]->id]);

        $this->actingAs($ctx['chairman']->user)
            ->get(route('admin.competitions.show', $ctx['competition']))
            ->assertOk()
            ->assertDontSee('Evidentiraj prvu sjednicu Komisije', false);

        $this->actingAs($ctx['chairman']->user)
            ->get(route('commission-sessions.first.edit', $ctx['competition']))
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']))
            ->assertDontSee('Evidencija prve sjednice', false);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.first.store', $ctx['competition']), $payload)
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']));
        $this->actingAs($ctx['chairman']->user)
            ->put(route('commission-sessions.first.update', $ctx['competition']), $payload)
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']));
        $this->actingAs($ctx['chairman']->user)
            ->post(route('commission-sessions.first.confirm', $ctx['competition']))
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']));

        $this->assertNull($ctx['competition']->fresh()->firstCommissionSession());
        $this->assertFalse($ctx['competition']->fresh()->isCommissionProcessingBlocked());
        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk();
    }

    public function test_processing_is_blocked_without_complete_commission_but_not_without_first_session(): void
    {
        $incomplete = $this->makeYouthContext(complete: false, deadlinePassed: true);
        $this->assertTrue($incomplete['competition']->isCommissionProcessingBlocked());
        $this->actingAs($incomplete['chairman']->user)
            ->get(route('evaluation.create', $incomplete['application']))
            ->assertForbidden();

        $complete = $this->makeYouthContext(complete: true, deadlinePassed: true, year: 2029);
        $this->assertTrue($complete['competition']->hasCompleteValidCommission());
        $this->assertNull($complete['competition']->firstCommissionSession());
        $this->assertFalse($complete['competition']->isCommissionProcessingBlocked());
        $this->actingAs($complete['chairman']->user)
            ->get(route('evaluation.create', $complete['application']))
            ->assertOk();
        $this->assertNull($complete['application']->fresh()->eliminatoryCheck);
    }

    public function test_historical_first_session_row_is_kept_and_does_not_open_the_form(): void
    {
        $ctx = $this->makeYouthContext(complete: true, deadlinePassed: true, year: 2034);
        $session = CommissionSession::create([
            'competition_id' => $ctx['competition']->id,
            'commission_id' => $ctx['competition']->commission_id,
            'session_type' => CommissionSession::TYPE_FIRST,
            'held_at' => now()->subDays(3),
            'completed_at' => now()->subDays(3),
            'recorded_by_user_id' => $ctx['chairman']->user_id,
            'notes' => 'Istorijska prva sjednica',
        ]);
        foreach ($ctx['members'] as $index => $member) {
            CommissionSessionAttendance::create([
                'commission_session_id' => $session->id,
                'commission_member_id' => $member->id,
                'present' => $index < 2,
            ]);
        }

        $this->actingAs($ctx['chairman']->user)
            ->get(route('commission-sessions.first.edit', $ctx['competition']))
            ->assertRedirect(route('admin.competitions.show', $ctx['competition']))
            ->assertDontSee('Evidencija prve sjednice', false);

        $this->assertTrue($ctx['competition']->fresh()->firstCommissionSession()->isConfirmed());
        $this->assertSame('Istorijska prva sjednica', $ctx['competition']->fresh()->firstCommissionSession()->notes);
        $this->assertFalse($ctx['competition']->fresh()->isCommissionProcessingBlocked());
        $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $ctx['application']))
            ->assertOk();
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
            'profile_type' => 'omladinsko',
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
            'profile_type' => 'zensko',
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
