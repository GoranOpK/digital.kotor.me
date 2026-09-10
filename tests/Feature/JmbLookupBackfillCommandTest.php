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
use App\Security\JmbLookupService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class JmbLookupBackfillCommandTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    private const SYNTHETIC_JMB = '0101990009015';

    private int $jmbSerial = 20;

    private string $lookupRawKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->lookupRawKey = random_bytes(32);
        config([
            'jmb.lookup.key' => 'base64:'.base64_encode($this->lookupRawKey),
            'jmb.lookup.key_id' => 'v1',
            'jmb.encryption.key' => 'base64:'.base64_encode(random_bytes(32)),
            'jmb.encryption.key_id' => 'v1',
            'jmb.encryption.previous_keys' => '',
        ]);
        $this->app->forgetInstance(JmbLookupService::class);
    }

    public function test_dry_run_users_writes_nothing(): void
    {
        $id = $this->insertUser(self::SYNTHETIC_JMB);

        $result = $this->runCommand(['--scope' => 'users', '--dry-run' => true]);

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('mode=dry-run', $result['output']);
        $this->assertStringContainsString('scope=users', $result['output']);
        $this->assertStringContainsString('would_backfill=1', $result['output']);
        $this->assertNull($this->raw('users', $id)->jmb_lookup);
        $this->assertUnchangedIdentity($id, self::SYNTHETIC_JMB, $result['output']);
    }

    public function test_dry_run_physical_identities_writes_nothing(): void
    {
        $id = $this->insertPhysicalPerson(self::SYNTHETIC_JMB);

        $result = $this->runCommand(['--scope' => 'physical-identities', '--dry-run' => true]);

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('scope=physical-identities', $result['output']);
        $this->assertStringContainsString('would_backfill=1', $result['output']);
        $this->assertNull($this->raw('physical_person_identities', $id)->jmb_lookup);
        $this->assertSame(self::SYNTHETIC_JMB, $this->raw('physical_person_identities', $id)->jmb);
        $this->assertOutputSanitized($result['output'], [self::SYNTHETIC_JMB]);
    }

    public function test_dry_run_all_covers_both_scopes_without_writes(): void
    {
        $userId = $this->insertUser($this->nextJmb());
        $flId = $this->insertPhysicalPerson($this->nextJmb());

        $result = $this->runCommand(['--scope' => 'all', '--dry-run' => true]);

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('scope=users', $result['output']);
        $this->assertStringContainsString('scope=physical-identities', $result['output']);
        $this->assertStringContainsString('would_backfill=', $result['output']);
        $this->assertNull($this->raw('users', $userId)->jmb_lookup);
        $this->assertNull($this->raw('physical_person_identities', $flId)->jmb_lookup);
    }

    public function test_write_backfill_users(): void
    {
        $id = $this->insertUser(self::SYNTHETIC_JMB);
        $before = $this->raw('users', $id);

        $result = $this->runCommand(['--scope' => 'users']);

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('mode=apply', $result['output']);
        $this->assertStringContainsString('backfilled=1', $result['output']);
        $after = $this->raw('users', $id);
        $this->assertSame($before->jmb, $after->jmb);
        $this->assertSame($before->jmb_encrypted, $after->jmb_encrypted);
        $this->assertSame($this->digest(self::SYNTHETIC_JMB), $after->jmb_lookup);
        $this->assertUnchangedIdentity($id, self::SYNTHETIC_JMB, $result['output']);
    }

    public function test_write_backfill_physical_identities(): void
    {
        $id = $this->insertPhysicalPerson(self::SYNTHETIC_JMB);
        $before = $this->raw('physical_person_identities', $id);

        $result = $this->runCommand(['--scope' => 'physical-identities']);

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('backfilled=1', $result['output']);
        $after = $this->raw('physical_person_identities', $id);
        $this->assertSame($before->jmb, $after->jmb);
        $this->assertSame($before->jmb_encrypted, $after->jmb_encrypted);
        $this->assertSame($this->digest(self::SYNTHETIC_JMB), $after->jmb_lookup);
        $this->assertOutputSanitized($result['output'], [self::SYNTHETIC_JMB, (string) $after->jmb_lookup]);
    }

    public function test_second_run_is_idempotent_already_valid(): void
    {
        $id = $this->insertUser(self::SYNTHETIC_JMB);
        $this->assertSame(0, $this->runCommand(['--scope' => 'users'])['exit']);
        $first = $this->raw('users', $id)->jmb_lookup;

        $second = $this->runCommand(['--scope' => 'users']);
        $this->assertSame(0, $second['exit']);
        $this->assertStringContainsString('already_valid=1', $second['output']);
        $this->assertStringContainsString('backfilled=0', $second['output']);
        $this->assertSame($first, $this->raw('users', $id)->jmb_lookup);
        $this->assertSame(self::SYNTHETIC_JMB, $this->raw('users', $id)->jmb);
    }

    public function test_matching_digest_is_already_valid_without_rewrite(): void
    {
        $id = $this->insertUser(self::SYNTHETIC_JMB);
        $digest = $this->digest(self::SYNTHETIC_JMB);
        DB::table('users')->where('id', $id)->update(['jmb_lookup' => $digest]);

        $result = $this->runCommand(['--scope' => 'users']);

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('already_valid=1', $result['output']);
        $this->assertSame($digest, $this->raw('users', $id)->jmb_lookup);
        $this->assertOutputSanitized($result['output'], [self::SYNTHETIC_JMB, $digest]);
    }

    public function test_mismatching_digest_fails_and_does_not_overwrite(): void
    {
        $id = $this->insertUser(self::SYNTHETIC_JMB);
        $other = $this->digest($this->nextJmb());
        DB::table('users')->where('id', $id)->update(['jmb_lookup' => $other]);

        $result = $this->runCommand(['--scope' => 'users']);

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('reason=lookup_digest_mismatch', $result['output']);
        $this->assertStringContainsString('id='.$id, $result['output']);
        $this->assertSame($other, $this->raw('users', $id)->jmb_lookup);
        $this->assertSame(self::SYNTHETIC_JMB, $this->raw('users', $id)->jmb);
        $this->assertOutputSanitized($result['output'], [self::SYNTHETIC_JMB, $other]);
    }

    public function test_null_plaintext_and_null_lookup_is_empty(): void
    {
        $id = $this->insertUser(null);

        $result = $this->runCommand(['--scope' => 'users']);

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('empty=1', $result['output']);
        $this->assertNull($this->raw('users', $id)->jmb);
        $this->assertNull($this->raw('users', $id)->jmb_lookup);
    }

    public function test_null_plaintext_with_existing_lookup_fails(): void
    {
        $id = $this->insertUser(null);
        $orphan = str_repeat('ab', 32);
        DB::table('users')->where('id', $id)->update(['jmb_lookup' => $orphan]);

        $result = $this->runCommand(['--scope' => 'users']);

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('reason=empty_plaintext_with_lookup', $result['output']);
        $this->assertSame($orphan, $this->raw('users', $id)->jmb_lookup);
        $this->assertNull($this->raw('users', $id)->jmb);
        $this->assertStringNotContainsString($orphan, $result['output']);
    }

    public function test_invalid_plaintext_fails_without_write(): void
    {
        $id = $this->insertUser(null);
        DB::table('users')->where('id', $id)->update(['jmb' => '123']);

        $result = $this->runCommand(['--scope' => 'users']);

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('reason=invalid_plaintext', $result['output']);
        $this->assertNull($this->raw('users', $id)->jmb_lookup);
        $this->assertSame('123', $this->raw('users', $id)->jmb);
        $this->assertStringNotContainsString('123', $result['output']);
    }

    public function test_users_digest_collision_fails_without_overwrite(): void
    {
        $jmb = self::SYNTHETIC_JMB;
        $firstId = $this->insertUser($jmb);
        $secondId = $this->insertUser($this->nextJmb());
        DB::table('users')->where('id', $secondId)->update([
            'jmb_lookup' => $this->digest($jmb),
        ]);

        $result = $this->runCommand(['--scope' => 'users']);

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('reason=lookup_digest_collision', $result['output']);
        $this->assertStringContainsString('id='.$firstId, $result['output']);
        $this->assertNull($this->raw('users', $firstId)->jmb_lookup);
        $this->assertSame($this->digest($jmb), $this->raw('users', $secondId)->jmb_lookup);
        $this->assertOutputSanitized($result['output'], [$jmb, $this->digest($jmb)]);
    }

    public function test_physical_identity_duplicate_digest_fails(): void
    {
        $jmb = self::SYNTHETIC_JMB;
        $firstId = $this->insertPhysicalPerson($jmb);
        $secondId = $this->insertPhysicalPerson($jmb);

        $result = $this->runCommand(['--scope' => 'physical-identities']);

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('reason=lookup_digest_collision', $result['output']);
        $this->assertStringContainsString('id='.$secondId, $result['output']);
        $this->assertSame($this->digest($jmb), $this->raw('physical_person_identities', $firstId)->jmb_lookup);
        $this->assertNull($this->raw('physical_person_identities', $secondId)->jmb_lookup);
        $this->assertOutputSanitized($result['output'], [$jmb, $this->digest($jmb)]);
    }

    public function test_missing_key_fails_before_writes(): void
    {
        $id = $this->insertUser(self::SYNTHETIC_JMB);
        config(['jmb.lookup.key' => '']);
        $this->app->forgetInstance(JmbLookupService::class);

        $result = $this->runCommand(['--scope' => 'users']);

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('JMB lookup key is missing', $result['output']);
        $this->assertNull($this->raw('users', $id)->jmb_lookup);
        $this->assertSame(self::SYNTHETIC_JMB, $this->raw('users', $id)->jmb);
        $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $result['output']);
    }

    public function test_wrong_key_fails_closed_on_existing_digest(): void
    {
        $id = $this->insertUser(self::SYNTHETIC_JMB);
        $this->assertSame(0, $this->runCommand(['--scope' => 'users'])['exit']);
        $original = $this->raw('users', $id)->jmb_lookup;

        config(['jmb.lookup.key' => 'base64:'.base64_encode(random_bytes(32))]);
        $this->app->forgetInstance(JmbLookupService::class);

        $result = $this->runCommand(['--scope' => 'users']);

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('reason=lookup_digest_mismatch', $result['output']);
        $this->assertSame($original, $this->raw('users', $id)->jmb_lookup);
        $this->assertSame(self::SYNTHETIC_JMB, $this->raw('users', $id)->jmb);
        $this->assertOutputSanitized($result['output'], [self::SYNTHETIC_JMB, (string) $original]);
    }

    public function test_chunk_option_processes_all_rows(): void
    {
        $ids = [
            $this->insertUser($this->nextJmb()),
            $this->insertUser($this->nextJmb()),
            $this->insertUser($this->nextJmb()),
        ];

        $result = $this->runCommand(['--scope' => 'users', '--chunk' => '1']);

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('backfilled=3', $result['output']);
        foreach ($ids as $id) {
            $row = $this->raw('users', $id);
            $this->assertSame($this->digest((string) $row->jmb), $row->jmb_lookup);
        }
    }

    public function test_invalid_scope_and_chunk_are_rejected(): void
    {
        $id = $this->insertUser(self::SYNTHETIC_JMB);

        $unknown = $this->runCommand(['--scope' => 'applications']);
        $this->assertSame(1, $unknown['exit']);
        $this->assertStringContainsString('Unknown JMB lookup backfill scope', $unknown['output']);
        $this->assertNull($this->raw('users', $id)->jmb_lookup);

        $chunk = $this->runCommand(['--scope' => 'users', '--chunk' => '0']);
        $this->assertSame(1, $chunk['exit']);
        $this->assertStringContainsString('Invalid JMB lookup backfill chunk', $chunk['output']);
        $this->assertNull($this->raw('users', $id)->jmb_lookup);

        $nonInt = $this->runCommand(['--scope' => 'users', '--chunk' => 'abc']);
        $this->assertSame(1, $nonInt['exit']);
        $this->assertNull($this->raw('users', $id)->jmb_lookup);
    }

    public function test_does_not_touch_snapshot_or_other_identity_tables(): void
    {
        $userId = $this->insertUser($this->nextJmb());
        $flId = $this->insertPhysicalPerson($this->nextJmb());
        $authId = $this->insertAuthorizedPerson($this->nextJmb());
        $repId = $this->insertRepresentative($this->nextJmb());
        $app = $this->insertApplication($this->nextJmb(), $this->nextJmb());
        $planId = $this->insertBusinessPlan($app['id'], $this->nextJmb());

        $before = [
            'authorized' => $this->raw('legal_entity_authorized_persons', $authId),
            'representative' => $this->raw('foreign_branch_representatives', $repId),
            'application' => $this->raw('applications', $app['id']),
            'plan' => $this->raw('business_plans', $planId),
        ];

        $result = $this->runCommand(['--scope' => 'all']);

        $this->assertSame(0, $result['exit']);
        $this->assertNotNull($this->raw('users', $userId)->jmb_lookup);
        $this->assertNotNull($this->raw('physical_person_identities', $flId)->jmb_lookup);

        $afterAuth = $this->raw('legal_entity_authorized_persons', $authId);
        $afterRep = $this->raw('foreign_branch_representatives', $repId);
        $afterApp = $this->raw('applications', $app['id']);
        $afterPlan = $this->raw('business_plans', $planId);

        $this->assertEquals($before['authorized'], $afterAuth);
        $this->assertEquals($before['representative'], $afterRep);
        $this->assertEquals($before['application'], $afterApp);
        $this->assertEquals($before['plan'], $afterPlan);
        $this->assertFalse(isset($afterAuth->jmb_lookup));
        $this->assertFalse(isset($afterApp->physical_person_jmbg_lookup));
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{exit: int, output: string}
     */
    private function runCommand(array $options = []): array
    {
        return [
            'exit' => Artisan::call('jmb:backfill-lookup', $options),
            'output' => Artisan::output(),
        ];
    }

    private function digest(string $jmb): string
    {
        return (string) JmbLookupService::fromConfig()->digest($jmb);
    }

    private function insertUser(?string $jmb): int
    {
        return (int) User::factory()->create([
            'email' => 'jmb-lookup-'.uniqid('', true).'@example.test',
            'jmb' => $jmb,
        ])->id;
    }

    private function insertPhysicalPerson(?string $jmb): int
    {
        $user = User::factory()->create([
            'email' => 'jmb-lookup-fl-'.uniqid('', true).'@example.test',
            'jmb' => null,
        ]);
        $platform = PlatformIdentity::create([
            'user_id' => $user->id,
            'subject_type' => PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            'mobile_phone' => '+38267000011',
        ]);

        return (int) PhysicalPersonIdentity::create([
            'platform_identity_id' => $platform->id,
            'first_name' => 'Ana',
            'last_name' => 'Test',
            'residential_status' => PhysicalPersonIdentity::RESIDENTIAL_RESIDENT,
            'id_document_type' => $jmb === null ? null : PhysicalPersonIdentity::DOCUMENT_JMB,
            'jmb' => $jmb,
            'street_and_number' => 'Njegoševa 1',
            'city' => 'Kotor',
        ])->id;
    }

    private function insertAuthorizedPerson(string $jmb): int
    {
        $user = User::factory()->create(['email' => 'jmb-lookup-le-'.uniqid('', true).'@example.test']);
        $platform = PlatformIdentity::create([
            'user_id' => $user->id,
            'subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY,
            'mobile_phone' => '+38267000012',
        ]);
        $legal = LegalEntityIdentity::create([
            'platform_identity_id' => $platform->id,
            'legal_form' => LegalEntityIdentity::FORM_DOO,
            'legal_name' => 'Lookup Test DOO',
            'pib' => $this->validPib($this->jmbSerial),
            'crps_number' => $this->validCrps(5, $this->jmbSerial),
            'street_and_number' => 'Stari grad 1',
            'city' => 'Kotor',
        ]);

        return (int) LegalEntityAuthorizedPerson::create([
            'legal_entity_identity_id' => $legal->id,
            'first_name' => 'Mila',
            'last_name' => 'Test',
            'id_document_type' => LegalEntityAuthorizedPerson::DOCUMENT_JMB,
            'jmb' => $jmb,
        ])->id;
    }

    private function insertRepresentative(string $jmb): int
    {
        $user = User::factory()->create(['email' => 'jmb-lookup-fb-'.uniqid('', true).'@example.test']);
        $platform = PlatformIdentity::create([
            'user_id' => $user->id,
            'subject_type' => PlatformIdentity::SUBJECT_FOREIGN_BRANCH,
            'mobile_phone' => '+38267000013',
        ]);
        $branch = ForeignBranchIdentity::create([
            'platform_identity_id' => $platform->id,
            'foreign_company_name' => 'Lookup Foreign Co',
            'branch_name_in_montenegro' => 'Lookup Branch',
            'pib' => $this->validPib($this->jmbSerial + 50),
            'crps_number' => $this->validCrps(6, $this->jmbSerial),
            'street_and_number' => 'Obala 2',
            'city' => 'Kotor',
        ]);

        return (int) ForeignBranchRepresentative::create([
            'foreign_branch_identity_id' => $branch->id,
            'first_name' => 'Iva',
            'last_name' => 'Test',
            'id_document_type' => ForeignBranchRepresentative::DOCUMENT_JMB,
            'jmb' => $jmb,
        ])->id;
    }

    /**
     * @return array{id: int}
     */
    private function insertApplication(string $physicalJmbg, string $applicantJmbg): array
    {
        $user = User::factory()->create(['email' => 'jmb-lookup-app-'.uniqid('', true).'@example.test']);
        $competition = Competition::create([
            'title' => 'Lookup JMB backfill '.uniqid(),
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
            'business_plan_name' => 'Lookup plan',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'draft',
            'physical_person_jmbg' => $physicalJmbg,
            'applicant_jmbg' => $applicantJmbg,
        ]);

        return ['id' => (int) $application->id];
    }

    private function insertBusinessPlan(int $applicationId, string $jmbg): int
    {
        return (int) BusinessPlan::create([
            'application_id' => $applicationId,
            'applicant_jmbg' => $jmbg,
        ])->id;
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

    private function assertUnchangedIdentity(int $id, string $jmb, string $output): void
    {
        $row = $this->raw('users', $id);
        $this->assertSame($jmb, $row->jmb);
        $this->assertOutputSanitized($output, [$jmb, (string) $row->jmb_lookup, (string) $row->jmb_encrypted]);
    }

    /**
     * @param  list<string>  $secrets
     */
    private function assertOutputSanitized(string $output, array $secrets): void
    {
        $this->assertStringNotContainsString(base64_encode($this->lookupRawKey), $output);
        $this->assertStringNotContainsString('JMB_LOOKUP_KEY=', $output);
        foreach ($secrets as $secret) {
            if ($secret !== '' && $secret !== '0') {
                $this->assertStringNotContainsString($secret, $output);
            }
        }
    }
}
