<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZenskoCommissionApplicationCountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_commission_sees_no_application_count_before_the_real_deadline(): void
    {
        $competition = $this->competition('zensko', 'published', false);
        $member = $this->attachCompleteCommission($competition);
        $this->application($competition, 'submitted', 'Zenska Podnesena Jedan');
        $this->application($competition, 'submitted', 'Zenska Podnesena Dva');
        $this->application($competition, 'draft', 'Zenska Nacrt Jedan');

        foreach (['active', 'all'] as $tab) {
            $response = $this->actingAs($member)->get(route('admin.competitions.index', ['tab' => $tab]));

            $response->assertOk();
            $this->assertNull($this->listedCount($response, $competition->id));
            $response->assertSee('<td>—</td>', false);
            $response->assertDontSee('<td>1</td>', false);
            $response->assertDontSee('<td>2</td>', false);
            $response->assertDontSee('<td>3</td>', false);
            $response->assertDontSee('Zenska Podnesena Jedan');
            $response->assertDontSee('Zenska Podnesena Dva');
            $response->assertDontSee('Zenska Nacrt Jedan');
        }
    }

    public function test_completed_status_before_the_real_deadline_does_not_reveal_the_count(): void
    {
        $competition = $this->competition('zensko', 'completed', false);
        $member = $this->attachCompleteCommission($competition);
        $this->application($competition, 'submitted');
        $this->application($competition, 'draft');

        foreach (['archive', 'all'] as $tab) {
            $response = $this->actingAs($member)->get(route('admin.competitions.index', ['tab' => $tab]));

            $response->assertOk();
            $this->assertNull($this->listedCount($response, $competition->id));
            $response->assertSee('<td>—</td>', false);
            $response->assertDontSee('<td>1</td>', false);
            $response->assertDontSee('<td>2</td>', false);
        }

        $archive = $this->actingAs($member)->get(route('competitions.archive'));
        $archive->assertOk();
        $this->assertNull($this->listedCount($archive, $competition->id));
        $archive->assertSee('<td>—</td>', false);
        $archive->assertDontSee('<td>1</td>', false);
        $archive->assertDontSee('<td>2</td>', false);
        $archive->assertDontSee('Zenska Podnesena 1');
    }

    public function test_commission_sees_only_non_draft_applications_after_the_real_deadline(): void
    {
        $competition = $this->competition('zensko', 'published', true);
        $member = $this->attachCompleteCommission($competition);
        $this->application($competition, 'submitted');
        $this->application($competition, 'submitted');
        $this->application($competition, 'evaluated');
        $this->application($competition, 'draft');

        $response = $this->actingAs($member)->get(route('admin.competitions.index'));

        $response->assertOk();
        $this->assertSame(3, $this->listedCount($response, $competition->id));
        $response->assertSee('<td>3</td>', false);
        $response->assertDontSee('<td>4</td>', false);
        $response->assertDontSee('<td>1</td>', false);
    }

    public function test_incomplete_commission_does_not_receive_the_count_after_the_real_deadline(): void
    {
        $competition = $this->competition('zensko', 'published', true);
        $member = $this->attachIncompleteCommission($competition);
        $this->application($competition, 'submitted');
        $this->application($competition, 'submitted');
        $this->application($competition, 'draft');

        $response = $this->actingAs($member)->get(route('admin.competitions.index'));

        $response->assertOk();
        $this->assertNull($this->listedCount($response, $competition->id));
        $response->assertSee('<td>—</td>', false);
        $response->assertDontSee('<td>2</td>', false);
        $response->assertDontSee('<td>3</td>', false);
    }

    public function test_competition_admin_keeps_the_total_recorded_count(): void
    {
        $competition = $this->competition('zensko', 'published', false);
        $this->application($competition, 'submitted');
        $this->application($competition, 'submitted');
        $this->application($competition, 'draft');
        $admin = $this->userWithRole('konkurs_admin');

        $response = $this->actingAs($admin)->get(route('admin.competitions.index'));

        $response->assertOk();
        $this->assertSame(3, $this->listedCount($response, $competition->id));
        $response->assertSee('<td>3</td>', false);
    }

    public function test_youth_and_mmsp_commission_counts_stay_unchanged_before_the_deadline(): void
    {
        $youth = $this->competition(CompetitionProfileResolver::TYPE_YOUTH, 'published', false);
        $mmsp = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'published', false);
        $member = $this->attachCompleteCommission($youth);
        $mmsp->update(['commission_id' => $youth->commission_id]);
        $this->application($youth, 'submitted');
        $this->application($youth, 'draft');
        $this->application($mmsp, 'submitted');
        $this->application($mmsp, 'submitted');
        $this->application($mmsp, 'draft');

        $response = $this->actingAs($member)->get(route('admin.competitions.index'));

        $response->assertOk();
        $this->assertSame(2, $this->listedCount($response, $youth->id));
        $this->assertSame(3, $this->listedCount($response, $mmsp->id));
        $response->assertSee('<td>2</td>', false);
        $response->assertSee('<td>3</td>', false);
    }

    public function test_dashboard_does_not_reveal_zensko_applications_before_the_real_deadline(): void
    {
        $zensko = $this->competition('zensko', 'completed', false);
        $youth = $this->competition(CompetitionProfileResolver::TYPE_YOUTH, 'closed', false);
        $member = $this->attachCompleteCommission($zensko);
        $youth->update(['commission_id' => $zensko->commission_id]);
        $this->application($zensko, 'submitted', 'Zenska Skrivena Tabla');
        $this->application($youth, 'submitted', 'Omladinski Vidljiv Tabla');

        $response = $this->actingAs($member)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Zenska Skrivena Tabla');
        $response->assertSee('Omladinski Vidljiv Tabla');
    }

    public function test_direct_archive_route_does_not_open_new_commission_access(): void
    {
        $competition = $this->competition('zensko', 'completed', false);
        $this->attachCompleteCommission($competition);
        $this->application($competition, 'submitted', 'Zenska Arhiva Sakrivena');
        $outsider = $this->userWithRole('komisija');

        $this->actingAs($outsider)
            ->get(route('competitions.archive'))
            ->assertForbidden()
            ->assertDontSee('Zenska Arhiva Sakrivena');
    }

    private function listedCount($response, int $competitionId): mixed
    {
        $listed = collect($response->viewData('competitions')->items())
            ->firstWhere('id', $competitionId);

        $this->assertNotNull($listed);

        return $listed->applications_count;
    }

    private function userWithRole(string $role, ?string $name = null): User
    {
        return User::factory()->create([
            'name' => $name ?? ('Korisnik '.$role.' '.uniqid()),
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function competition(string $type, string $status, bool $deadlinePassed): Competition
    {
        return Competition::create([
            'title' => $type.' '.$status.' '.uniqid(),
            'type' => $type,
            'status' => $status,
            'year' => (int) now()->year,
            'budget' => 10000,
            'deadline_days' => 20,
            'start_date' => $deadlinePassed ? now()->subDays(40)->toDateString() : now()->subDay()->toDateString(),
            'end_date' => $deadlinePassed ? now()->subDays(10)->toDateString() : now()->addDays(19)->toDateString(),
            'published_at' => now()->subDays(40),
        ]);
    }

    private function application(Competition $competition, string $status, ?string $applicantName = null): Application
    {
        $applicantName ??= 'Zenska '.ucfirst($status).' '.($competition->applications()->count() + 1);
        $owner = $this->userWithRole('korisnik', $applicantName);

        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'applicant_type' => 'preduzetnik',
            'status' => $status,
            'business_plan_name' => 'Plan '.$applicantName,
            'founder_name' => $applicantName,
            'submitted_at' => $status === 'draft' ? null : now(),
            'redni_broj' => $competition->applications()->count() + 1,
        ]);
    }

    private function attachCompleteCommission(Competition $competition): User
    {
        return $this->attachCommission($competition, 5);
    }

    private function attachIncompleteCommission(Competition $competition): User
    {
        return $this->attachCommission($competition, 1);
    }

    private function attachCommission(Competition $competition, int $members): User
    {
        $commission = Commission::create([
            'name' => 'Komisija '.uniqid(),
            'year' => (int) now()->year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => $competition->type,
        ]);

        $chair = null;
        $types = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];

        for ($i = 0; $i < $members; $i++) {
            $user = $this->userWithRole('komisija');
            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $i === 0 ? 'predsjednik' : 'clan',
                'member_type' => $types[$i],
                'status' => 'active',
                'canonical_seat_no' => $i + 1,
            ]);
            $chair ??= $user;
        }

        $competition->update(['commission_id' => $commission->id]);

        return $chair;
    }
}
