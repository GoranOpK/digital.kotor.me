<?php

namespace Tests\Feature;

use App\Models\Application;
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

class MmspP2SaveLabelTest extends TestCase
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

    public function test_p2_save_button_follows_server_completeness_and_does_not_submit(): void
    {
        $owner = $this->owner();
        $application = $this->canonicalDraft($owner, $this->competition(), [
            'with_p2' => false,
            'with_documents' => false,
        ]);
        $service = app(CanonicalInvestmentPlanService::class);

        $empty = $this->actingAs($owner)
            ->get(route('applications.canonical-p2.edit', $application))
            ->assertOk();
        $this->assertNotNull($service->finalBlockReason($application));
        $this->assertButtonLabel($empty->getContent(), 'Sačuvaj nacrt plana', 'mmsp-p2-save-draft');
        $empty->assertSee('js/mmsp-p2-save-label.js', false);
        $empty->assertSee('js/mmsp-p2-financials.js', false);
        $empty->assertSee('/plan-ulaganja', false);
        $this->assertStringNotContainsString(route('applications.final-submit', $application), $empty->getContent());

        $partial = $this->payload();
        $partial['narrative_sections']['sustainability'] = '';
        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $partial)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHas('success', 'Plan ulaganja (Obrazac P2) je sačuvan.');

        $application->refresh();
        $this->assertSame('draft', $application->status);
        $this->assertNull($application->submitted_at);
        $this->assertSame(0, FinalApplicationClaim::query()->count());
        $this->assertNotNull($service->finalBlockReason($application));

        $draftPage = $this->actingAs($owner)
            ->get(route('applications.canonical-p2.edit', $application))
            ->assertOk();
        $this->assertButtonLabel($draftPage->getContent(), 'Sačuvaj nacrt plana', 'mmsp-p2-save-draft');
        $draftPage->assertSee('Testni opis plana ulaganja.', false);

        $complete = $this->payload();
        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $complete)
            ->assertSessionHasNoErrors();

        $application->refresh();
        $this->assertSame('draft', $application->status);
        $this->assertNull($application->submitted_at);
        $this->assertSame(0, FinalApplicationClaim::query()->count());
        $this->assertNull($service->finalBlockReason($application));

        $readyPage = $this->actingAs($owner)
            ->get(route('applications.canonical-p2.edit', $application))
            ->assertOk();
        $this->assertButtonLabel($readyPage->getContent(), 'Sačuvaj plan ulaganja', 'mmsp-p2-save-ready');

        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $complete)
            ->assertSessionHasNoErrors();
        $application->refresh();
        $this->assertSame('draft', $application->status);
        $this->assertNull($application->submitted_at);
        $this->assertSame('1000.00', $application->canonicalInvestmentPlan()->value('total_investment'));
    }

    public function test_expected_subsidy_may_differ_from_total_minus_own_contribution(): void
    {
        $owner = $this->owner();
        $application = $this->canonicalDraft($owner, $this->competition(), [
            'with_p2' => false,
            'with_documents' => false,
        ]);

        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $this->payload())
            ->assertSessionHasNoErrors();

        $plan = $application->canonicalInvestmentPlan()->firstOrFail();
        $this->assertSame('1000.00', $plan->total_investment);
        $this->assertSame('200.00', $plan->own_contribution);
        $this->assertSame('500.00', $plan->expected_subsidy);
        $this->assertNull(app(CanonicalInvestmentPlanService::class)->finalBlockReason($application->fresh()));
        $this->assertSame('draft', $application->fresh()->status);
        $this->assertNull($application->fresh()->submitted_at);

        $page = $this->actingAs($owner)
            ->get(route('applications.canonical-p2.edit', $application))
            ->assertOk();
        $this->assertButtonLabel($page->getContent(), 'Sačuvaj plan ulaganja', 'mmsp-p2-save-ready');
        $page->assertSee('value="500.00"', false);

        $overTotal = $this->payload();
        $overTotal['expected_subsidy'] = '1000.01';
        $this->actingAs($owner)
            ->from(route('applications.canonical-p2.edit', $application))
            ->put(route('applications.canonical-p2.update', $application), $overTotal)
            ->assertSessionHasErrors('expected_subsidy');

        $plan->refresh();
        $this->assertSame('500.00', $plan->expected_subsidy);
        $this->assertSame('200.00', $plan->own_contribution);
        $this->assertSame('draft', $application->fresh()->status);
        $this->assertNull($application->fresh()->submitted_at);
        $this->assertNull(app(CanonicalInvestmentPlanService::class)->finalBlockReason($application->fresh()));
    }

    public function test_reopened_complete_p2_shows_the_plan_label_before_any_edit(): void
    {
        $owner = $this->owner();
        $application = $this->canonicalDraft($owner, $this->competition());

        $this->assertNull(app(CanonicalInvestmentPlanService::class)->finalBlockReason($application));
        $page = $this->actingAs($owner)
            ->get(route('applications.canonical-p2.edit', $application))
            ->assertOk();
        $this->assertButtonLabel($page->getContent(), 'Sačuvaj plan ulaganja', 'mmsp-p2-save-ready');
    }

    public function test_youth_and_women_business_plan_buttons_stay_unchanged(): void
    {
        foreach ([
            CompetitionProfileResolver::TYPE_YOUTH => 871,
            CompetitionProfileResolver::TYPE_WOMEN => 872,
        ] as $type => $serial) {
            $owner = $this->makeKorisnik([
                'email' => $type.'-label@example.com',
                'jmb' => $this->validJmb($serial),
            ]);
            $competition = Competition::create([
                'title' => 'Dugme '.$type,
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

            $page = $this->actingAs($owner)
                ->get(route('applications.business-plan.create', $application))
                ->assertOk();
            $page->assertSee('id="bpSubmitBtnText">Sačuvaj nacrt biznis plana</span>', false);
            $page->assertSee("allFilled ? 'Sačuvaj biznis plan' : 'Sačuvaj nacrt biznis plana'", false);
            $page->assertDontSee('Sačuvaj plan ulaganja', false);
            $page->assertDontSee('mmsp-p2-save-label.js', false);
            $page->assertDontSee('id="p2-save-button"', false);
            $page->assertDontSee('mmsp-p2-save-draft', false);
            $page->assertDontSee('mmsp-p2-save-ready', false);
            $this->actingAs($owner)->get(route('applications.canonical-p2.edit', $application))->assertNotFound();
        }
    }

    private function assertButtonLabel(string $html, string $label, string $class): void
    {
        $this->assertMatchesRegularExpression(
            '/<button\b(?=[^>]*\bid="p2-save-button")(?=[^>]*\bclass="'.preg_quote($class, '/').'")[^>]*>\s*'
            .preg_quote($label, '/').'\s*<\/button>/',
            $html
        );
    }

    private function owner(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'name' => 'P2 Dugme',
            'jmb' => '0101990123456',
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
            'phone' => '067000000',
        ]);
    }

    private function competition(): Competition
    {
        return Competition::create([
            'title' => 'MMSP P2 dugme',
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
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'subsidy_type' => CanonicalSubsidyType::INNOVATIVE,
            'total_investment' => '1000.00',
            'own_contribution' => '200.00',
            'expected_subsidy' => '500.00',
            'expense_lines' => [
                ['cost_type' => 'Oprema', 'description' => 'Mašina za preradu', 'amount' => '1000.00'],
            ],
            'narrative_sections' => array_fill_keys(array_keys(CanonicalInvestmentPlanService::NARRATIVE_FIELDS), 'Testni opis plana ulaganja.'),
        ];
    }
}
