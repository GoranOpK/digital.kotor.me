<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PmApplicationFormNameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_pm_physical_person_shows_m1a_and_keeps_the_existing_link(): void
    {
        [$owner, $admin, $application] = $this->makeApplication('omladinsko', 'fizicko_lice', true);

        $this->assertTrue($application->isObrazacComplete());
        $this->assertFourScreens($owner, $admin, $application, 'Obrazac M1a popunjen');
        $this->assertStringNotContainsString('Obrazac 1a/1b', $this->page($owner, 'applications.show', $application));
        $this->assertStringNotContainsString('Obrazac M1b', $this->page($owner, 'applications.show', $application));

        $stranger = $this->user('korisnik');
        $this->actingAs($stranger)->get(route('applications.show', $application))->assertForbidden();
        $this->actingAs($stranger)->get(route('admin.applications.show', $application))->assertForbidden();
    }

    public function test_pm_entrepreneur_types_show_m1a_with_completeness(): void
    {
        [$owner, $admin, $draft] = $this->makeApplication('omladinsko', 'preduzetnik', false);
        $complete = $this->applicationFor($owner, $draft->competition, 'preduzetnica', true);

        $this->assertFalse($draft->isObrazacComplete());
        $this->assertTrue($complete->isObrazacComplete());

        $this->assertFourScreens($owner, $admin, $draft, 'Obrazac M1a - Nacrt');
        $this->assertFourScreens($owner, $admin, $complete, 'Obrazac M1a popunjen');
        $this->assertStringNotContainsString('Obrazac M1a popunjen', $this->page($owner, 'applications.show', $draft));
        $this->assertStringNotContainsString('Obrazac 1a popunjen', $this->page($owner, 'applications.show', $complete));
    }

    public function test_pm_company_shows_m1b_with_completeness(): void
    {
        [$owner, $admin, $complete] = $this->makeApplication('omladinsko', 'privredno_drustvo', true);
        $draft = $this->applicationFor($owner, $complete->competition, 'privredno_drustvo', false);

        $this->assertTrue($complete->isObrazacComplete());
        $this->assertFalse($draft->isObrazacComplete());

        $this->assertFourScreens($owner, $admin, $complete, 'Obrazac M1b popunjen');
        $this->assertFourScreens($owner, $admin, $draft, 'Obrazac M1b - Nacrt');
        $this->assertStringNotContainsString('Obrazac 1b popunjen', $this->page($admin, 'admin.applications.show', $complete));
        $this->assertStringNotContainsString('Obrazac M1a', $this->page($owner, 'applications.show', $complete));
    }

    public function test_zensko_screens_keep_existing_form_names(): void
    {
        [$owner, $admin, $entrepreneur] = $this->makeApplication('zensko', 'preduzetnica', true, deadlinePassed: true);
        $company = $this->applicationFor($owner, $entrepreneur->competition, 'doo', false);
        $physical = $this->applicationFor($owner, $entrepreneur->competition, 'fizicko_lice', true);
        $youthOnlyType = $this->applicationFor($owner, $entrepreneur->competition, 'preduzetnik', true);

        $this->assertFourScreens($owner, $admin, $entrepreneur, 'Obrazac 1a popunjen');
        $this->assertFourScreens($owner, $admin, $company, 'Obrazac 1b - Nacrt');
        $this->assertFourScreens($owner, $admin, $physical, 'Obrazac 1a/1b popunjen');

        $youthOnlyPage = $this->page($owner, 'applications.show', $youthOnlyType);
        $this->assertStringContainsString('Nije popunjen', $youthOnlyPage);
        $this->assertStringContainsString('Obrazac 1a/1b', $youthOnlyPage);
        $this->assertStringNotContainsString('Obrazac M1a', $youthOnlyPage);
        $this->assertStringNotContainsString('Obrazac M1b', $this->page($admin, 'admin.competitions.show', $entrepreneur->competition));
    }

    private function assertFourScreens(User $owner, User $admin, Application $application, string $badge): void
    {
        $link = route('applications.create', $application->competition_id).'?application_id='.$application->id;
        $pages = [
            $this->page($owner, 'applications.show', $application),
            $this->page($owner, 'dashboard', $application),
            $this->page($admin, 'admin.applications.show', $application),
            $this->page($admin, 'admin.competitions.show', $application->competition),
        ];

        foreach ($pages as $html) {
            $this->assertStringContainsString($badge, $html);
            $this->assertStringContainsString($link, $html);
        }
    }

    private function page(User $user, string $routeName, Application|Competition $subject): string
    {
        $url = $routeName === 'dashboard' ? route('dashboard') : route($routeName, $subject);

        return $this->actingAs($user)
            ->get($url)
            ->assertOk()
            ->getContent();
    }

    /**
     * @return array{0: User, 1: User, 2: Application}
     */
    private function makeApplication(string $competitionType, string $applicantType, bool $complete, bool $deadlinePassed = false): array
    {
        $owner = $this->user('korisnik');
        $admin = $this->user('superadmin');
        $competition = $this->competition($competitionType, $deadlinePassed);

        return [$owner, $admin, $this->applicationFor($owner, $competition, $applicantType, $complete)];
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function competition(string $type, bool $deadlinePassed): Competition
    {
        return Competition::create([
            'title' => 'Naziv obrasca '.$type.' '.uniqid(),
            'description' => 'Opis',
            'start_date' => $deadlinePassed ? now()->subDays(40)->toDateString() : now()->subDays(2)->toDateString(),
            'end_date' => $deadlinePassed ? now()->subDay()->toDateString() : now()->addDays(18)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => $deadlinePassed ? 10 : 20,
            'published_at' => now()->subDays(40),
        ]);
    }

    private function applicationFor(User $owner, Competition $competition, string $applicantType, bool $complete): Application
    {
        $jmb = '0101990123456';
        $attributes = [
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'status' => 'draft',
            'business_plan_name' => 'Plan '.$applicantType.' '.uniqid(),
            'applicant_type' => $applicantType,
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'is_registered' => false,
            'accuracy_declaration' => $complete,
        ];

        if ($applicantType === 'fizicko_lice') {
            $attributes = array_merge($attributes, [
                'physical_person_name' => $owner->name,
                'physical_person_jmbg' => $jmb,
                'physical_person_phone' => '067000000',
                'physical_person_email' => $owner->email,
                'physical_person_address' => 'Njegoševa 1, Kotor',
            ]);
        } elseif ($applicantType === 'preduzetnica' || $applicantType === 'preduzetnik') {
            $attributes = array_merge($attributes, [
                'applicant_jmbg' => $jmb,
                'preduzetnik_name' => $owner->name,
                'preduzetnik_phone' => '067000000',
                'preduzetnik_email' => $owner->email,
                'preduzetnik_address' => 'Njegoševa 1, Kotor',
            ]);
        } else {
            $attributes = array_merge($attributes, [
                'applicant_jmbg' => $jmb,
                'doo_name' => 'Društvo',
                'doo_phone' => '067000000',
                'doo_email' => $owner->email,
                'doo_address' => 'Njegoševa 1, Kotor',
            ]);
        }

        return Application::create($attributes);
    }
}
