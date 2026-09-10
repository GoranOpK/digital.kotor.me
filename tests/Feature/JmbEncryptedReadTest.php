<?php

namespace Tests\Feature;

use App\Identity\CanonicalIdentityReader;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\ForeignBranchSnapshot;
use App\Identity\IdentitySnapshot;
use App\Identity\PersonInRoleSnapshot;
use App\Identity\Runtime\CanonicalIdentifierUniqueness;
use App\Identity\Runtime\CurrentIdentityResolver;
use App\Identity\Runtime\ExistingSubjectIdentityEligibilityResult;
use App\Identity\Runtime\ExistingSubjectIdentityPrefill;
use App\Identity\Runtime\IdentityAccess;
use App\Identity\Runtime\IdentityUseGateException;
use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\Competition;
use App\Models\ForeignBranchRepresentative;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Security\JmbEncryptedReadException;
use App\Security\JmbEncryptedReadService;
use App\Security\JmbEncryptionService;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class JmbEncryptedReadTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesIdentitySnapshots;
    use RefreshDatabase;

    private int $jmbSerial = 200;

    /**
     * @var list<array{level: string, message: string, context: array<string, mixed>}>
     */
    private array $loggedMessages = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->app->forgetInstance(JmbEncryptionService::class);
        $this->app->forgetInstance(JmbEncryptedReadService::class);
        $this->loggedMessages = [];

        Event::listen(MessageLogged::class, function (MessageLogged $event): void {
            $this->loggedMessages[] = [
                'level' => $event->level,
                'message' => (string) $event->message,
                'context' => is_array($event->context) ? $event->context : [],
            ];
        });
    }

    public function test_encrypted_present_and_valid_returns_decrypted_value(): void
    {
        $jmb = $this->nextJmb();
        $user = User::factory()->create(['jmb' => $jmb]);

        $this->assertSame(
            $jmb,
            $this->readService()->readValue(
                $user->jmb_encrypted,
                $user->jmb,
                'users',
                $user->id,
                'jmb/jmb_encrypted',
            )
        );
    }

    public function test_encrypted_value_is_authoritative_when_plaintext_differs(): void
    {
        $encryptedJmb = $this->nextJmb();
        $plaintextJmb = $this->nextJmb();
        $user = User::factory()->create(['jmb' => $encryptedJmb]);
        $ciphertext = $user->jmb_encrypted;

        DB::table('users')->where('id', $user->id)->update(['jmb' => $plaintextJmb]);
        $user->refresh();

        $this->assertSame($plaintextJmb, $user->jmb);
        $this->assertSame($ciphertext, $user->jmb_encrypted);
        $this->assertSame(
            $encryptedJmb,
            $this->readService()->readValue(
                $user->jmb_encrypted,
                $user->jmb,
                'users',
                $user->id,
                'jmb/jmb_encrypted',
            )
        );
        $this->assertNotSame($plaintextJmb, $encryptedJmb);
    }

    public function test_null_encrypted_with_plaintext_falls_back_and_emits_sanitized_signal(): void
    {
        $jmb = $this->nextJmb();
        $user = User::factory()->create(['jmb' => $jmb]);
        DB::table('users')->where('id', $user->id)->update(['jmb_encrypted' => null]);
        $user->refresh();

        $service = $this->readService();
        $this->assertSame($jmb, $service->readValue(
            $user->jmb_encrypted,
            $user->jmb,
            'users',
            $user->id,
            'jmb/jmb_encrypted',
        ));
        $service->readValue(
            $user->jmb_encrypted,
            $user->jmb,
            'users',
            $user->id,
            'jmb/jmb_encrypted',
        );

        $fallbackLogs = array_values(array_filter(
            $this->loggedMessages,
            fn (array $log): bool => $log['message'] === 'jmb.encrypted_read'
                && ($log['context']['reason'] ?? null) === 'plaintext_fallback'
        ));

        $this->assertCount(1, $fallbackLogs);
        $this->assertSame('warning', $fallbackLogs[0]['level']);
        $this->assertSame('users', $fallbackLogs[0]['context']['table']);
        $this->assertSame($user->id, $fallbackLogs[0]['context']['id']);
        $this->assertSame('jmb/jmb_encrypted', $fallbackLogs[0]['context']['pair']);
        $this->assertSame('plaintext_fallback', $fallbackLogs[0]['context']['reason']);
        $this->assertLogsAndExceptionDoNotLeak($jmb, $user->jmb_encrypted);
    }

    public function test_tampered_ciphertext_fails_closed_without_plaintext_fallback(): void
    {
        $jmb = $this->nextJmb();
        $user = User::factory()->create(['jmb' => $jmb]);
        $ciphertext = (string) $user->jmb_encrypted;
        $tampered = 'jmb:v1:not-a-valid-payload';

        DB::table('users')->where('id', $user->id)->update(['jmb_encrypted' => $tampered]);
        $user->refresh();

        try {
            $this->readService()->readValue(
                $user->jmb_encrypted,
                $user->jmb,
                'users',
                $user->id,
                'jmb/jmb_encrypted',
            );
            $this->fail('Tampered ciphertext must fail closed.');
        } catch (JmbEncryptedReadException $e) {
            $this->assertSame('Encrypted identifier could not be decrypted.', $e->getMessage());
            $this->assertLogsAndExceptionDoNotLeak($jmb, $tampered, $e);
            $this->assertStringNotContainsString($ciphertext, $e->getMessage());
        }
    }

    public function test_wrong_key_ciphertext_fails_closed(): void
    {
        $jmb = $this->nextJmb();
        $foreign = new \Illuminate\Encryption\Encrypter(random_bytes(32), JmbEncryptionService::CIPHER);
        $wrong = 'jmb:v1:'.$foreign->encryptString($jmb);

        try {
            $this->readService()->readValue($wrong, $jmb, 'users', 1, 'jmb/jmb_encrypted');
            $this->fail('Wrong-key ciphertext must fail closed.');
        } catch (JmbEncryptedReadException $e) {
            $this->assertLogsAndExceptionDoNotLeak($jmb, $wrong, $e);
        }
    }

    public function test_canonical_identity_reader_physical_person_is_encrypted_first(): void
    {
        $encryptedJmb = $this->nextJmb();
        $plaintextJmb = $this->nextJmb();
        $user = $this->makeKorisnik(['jmb' => null, 'email' => 'd-fl@example.test']);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => ['jmb' => $encryptedJmb],
        ]));

        $fl = PhysicalPersonIdentity::query()->firstOrFail();
        DB::table('physical_person_identities')->where('id', $fl->id)->update(['jmb' => $plaintextJmb]);

        $snapshot = (new CanonicalIdentityReader)->forUser($user->fresh());
        $this->assertSame($encryptedJmb, $snapshot->physicalPerson?->jmb);
        $this->assertNotSame($plaintextJmb, $snapshot->physicalPerson?->jmb);

        config(['identity.canonical_read' => true]);
        $view = app(CurrentIdentityResolver::class)->viewFor($user->fresh());
        $this->assertSame(IdentityAccess::CURRENT, $view->access);
        $this->assertSame($encryptedJmb, $view->jmb);
    }

    public function test_authorized_person_and_representative_live_reads_are_encrypted_first(): void
    {
        $writer = new CanonicalIdentityWriter;
        $reader = new CanonicalIdentityReader;

        $apEncrypted = $this->nextJmb();
        $apPlaintext = $this->nextJmb();
        $plUser = $this->makeKorisnik(['jmb' => null, 'email' => 'd-pl@example.test']);
        $writer->createForUser($plUser, $this->plSnapshot($plUser, ['jmb' => $apEncrypted]));
        $person = LegalEntityAuthorizedPerson::query()->firstOrFail();
        DB::table('legal_entity_authorized_persons')->where('id', $person->id)->update(['jmb' => $apPlaintext]);
        $this->assertSame($apEncrypted, $reader->forUser($plUser->fresh())->legalEntity?->authorizedPerson?->jmb);

        $repEncrypted = $this->nextJmb();
        $repPlaintext = $this->nextJmb();
        $fbUser = $this->makeKorisnik(['jmb' => null, 'email' => 'd-fb@example.test']);
        $writer->createForUser($fbUser, $this->dspdSnapshotWithJmb($fbUser, $repEncrypted));
        $rep = ForeignBranchRepresentative::query()->firstOrFail();
        DB::table('foreign_branch_representatives')->where('id', $rep->id)->update(['jmb' => $repPlaintext]);
        $this->assertSame($repEncrypted, $reader->forUser($fbUser->fresh())->foreignBranch?->representative?->jmb);
    }

    public function test_application_reads_its_own_encrypted_snapshots(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->nextJmb()]);
        $liveJmb = $user->jmb;
        $physicalEncrypted = $this->nextJmb();
        $physicalPlain = $this->nextJmb();
        $applicantEncrypted = $this->nextJmb();
        $applicantPlain = $this->nextJmb();
        $competition = $this->openCompetition();

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'applicant_type' => 'preduzetnica',
            'status' => 'draft',
            'physical_person_jmbg' => $physicalEncrypted,
            'applicant_jmbg' => $applicantEncrypted,
        ]);

        DB::table('applications')->where('id', $application->id)->update([
            'physical_person_jmbg' => $physicalPlain,
            'applicant_jmbg' => $applicantPlain,
        ]);
        $application->refresh();

        $this->assertSame($physicalEncrypted, $application->physicalPersonJmbgForRead());
        $this->assertSame($applicantEncrypted, $application->applicantJmbgForRead());
        $this->assertSame($applicantEncrypted, $application->resolvedApplicantJmbg());
        $this->assertNotSame($liveJmb, $application->physicalPersonJmbgForRead());
        $this->assertNotSame($liveJmb, $application->applicantJmbgForRead());
    }

    public function test_business_plan_reads_its_own_encrypted_snapshot_not_live_profile(): void
    {
        $profileJmb = $this->nextJmb();
        $planEncrypted = $this->nextJmb();
        $planPlain = $this->nextJmb();
        $user = $this->makeKorisnik(['jmb' => $profileJmb]);
        $competition = $this->openCompetition();
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'applicant_type' => 'fizicko_lice',
            'status' => 'draft',
        ]);

        $plan = BusinessPlan::create([
            'application_id' => $application->id,
            'applicant_name' => 'Ana',
            'applicant_jmbg' => $planEncrypted,
        ]);
        DB::table('business_plans')->where('id', $plan->id)->update(['applicant_jmbg' => $planPlain]);
        $plan->refresh();

        $this->assertSame($planEncrypted, $plan->applicantJmbgForRead());
        $this->assertNotSame($profileJmb, $plan->applicantJmbgForRead());
        $this->assertNotSame($planPlain, $plan->applicantJmbgForRead());
    }

    public function test_absent_application_snapshot_retains_live_identity_fallback_for_preduzetnica(): void
    {
        $liveJmb = $this->nextJmb();
        $user = $this->makeKorisnik(['jmb' => $liveJmb]);
        $competition = $this->openCompetition();
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'applicant_type' => 'preduzetnica',
            'status' => 'draft',
        ]);

        $this->assertFalse($application->hasApplicantJmbgSnapshot());
        $this->assertSame($liveJmb, $application->resolvedApplicantJmbg());
    }

    public function test_completeness_does_not_treat_decrypt_failed_ciphertext_as_filled(): void
    {
        $jmb = $this->nextJmb();
        $user = $this->makeKorisnik(['jmb' => $jmb]);
        $competition = $this->openCompetition();
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Plan',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'status' => 'draft',
            'is_registered' => false,
            'physical_person_name' => $user->name,
            'physical_person_jmbg' => $jmb,
            'physical_person_phone' => '067000000',
            'physical_person_email' => $user->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
            'accuracy_declaration' => true,
        ]);

        $this->assertTrue($application->fresh()->isObrazacComplete());

        DB::table('applications')->where('id', $application->id)->update([
            'physical_person_jmbg_encrypted' => 'jmb:v1:not-a-valid-payload',
        ]);

        $this->assertFalse($application->fresh()->isObrazacComplete());

        $plan = BusinessPlan::create([
            'application_id' => $application->id,
            'business_idea_name' => 'Ideja',
            'applicant_name' => $user->name,
            'applicant_jmbg' => $jmb,
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $user->email,
            'summary' => 'Sažetak',
        ]);
        $this->assertTrue($plan->fresh()->isComplete());

        DB::table('business_plans')->where('id', $plan->id)->update([
            'applicant_jmbg_encrypted' => 'jmb:v1:not-a-valid-payload',
        ]);
        $this->assertFalse($plan->fresh()->isComplete());
    }

    public function test_canonical_decrypt_failure_is_invalid_not_plaintext(): void
    {
        $jmb = $this->nextJmb();
        $user = $this->makeKorisnik(['jmb' => null, 'email' => 'd-fail@example.test']);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => ['jmb' => $jmb],
        ]));
        $fl = PhysicalPersonIdentity::query()->firstOrFail();
        DB::table('physical_person_identities')->where('id', $fl->id)->update([
            'jmb_encrypted' => 'jmb:v1:not-a-valid-payload',
        ]);

        config(['identity.canonical_read' => true]);
        $view = app(CurrentIdentityResolver::class)->viewFor($user->fresh());
        $this->assertSame(IdentityAccess::INVALID, $view->access);
        $this->assertNull($view->jmb);

        try {
            app(CurrentIdentityResolver::class)->requireCurrentSubject($user->fresh());
            $this->fail('Decrypt failure must gate subject flows.');
        } catch (IdentityUseGateException $e) {
            $this->assertSame(IdentityAccess::INVALID, $e->access);
            $this->assertSame(JmbEncryptedReadException::USER_MESSAGE, $e->getMessage());
            $this->assertLogsAndExceptionDoNotLeak($jmb, 'jmb:v1:not-a-valid-payload', $e);
        }
    }

    public function test_legacy_users_jmb_prefill_is_encrypted_first(): void
    {
        $encryptedJmb = $this->nextJmb();
        $plaintextJmb = $this->nextJmb();
        $user = $this->makeKorisnik(['jmb' => $encryptedJmb]);
        DB::table('users')->where('id', $user->id)->update(['jmb' => $plaintextJmb]);

        $prefill = (new ExistingSubjectIdentityPrefill)->forUser(
            $user->fresh(),
            ExistingSubjectIdentityEligibilityResult::BRANCH_PREDUZETNIK
        );

        $this->assertSame($encryptedJmb, $prefill['jmb']);
        $this->assertNotSame($plaintextJmb, $prefill['jmb']);
    }

    public function test_plaintext_uniqueness_and_unique_rule_remain_on_users_jmb(): void
    {
        $taken = $this->nextJmb();
        $other = $this->nextJmb();
        $user = User::factory()->create(['email' => 'd-unique@example.test', 'jmb' => $taken]);
        DB::table('users')->where('id', $user->id)->update(['jmb' => $other]);

        $this->assertTrue((new CanonicalIdentifierUniqueness)->jmbTaken($other));
        $this->assertFalse((new CanonicalIdentifierUniqueness)->jmbTaken($taken));

        $validator = Validator::make(
            ['jmb' => $other],
            ['jmb' => Rule::unique(User::class, 'jmb')]
        );
        $this->assertTrue($validator->fails());

        $validatorTakenCiphertext = Validator::make(
            ['jmb' => $taken],
            ['jmb' => Rule::unique(User::class, 'jmb')]
        );
        $this->assertFalse($validatorTakenCiphertext->fails());
    }

    private function readService(): JmbEncryptedReadService
    {
        return app(JmbEncryptedReadService::class);
    }

    private function dspdSnapshotWithJmb(User $user, string $jmb): IdentitySnapshot
    {
        return new IdentitySnapshot(
            userId: $user->id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_FOREIGN_BRANCH,
            mobilePhone: '+38267000003',
            streetAndNumber: 'Bulevar 8',
            city: 'Podgorica',
            foreignBranch: new ForeignBranchSnapshot(
                foreignCompanyName: 'Foreign Co',
                branchNameInMontenegro: 'Ogranak CG',
                streetAndNumber: 'Bulevar 8',
                city: 'Podgorica',
                representative: new PersonInRoleSnapshot(
                    firstName: 'Jelena',
                    lastName: 'Jovanović',
                    idDocumentType: 'jmb',
                    jmb: $jmb,
                ),
                pib: '00000007',
                crpsNumber: '60000001',
            ),
        );
    }

    private function openCompetition(): Competition
    {
        return Competition::create([
            'title' => 'Faza D read',
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

    private function assertLogsAndExceptionDoNotLeak(string $jmb, ?string $ciphertext, ?\Throwable $exception = null): void
    {
        $key = (string) config('jmb.encryption.key');
        $haystacks = [json_encode($this->loggedMessages, JSON_UNESCAPED_UNICODE) ?: ''];
        if ($exception !== null) {
            $haystacks[] = $exception->getMessage();
            $haystacks[] = $exception->getTraceAsString();
            $previous = $exception->getPrevious();
            if ($previous !== null) {
                $haystacks[] = $previous->getMessage();
            }
        }

        foreach ($haystacks as $haystack) {
            $this->assertStringNotContainsString($jmb, $haystack);
            if ($ciphertext !== null && $ciphertext !== '') {
                $this->assertStringNotContainsString($ciphertext, $haystack);
            }
            if ($key !== '') {
                $this->assertStringNotContainsString($key, $haystack);
            }
            $this->assertStringNotContainsString('APP_KEY', $haystack);
        }
    }

    /**
     * @var array<string, true>
     */
    private array $usedJmbs = [];

    private function nextJmb(): string
    {
        do {
            $this->jmbSerial++;
            $jmb = $this->validJmb($this->jmbSerial);
        } while (isset($this->usedJmbs[$jmb]));

        $this->usedJmbs[$jmb] = true;

        return $jmb;
    }
}
