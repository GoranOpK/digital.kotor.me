<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\CanonicalInvestmentPlan;
use App\Models\Competition;
use App\Models\FinalApplicationClaim;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\CanonicalInvestmentPlanService;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalSubsidyType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Kn\Concerns\BuildsCanonicalApplicantDraft;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class MmspP2IncompleteDraftTest extends TestCase
{
    use BuildsCanonicalApplicantDraft;
    use MakesCanonicalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_partial_p2_draft_survives_reopen_and_stays_short_of_final_submission(): void
    {
        $owner = $this->owner();
        $application = $this->canonicalDraft($owner, $this->competition(), [
            'with_p2' => false,
            'with_documents' => true,
        ]);
        $description = 'Nabavka opreme za preradu lokalnih proizvoda.';

        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $this->browserPayload($description))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHas('success', 'Plan ulaganja (Obrazac P2) je sačuvan.');

        $plan = CanonicalInvestmentPlan::query()->where('application_id', $application->id)->firstOrFail();
        $this->assertSame($description, $plan->narrative_sections['investment_description']);
        $this->assertSame('', $plan->narrative_sections['investment_goals']);
        $this->assertSame('0.00', $plan->total_investment);
        $this->assertNull($plan->own_contribution);
        $this->assertNull($plan->expected_subsidy);
        $this->assertSame([], $plan->expenseLines());
        $this->assertSame('draft', $application->fresh()->status);
        $this->assertNull($application->fresh()->submitted_at);

        $service = app(CanonicalInvestmentPlanService::class);
        $this->assertNotNull($service->finalBlockReason($application));
        $this->assertStringContainsString('Ukupna vrijednost ulaganja mora biti veća od 0.', $service->finalBlockReason($application));

        $reopened = $this->actingAs($owner)
            ->get(route('applications.canonical-p2.edit', $application))
            ->assertOk();
        $reopened->assertSee($description, false);
        $reopened->assertSee('value="0.00"', false);

        $this->actingAs($owner)
            ->from(route('applications.show', $application))
            ->post(route('applications.final-submit', $application))
            ->assertSessionHasErrors('error');
        $this->assertSame('draft', $application->fresh()->status);
        $this->assertSame(0, FinalApplicationClaim::query()->count());

        $this->actingAs($owner)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('Nacrt', false)
            ->assertSee('Obrazac P2 - Nacrt', false)
            ->assertSee('Plan ulaganja (Obrazac P2) nije kompletan: Ukupna vrijednost ulaganja mora biti veća od 0.', false)
            ->assertDontSee('Dovršite obrazac, plan ulaganja i obaveznu dokumentaciju.', false);

        $this->actingAs($owner)
            ->from(route('applications.canonical-p2.edit', $application))
            ->put(route('applications.canonical-p2.update', $application), $this->browserPayload($description, [
                'own_contribution' => '50',
                'expected_subsidy' => '0',
            ]))
            ->assertSessionHasErrors('own_contribution');
        $plan->refresh();
        $this->assertSame($description, $plan->narrative_sections['investment_description']);
        $this->assertSame('0.00', $plan->total_investment);

        $this->actingAs($owner)
            ->from(route('applications.canonical-p2.edit', $application))
            ->put(route('applications.canonical-p2.update', $application), $this->browserPayload($description, [
                'total_investment' => 'abc',
            ]))
            ->assertSessionHasErrors('total_investment');

        $this->actingAs($owner)
            ->from(route('applications.canonical-p2.edit', $application))
            ->put(route('applications.canonical-p2.update', $application), $this->browserPayload($description, [
                'expense_lines' => [
                    ['cost_type' => 'Oprema', 'description' => 'Mašina', 'amount' => '-5'],
                ],
            ]))
            ->assertSessionHasErrors('expense_lines.0.amount');

        $this->actingAs($owner)
            ->from(route('applications.canonical-p2.edit', $application))
            ->put(route('applications.canonical-p2.update', $application), $this->browserPayload($description, [
                'total_investment' => '10.00',
                'expense_lines' => [
                    ['cost_type' => 'Oprema', 'description' => 'Mašina', 'amount' => '0'],
                ],
            ]))
            ->assertSessionHasErrors('expense_lines.0.amount');

        $plan->refresh();
        $this->assertSame([], $plan->expenseLines());
        $this->assertSame($description, $plan->narrative_sections['investment_description']);

        $lines = [
            ['cost_type' => 'Oprema', 'description' => 'Mašina', 'amount' => '10,50'],
            ['cost_type' => 'Alat', 'description' => 'Set alata', 'amount' => '20.25'],
        ];
        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $this->browserPayload($description, [
                'total_investment' => '30.75',
                'own_contribution' => '10.75',
                'expected_subsidy' => '20.00',
                'expense_lines' => $lines,
                'narrative_sections' => [
                    'investment_description' => $description,
                    'investment_goals' => 'Povećanje prerade.',
                ],
            ]))
            ->assertSessionHasNoErrors();

        $plan->refresh();
        $this->assertSame('30.75', $plan->total_investment);
        $this->assertSame('10.75', $plan->own_contribution);
        $this->assertSame('20.00', $plan->expected_subsidy);
        $this->assertSame([
            ['cost_type' => 'Oprema', 'description' => 'Mašina', 'amount' => '10.50'],
            ['cost_type' => 'Alat', 'description' => 'Set alata', 'amount' => '20.25'],
        ], $plan->expenseLines());
        $this->assertSame($description, $plan->narrative_sections['investment_description']);
        $this->assertSame('Povećanje prerade.', $plan->narrative_sections['investment_goals']);
        $this->assertNotNull($service->finalBlockReason($application->fresh()));
        $this->assertSame('draft', $application->fresh()->status);

        $completed = $this->browserPayload($description, [
            'total_investment' => '30.75',
            'own_contribution' => '10.75',
            'expected_subsidy' => '20.00',
            'expense_lines' => $lines,
            'narrative_sections' => array_fill_keys(array_keys(CanonicalInvestmentPlanService::NARRATIVE_FIELDS), $description),
        ]);
        $completed['narrative_sections']['investment_goals'] = 'Povećanje prerade.';
        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $completed)
            ->assertSessionHasNoErrors();

        $plan->refresh();
        $this->assertSame($description, $plan->narrative_sections['investment_description']);
        $this->assertSame('Povećanje prerade.', $plan->narrative_sections['investment_goals']);
        $this->assertSame('30.75', $plan->total_investment);
        $this->assertNull($service->finalBlockReason($application->fresh()));
        $this->assertSame('draft', $application->fresh()->status);
        $this->assertNull($application->fresh()->submitted_at);
    }

    public function test_zero_own_contribution_can_be_stored_with_an_otherwise_empty_financial_frame(): void
    {
        $owner = $this->owner();
        $application = $this->canonicalDraft($owner, $this->competition(), [
            'with_p2' => false,
            'with_documents' => false,
        ]);

        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $this->browserPayload('Opis uz nulu.', [
                'own_contribution' => '0',
                'expected_subsidy' => '0.00',
            ]))
            ->assertSessionHasNoErrors();

        $plan = CanonicalInvestmentPlan::query()->where('application_id', $application->id)->firstOrFail();
        $this->assertSame('0.00', $plan->total_investment);
        $this->assertSame('0.00', $plan->own_contribution);
        $this->assertSame('0.00', $plan->expected_subsidy);
        $this->assertNotNull(app(CanonicalInvestmentPlanService::class)->finalBlockReason($application));
        $this->assertSame('draft', $application->fresh()->status);
    }

    public function test_youth_and_women_partial_drafts_remain_on_their_business_plan(): void
    {
        foreach ([
            CompetitionProfileResolver::TYPE_YOUTH => 'Omladinska djelimična ideja',
            CompetitionProfileResolver::TYPE_WOMEN => 'Ženska djelimična ideja',
        ] as $type => $idea) {
            $owner = $this->makeKorisnik([
                'email' => $type.'-draft@example.com',
                'jmb' => $this->validJmb($type === CompetitionProfileResolver::TYPE_YOUTH ? 861 : 862),
            ]);
            $competition = Competition::create([
                'title' => 'Izolacija '.$type,
                'description' => 'Test',
                'type' => $type,
                'status' => 'published',
                'year' => 2026,
                'budget' => 10000,
                'deadline_days' => 20,
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addDays(20)->toDateString(),
                'published_at' => now()->subDay(),
            ]);
            $application = Application::create([
                'competition_id' => $competition->id,
                'user_id' => $owner->id,
                'status' => 'draft',
                'business_plan_name' => 'Nacrt '.$type,
                'business_area' => 'Usluge',
                'applicant_type' => 'fizicko_lice',
                'business_stage' => 'započinjanje',
                'is_registered' => false,
                'accuracy_declaration' => true,
                'physical_person_name' => $owner->name,
                'physical_person_jmbg' => $owner->jmb,
                'physical_person_phone' => '067000000',
                'physical_person_email' => $owner->email,
                'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
            ]);

            $this->actingAs($owner)
                ->post(route('applications.business-plan.store', $application), [
                    'save_as_draft' => '1',
                    'has_registered_business' => '0',
                    'business_idea_name' => $idea,
                    'funding_sources_table' => [['type' => '', 'price' => '']],
                ])
                ->assertRedirect(route('applications.show', $application))
                ->assertSessionHas('success', 'Nacrt biznis plana je uspješno sačuvan.');

            $this->assertSame($idea, $application->fresh()->businessPlan->business_idea_name);
            $this->assertSame('draft', $application->fresh()->status);
            $this->actingAs($owner)->get(route('applications.canonical-p2.edit', $application))->assertNotFound();
        }

        $this->assertSame(0, CanonicalInvestmentPlan::query()->count());
    }

    private function owner(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'name' => 'P2 Nacrt',
            'jmb' => '0101990123456',
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
            'phone' => '067000000',
        ]);
    }

    private function competition(): Competition
    {
        return Competition::create([
            'title' => 'MMSP P2 nepotpun nacrt',
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
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function browserPayload(string $description, array $overrides = []): array
    {
        $narratives = array_fill_keys(array_keys(CanonicalInvestmentPlanService::NARRATIVE_FIELDS), '');
        $narratives['investment_description'] = $description;

        return array_replace_recursive([
            'subsidy_type' => CanonicalSubsidyType::INNOVATIVE,
            'total_investment' => '0.00',
            'own_contribution' => '',
            'expected_subsidy' => '',
            'expense_lines' => [
                ['cost_type' => '', 'description' => '', 'amount' => ''],
                ['cost_type' => '', 'description' => '', 'amount' => ''],
                ['cost_type' => '', 'description' => '', 'amount' => ''],
            ],
            'narrative_sections' => $narratives,
        ], $overrides);
    }
}
