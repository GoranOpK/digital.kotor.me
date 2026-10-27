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

class ZenskoPreDeadlineAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_platform_admins_cannot_see_individual_zensko_applications_before_the_deadline(): void
    {
        $open = $this->competition('zensko', 'published', false);
        $application = $this->application($open, 'Zenska Podnositeljka Alfa', 'Plan Alfa Zensko');
        $other = $this->application($open, 'Zenska Podnositeljka Beta', 'Plan Beta Zensko');

        foreach (['admin', 'superadmin'] as $role) {
            $actor = $this->userWithRole($role);

            $this->actingAs($actor)
                ->get(route('admin.competitions.show', $open))
                ->assertOk()
                ->assertDontSee('Zenska Podnositeljka Alfa')
                ->assertDontSee('Plan Alfa Zensko')
                ->assertDontSee('Zenska Podnositeljka Beta')
                ->assertDontSee(route('admin.applications.show', $application));

            $this->actingAs($actor)
                ->get(route('admin.applications.index'))
                ->assertOk()
                ->assertDontSee('Zenska Podnositeljka Alfa')
                ->assertDontSee('Plan Alfa Zensko');

            $this->actingAs($actor)
                ->get(route('admin.applications.index', ['search' => 'Plan Alfa Zensko', 'competition_id' => $open->id]))
                ->assertOk()
                ->assertDontSee('Zenska Podnositeljka Alfa');

            $this->actingAs($actor)
                ->get(route('admin.applications.show', $application))
                ->assertForbidden()
                ->assertDontSee('Zenska Podnositeljka Alfa');

            $this->actingAs($actor)
                ->get(route('admin.applications.show', $other))
                ->assertForbidden()
                ->assertDontSee('Zenska Podnositeljka Beta');

            $this->actingAs($actor)
                ->get(route('applications.create', $open).'?application_id='.$application->id)
                ->assertForbidden()
                ->assertDontSee('Plan Alfa Zensko');

            $this->actingAs($actor)
                ->get(route('applications.create', $open).'?application_id='.$other->id)
                ->assertForbidden()
                ->assertDontSee('Plan Beta Zensko');
        }
    }

    public function test_competition_admin_cannot_open_individual_zensko_application_before_or_after_deadline_until_archived(): void
    {
        $konkursAdmin = $this->userWithRole('konkurs_admin');
        $open = $this->competition('zensko', 'published', false);
        $openApplication = $this->application($open, 'Zenska Sakrivena Prije Roka', 'Plan Sakriven Prije Roka');

        $this->actingAs($konkursAdmin)
            ->get(route('admin.competitions.show', $open))
            ->assertOk()
            ->assertDontSee('Zenska Sakrivena Prije Roka')
            ->assertDontSee('Plan Sakriven Prije Roka');

        $index = $this->actingAs($konkursAdmin)->get(route('admin.competitions.index'));
        $index->assertOk()->assertDontSee('Zenska Sakrivena Prije Roka');
        $listed = $index->viewData('competitions')->firstWhere('id', $open->id);
        $this->assertNotNull($listed);
        $this->assertSame(1, $listed->applications_count);

        $this->actingAs($konkursAdmin)
            ->get(route('admin.applications.show', $openApplication))
            ->assertForbidden();

        $this->actingAs($konkursAdmin)
            ->get(route('admin.applications.index'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertDontSee('Zenska Sakrivena Prije Roka')
            ->assertDontSee('Plan Sakriven Prije Roka');

        $expired = $this->competition('zensko', 'published', true);
        $expiredApplication = $this->application($expired, 'Zenska Poslije Roka', 'Plan Poslije Roka');

        $this->actingAs($konkursAdmin)
            ->get(route('admin.applications.show', $expiredApplication))
            ->assertForbidden()
            ->assertDontSee('Zenska Poslije Roka');

        $this->actingAs($konkursAdmin)
            ->get(route('applications.create', $expired).'?application_id='.$expiredApplication->id)
            ->assertForbidden();
    }

    public function test_completed_status_before_the_real_deadline_does_not_unlock_zensko_content(): void
    {
        $competition = $this->competition('zensko', 'completed', false);
        $application = $this->application($competition, 'Zenska Nekonzistentni Status', 'Plan Nekonzistentni Status');
        $commissionUser = $this->attachCompleteCommission($competition);

        foreach (['admin', 'superadmin', 'konkurs_admin'] as $role) {
            $actor = $this->userWithRole($role);

            $this->actingAs($actor)
                ->get(route('admin.competitions.show', $competition))
                ->assertOk()
                ->assertDontSee('Zenska Nekonzistentni Status')
                ->assertDontSee('Plan Nekonzistentni Status');

            $this->actingAs($actor)
                ->get(route('admin.applications.show', $application))
                ->assertForbidden()
                ->assertDontSee('Zenska Nekonzistentni Status');

            $this->actingAs($actor)
                ->get(route('applications.create', $competition).'?application_id='.$application->id)
                ->assertForbidden()
                ->assertDontSee('Plan Nekonzistentni Status');
        }

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertDontSee('Zenska Nekonzistentni Status');

        $this->actingAs($commissionUser)
            ->get(route('admin.applications.show', $application))
            ->assertForbidden()
            ->assertDontSee('Zenska Nekonzistentni Status');

        $this->actingAs($commissionUser)
            ->get(route('admin.competitions.show', $competition))
            ->assertOk()
            ->assertDontSee('Zenska Nekonzistentni Status');
    }

    public function test_owner_keeps_access_and_commission_opens_submitted_application_after_the_real_deadline(): void
    {
        $open = $this->competition('zensko', 'published', false);
        $owner = $this->userWithRole('korisnik', 'Zenska Vlasnica Prijave');
        $own = $this->application($open, 'Zenska Vlasnica Prijave', 'Plan Vlasnice', $owner);

        $this->actingAs($owner)
            ->get(route('applications.show', $own))
            ->assertOk()
            ->assertSee('Plan Vlasnice');

        $this->actingAs($owner)
            ->get(route('applications.create', $open).'?application_id='.$own->id)
            ->assertOk()
            ->assertSee('Plan Vlasnice');

        $this->actingAs($this->userWithRole('korisnik'))
            ->get(route('applications.create', $open).'?application_id='.$own->id)
            ->assertForbidden()
            ->assertDontSee('Plan Vlasnice');

        $expired = $this->competition('zensko', 'published', true);
        $submitted = $this->application($expired, 'Zenska Podnesena Poslije Roka', 'Plan Podnesen Poslije Roka');
        $member = $this->attachCompleteCommission($expired);

        $this->actingAs($member)
            ->get(route('admin.applications.show', $submitted))
            ->assertOk()
            ->assertSee('Zenska Podnesena Poslije Roka')
            ->assertSee('Plan Podnesen Poslije Roka');

        $this->actingAs($member)
            ->get(route('admin.competitions.show', $expired))
            ->assertOk()
            ->assertSee('Zenska Podnesena Poslije Roka');

        $this->actingAs($member)
            ->get(route('applications.create', $expired).'?application_id='.$submitted->id)
            ->assertOk()
            ->assertViewHas('readOnly', true)
            ->assertSee('Plan Podnesen Poslije Roka');

        $archived = $this->competition('zensko', 'completed', true);
        $archivedApplication = $this->application($archived, 'Zenska Arhiva', 'Plan Arhiva');
        $konkursAdmin = $this->userWithRole('konkurs_admin');

        $this->actingAs($konkursAdmin)
            ->get(route('admin.applications.show', $archivedApplication))
            ->assertOk()
            ->assertSee('Zenska Arhiva');

        $this->actingAs($konkursAdmin)
            ->get(route('applications.create', $archived).'?application_id='.$archivedApplication->id)
            ->assertOk()
            ->assertViewHas('readOnly', true)
            ->assertSee('Plan Arhiva');

        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)
            ->get(route('admin.applications.show', $submitted))
            ->assertOk()
            ->assertSee('Zenska Podnesena Poslije Roka');

        $this->actingAs($admin)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertSee('Zenska Podnesena Poslije Roka')
            ->assertDontSee('Zenska Vlasnica Prijave');
    }

    public function test_youth_and_mmsp_admin_visibility_before_deadline_stays_unchanged(): void
    {
        $admin = $this->userWithRole('admin');
        $youth = $this->competition(CompetitionProfileResolver::TYPE_YOUTH, 'published', false);
        $youthApplication = $this->application($youth, 'Omladinski Podnosilac', 'Plan Omladinski');
        $mmsp = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'published', false);
        $mmspApplication = $this->application($mmsp, 'MMSP Podnosilac', 'Plan MMSP');

        $this->actingAs($admin)
            ->get(route('admin.competitions.show', $youth))
            ->assertOk()
            ->assertSee('Omladinski Podnosilac')
            ->assertSee('Plan Omladinski');

        $this->actingAs($admin)
            ->get(route('admin.competitions.show', $mmsp))
            ->assertOk()
            ->assertSee('MMSP Podnosilac');

        $this->actingAs($admin)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertSee('Omladinski Podnosilac')
            ->assertSee('MMSP Podnosilac');

        $this->actingAs($admin)
            ->get(route('admin.applications.show', $youthApplication))
            ->assertOk()
            ->assertSee('Omladinski Podnosilac');

        $this->actingAs($admin)
            ->get(route('admin.applications.show', $mmspApplication))
            ->assertOk()
            ->assertSee('MMSP Podnosilac');

        $this->actingAs($admin)
            ->get(route('applications.create', $youth).'?application_id='.$youthApplication->id)
            ->assertOk()
            ->assertSee('Plan Omladinski');
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

    private function application(Competition $competition, string $applicantName, string $planName, ?User $owner = null): Application
    {
        $owner ??= $this->userWithRole('korisnik', $applicantName);

        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'applicant_type' => 'preduzetnik',
            'status' => 'submitted',
            'business_plan_name' => $planName,
            'founder_name' => $applicantName,
            'submitted_at' => now(),
            'redni_broj' => Application::query()->where('competition_id', $competition->id)->count() + 1,
        ]);
    }

    private function attachCompleteCommission(Competition $competition): User
    {
        $commission = Commission::create([
            'name' => 'Komisija '.uniqid(),
            'year' => (int) now()->year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'zensko',
        ]);

        $chair = null;
        $types = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];

        for ($i = 0; $i < 5; $i++) {
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
