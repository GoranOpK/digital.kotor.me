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

class ApplicationFinalSubmitDeadlineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_draft_cannot_be_submitted_after_application_deadline(): void
    {
        [$owner, $application] = $this->createDraftWithBusinessPlan(deadlinePassed: true);

        $response = $this->actingAs($owner)->post(route('applications.final-submit', $application));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');

        $application->refresh();

        $this->assertSame('draft', $application->status);
        $this->assertNull($application->submitted_at);
        $this->assertNull($application->redni_broj);
    }

    public function test_draft_can_be_submitted_while_competition_is_open(): void
    {
        [$owner, $application] = $this->createDraftWithBusinessPlan(deadlinePassed: false);

        $response = $this->actingAs($owner)->post(route('applications.final-submit', $application));

        $response->assertRedirect(route('applications.show', $application));
        $response->assertSessionHasNoErrors();

        $application->refresh();

        $this->assertSame('submitted', $application->status);
        $this->assertNotNull($application->submitted_at);
        $this->assertSame(1, $application->redni_broj);
    }

    /**
     * @return array{0: User, 1: Application}
     */
    private function createDraftWithBusinessPlan(bool $deadlinePassed): array
    {
        $role = Role::where('name', 'korisnik')->firstOrFail();

        $owner = User::factory()->create([
            'role_id' => $role->id,
            'activation_status' => 'active',
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
        ]);

        $startDate = $deadlinePassed
            ? now()->subDays(30)->toDateString()
            : now()->subDays(2)->toDateString();
        $endDate = $deadlinePassed
            ? now()->subDays(5)->toDateString()
            : now()->addDays(18)->toDateString();

        $competition = Competition::create([
            'title' => 'Test konkurs '.uniqid(),
            'description' => 'Opis',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'type' => 'zensko',
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
        ]);

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'business_plan_name' => 'Test plan',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'draft',
        ]);

        BusinessPlan::create([
            'application_id' => $application->id,
            'business_idea_name' => 'Test ideja',
            'applicant_name' => $owner->name,
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $owner->email,
        ]);

        return [$owner, $application];
    }
}
