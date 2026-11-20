<?php

namespace Tests\Feature;

use App\Models\CanonicalInvestmentPlan;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\CanonicalInvestmentPlanService;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalSubsidyType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Kn\Concerns\BuildsCanonicalApplicantDraft;
use Tests\TestCase;

class MmspP2FinancialTotalsTest extends TestCase
{
    use BuildsCanonicalApplicantDraft;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_form_calculates_totals_and_reopens_the_saved_draft(): void
    {
        $owner = $this->owner();
        $application = $this->canonicalDraft($owner, $this->competition(), [
            'with_p2' => false,
            'with_documents' => false,
        ]);

        $first = $this->actingAs($owner)
            ->get(route('applications.canonical-p2.edit', $application))
            ->assertOk()
            ->assertSee('data-mmsp-expense-amount', false)
            ->assertSee('js/mmsp-p2-financials.js', false)
            ->assertSee('id="total_investment"', false)
            ->assertSee('id="expected_subsidy"', false)
            ->assertSee('name="total_investment"', false)
            ->assertSee('name="expected_subsidy"', false);
        $html = $first->getContent();
        $this->assertMatchesRegularExpression('/id="total_investment"[^>]*\sreadonly(?:=|\s|>)/', $html);
        $this->assertMatchesRegularExpression('/id="expected_subsidy"[^>]*\sreadonly(?:=|\s|>)/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="own_contribution"[^>]*\sreadonly(?:=|\s|>)/', $html);

        $lines = [
            ['cost_type' => 'Oprema', 'description' => 'Mašina', 'amount' => '10.50'],
            ['cost_type' => 'Alat', 'description' => 'Set alata', 'amount' => '20.25'],
            ['cost_type' => 'Materijal', 'description' => 'Ambalaža', 'amount' => '1.00'],
            ['cost_type' => 'Usluga', 'description' => 'Montaža', 'amount' => '0.01'],
            ['cost_type' => 'Obuka', 'description' => 'Obuka osoblja', 'amount' => '100.00'],
        ];
        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $this->payload($lines, '131.76', '31.76', '100.00'))
            ->assertSessionHasNoErrors();

        $plan = CanonicalInvestmentPlan::query()->where('application_id', $application->id)->firstOrFail();
        $this->assertSame('131.76', $plan->total_investment);
        $this->assertSame('31.76', $plan->own_contribution);
        $this->assertSame('100.00', $plan->expected_subsidy);
        $this->assertCount(5, $plan->expenseLines());

        $reopened = $this->actingAs($owner)
            ->get(route('applications.canonical-p2.edit', $application))
            ->assertOk();
        $reopened->assertSee('value="131.76"', false);
        $reopened->assertSee('value="31.76"', false);
        $reopened->assertSee('value="100.00"', false);
        foreach (['10.50', '20.25', '1.00', '0.01', '100.00'] as $amount) {
            $reopened->assertSee('value="'.$amount.'"', false);
        }

        $changed = $lines;
        $changed[1]['amount'] = '22.25';
        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $this->payload($changed, '133.76', '40.00', '93.76'))
            ->assertSessionHasNoErrors();
        $plan->refresh();
        $this->assertSame('133.76', $plan->total_investment);
        $this->assertSame('40.00', $plan->own_contribution);
        $this->assertSame('93.76', $plan->expected_subsidy);

        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $this->payload($lines, '131.76', '131.76', '0.00'))
            ->assertSessionHasErrors('expected_subsidy');
        $plan->refresh();
        $this->assertSame('93.76', $plan->expected_subsidy);
    }

    private function owner(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'name' => 'P2 Podnosilac',
            'jmb' => '0101990123456',
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
            'phone' => '067000000',
        ]);
    }

    private function competition(): Competition
    {
        return Competition::create([
            'title' => 'MMSP P2 zbir',
            'description' => 'Test',
            'type' => CompetitionProfileResolver::TYPE_CANONICAL,
            'status' => 'published',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'published_at' => now()->subDay(),
        ]);
    }

    /**
     * @param  list<array{cost_type: string, description: string, amount: string}>  $lines
     * @return array<string, mixed>
     */
    private function payload(array $lines, string $total, string $own, string $subsidy): array
    {
        return [
            'subsidy_type' => CanonicalSubsidyType::INNOVATIVE,
            'total_investment' => $total,
            'own_contribution' => $own,
            'expected_subsidy' => $subsidy,
            'expense_lines' => $lines,
            'narrative_sections' => array_fill_keys(array_keys(CanonicalInvestmentPlanService::NARRATIVE_FIELDS), 'Testni opis plana ulaganja.'),
        ];
    }
}
