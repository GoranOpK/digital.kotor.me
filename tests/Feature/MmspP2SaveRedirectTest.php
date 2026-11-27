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
use App\Support\CanonicalLegalForm;
use App\Support\CanonicalSubsidyType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Kn\Concerns\BuildsCanonicalApplicantDraft;
use Tests\TestCase;

class MmspP2SaveRedirectTest extends TestCase
{
    use BuildsCanonicalApplicantDraft;
    use RefreshDatabase;

    private const SUCCESS = 'Plan ulaganja (Obrazac P2) je sačuvan.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_incomplete_and_complete_p2_saves_open_the_same_application_status(): void
    {
        foreach ([CanonicalLegalForm::PREDUZETNIK, CanonicalLegalForm::DRUSTVO] as $index => $legalForm) {
            $owner = $this->owner($index);
            $application = $this->canonicalDraft($owner, $this->competition($legalForm), [
                'legal_form' => $legalForm,
                'with_p2' => false,
                'with_documents' => false,
            ]);
            $description = 'Sačuvan opis '.$legalForm;

            $saved = $this->actingAs($owner)
                ->put(route('applications.canonical-p2.update', $application), $this->incompletePayload($description));
            $saved->assertSessionHasNoErrors()
                ->assertRedirect(route('applications.show', $application))
                ->assertSessionHas('success', self::SUCCESS);
            $this->assertStatusPageShowsSuccess($owner, $application);

            $application->refresh();
            $this->assertSame('draft', $application->status);
            $this->assertNull($application->submitted_at);
            $this->assertSame(0, FinalApplicationClaim::query()->where('application_id', $application->id)->count());
            $this->assertNotNull(app(CanonicalInvestmentPlanService::class)->finalBlockReason($application));

            $draftPage = $this->actingAs($owner)
                ->get(route('applications.canonical-p2.edit', $application))
                ->assertOk();
            $draftPage->assertSee($description, false);
            $this->assertButtonLabel($draftPage->getContent(), 'Sačuvaj nacrt plana', 'mmsp-p2-save-draft');

            $complete = $this->completePayload($description);
            $finished = $this->actingAs($owner)
                ->put(route('applications.canonical-p2.update', $application), $complete);
            $finished->assertSessionHasNoErrors()
                ->assertRedirect(route('applications.show', $application))
                ->assertSessionHas('success', self::SUCCESS);
            $this->assertStatusPageShowsSuccess($owner, $application);

            $application->refresh();
            $plan = CanonicalInvestmentPlan::query()->where('application_id', $application->id)->firstOrFail();
            $this->assertSame('draft', $application->status);
            $this->assertNull($application->submitted_at);
            $this->assertSame('1000.00', $plan->total_investment);
            $this->assertSame('500.00', $plan->own_contribution);
            $this->assertSame('500.00', $plan->expected_subsidy);
            $this->assertSame($description, $plan->narrative_sections['investment_description']);
            $this->assertNull(app(CanonicalInvestmentPlanService::class)->finalBlockReason($application));

            $readyPage = $this->actingAs($owner)
                ->get(route('applications.canonical-p2.edit', $application))
                ->assertOk();
            $readyPage->assertSee($description, false);
            $readyPage->assertSee('value="500.00"', false);
            $this->assertButtonLabel($readyPage->getContent(), 'Sačuvaj plan ulaganja', 'mmsp-p2-save-ready');

            $invalid = $complete;
            $invalid['expected_subsidy'] = '1000.01';
            $this->actingAs($owner)
                ->from(route('applications.canonical-p2.edit', $application))
                ->put(route('applications.canonical-p2.update', $application), $invalid)
                ->assertRedirect(route('applications.canonical-p2.edit', $application))
                ->assertSessionHasErrors('expected_subsidy');
            $this->assertSame('500.00', $plan->fresh()->expected_subsidy);
            $this->assertSame('draft', $application->fresh()->status);
        }
    }

    private function assertStatusPageShowsSuccess(User $owner, Application $application): void
    {
        $page = $this->actingAs($owner)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('Status prijave', false);
        $this->assertMatchesRegularExpression(
            '/class="alert alert-success"[^>]*>\s*'.preg_quote(self::SUCCESS, '/').'\s*</',
            $page->getContent()
        );
    }

    private function assertButtonLabel(string $html, string $label, string $class): void
    {
        $this->assertMatchesRegularExpression(
            '/<button\b(?=[^>]*\bid="p2-save-button")(?=[^>]*\bclass="'.preg_quote($class, '/').'")[^>]*>\s*'
            .preg_quote($label, '/').'\s*<\/button>/',
            $html
        );
    }

    private function owner(int $index): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'name' => 'P2 Status '.$index,
            'jmb' => '01019900000'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
            'phone' => '067000000',
        ]);
    }

    private function competition(string $legalForm): Competition
    {
        return Competition::create([
            'title' => 'MMSP P2 status '.$legalForm,
            'description' => 'Test',
            'type' => CompetitionProfileResolver::TYPE_CANONICAL,
            'status' => 'published',
            'year' => (int) now()->format('Y'),
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
    private function incompletePayload(string $description): array
    {
        $narratives = array_fill_keys(array_keys(CanonicalInvestmentPlanService::NARRATIVE_FIELDS), '');
        $narratives['investment_description'] = $description;

        return [
            'subsidy_type' => CanonicalSubsidyType::INNOVATIVE,
            'total_investment' => '0.00',
            'own_contribution' => '0.00',
            'expected_subsidy' => '0.00',
            'expense_lines' => [
                ['cost_type' => '', 'description' => '', 'amount' => ''],
            ],
            'narrative_sections' => $narratives,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function completePayload(string $description): array
    {
        $narratives = array_fill_keys(array_keys(CanonicalInvestmentPlanService::NARRATIVE_FIELDS), 'Testni opis plana ulaganja.');
        $narratives['investment_description'] = $description;

        return [
            'subsidy_type' => CanonicalSubsidyType::INNOVATIVE,
            'total_investment' => '1000.00',
            'own_contribution' => '500.00',
            'expected_subsidy' => '500.00',
            'expense_lines' => [
                ['cost_type' => 'Oprema', 'description' => 'Mašina za preradu', 'amount' => '1000.00'],
            ],
            'narrative_sections' => $narratives,
        ];
    }
}
