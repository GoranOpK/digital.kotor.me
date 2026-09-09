<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\Competition;
use App\Support\SensitiveIdentifierLogSanitizer;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class BusinessPlanSensitiveIdentifierLoggingTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    /**
     * @var list<array{level: string, message: string, context: array<string, mixed>}>
     */
    private array $loggedMessages = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $this->loggedMessages = [];

        Event::listen(MessageLogged::class, function (MessageLogged $event): void {
            $this->loggedMessages[] = [
                'level' => $event->level,
                'message' => (string) $event->message,
                'context' => $event->context,
            ];
        });
    }

    public function test_opening_business_plan_form_does_not_log_plaintext_jmbg(): void
    {
        $jmb = $this->validJmb(901);
        [$owner, $application] = $this->createCompleteApplicationWithBusinessPlan($jmb);

        $this->actingAs($owner)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk();

        $this->assertLoggedContextsDoNotContainIdentifier($jmb);
        $this->assertLoggedContextsContainRedactedJmbg();
        $this->assertFallbackFileDoesNotContainIdentifier($jmb);
        $this->assertSame($jmb, $application->businessPlan()->value('applicant_jmbg'));
    }

    public function test_saving_business_plan_draft_does_not_log_plaintext_jmbg_and_persists_value(): void
    {
        $jmb = $this->validJmb(902);
        [$owner, $application] = $this->createCompleteApplicationWithoutBusinessPlan($jmb);

        $this->actingAs($owner)
            ->post(route('applications.business-plan.store', $application), [
                'save_as_draft' => '1',
                'has_registered_business' => '0',
                'business_idea_name' => 'Test ideja bez log leak-a',
                'applicant_name' => $owner->name,
                'applicant_jmbg' => $jmb,
                'applicant_address' => 'Njegoševa 1, 85330 Kotor',
                'applicant_phone' => '067000000',
                'applicant_email' => $owner->email,
                'summary' => 'Sažetak nacrta.',
            ])
            ->assertRedirect(route('dashboard'));

        $application->refresh();
        $this->assertNotNull($application->businessPlan);
        $this->assertSame($jmb, $application->businessPlan->applicant_jmbg);
        $this->assertSame('Test ideja bez log leak-a', $application->businessPlan->business_idea_name);

        $this->assertLoggedContextsDoNotContainIdentifier($jmb);
        $this->assertLoggedContextsContainRedactedJmbg();
        $this->assertFallbackFileDoesNotContainIdentifier($jmb);
    }

    /**
     * @return array{0: \App\Models\User, 1: Application}
     */
    private function createCompleteApplicationWithBusinessPlan(string $jmb): array
    {
        [$owner, $application] = $this->createCompleteApplicationWithoutBusinessPlan($jmb);

        BusinessPlan::create([
            'application_id' => $application->id,
            'business_idea_name' => 'Kompletna ideja',
            'applicant_name' => $owner->name,
            'applicant_jmbg' => $jmb,
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $owner->email,
            'summary' => 'Sažetak.',
        ]);

        $application->refresh();
        $application->load('businessPlan');

        return [$owner, $application];
    }

    /**
     * @return array{0: \App\Models\User, 1: Application}
     */
    private function createCompleteApplicationWithoutBusinessPlan(string $jmb): array
    {
        $owner = $this->makeKorisnik([
            'email' => 'bp-log-'.uniqid().'@example.test',
            'jmb' => $jmb,
        ]);
        $competition = Competition::create([
            'title' => 'Test konkurs BP log '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(2),
        ]);

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
            'physical_person_jmbg' => $jmb,
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
            'accuracy_declaration' => true,
        ]);

        return [$owner, $application];
    }

    private function assertLoggedContextsDoNotContainIdentifier(string $jmb): void
    {
        $this->assertNotEmpty($this->loggedMessages, 'BusinessPlan logging did not emit MessageLogged events.');
        $encoded = json_encode($this->loggedMessages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        $this->assertStringNotContainsString($jmb, $encoded);
        $this->assertStringNotContainsString(hash('sha256', $jmb), $encoded);
        $this->assertStringNotContainsString(base64_encode($jmb), $encoded);
    }

    private function assertLoggedContextsContainRedactedJmbg(): void
    {
        $encoded = json_encode($this->loggedMessages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        $this->assertStringContainsString('applicant_jmbg', $encoded);
        $this->assertStringContainsString(SensitiveIdentifierLogSanitizer::REDACTED, $encoded);
    }

    private function assertFallbackFileDoesNotContainIdentifier(string $jmb): void
    {
        $path = storage_path('logs/business-plan.log');
        if (! is_file($path)) {
            return;
        }

        $contents = (string) file_get_contents($path);
        $this->assertStringNotContainsString($jmb, $contents);
        $this->assertStringNotContainsString(hash('sha256', $jmb), $contents);
    }
}
