<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\ZenskoPreDeadlineContentGate;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class Sec01ApplicationAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FAKE_JMBG = '0000000000017';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_other_applicant_cannot_open_a_foreign_pm_application_while_the_call_is_open(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac SEC01');
        $stranger = $this->user('korisnik', 'Drugi podnosilac SEC01');
        $competition = $this->competition('omladinsko', open: true);
        $application = $this->application($owner, $competition, 'fizicko_lice', 'draft');

        $this->deny($stranger, $this->formUrl($application), 403, $application);
        $this->assertSame('draft', $application->fresh()->status);
    }

    public function test_other_applicant_cannot_open_a_foreign_zensko_application(): void
    {
        $owner = $this->user('korisnik', 'Podnositeljka SEC01');
        $stranger = $this->user('korisnik', 'Druga podnositeljka SEC01');
        $competition = $this->competition('zensko', open: true);
        $application = $this->application($owner, $competition, 'preduzetnica', 'draft');

        $this->deny(
            $stranger,
            $this->formUrl($application),
            403,
            $application,
            ZenskoPreDeadlineContentGate::INDIVIDUAL_CONTENT_LOCKED_MESSAGE
        );
    }

    public function test_zensko_application_opened_through_an_open_pm_url_is_not_found(): void
    {
        $owner = $this->user('korisnik', 'Podnositeljka SEC01');
        $stranger = $this->user('korisnik', 'Drugi podnosilac SEC01');
        $pm = $this->competition('omladinsko', open: true);
        $zensko = $this->competition('zensko', open: false);
        $application = $this->application($owner, $zensko, 'preduzetnica', 'submitted');

        $this->deny($stranger, $this->formUrl($application, $pm), 404, $application);
        $this->deny($owner, $this->formUrl($application, $pm), 404, $application);
    }

    public function test_application_from_another_competition_is_not_found_even_for_its_owner(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac SEC01');
        $home = $this->competition('omladinsko', open: true);
        $other = $this->competition('omladinsko', open: true);
        $application = $this->application($owner, $home, 'fizicko_lice', 'draft');

        $this->deny($owner, $this->formUrl($application, $other), 404, $application);
    }

    public function test_owner_still_opens_own_draft(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac SEC01');
        $competition = $this->competition('omladinsko', open: true);
        $application = $this->application($owner, $competition, 'fizicko_lice', 'draft');

        $html = $this->form($owner, $application);

        $this->assertStringContainsString($application->business_plan_name, $html);
        $this->assertStringContainsString('Nastavak popunjavanja', $html);
        $this->assertStringNotContainsString('Pregled prijave:', $html);
    }

    public function test_owner_still_opens_own_submitted_application_as_read_only(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac SEC01');
        $competition = $this->competition('omladinsko', open: true);
        $application = $this->application($owner, $competition, 'fizicko_lice', 'submitted');

        $html = $this->form($owner, $application);

        $this->assertStringContainsString($application->business_plan_name, $html);
        $this->assertStringContainsString('Pregled prijave:', $html);
        $this->assertStringNotContainsString('Nastavak popunjavanja', $html);
    }

    public function test_competent_chairman_reviews_a_submitted_application_after_the_deadline(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac SEC01');
        $call = $this->commissionCall();
        $application = $this->application($owner, $call['competition'], 'fizicko_lice', 'submitted');

        $html = $this->form($call['chairman']->user, $application);

        $this->assertStringContainsString($application->business_plan_name, $html);
        $this->assertStringContainsString('Pregled prijave:', $html);
        $this->assertStringNotContainsString('Nastavak popunjavanja', $html);
    }

    public function test_competent_member_reviews_a_submitted_application_after_the_deadline(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac SEC01');
        $call = $this->commissionCall();
        $application = $this->application($owner, $call['competition'], 'fizicko_lice', 'submitted');

        $html = $this->form($call['member']->user, $application);

        $this->assertStringContainsString($application->business_plan_name, $html);
        $this->assertStringContainsString('Pregled prijave:', $html);
        $this->assertStringNotContainsString('Nastavak popunjavanja', $html);
    }

    public function test_chairman_of_another_commission_cannot_open_the_application(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac SEC01');
        $call = $this->commissionCall();
        $other = $this->commissionCall();
        $application = $this->application($owner, $call['competition'], 'fizicko_lice', 'submitted');

        $this->deny($other['chairman']->user, $this->formUrl($application), 403, $application);
    }

    public function test_competition_admin_keeps_archive_access_and_still_cannot_open_an_open_call(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac SEC01');
        $admin = $this->user('konkurs_admin', 'Administrator konkursa SEC01');
        $open = $this->competition('omladinsko', open: true);
        $openApplication = $this->application($owner, $open, 'fizicko_lice', 'submitted');

        $this->deny(
            $admin,
            $this->formUrl($openApplication),
            403,
            $openApplication,
            'Administrator konkursa može pregledati obrasce samo za arhivirane konkurse.'
        );

        $archive = $this->competition('omladinsko', open: false);
        $archive->update(['status' => 'closed']);
        $archivedApplication = $this->application($owner, $archive, 'fizicko_lice', 'submitted');

        $html = $this->form($admin, $archivedApplication);
        $this->assertStringContainsString($archivedApplication->business_plan_name, $html);
        $this->assertStringContainsString('Pregled prijave:', $html);
    }

    public function test_guest_is_redirected_away_from_a_private_application(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac SEC01');
        $competition = $this->competition('omladinsko', open: true);
        $application = $this->application($owner, $competition, 'fizicko_lice', 'draft');

        $response = $this->get($this->formUrl($application));
        $response->assertRedirect(route('login'));
        $this->assertResponseHides($response, $application);
    }

    public function test_foreign_store_does_not_change_the_application(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac SEC01');
        $stranger = $this->user('korisnik', 'Drugi podnosilac SEC01');
        $competition = $this->competition('omladinsko', open: true);
        $application = $this->application($owner, $competition, 'fizicko_lice', 'draft');
        $before = $application->only([
            'business_plan_name',
            'status',
            'user_id',
            'competition_id',
            'physical_person_email',
            'physical_person_phone',
        ]);

        $response = $this->actingAs($stranger)
            ->from($this->formUrl($application))
            ->post(route('applications.store', $competition), [
                'application_id' => $application->id,
                'business_plan_name' => 'SEC01-TUDI-UPIS',
                'save_as_draft' => '1',
                'applicant_type' => 'fizicko_lice',
                'physical_person_email' => 'sec01-upis@example.test',
            ]);

        $this->assertSame($before, $application->fresh()->only(array_keys($before)));
        $this->assertSame(1, Application::query()->count());
        $this->assertNull(Application::query()->where('user_id', $stranger->id)->first());
        $this->assertResponseHides($response, $application);
        $response->assertDontSee('SEC01-TUDI-UPIS', false);
    }

    private function deny(
        User $user,
        string $url,
        int $status,
        Application $application,
        string $message = 'Nemate pristup ovoj prijavi.'
    ): TestResponse {
        $response = $this->actingAs($user)->get($url);
        $response->assertStatus($status);
        $this->assertResponseHides($response, $application);

        if ($status === 403) {
            $response->assertSee($message, false);
        }

        return $response;
    }

    private function assertResponseHides(TestResponse $response, Application $application): void
    {
        foreach ([
            $application->business_plan_name,
            $application->physical_person_email,
            $application->preduzetnik_email,
            $application->physical_person_phone,
            $application->preduzetnik_phone,
            self::FAKE_JMBG,
        ] as $secret) {
            if (is_string($secret) && $secret !== '') {
                $response->assertDontSee($secret, false);
            }
        }
    }

    private function form(User $user, Application $application): string
    {
        return $this->actingAs($user)
            ->get($this->formUrl($application))
            ->assertOk()
            ->getContent();
    }

    private function formUrl(Application $application, ?Competition $routeCompetition = null): string
    {
        $competitionId = $routeCompetition?->id ?? $application->competition_id;

        return route('applications.create', $competitionId).'?application_id='.$application->id;
    }

    private function user(string $role, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid().'@example.test',
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'address' => 'Test adresa 1',
            'city' => 'Kotor',
        ]);
    }

    private function competition(string $type, bool $open, ?int $commissionId = null): Competition
    {
        return Competition::create([
            'title' => 'Konkurs '.$type.' '.uniqid(),
            'description' => 'Opis',
            'start_date' => $open ? now()->subDay()->toDateString() : now()->subDays(40)->toDateString(),
            'end_date' => $open ? now()->addDays(20)->toDateString() : now()->subDays(5)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => $open ? 30 : 10,
            'published_at' => now()->subDays(40),
            'commission_id' => $commissionId,
        ]);
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, member: CommissionMember}
     */
    private function commissionCall(): array
    {
        $commission = Commission::create([
            'name' => 'Komisija SEC01 '.uniqid(),
            'year' => (int) now()->year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'omladinsko',
        ]);
        $chairman = $this->member($commission, 'predsjednik', 1);
        $member = $this->member($commission, 'clan', 2);
        $this->member($commission, 'clan', 3);

        return [
            'competition' => $this->competition('omladinsko', open: false, commissionId: $commission->id),
            'chairman' => $chairman->fresh('user'),
            'member' => $member->fresh('user'),
        ];
    }

    private function member(Commission $commission, string $position, int $seat): CommissionMember
    {
        $user = $this->user('komisija', $position.' '.$seat);

        return CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => $position,
            'canonical_seat_no' => $seat,
            'status' => 'active',
        ]);
    }

    private function application(User $owner, Competition $competition, string $applicantType, string $status): Application
    {
        $marker = uniqid();
        $attributes = [
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'status' => $status,
            'business_plan_name' => 'SEC01-PLAN-'.$marker,
            'applicant_type' => $applicantType,
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'is_registered' => false,
            'accuracy_declaration' => true,
        ];

        if ($applicantType === 'fizicko_lice') {
            $attributes = array_merge($attributes, [
                'physical_person_name' => $owner->name,
                'physical_person_jmbg' => self::FAKE_JMBG,
                'physical_person_phone' => '000000000',
                'physical_person_email' => 'sec01-'.$marker.'@example.test',
                'physical_person_address' => 'Test adresa 1',
            ]);
        } else {
            $attributes = array_merge($attributes, [
                'applicant_jmbg' => self::FAKE_JMBG,
                'preduzetnik_name' => $owner->name,
                'preduzetnik_phone' => '000000000',
                'preduzetnik_email' => 'sec01-'.$marker.'@example.test',
                'preduzetnik_address' => 'Test adresa 1',
            ]);
        }

        return Application::create($attributes);
    }
}
