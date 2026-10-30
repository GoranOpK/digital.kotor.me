<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\KotorAddress;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PmSubmitBlockMessageTest extends TestCase
{
    use RefreshDatabase;

    private const CREATE_PLAN = 'Morate kreirati biznis plan prije podnošenja prijave.';

    private const FINANCES = 'Morate potvrditi napomenu o finansijama u biznis planu prije konačne predaje.';

    private const DEADLINE = 'Rok za prijave je istekao ili konkurs nije otvoren za prijave.';

    private const SEAT = 'Sjedište društva mora biti na teritoriji Opštine Kotor.';

    private const FORM_WARNING = 'Jedan ili više obrazaca vaše prijave nijesu u potpunosti popunjeni.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_pm_without_a_plan_asks_to_create_it_before_later_blockers(): void
    {
        [$owner, $application] = $this->application('omladinsko', withPlan: false, address: 'Ulica 1, Podgorica');

        $page = $this->page($owner, $application);
        $this->assertStringContainsString(self::CREATE_PLAN, $page);
        $this->assertStringNotContainsString(KotorAddress::validationMessage(), $page);
        $this->assertStringNotContainsString(self::FINANCES, $page);
    }

    public function test_pm_missing_finance_notice_is_shown_before_the_address(): void
    {
        [$owner, $application] = $this->application('omladinsko', finances: false, address: 'Ulica 1, Podgorica');

        $page = $this->page($owner, $application);
        $this->assertStringContainsString(self::FINANCES, $page);
        $this->assertStringNotContainsString(KotorAddress::validationMessage(), $page);
        $this->assertStringNotContainsString(self::CREATE_PLAN, $page);
    }

    public function test_pm_address_outside_kotor_uses_the_server_message(): void
    {
        [$owner, $application] = $this->application('omladinsko', address: 'Ulica 1, Podgorica');

        $page = $this->page($owner, $application);
        $this->assertStringContainsString(KotorAddress::validationMessage(), $page);
        $this->assertStringNotContainsString(self::FINANCES, $page);
        $this->assertStringNotContainsString(self::CREATE_PLAN, $page);
    }

    public function test_pm_registered_company_with_a_seat_outside_kotor_uses_the_seat_message(): void
    {
        [$owner, $application] = $this->application(
            'omladinsko',
            applicantType: 'privredno_drustvo',
            registered: true,
            seat: 'Ulica 1, Podgorica',
        );

        $page = $this->page($owner, $application);
        $this->assertStringContainsString(self::SEAT, $page);
        $this->assertStringNotContainsString(KotorAddress::validationMessage(), $page);
    }

    public function test_pm_draft_after_the_deadline_shows_the_deadline_without_a_submit_button(): void
    {
        [$owner, $application] = $this->application('omladinsko', window: 'closed', withPlan: false);

        $page = $this->page($owner, $application);
        $this->assertStringContainsString(self::DEADLINE, $page);
        $this->assertStringNotContainsString(self::CREATE_PLAN, $page);
        $this->assertStringNotContainsString(route('applications.final-submit', $application), $page);
        $this->assertStringNotContainsString('Podnesi prijavu', $page);
    }

    public function test_pm_draft_on_an_unopened_competition_uses_the_same_deadline_message(): void
    {
        [$owner, $application] = $this->application('omladinsko', window: 'unpublished');

        $page = $this->page($owner, $application);
        $this->assertStringContainsString(self::DEADLINE, $page);
        $this->assertStringNotContainsString(route('applications.final-submit', $application), $page);
    }

    public function test_pm_incomplete_forms_keep_the_confirmation_warning(): void
    {
        [$owner, $application] = $this->application('omladinsko', m2Complete: false);

        $page = $this->page($owner, $application);
        $this->assertStringContainsString(self::FORM_WARNING, $page);
        $this->assertStringContainsString('name="confirm_incomplete_forms"', $page);
        $this->assertStringNotContainsString(self::CREATE_PLAN, $page);
        $this->assertStringNotContainsString(self::FINANCES, $page);
        $this->assertStringNotContainsString(self::DEADLINE, $page);
    }

    public function test_pm_complete_forms_show_the_normal_submit_button(): void
    {
        [$owner, $application] = $this->application('omladinsko', m2Complete: true);

        $page = $this->page($owner, $application);
        $this->assertStringContainsString('Podnesi prijavu', $page);
        $this->assertStringContainsString(route('applications.final-submit', $application), $page);
        $this->assertStringNotContainsString(self::FORM_WARNING, $page);
        $this->assertStringNotContainsString(self::CREATE_PLAN, $page);
        $this->assertStringNotContainsString(self::FINANCES, $page);
        $this->assertStringNotContainsString(KotorAddress::validationMessage(), $page);
    }

    public function test_submitted_pm_application_does_not_show_draft_block_messages(): void
    {
        [$owner, $application] = $this->application('omladinsko', window: 'closed', status: 'submitted');

        $page = $this->page($owner, $application);
        $this->assertStringNotContainsString(self::DEADLINE, $page);
        $this->assertStringNotContainsString(self::CREATE_PLAN, $page);
        $this->assertStringNotContainsString(self::FINANCES, $page);
        $this->assertStringContainsString('Podnesena', $page);
    }

    public function test_zensko_keeps_the_existing_screen_message(): void
    {
        [$owner, $open] = $this->application('zensko', withPlan: false);
        $openPage = $this->page($owner, $open);
        $this->assertStringContainsString('Morate popuniti biznis plan prije podnošenja prijave', $openPage);
        $this->assertStringNotContainsString(self::CREATE_PLAN, $openPage);

        [$owner, $closed] = $this->application('zensko', window: 'closed', withPlan: false);
        $closedPage = $this->page($owner, $closed);
        $this->assertStringNotContainsString(self::DEADLINE, $closedPage);
        $this->assertStringNotContainsString(self::CREATE_PLAN, $closedPage);
    }

    public function test_mmsp_status_screen_does_not_use_the_pm_block_messages(): void
    {
        [$owner, $application] = $this->application(CompetitionProfileResolver::TYPE_CANONICAL, withPlan: false);

        $page = $this->page($owner, $application);
        $this->assertStringNotContainsString(self::CREATE_PLAN, $page);
        $this->assertStringNotContainsString(self::FINANCES, $page);
        $this->assertStringNotContainsString(self::DEADLINE, $page);
        $this->assertStringNotContainsString(self::FORM_WARNING, $page);
    }

    private function page(User $owner, Application $application): string
    {
        return $this->actingAs($owner)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->getContent();
    }

    /**
     * @return array{0: User, 1: Application}
     */
    private function application(
        string $type,
        bool $withPlan = true,
        bool $finances = true,
        bool $m2Complete = false,
        string $address = 'Njegoševa 1, 85330 Kotor',
        string $window = 'open',
        string $status = 'draft',
        string $applicantType = 'fizicko_lice',
        bool $registered = false,
        ?string $seat = null,
    ): array {
        $owner = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $competition = Competition::create([
            'title' => 'Blokada '.$type.' '.uniqid(),
            'description' => 'Opis',
            'start_date' => $window === 'closed' ? now()->subDays(40)->toDateString() : now()->subDays(2)->toDateString(),
            'end_date' => $window === 'closed' ? now()->subDay()->toDateString() : now()->addDays(18)->toDateString(),
            'type' => $type,
            'status' => $window === 'unpublished' ? 'draft' : 'published',
            'year' => (int) now()->year,
            'deadline_days' => $window === 'closed' ? 10 : 20,
            'published_at' => $window === 'unpublished' ? null : now()->subDays(40),
        ]);
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'status' => $status,
            'business_plan_name' => 'Plan '.$type,
            'applicant_type' => $applicantType,
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'is_registered' => $registered,
            'accuracy_declaration' => true,
            'physical_person_name' => $owner->name,
            'physical_person_jmbg' => '0101990123456',
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => $address,
            'doo_name' => $applicantType === 'privredno_drustvo' ? 'Društvo' : null,
            'doo_phone' => $applicantType === 'privredno_drustvo' ? '067000000' : null,
            'doo_email' => $applicantType === 'privredno_drustvo' ? $owner->email : null,
            'doo_address' => $applicantType === 'privredno_drustvo' ? 'Njegoševa 1, 85330 Kotor' : null,
            'company_seat' => $seat,
            'applicant_jmbg' => $applicantType === 'privredno_drustvo' ? '0101990123456' : null,
            'submitted_at' => $status === 'submitted' ? now() : null,
        ]);
        if ($withPlan) {
            BusinessPlan::create([
                'application_id' => $application->id,
                'business_idea_name' => 'Ideja',
                'applicant_name' => $owner->name,
                'applicant_jmbg' => '0101990123456',
                'applicant_address' => $address,
                'applicant_phone' => '067000000',
                'applicant_email' => $owner->email,
                'summary' => $m2Complete ? 'Potpun sažetak' : null,
                'finances_notice_confirmed' => $finances,
            ]);
        }

        return [$owner, $application];
    }
}
