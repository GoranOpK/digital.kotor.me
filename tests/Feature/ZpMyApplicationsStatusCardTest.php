<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZpMyApplicationsStatusCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_zp_my_applications_card_shows_application_and_plan_status_separately(): void
    {
        [$owner, $submittedWithoutSummary] = $this->application('zensko', 'ZP-A podnesena bez rezimea');
        $this->plan($submittedWithoutSummary, $owner, null);
        $submittedWithoutSummary->update(['status' => 'submitted', 'submitted_at' => now()]);

        [, $submittedComplete] = $this->application('zensko', 'ZP-B podnesena kompletna', $owner);
        $this->plan($submittedComplete, $owner, 'Sažetak');
        $submittedComplete->update(['status' => 'submitted', 'submitted_at' => now()]);

        [, $draftIncomplete] = $this->application('zensko', 'ZP-C nacrt nepotpun', $owner);
        $this->plan($draftIncomplete, $owner, null);

        [, $submittedWithoutPlan] = $this->application('zensko', 'ZP-D podnesena bez plana', $owner);
        $submittedWithoutPlan->update(['status' => 'submitted', 'submitted_at' => now()]);

        $page = $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $withoutSummary = $this->card($page, 'ZP-A podnesena bez rezimea');
        $this->assertStringContainsString('Status prijave: U obradi', $withoutSummary);
        $this->assertStringContainsString('Status biznis plana: Nacrt', $withoutSummary);
        $this->assertStringNotContainsString('Status biznis plana: Popunjen', $withoutSummary);
        $this->assertStringContainsString(route('applications.show', $submittedWithoutSummary), $withoutSummary);
        $this->assertStringContainsString(route('applications.business-plan.create', $submittedWithoutSummary), $withoutSummary);

        $complete = $this->card($page, 'ZP-B podnesena kompletna');
        $this->assertStringContainsString('Status prijave: U obradi', $complete);
        $this->assertStringContainsString('Status biznis plana: Popunjen', $complete);
        $this->assertStringNotContainsString('Status biznis plana: Nacrt', $complete);

        $draft = $this->card($page, 'ZP-C nacrt nepotpun');
        $this->assertStringContainsString('Status prijave: Nacrt', $draft);
        $this->assertStringContainsString('Status biznis plana: Nacrt', $draft);
        $this->assertStringNotContainsString('Status prijave: U obradi', $draft);

        $withoutPlan = $this->card($page, 'ZP-D podnesena bez plana');
        $this->assertStringContainsString('Status prijave: U obradi', $withoutPlan);
        $this->assertStringNotContainsString('Status biznis plana:', $withoutPlan);
        $this->assertStringContainsString(route('applications.show', $submittedWithoutPlan), $withoutPlan);

        $this->assertSame('submitted', $submittedWithoutSummary->fresh()->status);
        $this->assertNull($submittedWithoutSummary->fresh()->businessPlan->summary);
        $this->assertSame('submitted', $submittedComplete->fresh()->status);
        $this->assertSame('Sažetak', $submittedComplete->fresh()->businessPlan->summary);
        $this->assertSame('draft', $draftIncomplete->fresh()->status);
        $this->assertSame('submitted', $submittedWithoutPlan->fresh()->status);
        $this->assertNull($submittedWithoutPlan->fresh()->businessPlan);
    }

    public function test_pm_and_mmsp_my_applications_cards_keep_their_existing_status_display(): void
    {
        [$pmOwner, $pm] = $this->application('omladinsko', 'PM postojeca kartica');
        $this->plan($pm, $pmOwner, null);
        $pm->update(['status' => 'submitted', 'submitted_at' => now()]);

        $pmPage = $this->actingAs($pmOwner)->get(route('dashboard'))->assertOk()->getContent();
        $pmCard = $this->card($pmPage, 'PM postojeca kartica');
        $this->assertStringContainsString('Biznis plan – nacrt', $pmCard);
        $this->assertStringNotContainsString('Status prijave:', $pmCard);
        $this->assertStringNotContainsString('Status biznis plana:', $pmCard);
        $this->assertSame('submitted', $pm->fresh()->status);

        [$mmspOwner, $mmsp] = $this->application(CompetitionProfileResolver::TYPE_CANONICAL, 'MMSP postojeca kartica');
        $this->plan($mmsp, $mmspOwner, null);
        $mmsp->update(['status' => 'submitted', 'submitted_at' => now()]);

        $mmsp->load('competition');
        $mmspPage = $this->actingAs($mmspOwner)->get(route('dashboard'))->assertOk()->getContent();
        $mmspCard = $this->card($mmspPage, ($mmsp->competition->title ?: 'MMSP konkurs').' — MMSP');
        $this->assertStringNotContainsString('Status prijave:', $mmspCard);
        $this->assertStringNotContainsString('Status biznis plana:', $mmspCard);
        $this->assertStringNotContainsString('Biznis plan –', $mmspCard);
        $this->assertSame('submitted', $mmsp->fresh()->status);
    }

    private function card(string $html, string $title): string
    {
        foreach (explode('border-bottom: 1px solid #f3f4f6;', $html) as $part) {
            if (str_contains($part, 'title="'.$title.'"')) {
                return $part;
            }
        }

        $this->fail('Kartica nije pronađena: '.$title);
    }

    /**
     * @return array{0: User, 1: Application}
     */
    private function application(string $type, string $name, ?User $owner = null): array
    {
        $owner ??= User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $competition = Competition::create([
            'title' => $name.' '.uniqid(),
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
            'business_plan_name' => $name,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'is_registered' => false,
            'accuracy_declaration' => true,
            'physical_person_name' => $owner->name,
            'physical_person_jmbg' => '0101990123456',
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
        ]);

        return [$owner, $application];
    }

    private function plan(Application $application, User $owner, ?string $summary): void
    {
        BusinessPlan::create([
            'application_id' => $application->id,
            'business_idea_name' => 'Ideja',
            'applicant_name' => $owner->name,
            'applicant_jmbg' => '0101990123456',
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $owner->email,
            'summary' => $summary,
            'finances_notice_confirmed' => true,
        ]);
    }
}
