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

class BusinessPlanPrintButtonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_pm_owner_sees_print_button_on_a_draft(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac');
        $application = $this->application($owner, $this->competition('omladinsko', open: true), 'draft');

        $html = $this->form($owner, $application);

        $this->assertPrintButton($html);
        $this->assertStringContainsString('Obrazac M2', $html);
        $this->assertStringContainsString('id="bpSubmitBtn"', $html);
    }

    public function test_pm_owner_sees_print_button_after_submission(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac');
        $application = $this->application($owner, $this->competition('omladinsko', open: false), 'submitted');

        $html = $this->form($owner, $application);

        $this->assertPrintButton($html);
        $this->assertStringContainsString('Obrazac M2', $html);
        $this->assertReadOnly($html);
    }

    public function test_zp_owner_sees_print_button_on_a_draft(): void
    {
        $owner = $this->user('korisnik', 'Podnositeljka');
        $application = $this->application($owner, $this->competition('zensko', open: true), 'draft');

        $html = $this->form($owner, $application);

        $this->assertPrintButton($html);
        $this->assertStringContainsString('class="obrazac-1a-1b">Obrazac 2</div>', $html);
        $this->assertStringNotContainsString('Obrazac M2', $html);
        $this->assertStringContainsString('id="bpSubmitBtn"', $html);
    }

    public function test_zp_owner_sees_print_button_after_submission(): void
    {
        $owner = $this->user('korisnik', 'Podnositeljka');
        $application = $this->application($owner, $this->competition('zensko', open: false), 'submitted');

        $html = $this->form($owner, $application);

        $this->assertPrintButton($html);
        $this->assertStringContainsString('class="obrazac-1a-1b">Obrazac 2</div>', $html);
        $this->assertStringNotContainsString('Obrazac M2', $html);
        $this->assertReadOnly($html);
    }

    public function test_another_user_cannot_open_someone_elses_business_plan(): void
    {
        $owner = $this->user('korisnik', 'Vlasnik');
        $stranger = $this->user('korisnik', 'Drugi korisnik');
        $application = $this->application($owner, $this->competition('omladinsko', open: true), 'submitted');
        $application->forceFill(['business_plan_name' => 'Tudji-plan-'.uniqid()])->save();

        $this->actingAs($stranger)
            ->get(route('applications.business-plan.create', $application))
            ->assertForbidden()
            ->assertDontSee($application->business_plan_name, false);
    }

    public function test_commission_president_can_print_and_a_member_cannot(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac');
        $call = $this->commissionCall('omladinsko');
        $application = $this->application($owner, $call['competition'], 'submitted');

        $chairman = $this->form($call['chairman']->user, $application);
        $this->assertPrintButton($chairman);
        $this->assertReadOnly($chairman);

        $member = $this->form($call['member']->user, $application);
        $this->assertOkWithoutPrintButton($member);
        $this->assertReadOnly($member);
    }

    public function test_other_competition_owner_does_not_gain_print_on_a_locked_plan(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac');
        $application = $this->application(
            $owner,
            $this->competition(CompetitionProfileResolver::TYPE_OTHER, open: false),
            'submitted'
        );

        $html = $this->form($owner, $application);

        $this->assertOkWithoutPrintButton($html);
        $this->assertReadOnly($html);
    }

    public function test_other_competition_owner_keeps_print_while_the_plan_is_editable(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac');
        $application = $this->application(
            $owner,
            $this->competition(CompetitionProfileResolver::TYPE_OTHER, open: true),
            'draft'
        );

        $html = $this->form($owner, $application);

        $this->assertPrintButton($html);
        $this->assertStringContainsString('id="bpSubmitBtn"', $html);
        $this->assertStringNotContainsString('onsubmit="event.preventDefault(); return false;"', $html);
    }

    public function test_submitted_plan_stays_read_only_while_the_owner_can_print(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac');
        $application = $this->application($owner, $this->competition('omladinsko', open: true), 'submitted');

        $html = $this->form($owner, $application);

        $this->assertPrintButton($html);
        $this->assertReadOnly($html);
        $this->assertStringContainsString('<form method="POST" action="#" id="businessPlanForm"', $html);
    }

    public function test_print_button_and_navigation_are_excluded_from_print(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac');
        $application = $this->application($owner, $this->competition('omladinsko', open: true), 'submitted');

        $html = $this->form($owner, $application);

        $this->assertMatchesRegularExpression(
            '/<div class="form-card no-print"[^>]*>\s*<button type="button" class="btn-primary" onclick="window\.print\(\);"/s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<p class="no-print bp-back-to-application">\s*<a href="[^"]+">← Nazad na pregled prijave<\/a>/s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/@media print\s*\{.*?\.obrazac-form-page \.no-print\s*\{\s*display:\s*none\s*!important;\s*\}/s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/@media print\s*\{.*?\.obrazac-form-page button,\s*\n\s*\.obrazac-form-page \.btn-primary,/s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/@media print\s*\{.*?\.obrazac-form-page a\[href\]\s*\{\s*display:\s*none\s*!important;\s*\}/s',
            $html
        );
    }

    private function assertPrintButton(string $html): void
    {
        $this->assertMatchesRegularExpression(
            '/<button[^>]*onclick="window\.print\(\);"[^>]*>\s*Štampaj\s*<\/button>/u',
            $html
        );
    }

    private function assertOkWithoutPrintButton(string $html): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]*>\s*Štampaj\s*<\/button>/u',
            $html
        );
        $this->assertStringContainsString('FORMA ZA BIZNIS PLAN', $html);
    }

    private function assertReadOnly(string $html): void
    {
        $this->assertStringNotContainsString('id="bpSubmitBtn"', $html);
        $this->assertStringContainsString('onsubmit="event.preventDefault(); return false;"', $html);
        $this->assertStringContainsString("field.setAttribute('readonly', 'readonly');", $html);
    }

    private function form(User $user, Application $application): string
    {
        return $this->actingAs($user)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();
    }

    private function user(string $role, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'address' => 'Njegoševa 1',
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
    private function commissionCall(string $type): array
    {
        $commission = Commission::create([
            'name' => 'Komisija '.$type.' '.uniqid(),
            'year' => (int) now()->year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => $type,
        ]);
        $chairman = $this->member($commission, 'predsjednik', 1);
        $member = $this->member($commission, 'clan', 2);
        $this->member($commission, 'clan', 3);

        return [
            'competition' => $this->competition($type, open: false, commissionId: $commission->id),
            'chairman' => $chairman->fresh('user'),
            'member' => $member->fresh('user'),
        ];
    }

    private function member(Commission $commission, string $position, int $seat): CommissionMember
    {
        $user = $this->user('komisija', $position.' '.$seat.' '.uniqid());

        return CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => $position,
            'canonical_seat_no' => $seat,
            'status' => 'active',
        ]);
    }

    private function application(User $owner, Competition $competition, string $status): Application
    {
        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'status' => $status,
            'business_plan_name' => 'Plan '.uniqid(),
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'is_registered' => false,
            'accuracy_declaration' => true,
            'physical_person_name' => $owner->name,
            'physical_person_jmbg' => '0101990123456',
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => 'Njegoševa 1, Kotor',
        ]);
    }
}
