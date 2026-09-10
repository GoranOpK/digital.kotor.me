<?php

namespace Tests\Feature;

use App\Console\Commands\JmbPlaintextRetirementPrecheckCommand;
use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\Competition;
use App\Models\ForeignBranchIdentity;
use App\Models\ForeignBranchRepresentative;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Security\JmbEncryptionService;
use App\Security\JmbLookupService;
use App\Security\JmbPlaintextRetirementPrecheckService;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class JmbPlaintextRetirementPrecheckCommandTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private const SEVEN_COLUMNS = [
        'users.jmb',
        'physical_person_identities.jmb',
        'legal_entity_authorized_persons.jmb',
        'foreign_branch_representatives.jmb',
        'applications.physical_person_jmbg',
        'applications.applicant_jmbg',
        'business_plans.applicant_jmbg',
    ];

    private int $jmbSerial = 400;

    /**
     * @var list<string>
     */
    private array $writeQueries = [];

    /**
     * @var list<string>
     */
    private array $seededSecrets = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['jmb.plaintext_retirement.enabled' => false]);
        $this->app->forgetInstance(JmbEncryptionService::class);
        $this->app->forgetInstance(JmbLookupService::class);
        $this->seededSecrets = [];
    }

    public function test_command_refuses_when_retirement_enabled(): void
    {
        config(['jmb.plaintext_retirement.enabled' => true]);

        $result = $this->runCommand();

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('refused', $result['output']);
        $this->assertStringContainsString(JmbPlaintextRetirementPrecheckCommand::FAIL_LINE, $result['output']);
        $this->assertTrue((bool) config('jmb.plaintext_retirement.enabled'));
    }

    public function test_fully_valid_seven_pair_dataset_passes(): void
    {
        $this->seedValidSevenPairs();
        $before = $this->fingerprint();
        $this->writeQueries = [];
        $this->listenForWrites();

        $result = $this->runCommand();

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString(JmbPlaintextRetirementPrecheckCommand::PASS_LINE, $result['output']);
        $this->assertStringNotContainsString(JmbPlaintextRetirementPrecheckCommand::FAIL_LINE, $result['output']);
        $this->assertSame($before, $this->fingerprint());
        $this->assertSame([], $this->writeQueries);
        $this->assertFalse((bool) config('jmb.plaintext_retirement.enabled'));
        $this->assertSevenColumnsPresent($result['output']);
        $this->assertSchemaPass($result['output']);
        $this->assertOutputSanitized($result['output']);
        $this->assertStringContainsString('plaintext_retirement=OFF', $result['output']);
        $this->assertStringContainsString('encryption_config=AVAILABLE', $result['output']);
        $this->assertStringContainsString('lookup_config=AVAILABLE', $result['output']);
        foreach (self::SEVEN_COLUMNS as $column) {
            $this->assertStringContainsString('column='.$column.' status=READY', $result['output']);
        }
    }

    public function test_plaintext_only_fails(): void
    {
        $ids = $this->seedValidSevenPairs();
        DB::table('users')->where('id', $ids['user'])->update([
            'jmb_encrypted' => null,
            'jmb_lookup' => null,
        ]);

        $result = $this->runCommand();

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('column=users.jmb status=BLOCKED', $result['output']);
        $this->assertStringContainsString('plaintext_only=1', $result['output']);
        $this->assertStringContainsString(JmbPlaintextRetirementPrecheckCommand::FAIL_LINE, $result['output']);
        $this->assertOutputSanitized($result['output']);
    }

    public function test_decrypt_mismatch_fails(): void
    {
        $ids = $this->seedValidSevenPairs();
        $other = $this->nextJmb();
        $cipher = app(JmbEncryptionService::class)->encrypt($other);
        DB::table('users')->where('id', $ids['user'])->update(['jmb_encrypted' => $cipher]);

        $result = $this->runCommand();

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('mismatches=1', $result['output']);
        $this->assertStringContainsString('column=users.jmb status=BLOCKED', $result['output']);
        $this->assertStringContainsString(JmbPlaintextRetirementPrecheckCommand::FAIL_LINE, $result['output']);
        $this->assertStringNotContainsString($cipher, $result['output']);
        $this->assertStringNotContainsString($other, $result['output']);
        $this->assertOutputSanitized($result['output']);
    }

    public function test_decrypt_failure_fails_without_sensitive_output(): void
    {
        $ids = $this->seedValidSevenPairs();
        $garbage = 'tampered-not-an-envelope';
        DB::table('users')->where('id', $ids['user'])->update(['jmb_encrypted' => $garbage]);

        $result = $this->runCommand();

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('decrypt_failures=1', $result['output']);
        $this->assertStringContainsString('column=users.jmb status=BLOCKED', $result['output']);
        $this->assertStringContainsString(JmbPlaintextRetirementPrecheckCommand::FAIL_LINE, $result['output']);
        $this->assertStringNotContainsString($garbage, $result['output']);
        $this->assertStringNotContainsString('id='.$ids['user'], $result['output']);
        $this->assertOutputSanitized($result['output']);
    }

    public function test_missing_lookup_fails(): void
    {
        $ids = $this->seedValidSevenPairs();
        DB::table('users')->where('id', $ids['user'])->update(['jmb_lookup' => null]);

        $result = $this->runCommand();

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('lookup_missing=1', $result['output']);
        $this->assertStringContainsString('column=users.jmb status=BLOCKED', $result['output']);
        $this->assertStringContainsString(JmbPlaintextRetirementPrecheckCommand::FAIL_LINE, $result['output']);
        $this->assertOutputSanitized($result['output']);
    }

    public function test_lookup_mismatch_fails(): void
    {
        $ids = $this->seedValidSevenPairs();
        $otherDigest = (string) app(JmbLookupService::class)->digest($this->nextJmb());
        DB::table('users')->where('id', $ids['user'])->update(['jmb_lookup' => $otherDigest]);

        $result = $this->runCommand();

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('digest_mismatches=1', $result['output']);
        $this->assertStringContainsString('column=users.jmb status=BLOCKED', $result['output']);
        $this->assertStringContainsString(JmbPlaintextRetirementPrecheckCommand::FAIL_LINE, $result['output']);
        $this->assertStringNotContainsString($otherDigest, $result['output']);
        $this->assertOutputSanitized($result['output']);
    }

    public function test_users_duplicate_lookup_groups_fail(): void
    {
        $first = $this->nextJmb();
        $second = $this->nextJmb();
        $userA = User::factory()->create(['jmb' => $first, 'email' => 'precheck-dup-a@example.test']);
        $userB = User::factory()->create(['jmb' => $second, 'email' => 'precheck-dup-b@example.test']);
        $this->rememberSecrets([$first, $second, (string) $userA->jmb_encrypted, (string) $userB->jmb_encrypted, (string) $userA->jmb_lookup, (string) $userB->jmb_lookup]);
        $digest = (string) $userA->jmb_lookup;

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_jmb_lookup_unique');
        });

        try {
            DB::table('users')->where('id', $userB->id)->update(['jmb_lookup' => $digest]);

            $result = $this->runCommand();

            $this->assertSame(1, $result['exit']);
            $this->assertStringContainsString('duplicate_digest_groups=1', $result['output']);
            $this->assertStringContainsString('rows_in_duplicate_groups=2', $result['output']);
            $this->assertStringContainsString('column=users.jmb status=BLOCKED', $result['output']);
            $this->assertStringContainsString(JmbPlaintextRetirementPrecheckCommand::FAIL_LINE, $result['output']);
            $this->assertStringNotContainsString($digest, $result['output']);
            $this->assertOutputSanitized($result['output']);
        } finally {
            DB::table('users')->whereIn('id', [$userA->id, $userB->id])->update([
                'jmb' => null,
                'jmb_encrypted' => null,
                'jmb_lookup' => null,
            ]);
            Schema::table('users', function (Blueprint $table) {
                $table->unique('jmb_lookup', 'users_jmb_lookup_unique');
            });
        }
    }

    public function test_encrypted_only_row_is_ready_when_encrypted_and_lookup_match(): void
    {
        $ids = $this->seedValidSevenPairs();
        DB::table('users')->where('id', $ids['user'])->update(['jmb' => null]);

        $result = $this->runCommand();

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('encrypted_only=1', $result['output']);
        $this->assertStringContainsString('column=users.jmb status=READY', $result['output']);
        $this->assertStringContainsString(JmbPlaintextRetirementPrecheckCommand::PASS_LINE, $result['output']);
        $this->assertOutputSanitized($result['output']);
    }

    public function test_neither_row_does_not_block(): void
    {
        $this->seedValidSevenPairs();
        User::factory()->create(['jmb' => null, 'email' => 'precheck-neither@example.test']);

        $result = $this->runCommand();

        $this->assertSame(0, $result['exit']);
        $this->assertMatchesRegularExpression(
            '/column=users\\.jmb total=\\d+ plaintext_non_null=\\d+ encrypted_non_null=\\d+ both_present=\\d+ plaintext_only=\\d+ encrypted_only=\\d+ neither=[1-9]\\d*/',
            $result['output']
        );
        $this->assertStringContainsString('column=users.jmb status=READY', $result['output']);
        $this->assertStringContainsString(JmbPlaintextRetirementPrecheckCommand::PASS_LINE, $result['output']);
        $this->assertOutputSanitized($result['output']);
    }

    public function test_schema_index_checks_are_reported(): void
    {
        $result = $this->runCommand();

        $this->assertSame(0, $result['exit']);
        $this->assertSchemaPass($result['output']);
        $this->assertTrue(Schema::hasColumn('users', 'jmb'));
        $this->assertContains('jmb', $this->uniqueColumns('users'));
        $this->assertContains('jmb_lookup', $this->uniqueColumns('users'));
        $this->assertNotContains('jmb_lookup', $this->uniqueColumns('physical_person_identities'));
    }

    public function test_all_seven_plaintext_columns_are_included(): void
    {
        $result = $this->runCommand();

        $this->assertSevenColumnsPresent($result['output']);
        $this->assertSame(7, count(JmbPlaintextRetirementPrecheckService::PAIRS));
        $mapped = array_column(JmbPlaintextRetirementPrecheckService::PAIRS, 'column');
        $this->assertSame(self::SEVEN_COLUMNS, $mapped);
    }

    public function test_unavailable_encryption_config_is_blocked_without_secrets(): void
    {
        config(['jmb.encryption.key' => '']);
        $this->app->forgetInstance(JmbEncryptionService::class);

        $result = $this->runCommand();

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('encryption_config=UNAVAILABLE', $result['output']);
        $this->assertStringContainsString(JmbPlaintextRetirementPrecheckCommand::FAIL_LINE, $result['output']);
        $this->assertStringNotContainsString((string) config('jmb.lookup.key'), $result['output']);
    }

    public function test_command_and_service_source_has_no_write_path(): void
    {
        $sources = [
            file_get_contents(base_path('app/Console/Commands/JmbPlaintextRetirementPrecheckCommand.php')),
            file_get_contents(base_path('app/Security/JmbPlaintextRetirementPrecheckService.php')),
        ];
        $combined = implode("\n", $sources);
        $this->assertIsString($combined);
        foreach (['->update(', '->insert(', '->delete(', '->save(', '->create(', 'migrate:', 'Schema::table'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $combined);
        }
    }

    /**
     * @return array{exit: int, output: string}
     */
    private function runCommand(): array
    {
        return [
            'exit' => Artisan::call('jmb:precheck-plaintext-retirement'),
            'output' => Artisan::output(),
        ];
    }

    /**
     * @return array{user: int, fl: int, authorized: int, representative: int, application: int, plan: int}
     */
    private function seedValidSevenPairs(): array
    {
        $userJmb = $this->nextJmb();
        $user = User::factory()->create([
            'jmb' => $userJmb,
            'email' => 'precheck-user@example.test',
        ]);

        $flJmb = $this->nextJmb();
        $flUser = User::factory()->create(['jmb' => null, 'email' => 'precheck-fl@example.test']);
        $flPlatform = PlatformIdentity::create([
            'user_id' => $flUser->id,
            'subject_type' => PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            'mobile_phone' => '+38267000011',
        ]);
        $fl = PhysicalPersonIdentity::create([
            'platform_identity_id' => $flPlatform->id,
            'first_name' => 'Ana',
            'last_name' => 'Test',
            'residential_status' => PhysicalPersonIdentity::RESIDENTIAL_RESIDENT,
            'id_document_type' => PhysicalPersonIdentity::DOCUMENT_JMB,
            'jmb' => $flJmb,
            'street_and_number' => 'Njegoševa 1',
            'city' => 'Kotor',
        ]);

        $authJmb = $this->nextJmb();
        $leUser = User::factory()->create(['jmb' => null, 'email' => 'precheck-le@example.test']);
        $lePlatform = PlatformIdentity::create([
            'user_id' => $leUser->id,
            'subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY,
            'mobile_phone' => '+38267000012',
        ]);
        $legal = LegalEntityIdentity::create([
            'platform_identity_id' => $lePlatform->id,
            'legal_form' => LegalEntityIdentity::FORM_DOO,
            'legal_name' => 'Precheck DOO',
            'pib' => $this->validPib($this->jmbSerial),
            'crps_number' => $this->validCrps(5, $this->jmbSerial),
            'street_and_number' => 'Stari grad 1',
            'city' => 'Kotor',
        ]);
        $authorized = LegalEntityAuthorizedPerson::create([
            'legal_entity_identity_id' => $legal->id,
            'first_name' => 'Mila',
            'last_name' => 'Test',
            'id_document_type' => LegalEntityAuthorizedPerson::DOCUMENT_JMB,
            'jmb' => $authJmb,
        ]);

        $repJmb = $this->nextJmb();
        $fbUser = User::factory()->create(['jmb' => null, 'email' => 'precheck-fb@example.test']);
        $fbPlatform = PlatformIdentity::create([
            'user_id' => $fbUser->id,
            'subject_type' => PlatformIdentity::SUBJECT_FOREIGN_BRANCH,
            'mobile_phone' => '+38267000013',
        ]);
        $branch = ForeignBranchIdentity::create([
            'platform_identity_id' => $fbPlatform->id,
            'foreign_company_name' => 'Precheck Foreign Co',
            'branch_name_in_montenegro' => 'Precheck Branch',
            'pib' => $this->validPib($this->jmbSerial + 50),
            'crps_number' => $this->validCrps(6, $this->jmbSerial),
            'street_and_number' => 'Obala 2',
            'city' => 'Kotor',
        ]);
        $representative = ForeignBranchRepresentative::create([
            'foreign_branch_identity_id' => $branch->id,
            'first_name' => 'Iva',
            'last_name' => 'Test',
            'id_document_type' => ForeignBranchRepresentative::DOCUMENT_JMB,
            'jmb' => $repJmb,
        ]);

        $physicalJmbg = $this->nextJmb();
        $applicantJmbg = $this->nextJmb();
        $appUser = User::factory()->create(['jmb' => null, 'email' => 'precheck-app@example.test']);
        $competition = Competition::create([
            'title' => 'Precheck JMB',
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
            'user_id' => $appUser->id,
            'business_plan_name' => 'Precheck plan',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'draft',
            'physical_person_jmbg' => $physicalJmbg,
            'applicant_jmbg' => $applicantJmbg,
        ]);

        $planJmbg = $this->nextJmb();
        $plan = BusinessPlan::create([
            'application_id' => $application->id,
            'applicant_jmbg' => $planJmbg,
        ]);

        $this->rememberSecrets([
            $userJmb,
            $flJmb,
            $authJmb,
            $repJmb,
            $physicalJmbg,
            $applicantJmbg,
            $planJmbg,
            (string) $user->jmb_encrypted,
            (string) $fl->jmb_encrypted,
            (string) $authorized->jmb_encrypted,
            (string) $representative->jmb_encrypted,
            (string) $application->physical_person_jmbg_encrypted,
            (string) $application->applicant_jmbg_encrypted,
            (string) $plan->applicant_jmbg_encrypted,
            (string) $user->jmb_lookup,
            (string) $fl->jmb_lookup,
        ]);

        return [
            'user' => (int) $user->id,
            'fl' => (int) $fl->id,
            'authorized' => (int) $authorized->id,
            'representative' => (int) $representative->id,
            'application' => (int) $application->id,
            'plan' => (int) $plan->id,
        ];
    }

    private function nextJmb(): string
    {
        $this->jmbSerial++;

        return $this->validJmb($this->jmbSerial);
    }

    /**
     * @param  list<string>  $secrets
     */
    private function rememberSecrets(array $secrets): void
    {
        foreach ($secrets as $secret) {
            if ($secret !== '') {
                $this->seededSecrets[] = $secret;
            }
        }
    }

    private function assertOutputSanitized(string $output): void
    {
        $this->assertDoesNotMatchRegularExpression('/\\bid=\\d+/', $output);
        $this->assertStringNotContainsString('jmb:v1:', $output);
        $this->assertStringNotContainsString((string) config('jmb.encryption.key'), $output);
        $this->assertStringNotContainsString((string) config('jmb.lookup.key'), $output);
        foreach ($this->seededSecrets as $secret) {
            $this->assertStringNotContainsString($secret, $output);
        }
    }

    private function assertSevenColumnsPresent(string $output): void
    {
        foreach (self::SEVEN_COLUMNS as $column) {
            $this->assertStringContainsString('column='.$column.' ', $output);
            $this->assertStringContainsString('column='.$column.' status=', $output);
        }
    }

    private function assertSchemaPass(string $output): void
    {
        $this->assertStringContainsString('schema all_plaintext_nullable=PASS', $output);
        $this->assertStringContainsString('schema users_jmb_unique=PASS', $output);
        $this->assertStringContainsString('schema users_jmb_lookup_unique=PASS', $output);
        $this->assertStringContainsString('schema physical_person_identities_jmb_lookup_index=PASS', $output);
        $this->assertStringContainsString('schema physical_person_identities_jmb_lookup_non_unique=PASS', $output);
    }

    private function listenForWrites(): void
    {
        DB::listen(function ($query): void {
            if (preg_match('/^\s*(insert|update|delete|replace|alter|drop|create|truncate|rename)\b/i', $query->sql) === 1) {
                $this->writeQueries[] = $query->sql;
            }
        });
    }

    private function fingerprint(): string
    {
        $parts = [];
        foreach (JmbPlaintextRetirementPrecheckService::PAIRS as $pair) {
            $columns = ['id', $pair['plaintext'], $pair['encrypted']];
            if ($pair['lookup'] !== null) {
                $columns[] = $pair['lookup'];
            }
            $parts[] = json_encode(
                DB::table($pair['table'])->orderBy('id')->get($columns)->toArray()
            );
        }

        return hash('sha256', implode('|', $parts));
    }

    /**
     * @return list<string>
     */
    private function uniqueColumns(string $table): array
    {
        $indexes = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Non_unique = 0');
        $columns = [];
        foreach ($indexes as $index) {
            if (($index->Key_name ?? '') === 'PRIMARY') {
                continue;
            }
            $columns[] = (string) $index->Column_name;
        }

        return array_values(array_unique($columns));
    }
}
