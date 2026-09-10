<?php

namespace Tests\Feature;

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
use App\Security\JmbEncryptedBackfillService;
use App\Security\JmbEncryptionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class JmbEncryptedBackfillCommandTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    private const SYNTHETIC_JMB = '0101990009015';

    private int $jmbSerial = 20;

    private string $jmbKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->jmbKey = 'base64:'.base64_encode(random_bytes(32));
        config([
            'jmb.encryption.key' => $this->jmbKey,
            'jmb.encryption.key_id' => 'v1',
            'jmb.encryption.previous_keys' => '',
        ]);
        $this->app->forgetInstance(JmbEncryptionService::class);
    }

    public function test_null_plaintext_is_skipped_and_plaintext_plus_null_target_is_encrypted(): void
    {
        $emptyId = $this->insertUser(null);
        $filledId = $this->insertUser(self::SYNTHETIC_JMB);
        $beforeEmpty = $this->raw('users', $emptyId);
        $beforeFilled = $this->raw('users', $filledId);

        $result = $this->runCommand(['--scope' => 'users']);

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('skipped_no_plaintext=1', $result['output']);
        $this->assertStringContainsString('encrypted=1', $result['output']);
        $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $result['output']);

        $afterEmpty = $this->raw('users', $emptyId);
        $afterFilled = $this->raw('users', $filledId);

        $this->assertNull($afterEmpty->jmb);
        $this->assertNull($afterEmpty->jmb_encrypted);
        $this->assertSame($beforeEmpty->jmb, $afterEmpty->jmb);
        $this->assertSame($beforeFilled->jmb, $afterFilled->jmb);
        $this->assertSame(self::SYNTHETIC_JMB, $afterFilled->jmb);
        $this->assertNotNull($afterFilled->jmb_encrypted);
        $this->assertSame(
            self::SYNTHETIC_JMB,
            JmbEncryptionService::fromConfig()->decrypt($afterFilled->jmb_encrypted)
        );
        $this->assertSame((string) $beforeFilled->updated_at, (string) $afterFilled->updated_at);
    }

    public function test_existing_valid_target_is_not_rewritten_and_second_run_is_idempotent(): void
    {
        $id = $this->insertUser(self::SYNTHETIC_JMB);

        $this->assertSame(0, $this->runCommand(['--scope' => 'users'])['exit']);
        $first = $this->raw('users', $id)->jmb_encrypted;
        $this->assertIsString($first);

        $second = $this->runCommand(['--scope' => 'users']);
        $this->assertSame(0, $second['exit']);
        $this->assertStringContainsString('already_valid=1', $second['output']);
        $this->assertStringContainsString('encrypted=0', $second['output']);
        $this->assertSame($first, $this->raw('users', $id)->jmb_encrypted);
        $this->assertSame(self::SYNTHETIC_JMB, $this->raw('users', $id)->jmb);
    }

    public function test_mismatching_encrypted_target_is_not_overwritten_and_command_fails(): void
    {
        $id = $this->insertUser(self::SYNTHETIC_JMB);
        $other = JmbEncryptionService::fromConfig()->encrypt($this->nextJmb());
        DB::table('users')->where('id', $id)->update(['jmb_encrypted' => $other]);

        $result = $this->runCommand(['--scope' => 'users']);

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('reason=encrypted_target_mismatch', $result['output']);
        $this->assertStringContainsString('id='.$id, $result['output']);
        $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $result['output']);
        $this->assertSame($other, $this->raw('users', $id)->jmb_encrypted);
        $this->assertSame(self::SYNTHETIC_JMB, $this->raw('users', $id)->jmb);
    }

    public function test_malformed_target_is_not_overwritten_and_command_fails(): void
    {
        $id = $this->insertUser(self::SYNTHETIC_JMB);
        DB::table('users')->where('id', $id)->update(['jmb_encrypted' => 'tampered-not-an-envelope']);

        $result = $this->runCommand(['--scope' => 'users']);

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('reason=encrypted_target_unreadable', $result['output']);
        $this->assertSame('tampered-not-an-envelope', $this->raw('users', $id)->jmb_encrypted);
        $this->assertSame(self::SYNTHETIC_JMB, $this->raw('users', $id)->jmb);
        $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $result['output']);
    }

    public function test_dry_run_writes_nothing_and_still_validates_round_trip(): void
    {
        $id = $this->insertUser(self::SYNTHETIC_JMB);

        $result = $this->runCommand(['--scope' => 'users', '--dry-run' => true]);

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('mode=dry-run', $result['output']);
        $this->assertStringContainsString('would_encrypt=1', $result['output']);
        $this->assertNull($this->raw('users', $id)->jmb_encrypted);
        $this->assertSame(self::SYNTHETIC_JMB, $this->raw('users', $id)->jmb);
        $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $result['output']);
    }

    public function test_dry_run_detects_mismatch_without_writing(): void
    {
        $id = $this->insertUser(self::SYNTHETIC_JMB);
        $other = JmbEncryptionService::fromConfig()->encrypt($this->nextJmb());
        DB::table('users')->where('id', $id)->update(['jmb_encrypted' => $other]);

        $result = $this->runCommand(['--scope' => 'users', '--dry-run' => true]);

        $this->assertSame(1, $result['exit']);
        $this->assertSame($other, $this->raw('users', $id)->jmb_encrypted);
        $this->assertSame(self::SYNTHETIC_JMB, $this->raw('users', $id)->jmb);
    }

    public function test_one_scope_does_not_touch_other_mappings(): void
    {
        $userId = $this->insertUser(self::SYNTHETIC_JMB);
        $flId = $this->insertPhysicalPerson($this->nextJmb());
        $app = $this->insertApplication($this->nextJmb(), $this->nextJmb());

        $result = $this->runCommand(['--scope' => 'users']);

        $this->assertSame(0, $result['exit']);
        $this->assertNotNull($this->raw('users', $userId)->jmb_encrypted);
        $this->assertNull($this->raw('physical_person_identities', $flId)->jmb_encrypted);
        $this->assertNull($this->raw('applications', $app['id'])->physical_person_jmbg_encrypted);
        $this->assertNull($this->raw('applications', $app['id'])->applicant_jmbg_encrypted);
    }

    public function test_application_mapping_scope_touches_only_that_column(): void
    {
        $personJmb = $this->nextJmb();
        $applicantJmb = $this->nextJmb();
        $app = $this->insertApplication($personJmb, $applicantJmb);

        $result = $this->runCommand(['--scope' => 'applications.physical_person_jmbg']);

        $this->assertSame(0, $result['exit']);
        $row = $this->raw('applications', $app['id']);
        $this->assertSame($personJmb, $row->physical_person_jmbg);
        $this->assertSame($applicantJmb, $row->applicant_jmbg);
        $this->assertNotNull($row->physical_person_jmbg_encrypted);
        $this->assertNull($row->applicant_jmbg_encrypted);
        $this->assertSame(
            $personJmb,
            JmbEncryptionService::fromConfig()->decrypt($row->physical_person_jmbg_encrypted)
        );
    }

    public function test_unknown_scope_and_invalid_chunk_fail_safely(): void
    {
        $id = $this->insertUser(self::SYNTHETIC_JMB);

        $unknown = $this->runCommand(['--scope' => 'applications']);
        $this->assertSame(1, $unknown['exit']);
        $this->assertStringContainsString('Unknown JMB backfill scope', $unknown['output']);
        $this->assertNull($this->raw('users', $id)->jmb_encrypted);

        $chunk = $this->runCommand(['--scope' => 'users', '--chunk' => '0']);
        $this->assertSame(1, $chunk['exit']);
        $this->assertStringContainsString('Invalid JMB backfill chunk', $chunk['output']);
        $this->assertNull($this->raw('users', $id)->jmb_encrypted);

        $nonInt = $this->runCommand(['--scope' => 'users', '--chunk' => 'abc']);
        $this->assertSame(1, $nonInt['exit']);
        $this->assertNull($this->raw('users', $id)->jmb_encrypted);
    }

    public function test_missing_jmb_key_fails_before_writes(): void
    {
        $id = $this->insertUser(self::SYNTHETIC_JMB);
        config(['jmb.encryption.key' => '']);
        $this->app->forgetInstance(JmbEncryptionService::class);

        $result = $this->runCommand(['--scope' => 'users']);

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('JMB encryption key is missing', $result['output']);
        $this->assertNull($this->raw('users', $id)->jmb_encrypted);
        $this->assertSame(self::SYNTHETIC_JMB, $this->raw('users', $id)->jmb);
        $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $result['output']);
    }

    public function test_processing_works_across_multiple_chunks(): void
    {
        $ids = [
            $this->insertUser($this->nextJmb()),
            $this->insertUser($this->nextJmb()),
            $this->insertUser($this->nextJmb()),
        ];

        $result = $this->runCommand(['--scope' => 'users', '--chunk' => '1']);

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('encrypted=3', $result['output']);
        foreach ($ids as $id) {
            $row = $this->raw('users', $id);
            $this->assertNotNull($row->jmb_encrypted);
            $this->assertSame($row->jmb, JmbEncryptionService::fromConfig()->decrypt($row->jmb_encrypted));
        }
    }

    public function test_all_seven_source_target_mappings_are_covered(): void
    {
        $this->assertCount(7, JmbEncryptedBackfillService::MAPPINGS);

        $values = [
            'users' => $this->nextJmb(),
            'physical_person_identities' => $this->nextJmb(),
            'legal_entity_authorized_persons' => $this->nextJmb(),
            'foreign_branch_representatives' => $this->nextJmb(),
            'applications.physical_person_jmbg' => $this->nextJmb(),
            'applications.applicant_jmbg' => $this->nextJmb(),
            'business_plans' => $this->nextJmb(),
        ];

        $userId = $this->insertUser($values['users']);
        $flId = $this->insertPhysicalPerson($values['physical_person_identities']);
        $leId = $this->insertAuthorizedPerson($values['legal_entity_authorized_persons']);
        $fbId = $this->insertRepresentative($values['foreign_branch_representatives']);
        $app = $this->insertApplication(
            $values['applications.physical_person_jmbg'],
            $values['applications.applicant_jmbg']
        );
        $planId = $this->insertBusinessPlan($app['id'], $values['business_plans']);

        $before = [
            'users' => $this->raw('users', $userId),
            'physical_person_identities' => $this->raw('physical_person_identities', $flId),
            'legal_entity_authorized_persons' => $this->raw('legal_entity_authorized_persons', $leId),
            'foreign_branch_representatives' => $this->raw('foreign_branch_representatives', $fbId),
            'applications' => $this->raw('applications', $app['id']),
            'business_plans' => $this->raw('business_plans', $planId),
        ];

        $result = $this->runCommand();

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('mode=apply', $result['output']);
        foreach ($values as $jmb) {
            $this->assertStringNotContainsString($jmb, $result['output']);
        }

        $encryption = JmbEncryptionService::fromConfig();
        foreach (JmbEncryptedBackfillService::MAPPINGS as $scope => $mapping) {
            $id = match ($scope) {
                'users' => $userId,
                'physical_person_identities' => $flId,
                'legal_entity_authorized_persons' => $leId,
                'foreign_branch_representatives' => $fbId,
                'applications.physical_person_jmbg', 'applications.applicant_jmbg' => $app['id'],
                'business_plans' => $planId,
            };
            $row = $this->raw($mapping['table'], $id);
            $beforeRow = $before[$mapping['table']];
            $this->assertSame($beforeRow->{$mapping['plaintext']}, $row->{$mapping['plaintext']});
            $this->assertSame($values[$scope], $row->{$mapping['plaintext']});
            $this->assertNotNull($row->{$mapping['encrypted']});
            $this->assertSame($values[$scope], $encryption->decrypt($row->{$mapping['encrypted']}));
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{exit: int, output: string}
     */
    private function runCommand(array $options = []): array
    {
        return [
            'exit' => Artisan::call('jmb:backfill-encrypted', $options),
            'output' => Artisan::output(),
        ];
    }

    private function insertUser(?string $jmb): int
    {
        $id = (int) User::factory()->create([
            'email' => 'jmb-b2-'.uniqid('', true).'@example.test',
            'jmb' => $jmb,
        ])->id;
        $this->clearEncrypted('users', $id, 'jmb_encrypted');

        return $id;
    }

    private function insertPhysicalPerson(?string $jmb): int
    {
        $user = User::factory()->create(['email' => 'jmb-b2-fl-'.uniqid('', true).'@example.test']);
        $platform = PlatformIdentity::create([
            'user_id' => $user->id,
            'subject_type' => PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            'mobile_phone' => '+38267000011',
        ]);

        $id = (int) PhysicalPersonIdentity::create([
            'platform_identity_id' => $platform->id,
            'first_name' => 'Ana',
            'last_name' => 'Test',
            'residential_status' => PhysicalPersonIdentity::RESIDENTIAL_RESIDENT,
            'id_document_type' => $jmb === null ? null : PhysicalPersonIdentity::DOCUMENT_JMB,
            'jmb' => $jmb,
            'street_and_number' => 'Njegoševa 1',
            'city' => 'Kotor',
        ])->id;
        $this->clearEncrypted('physical_person_identities', $id, 'jmb_encrypted');

        return $id;
    }

    private function insertAuthorizedPerson(string $jmb): int
    {
        $user = User::factory()->create(['email' => 'jmb-b2-le-'.uniqid('', true).'@example.test']);
        $platform = PlatformIdentity::create([
            'user_id' => $user->id,
            'subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY,
            'mobile_phone' => '+38267000012',
        ]);
        $legal = LegalEntityIdentity::create([
            'platform_identity_id' => $platform->id,
            'legal_form' => LegalEntityIdentity::FORM_DOO,
            'legal_name' => 'B2 Test DOO',
            'pib' => $this->validPib($this->jmbSerial),
            'crps_number' => $this->validCrps(5, $this->jmbSerial),
            'street_and_number' => 'Stari grad 1',
            'city' => 'Kotor',
        ]);

        $id = (int) LegalEntityAuthorizedPerson::create([
            'legal_entity_identity_id' => $legal->id,
            'first_name' => 'Mila',
            'last_name' => 'Test',
            'id_document_type' => LegalEntityAuthorizedPerson::DOCUMENT_JMB,
            'jmb' => $jmb,
        ])->id;
        $this->clearEncrypted('legal_entity_authorized_persons', $id, 'jmb_encrypted');

        return $id;
    }

    private function insertRepresentative(string $jmb): int
    {
        $user = User::factory()->create(['email' => 'jmb-b2-fb-'.uniqid('', true).'@example.test']);
        $platform = PlatformIdentity::create([
            'user_id' => $user->id,
            'subject_type' => PlatformIdentity::SUBJECT_FOREIGN_BRANCH,
            'mobile_phone' => '+38267000013',
        ]);
        $branch = ForeignBranchIdentity::create([
            'platform_identity_id' => $platform->id,
            'foreign_company_name' => 'B2 Foreign Co',
            'branch_name_in_montenegro' => 'B2 Branch',
            'pib' => $this->validPib($this->jmbSerial + 50),
            'crps_number' => $this->validCrps(6, $this->jmbSerial),
            'street_and_number' => 'Obala 2',
            'city' => 'Kotor',
        ]);

        $id = (int) ForeignBranchRepresentative::create([
            'foreign_branch_identity_id' => $branch->id,
            'first_name' => 'Iva',
            'last_name' => 'Test',
            'id_document_type' => ForeignBranchRepresentative::DOCUMENT_JMB,
            'jmb' => $jmb,
        ])->id;
        $this->clearEncrypted('foreign_branch_representatives', $id, 'jmb_encrypted');

        return $id;
    }

    /**
     * @return array{id: int}
     */
    private function insertApplication(string $physicalJmbg, string $applicantJmbg): array
    {
        $user = User::factory()->create(['email' => 'jmb-b2-app-'.uniqid('', true).'@example.test']);
        $competition = Competition::create([
            'title' => 'B2 JMB backfill '.uniqid(),
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
            'user_id' => $user->id,
            'business_plan_name' => 'B2 plan',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'draft',
            'physical_person_jmbg' => $physicalJmbg,
            'applicant_jmbg' => $applicantJmbg,
        ]);

        $id = (int) $application->id;
        DB::table('applications')->where('id', $id)->update([
            'physical_person_jmbg_encrypted' => null,
            'applicant_jmbg_encrypted' => null,
        ]);

        return ['id' => $id];
    }

    private function insertBusinessPlan(int $applicationId, string $jmbg): int
    {
        $id = (int) BusinessPlan::create([
            'application_id' => $applicationId,
            'applicant_jmbg' => $jmbg,
        ])->id;
        $this->clearEncrypted('business_plans', $id, 'applicant_jmbg_encrypted');

        return $id;
    }

    private function clearEncrypted(string $table, int $id, string $column): void
    {
        DB::table($table)->where('id', $id)->update([$column => null]);
    }

    private function raw(string $table, int $id): object
    {
        $row = DB::table($table)->where('id', $id)->first();
        $this->assertNotNull($row);

        return $row;
    }

    private function nextJmb(): string
    {
        $this->jmbSerial++;

        return $this->validJmb($this->jmbSerial);
    }
}
