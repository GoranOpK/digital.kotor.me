<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YouthApprovedAmountDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('opstinakotor_digital_testing', (string) config('database.connections.mysql.database'));
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_youth_approved_amount_is_shown_before_close_and_hidden_for_zero_rejection(): void
    {
        $competition = $this->competition('omladinsko', 'published');
        $approved = $this->application($competition, 'approved', '4000.00', null);
        $rejected = $this->application($competition, 'rejected', '0.00', 'Obrazloženje odbijanja ostaje na prijavi.');

        $this->actingAs($approved->user)
            ->get(route('applications.show', $approved))
            ->assertOk()
            ->assertSee('Odobrena', false)
            ->assertSee('Odobreni iznos', false)
            ->assertSee('4.000,00 €', false)
            ->assertDontSee('data-testid="youth-final-ranking-link"', false);

        $this->actingAs($rejected->user)
            ->get(route('applications.show', $rejected))
            ->assertOk()
            ->assertSee('Odbijena', false)
            ->assertSee('Razlog odbijanja', false)
            ->assertSee('Obrazloženje odbijanja ostaje na prijavi', false)
            ->assertDontSee('Odobreni iznos', false);
    }

    public function test_zensko_and_canonical_still_show_a_stored_zero_amount(): void
    {
        $zensko = $this->application(
            $this->competition('zensko', 'published'),
            'rejected',
            '0.00',
            'Ženski razlog ostaje.',
        );
        $canonical = $this->application(
            $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'published'),
            'rejected',
            '0.00',
            'MMSP razlog ostaje.',
        );

        $this->actingAs($zensko->user)
            ->get(route('applications.show', $zensko))
            ->assertOk()
            ->assertSee('Odobreni iznos', false)
            ->assertSee('0,00 €', false)
            ->assertSee('Ženski razlog ostaje', false);

        $this->actingAs($canonical->user)
            ->get(route('applications.show', $canonical))
            ->assertOk()
            ->assertSee('Obrazac P1a/P1b', false)
            ->assertSee('Odbijena', false)
            ->assertDontSee('Odobreni iznos', false);
    }

    private function competition(string $type, string $status): Competition
    {
        return Competition::create([
            'title' => 'Prikaz iznosa '.$type,
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => $type,
            'status' => $status,
            'year' => 2026,
            'call_number' => 1,
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
        ]);
    }

    private function application(
        Competition $competition,
        string $status,
        string $approvedAmount,
        ?string $rejectionReason,
    ): Application {
        $user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Plan '.$status.' '.$competition->type,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'requested_amount' => 5000,
            'approved_amount' => $approvedAmount,
            'status' => $status,
            'rejection_reason' => $rejectionReason,
            'submitted_at' => now()->subDays(20),
        ])->fresh('user');
    }
}
