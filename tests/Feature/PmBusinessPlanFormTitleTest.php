<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PmBusinessPlanFormTitleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_pm_form_shows_m2_on_first_open_and_on_a_saved_draft(): void
    {
        [$owner, $application] = $this->application('omladinsko');
        $this->assertTrue($application->isObrazacComplete());
        $this->assertNull($application->businessPlan);

        $first = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('class="obrazac-1a-1b">Obrazac M2</div>', $first);
        $this->assertStringNotContainsString('class="obrazac-1a-1b">Obrazac 2</div>', $first);

        $this->actingAs($owner)
            ->post(route('applications.business-plan.store', $application), [
                'save_as_draft' => '1',
                'has_registered_business' => '0',
                'business_idea_name' => 'Sačuvana PM ideja',
            ])
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHasNoErrors();

        $saved = $application->fresh()->businessPlan;
        $this->assertNotNull($saved);
        $this->assertSame('Sačuvana PM ideja', $saved->business_idea_name);
        $this->assertSame('draft', $application->fresh()->status);

        $again = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('class="obrazac-1a-1b">Obrazac M2</div>', $again);
        $this->assertStringContainsString('Sačuvana PM ideja', $again);
        $this->assertSame('Sačuvana PM ideja', $application->fresh()->businessPlan->business_idea_name);
    }

    public function test_zensko_form_keeps_obrazac_2_title(): void
    {
        [$owner, $application] = $this->application('zensko');

        $html = $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('class="obrazac-1a-1b">Obrazac 2</div>', $html);
        $this->assertStringNotContainsString('Obrazac M2', $html);
    }

    /**
     * @return array{0: User, 1: Application}
     */
    private function application(string $type): array
    {
        $owner = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $competition = Competition::create([
            'title' => 'Naslov plana '.$type,
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(2),
        ]);
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'status' => 'draft',
            'business_plan_name' => 'Plan '.$type,
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

        return [$owner, $application];
    }
}
