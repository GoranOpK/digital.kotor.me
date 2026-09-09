<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Competition;
use App\Support\SensitiveIdentifierLogSanitizer;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class ApplicationControllerSensitiveIdentifierLoggingTest extends TestCase
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

    public function test_store_draft_logs_validation_without_physical_person_jmbg_value(): void
    {
        $jmb = $this->validJmb(911);
        $owner = $this->makeKorisnik([
            'email' => 'app-log-fl-'.uniqid().'@example.test',
            'jmb' => $jmb,
        ]);
        $competition = $this->openCompetition();
        $token = $this->issueStartToken($owner, $competition, [
            'planned_intent' => 'future_entrepreneur',
        ]);

        $this->actingAs($owner)
            ->post(route('applications.store', $competition), [
                'save_as_draft' => '1',
                'start_context_token' => $token,
                'applicant_type' => 'fizicko_lice',
                'business_stage' => 'započinjanje',
                'business_plan_name' => 'Plan bez log leak-a',
                'business_area' => 'Usluge',
                'physical_person_name' => $owner->name,
                'physical_person_jmbg' => $jmb,
                'physical_person_phone' => '067000011',
                'physical_person_email' => $owner->email,
                'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
            ])
            ->assertRedirect();

        $application = Application::query()->where('user_id', $owner->id)->firstOrFail();
        $this->assertSame('fizicko_lice', $application->applicant_type);
        $this->assertSame('Plan bez log leak-a', $application->business_plan_name);
        $this->assertSame($jmb, $application->physical_person_jmbg);
        $this->assertNotSame('', (string) $application->physical_person_phone);

        $this->assertValidationPassedLogExists();
        $this->assertLoggedPayloadDoesNotContainIdentifier($jmb);
        $this->assertLoggedPayloadContainsRedactedKey('physical_person_jmbg');
        $this->assertLoggedPayloadContainsDiagnosticFragment('Plan bez log leak-a');
    }

    public function test_store_draft_logs_validation_without_applicant_jmbg_value(): void
    {
        $jmb = $this->validJmb(912);
        $owner = $this->makeKorisnik([
            'email' => 'app-log-pred-'.uniqid().'@example.test',
            'user_type' => UserType::ENTREPRENEUR,
            'pib' => $this->validPib(91),
            'jmb' => $jmb,
        ]);
        $competition = $this->openCompetition();
        $token = $this->issueStartToken($owner, $competition, [
            'business_stage' => 'započinjanje',
        ]);

        $this->actingAs($owner)
            ->post(route('applications.store', $competition), [
                'save_as_draft' => '1',
                'start_context_token' => $token,
                'applicant_type' => 'preduzetnica',
                'business_stage' => 'započinjanje',
                'registration_form' => 'Preduzetnik',
                'business_plan_name' => 'Preduzetnica plan bez leak-a',
                'business_area' => 'Usluge',
                'preduzetnik_name' => $owner->name,
                'preduzetnik_jmbg' => $jmb,
                'preduzetnik_phone' => '067000012',
                'preduzetnik_email' => $owner->email,
                'preduzetnik_address' => 'Njegoševa 1, 85330 Kotor',
            ])
            ->assertRedirect();

        $application = Application::query()->where('user_id', $owner->id)->firstOrFail();
        $this->assertSame('preduzetnica', $application->applicant_type);
        $this->assertSame('Preduzetnica plan bez leak-a', $application->business_plan_name);
        $this->assertSame($jmb, $application->applicant_jmbg);

        $this->assertValidationPassedLogExists();
        $this->assertLoggedPayloadDoesNotContainIdentifier($jmb);
        $this->assertLoggedPayloadContainsRedactedKey('applicant_jmbg');
        $this->assertLoggedPayloadContainsDiagnosticFragment('Preduzetnica plan bez leak-a');
    }

    private function assertValidationPassedLogExists(): void
    {
        $messages = array_column($this->loggedMessages, 'message');
        $this->assertNotEmpty(
            array_filter($messages, static fn (string $message): bool => str_contains($message, 'Validation passed! Validated data:')),
            'ApplicationController did not emit the validation diagnostic log.'
        );
    }

    private function assertLoggedPayloadDoesNotContainIdentifier(string $jmb): void
    {
        $encoded = json_encode($this->loggedMessages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        $this->assertStringNotContainsString($jmb, $encoded);
        $this->assertStringNotContainsString(hash('sha256', $jmb), $encoded);
        $this->assertStringNotContainsString(base64_encode($jmb), $encoded);
    }

    private function assertLoggedPayloadContainsRedactedKey(string $key): void
    {
        $encoded = json_encode($this->loggedMessages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        $this->assertStringContainsString($key, $encoded);
        $this->assertStringContainsString(SensitiveIdentifierLogSanitizer::REDACTED, $encoded);
    }

    private function assertLoggedPayloadContainsDiagnosticFragment(string $fragment): void
    {
        $encoded = json_encode($this->loggedMessages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        $this->assertStringContainsString($fragment, $encoded);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function issueStartToken(\App\Models\User $user, Competition $competition, array $payload = []): string
    {
        $response = $this->actingAs($user)->post(route('applications.start', $competition), $payload);
        $response->assertRedirect();
        $query = [];
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('start_token', $query);

        return $query['start_token'];
    }

    private function openCompetition(): Competition
    {
        return Competition::create([
            'title' => 'Test konkurs app log '.uniqid(),
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
}
