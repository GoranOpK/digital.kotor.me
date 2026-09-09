<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hotfix regression: BusinessPlanController::create must not 500 when
 * isObrazacComplete() is false, and must allow viewing an existing plan.
 */
class BusinessPlanCreateCompletenessGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_incomplete_application_without_business_plan_redirects_to_form(): void
    {
        [$owner, $application] = $this->createIncompleteApplication(withBusinessPlan: false);

        $this->assertFalse($application->isObrazacComplete());
        $this->assertNull($application->businessPlan);

        $expectedUrl = route('applications.create', $application->competition_id).'?application_id='.$application->id;

        $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertRedirect($expectedUrl)
            ->assertSessionHasErrors('error');
    }

    public function test_incomplete_application_with_existing_business_plan_is_viewable(): void
    {
        [$owner, $application] = $this->createIncompleteApplication(withBusinessPlan: true);

        $this->assertFalse($application->isObrazacComplete());
        $this->assertNotNull($application->businessPlan);

        $html = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Legacy ideja za pregled', $html);
        $this->assertStringContainsString('name="business_idea_name"', $html);
    }

    public function test_complete_application_with_business_plan_returns_ok(): void
    {
        [$owner, $application] = $this->createCompleteApplicationWithBusinessPlan();

        $this->assertTrue($application->isObrazacComplete());
        $this->assertNotNull($application->businessPlan);

        $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk();
    }

    public function test_non_owner_cannot_view_business_plan(): void
    {
        [$owner, $application] = $this->createIncompleteApplication(withBusinessPlan: true);
        $intruder = $this->makeKorisnik('intruder@example.com');

        $this->assertNotSame($owner->id, $intruder->id);

        $this->actingAs($intruder)
            ->get(route('applications.business-plan.create', $application))
            ->assertForbidden();
    }

    public function test_approved_incomplete_application_with_business_plan_is_viewable(): void
    {
        [$owner, $application] = $this->createIncompleteApplication(
            withBusinessPlan: true,
            status: 'approved'
        );

        $this->assertSame('approved', $application->status);
        $this->assertFalse($application->isObrazacComplete());
        $this->assertNotNull($application->businessPlan);

        $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk();
    }

    /**
     * @return array{0: User, 1: Application}
     */
    private function createIncompleteApplication(bool $withBusinessPlan, string $status = 'draft'): array
    {
        $owner = $this->makeKorisnik('bp-owner-'.uniqid().'@example.com');
        $competition = $this->openCompetition();

        // Namjerno nepotpun Obrazac 1a/1b (nema JMBG / tip-specifična polja / accuracy_declaration)
        // — simulira legacy mismatch sa današnjim isObrazacComplete().
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'business_plan_name' => 'Legacy plan',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'business_area' => 'usluge',
            'status' => $status,
            'is_registered' => false,
        ]);

        if ($withBusinessPlan) {
            BusinessPlan::create([
                'application_id' => $application->id,
                'business_idea_name' => 'Legacy ideja za pregled',
                'applicant_name' => $owner->name,
                'applicant_jmbg' => '0101990123456',
                'applicant_address' => 'Njegoševa 1, 85330 Kotor',
                'applicant_phone' => '067000000',
                'applicant_email' => $owner->email,
                'summary' => 'Sažetak legacy biznis plana.',
            ]);
        }

        $application->refresh();
        $application->load('businessPlan');

        return [$owner, $application];
    }

    /**
     * @return array{0: User, 1: Application}
     */
    private function createCompleteApplicationWithBusinessPlan(): array
    {
        $owner = $this->makeKorisnik('bp-complete-'.uniqid().'@example.com', [
            'jmb' => '0101990123456',
            'user_type' => 'Fizičko lice',
            'residential_status' => 'resident',
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
        ]);
        $competition = $this->openCompetition();

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'business_plan_name' => 'Kompletan plan',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'business_area' => 'usluge',
            'status' => 'draft',
            'is_registered' => false,
            'physical_person_name' => $owner->name,
            'physical_person_jmbg' => '0101990123456',
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
            'accuracy_declaration' => true,
        ]);

        BusinessPlan::create([
            'application_id' => $application->id,
            'business_idea_name' => 'Kompletna ideja',
            'applicant_name' => $owner->name,
            'applicant_jmbg' => '0101990123456',
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $owner->email,
            'summary' => 'Sažetak.',
        ]);

        $application->refresh();
        $application->load('businessPlan');

        return [$owner, $application];
    }

    private function openCompetition(): Competition
    {
        return Competition::create([
            'title' => 'Test konkurs BP gate '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(2),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeKorisnik(string $email, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email' => $email,
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'first_name' => 'Ana',
            'last_name' => 'Test',
            'name' => 'Ana Test',
            'phone' => '+38267000001',
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
            'user_type' => 'Fizičko lice',
            'residential_status' => 'resident',
        ], $overrides));
    }
}
