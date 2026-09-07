<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionCommissionTimingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_zensko_competition_can_publish_without_commission(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createZenskoCompetition('draft', deadlinePassed: false, commission: null);

        $response = $this->actingAs($admin)->post(route('admin.competitions.publish', $competition));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();

        $competition->refresh();
        $this->assertSame('published', $competition->status);
        $this->assertNull($competition->commission_id);
        $this->assertNotNull($competition->published_at);
    }

    public function test_published_competition_can_receive_valid_commission_before_deadline(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->createZenskoCompetition('published', deadlinePassed: false, commission: null);
        $commission = $this->createCommissionWithMembers(5, withPresident: true);

        $response = $this->actingAs($admin)->put(
            route('admin.competitions.update', $competition),
            $this->updatePayload($competition, $commission->id),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasNoErrors();

        $competition->refresh();
        $this->assertSame($commission->id, $competition->commission_id);
        $this->assertTrue($competition->hasCompleteValidCommission());
    }

    public function test_whole_commission_can_be_replaced_before_deadline_if_complete_and_valid(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $original = $this->createCommissionWithMembers(5, withPresident: true);
        $replacement = $this->createCommissionWithMembers(5, withPresident: true);
        $incomplete = $this->createCommissionWithMembers(2, withPresident: true);
        $competition = $this->createZenskoCompetition('published', deadlinePassed: false, commission: $original);

        $rejected = $this->actingAs($admin)->from(route('admin.competitions.edit', $competition))->put(
            route('admin.competitions.update', $competition),
            $this->updatePayload($competition, $incomplete->id),
        );

        $rejected->assertRedirect(route('admin.competitions.edit', $competition));
        $rejected->assertSessionHasErrors('commission_id');
        $this->assertSame($original->id, $competition->fresh()->commission_id);

        $accepted = $this->actingAs($admin)->put(
            route('admin.competitions.update', $competition),
            $this->updatePayload($competition, $replacement->id),
        );

        $accepted->assertRedirect(route('admin.competitions.show', $competition));
        $accepted->assertSessionHasNoErrors();
        $this->assertSame($replacement->id, $competition->fresh()->commission_id);
    }

    public function test_whole_commission_cannot_be_replaced_after_deadline(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $original = $this->createCommissionWithMembers(5, withPresident: true);
        $replacement = $this->createCommissionWithMembers(5, withPresident: true);
        $competition = $this->createZenskoCompetition('published', deadlinePassed: true, commission: $original);

        $response = $this->actingAs($admin)->from(route('admin.competitions.edit', $competition))->put(
            route('admin.competitions.update', $competition),
            $this->updatePayload($competition, $replacement->id),
        );

        $response->assertRedirect(route('admin.competitions.edit', $competition));
        $response->assertSessionHasErrors('commission_id');
        $this->assertSame($original->id, $competition->fresh()->commission_id);
        $this->assertSame(
            Competition::WHOLE_COMMISSION_REPLACE_AFTER_DEADLINE_MESSAGE,
            session('errors')->first('commission_id'),
        );
    }

    public function test_deadline_expires_normally_without_commission(): void
    {
        $owner = $this->userWithRole('korisnik');
        $competition = $this->createZenskoCompetition('published', deadlinePassed: true, commission: null);

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'business_plan_name' => 'Test plan',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'draft',
        ]);

        $this->assertTrue($competition->isApplicationDeadlinePassed());
        $this->assertFalse($competition->is_open);
        $this->assertSame('published', $competition->status);

        $response = $this->actingAs($owner)->post(route('applications.final-submit', $application));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
        $this->assertSame('draft', $application->fresh()->status);
        $this->assertNull($application->fresh()->submitted_at);
    }

    public function test_after_deadline_processing_is_blocked_while_commission_missing_or_invalid(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $incomplete = $this->createCommissionWithMembers(2, withPresident: true);
        $competition = $this->createZenskoCompetition('published', deadlinePassed: true, commission: null);
        $member = $incomplete->activeMembers()->where('position', 'predsjednik')->first();
        $memberUser = $member->user;

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Test plan',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);

        $this->assertTrue($competition->isCommissionProcessingBlocked());

        $this->actingAs($memberUser)->get(route('evaluation.create', $application))->assertForbidden();
        $this->actingAs($memberUser)->get(route('admin.competitions.ranking', $competition))->assertForbidden();

        $assignIncomplete = $this->actingAs($admin)->put(
            route('admin.competitions.update', $competition),
            $this->updatePayload($competition, $incomplete->id),
        );
        $assignIncomplete->assertRedirect(route('admin.competitions.show', $competition));
        $assignIncomplete->assertSessionHasNoErrors();

        $competition->refresh();
        $this->assertSame($incomplete->id, $competition->commission_id);
        $this->assertTrue($competition->isCommissionProcessingBlocked());

        $this->actingAs($memberUser)->get(route('evaluation.create', $application))->assertForbidden();
        $this->actingAs($memberUser)->get(route('admin.competitions.ranking', $competition))->assertForbidden();
    }

    public function test_processing_may_continue_once_complete_valid_commission_is_assigned_after_deadline(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $valid = $this->createCommissionWithMembers(5, withPresident: true);
        $competition = $this->createZenskoCompetition('published', deadlinePassed: true, commission: null);
        $president = $valid->activeMembers()->where('position', 'predsjednik')->first();

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Test plan',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);

        $this->assertTrue($competition->isCommissionProcessingBlocked());

        $response = $this->actingAs($admin)->put(
            route('admin.competitions.update', $competition),
            $this->updatePayload($competition, $valid->id),
        );

        $response->assertRedirect(route('admin.competitions.show', $competition));
        $response->assertSessionHasNoErrors();

        $competition->refresh();
        $this->assertTrue($competition->hasCompleteValidCommission());
        $this->assertFalse($competition->isCommissionProcessingBlocked());

        $this->actingAs($president->user)->get(route('evaluation.create', $application))->assertOk();
        $this->actingAs($president->user)->get(route('admin.competitions.ranking', $competition))->assertOk();
    }

    public function test_replacement_member_may_be_added_after_deadline_with_existing_traceability(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $commission = $this->createCommissionWithMembers(5, withPresident: true);
        $this->createZenskoCompetition('published', deadlinePassed: true, commission: $commission);
        $replaced = $commission->activeMembers()->where('position', 'clan')->first();

        $response = $this->actingAs($admin)->post(
            route('admin.commissions.members.add', $commission),
            [
                'name' => 'Zamjenski član',
                'email' => 'zamjena.'.uniqid().'@kotor.me',
                'password' => 'password12',
                'position' => 'clan',
                'member_type' => 'zamjenski',
                'replaces_member_number' => 2,
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $substitute = CommissionMember::query()
            ->where('commission_id', $commission->id)
            ->where('is_substitute', true)
            ->first();

        $this->assertNotNull($substitute);
        $this->assertSame(2, $substitute->replaces_member_number);
        $this->assertNotNull($substitute->created_at);
        $this->assertSame('inactive', $replaced->fresh()->status);
        $this->assertSame('active', $substitute->status);
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

    private function createZenskoCompetition(string $status, bool $deadlinePassed, ?Commission $commission): Competition
    {
        $startDate = $deadlinePassed
            ? now()->subDays(30)->toDateString()
            : now()->subDays(2)->toDateString();
        $endDate = $deadlinePassed
            ? now()->subDays(5)->toDateString()
            : now()->addDays(18)->toDateString();

        $competition = Competition::create([
            'title' => 'Konkurs timing '.uniqid(),
            'description' => 'Opis',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'type' => 'zensko',
            'status' => $status,
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'published_at' => $status === 'draft' ? null : now()->subDays($deadlinePassed ? 30 : 2),
            'commission_id' => $commission?->id,
        ]);

        UpNumber::create([
            'competition_id' => $competition->id,
            'number' => 'UP-'.uniqid(),
        ]);

        return $competition->fresh();
    }

    private function createCommissionWithMembers(int $count, bool $withPresident): Commission
    {
        $commission = Commission::create([
            'name' => 'Komisija '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        $types = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];

        for ($i = 0; $i < $count; $i++) {
            $user = User::factory()->create([
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);

            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => ($withPresident && $i === 0) ? 'predsjednik' : 'clan',
                'member_type' => $types[$i] ?? 'opstina',
                'status' => 'active',
            ]);
        }

        return $commission->fresh(['activeMembers.user']);
    }

    private function updatePayload(Competition $competition, ?int $commissionId): array
    {
        return [
            'title' => $competition->title,
            'description' => $competition->description,
            'type' => $competition->type,
            'up_number' => $competition->upNumber->number,
            'year' => $competition->year,
            'budget' => $competition->budget,
            'start_date' => $competition->start_date?->toDateString(),
            'status' => $competition->status,
            'commission_id' => $commissionId,
        ];
    }
}
