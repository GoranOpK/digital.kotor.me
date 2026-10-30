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

class PmM1PrintButtonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_applicant_sees_print_button_on_own_pm_m1a_and_m1b(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac');
        $competition = $this->competition('omladinsko', open: true);
        $m1a = $this->application($owner, $competition, 'fizicko_lice', 'draft');
        $m1b = $this->application($owner, $competition, 'privredno_drustvo', 'submitted');

        $draft = $this->form($owner, $m1a);
        $this->assertPrintButton($draft);
        $this->assertStringContainsString('Obrazac M1a', $draft);
        $this->assertMemorandum($draft);

        $submitted = $this->form($owner, $m1b);
        $this->assertPrintButton($submitted);
        $this->assertStringContainsString('Obrazac M1b', $submitted);
        $this->assertMemorandum($submitted);
    }

    public function test_applicant_cannot_open_another_users_pm_application(): void
    {
        $owner = $this->user('korisnik', 'Vlasnik');
        $stranger = $this->user('korisnik', 'Drugi podnosilac');
        $competition = $this->competition('omladinsko', open: true);
        $application = $this->application($owner, $competition, 'fizicko_lice', 'draft');

        $this->actingAs($stranger)->get(route('applications.show', $application))->assertForbidden();

        $form = $this->actingAs($stranger)->get($this->formUrl($application))->assertForbidden();
        $form->assertDontSee($application->business_plan_name, false);
        $form->assertDontSee($application->physical_person_email, false);
        $form->assertDontSee($application->physical_person_phone, false);
        $form->assertDontSee($application->physical_person_jmbg, false);
    }

    public function test_competent_pm_chairman_sees_button_and_another_chairman_does_not_gain_access(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac');
        $call = $this->commissionCall('omladinsko');
        $application = $this->application($owner, $call['competition'], 'fizicko_lice', 'submitted');
        $other = $this->commissionCall('omladinsko');

        $html = $this->form($call['chairman']->user, $application);
        $this->assertPrintButton($html);
        $this->assertMemorandum($html);

        $this->actingAs($other['chairman']->user)
            ->get($this->formUrl($application))
            ->assertForbidden();
        $this->actingAs($other['chairman']->user)
            ->get(route('admin.applications.show', $application))
            ->assertForbidden();
    }

    public function test_other_roles_do_not_see_the_pm_print_button(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac');
        $call = $this->commissionCall('omladinsko');
        $application = $this->application($owner, $call['competition'], 'preduzetnik', 'submitted');

        $member = $this->form($call['member']->user, $application);
        $this->assertOkWithoutPrintButton($member);
        $this->assertStringContainsString('Obrazac M1a', $member);

        $call['competition']->update(['status' => 'closed']);
        $admin = $this->user('konkurs_admin', 'Administrator konkursa');
        $archive = $this->form($admin, $application->fresh());
        $this->assertOkWithoutPrintButton($archive);
    }

    public function test_print_button_is_outside_the_printed_page(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac');
        $competition = $this->competition('omladinsko', open: true);
        $application = $this->application($owner, $competition, 'fizicko_lice', 'draft');
        $html = $this->form($owner, $application);

        $this->assertMatchesRegularExpression(
            '/<div class="form-card no-print"[^>]*>\s*<button type="button" class="btn-primary" onclick="window\.print\(\);"/s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/@media print\s*\{.*?\.no-print\s*\{\s*display:\s*none\s*!important;\s*\}/s',
            $html
        );
    }

    public function test_zensko_print_button_keeps_the_previous_audience(): void
    {
        $owner = $this->user('korisnik', 'Podnositeljka');
        $open = $this->competition('zensko', open: true);
        $draft = $this->application($owner, $open, 'preduzetnica', 'draft');
        $openHtml = $this->form($owner, $draft);
        $this->assertPrintButton($openHtml);
        $this->assertStringContainsString('id="obrazacLabelHeader">Obrazac 1a – preduzetnica', $openHtml);
        $this->assertStringContainsString('ženskom preduzetništvu', $openHtml);

        $call = $this->commissionCall('zensko');
        $submitted = $this->application($owner, $call['competition'], 'doo', 'submitted');

        $this->assertOkWithoutPrintButton($this->form($call['chairman']->user, $submitted));
        $this->assertOkWithoutPrintButton($this->form($call['member']->user, $submitted));
        $lockedOwner = $this->form($owner, $submitted);
        $this->assertOkWithoutPrintButton($lockedOwner);
        $this->assertStringContainsString('id="obrazacLabelHeader">Obrazac 1b – privredno društvo', $lockedOwner);
        $this->assertStringContainsString('ženskom preduzetništvu', $lockedOwner);
    }

    public function test_canonical_application_form_is_not_this_view(): void
    {
        $owner = $this->user('korisnik', 'Podnosilac');
        $competition = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, open: true);

        $this->actingAs($owner)
            ->get(route('applications.create', $competition))
            ->assertRedirect();
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
        $this->assertStringContainsString('PRIJAVA', $html);
    }

    private function assertMemorandum(string $html): void
    {
        $this->assertStringContainsString('images/srednji_grb.png', $html);
        $this->assertStringContainsString('Crna Gora', $html);
        $this->assertStringContainsString('Opština Kotor', $html);
        $this->assertStringContainsString('Sekretarijat za razvoj preduzetništva', $html);
        $this->assertStringContainsString('Stari grad 317', $html);
        $this->assertStringContainsString('privreda@kotor.me', $html);
        $this->assertStringContainsString('Broj prijave:', $html);
        $this->assertStringContainsString('PRIJAVA', $html);
    }

    private function form(User $user, Application $application): string
    {
        return $this->actingAs($user)
            ->get($this->formUrl($application))
            ->assertOk()
            ->getContent();
    }

    private function formUrl(Application $application): string
    {
        return route('applications.create', $application->competition_id).'?application_id='.$application->id;
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
        if ($type === 'zensko') {
            $memberTypes = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];
            $chairman = null;
            $member = null;
            foreach ($memberTypes as $index => $memberType) {
                $position = $index === 0 ? 'predsjednik' : 'clan';
                $created = $this->member($commission, $position, $index + 1, $memberType);
                $chairman = $position === 'predsjednik' ? $created : $chairman;
                $member = $position === 'clan' ? $created : $member;
            }
        } else {
            $chairman = $this->member($commission, 'predsjednik', 1);
            $member = $this->member($commission, 'clan', 2);
            $this->member($commission, 'clan', 3);
        }

        return [
            'competition' => $this->competition($type, open: false, commissionId: $commission->id),
            'chairman' => $chairman->fresh('user'),
            'member' => $member->fresh('user'),
        ];
    }

    private function member(Commission $commission, string $position, int $seat, ?string $memberType = null): CommissionMember
    {
        $user = $this->user('komisija', $position.' '.$seat.' '.uniqid());

        return CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => $position,
            'member_type' => $memberType,
            'canonical_seat_no' => $seat,
            'status' => 'active',
        ]);
    }

    private function application(User $owner, Competition $competition, string $applicantType, string $status): Application
    {
        $jmb = '0101990123456';
        $attributes = [
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'status' => $status,
            'business_plan_name' => 'Plan '.uniqid(),
            'applicant_type' => $applicantType,
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'is_registered' => false,
            'accuracy_declaration' => true,
        ];

        if ($applicantType === 'fizicko_lice') {
            $attributes = array_merge($attributes, [
                'physical_person_name' => $owner->name,
                'physical_person_jmbg' => $jmb,
                'physical_person_phone' => '067000000',
                'physical_person_email' => $owner->email,
                'physical_person_address' => 'Njegoševa 1, Kotor',
            ]);
        } elseif (in_array($applicantType, ['preduzetnica', 'preduzetnik'], true)) {
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
