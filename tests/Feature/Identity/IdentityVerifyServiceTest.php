<?php

namespace Tests\Feature\Identity;

use App\Identity\Backfill\IdentityBackfillProjector;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\Census\IdentityCensusRow;
use App\Identity\Census\IdentityCensusService;
use App\Identity\IdentitySnapshot;
use App\Identity\Verify\IdentityVerifyException;
use App\Identity\Verify\IdentityVerifyService;
use App\Identity\Verify\IdentityVerifyUserOutcome;
use App\Models\ForeignBranchIdentity;
use App\Models\ForeignBranchRepresentative;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\Role;
use App\Models\User;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class IdentityVerifyServiceTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesIdentitySnapshots;
    use RefreshDatabase;

    private int $jmbSerial = 20;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->assertSame(false, config('identity.canonical_write'));
        $this->assertSame(false, config('identity.canonical_read'));
        $this->bindDedicatedReadOnlyConnection();
    }

    public function test_happy_path_verified_step4_physical_person_graph_equality(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $beforeUser = $this->usersTableFingerprint();
        $beforeCanonical = $this->canonicalFingerprint();

        $report = $this->runVerify();

        $this->assertSame(IdentityVerifyUserOutcome::VERIFIED, $this->statusFor($report, $user));
        $this->assertTrue($report->passed());
        $this->assertSame(1, $report->aggregates['by_status'][IdentityVerifyUserOutcome::VERIFIED]);
        $this->assertSame(1, $report->aggregates['live_backfillable_in_boundary']);
        $this->assertSame(1, $report->aggregates['canonical_table_counts']['platform_identities']);
        $this->assertSame(1, $report->aggregates['canonical_table_counts']['physical_person_identities']);
        $this->assertSame(0, $report->aggregates['canonical_table_counts']['legal_entity_identities']);
        $this->assertSame($beforeUser, $this->usersTableFingerprint());
        $this->assertSame($beforeCanonical, $this->canonicalFingerprint());
    }

    public function test_missing_canonical_for_live_backfillable_user(): void
    {
        $user = $this->backfillableUser();

        $report = $this->runVerify();

        $this->assertSame(IdentityVerifyUserOutcome::MISSING_CANONICAL, $this->statusFor($report, $user));
        $this->assertFalse($report->passed());
        $this->assertSame(1, $report->aggregates['by_status'][IdentityVerifyUserOutcome::MISSING_CANONICAL]);
        $this->assertCanonicalTablesEmpty();
    }

    public function test_canonical_fingerprint_mismatch(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $user->update(['first_name' => 'Changed']);

        $report = $this->runVerify();

        $this->assertSame(IdentityVerifyUserOutcome::CANONICAL_MISMATCH, $this->statusFor($report, $user));
        $this->assertContains('canonical_differs_from_projection', $this->outcomeFor($report, $user)->reasonCodes);
        $this->assertFalse($report->passed());
        $this->assertSame(1, PlatformIdentity::query()->count());
    }

    public function test_no_longer_backfillable_with_graph_is_source_drift(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $user->update(['city' => null]);

        $report = $this->runVerify();

        $this->assertSame(IdentityVerifyUserOutcome::SOURCE_DRIFT, $this->statusFor($report, $user));
        $this->assertSame(IdentityCensusRow::MISSING_REQUIRED, $this->outcomeFor($report, $user)->liveRowStatus);
        $this->assertFalse($report->passed());
        $this->assertSame(1, PlatformIdentity::query()->count());
    }

    public function test_non_eligible_without_graph_is_skipped_not_eligible(): void
    {
        $user = $this->backfillableUser(['city' => null, 'email' => 'skip-not-eligible@example.test']);

        $report = $this->runVerify();

        $this->assertSame(IdentityVerifyUserOutcome::SKIPPED_NOT_ELIGIBLE, $this->statusFor($report, $user));
        $this->assertTrue($report->passed());
        $this->assertCanonicalTablesEmpty();
    }

    public function test_staff_without_graph_is_skipped_not_eligible(): void
    {
        $this->makeKorisnik([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'jmb' => '0000000000000',
            'email' => 'staff-verify@example.test',
        ]);

        $report = $this->runVerify();

        $this->assertSame(IdentityVerifyUserOutcome::SKIPPED_NOT_ELIGIBLE, $report->outcomes[0]->status);
        $this->assertSame(IdentityCensusRow::NON_SUBJECT_ACCOUNT, $report->outcomes[0]->liveRowStatus);
        $this->assertTrue($report->passed());
    }

    public function test_outside_boundary_without_graph_is_skipped(): void
    {
        $inside = $this->backfillableUser();
        $this->seedProjectedGraph($inside);
        $outside = $this->backfillableUser();

        $report = $this->runVerify(['census_max_user_id' => (int) $inside->id]);

        $this->assertSame(IdentityVerifyUserOutcome::VERIFIED, $this->statusFor($report, $inside));
        $this->assertSame(IdentityVerifyUserOutcome::SKIPPED_OUTSIDE_CENSUS_BOUNDARY, $this->statusFor($report, $outside));
        $this->assertTrue($report->passed());
        $this->assertSame(0, PlatformIdentity::query()->where('user_id', $outside->id)->count());
    }

    public function test_outside_boundary_with_graph_is_unexpected_canonical(): void
    {
        $inside = $this->backfillableUser();
        $outside = $this->backfillableUser();
        $this->seedProjectedGraph($outside);

        $report = $this->runVerify(['census_max_user_id' => (int) $inside->id]);

        $this->assertSame(IdentityVerifyUserOutcome::MISSING_CANONICAL, $this->statusFor($report, $inside));
        $this->assertSame(IdentityVerifyUserOutcome::UNEXPECTED_CANONICAL, $this->statusFor($report, $outside));
        $this->assertContains('outside_census_boundary', $this->outcomeFor($report, $outside)->reasonCodes);
        $this->assertFalse($report->passed());
    }

    public function test_wrong_subject_type_is_canonical_graph_invalid(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        PlatformIdentity::query()->where('user_id', $user->id)->update([
            'subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY,
        ]);

        $report = $this->runVerify();

        $this->assertSame(IdentityVerifyUserOutcome::CANONICAL_GRAPH_INVALID, $this->statusFor($report, $user));
        $this->assertContains('subject_type_mismatch', $this->outcomeFor($report, $user)->reasonCodes);
        $this->assertFalse($report->passed());
    }

    public function test_missing_physical_person_child_is_canonical_graph_invalid(): void
    {
        $user = $this->backfillableUser();
        PlatformIdentity::query()->create([
            'user_id' => $user->id,
            'subject_type' => PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            'mobile_phone' => $user->phone,
        ]);

        $report = $this->runVerify();

        $this->assertSame(IdentityVerifyUserOutcome::CANONICAL_GRAPH_INVALID, $this->statusFor($report, $user));
        $this->assertContains('missing_subtype', $this->outcomeFor($report, $user)->reasonCodes);
        $this->assertFalse($report->passed());
    }

    public function test_extra_branch_is_canonical_graph_invalid(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $platform = PlatformIdentity::query()->where('user_id', $user->id)->firstOrFail();
        LegalEntityIdentity::query()->create([
            'platform_identity_id' => $platform->id,
            'legal_form' => LegalEntityIdentity::FORM_DOO,
            'legal_name' => 'Extra DOO',
            'street_and_number' => 'Slobode 1',
            'city' => 'Kotor',
        ]);

        $report = $this->runVerify();

        $this->assertSame(IdentityVerifyUserOutcome::CANONICAL_GRAPH_INVALID, $this->statusFor($report, $user));
        $this->assertContains('multiple_branches', $this->outcomeFor($report, $user)->reasonCodes);
        $this->assertFalse($report->passed());
    }

    public function test_orphan_subtype_fails_global_scan_without_double_counting_user_status(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);

        Schema::disableForeignKeyConstraints();
        DB::table('physical_person_identities')->insert([
            'platform_identity_id' => 999999,
            'first_name' => 'Orphan',
            'last_name' => 'Row',
            'residential_status' => PhysicalPersonIdentity::RESIDENTIAL_RESIDENT,
            'street_and_number' => 'Hidden 1',
            'city' => 'Kotor',
            'is_entrepreneur' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Schema::enableForeignKeyConstraints();

        $report = $this->runVerify();

        $this->assertSame(IdentityVerifyUserOutcome::VERIFIED, $this->statusFor($report, $user));
        $this->assertSame(0, $report->aggregates['by_status'][IdentityVerifyUserOutcome::CANONICAL_GRAPH_INVALID]);
        $this->assertSame(0, $report->aggregates['by_status'][IdentityVerifyUserOutcome::UNEXPECTED_CANONICAL]);
        $this->assertGreaterThan(0, $report->aggregates['global_unassigned_failures']);
        $this->assertSame('orphan_physical_person_identities', $report->aggregates['global_findings'][0]['reason_code']);
        $this->assertFalse($report->passed());
        $this->assertSame(2, PhysicalPersonIdentity::query()->count());
    }

    public function test_unexpected_legal_entity_graph(): void
    {
        $user = $this->backfillableUser([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'company_name' => 'Primjer DOO',
            'pib' => '12345672',
            'jmb' => null,
            'residential_status' => null,
            'email' => 'doo-verify@example.test',
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->plSnapshot($user));

        $report = $this->runVerify();

        $this->assertSame(IdentityVerifyUserOutcome::UNEXPECTED_CANONICAL, $this->statusFor($report, $user));
        $this->assertContains('unexpected_legal_entity_graph', $this->outcomeFor($report, $user)->reasonCodes);
        $this->assertFalse($report->passed());
    }

    public function test_unexpected_foreign_branch_graph(): void
    {
        $user = $this->backfillableUser([
            'user_type' => UserType::LEGACY_FOREIGN_BRANCH,
            'pib' => '00000007',
            'jmb' => null,
            'residential_status' => null,
            'email' => 'dspd-verify@example.test',
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->dspdSnapshot($user));

        $report = $this->runVerify();

        $this->assertSame(IdentityVerifyUserOutcome::UNEXPECTED_CANONICAL, $this->statusFor($report, $user));
        $this->assertContains('unexpected_foreign_branch_graph', $this->outcomeFor($report, $user)->reasonCodes);
        $this->assertFalse($report->passed());
    }

    public function test_compare_exception_is_failed_without_leaking_exception_text(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $inner = new IdentityBackfillProjector;
        $projector = new class($inner, (int) $user->id) extends IdentityBackfillProjector
        {
            public function __construct(
                private IdentityBackfillProjector $inner,
                private int $badId,
            ) {
            }

            public function project($user, $row): ?IdentitySnapshot
            {
                if ((int) $user->id === $this->badId) {
                    throw new \RuntimeException('secret-jmb-0202990123456');
                }

                return $this->inner->project($user, $row);
            }
        };

        $report = (new IdentityVerifyService(new IdentityCensusService, $projector))->run([
            'census_max_user_id' => max(1, (int) User::query()->max('id')),
        ]);

        $outcome = $this->outcomeFor($report, $user);
        $this->assertSame(IdentityVerifyUserOutcome::FAILED, $outcome->status);
        $this->assertSame(['verify_failed'], $outcome->reasonCodes);
        $encoded = json_encode($report->aggregateDocument()).json_encode($outcome->toArray());
        $this->assertStringNotContainsString('secret-jmb-0202990123456', $encoded);
        $this->assertFalse($report->passed());
    }

    public function test_canonical_read_switch_on_aborts_before_read(): void
    {
        $this->backfillableUser();
        config(['identity.canonical_read' => true]);

        try {
            $this->runVerify();
            $this->fail('Canonical read switch must abort Step 5.');
        } catch (IdentityVerifyException $e) {
            $this->assertStringContainsString('canonical identity authority', $e->getMessage());
        }
    }

    public function test_canonical_write_switch_on_aborts_before_read(): void
    {
        $this->backfillableUser();
        config(['identity.canonical_write' => true]);

        try {
            $this->runVerify();
            $this->fail('Canonical write switch must abort Step 5.');
        } catch (IdentityVerifyException $e) {
            $this->assertStringContainsString('canonical identity authority', $e->getMessage());
        }
    }

    public function test_census_max_user_id_is_required(): void
    {
        $this->backfillableUser();

        try {
            (new IdentityVerifyService)->run([]);
            $this->fail('Missing census_max_user_id must abort Step 5.');
        } catch (IdentityVerifyException $e) {
            $this->assertStringContainsString('census_max_user_id', $e->getMessage());
        }
    }

    public function test_boundary_is_not_hardcoded(): void
    {
        $service = (string) file_get_contents(app_path('Identity/Verify/IdentityVerifyService.php'));
        $command = (string) file_get_contents(app_path('Console/Commands/IdentityProductionVerifyCommand.php'));
        $this->assertStringNotContainsString('67', $service);
        $this->assertStringNotContainsString('67', $command);

        $first = $this->backfillableUser();
        $this->seedProjectedGraph($first);
        $second = $this->backfillableUser();
        $this->seedProjectedGraph($second);

        $report = $this->runVerify(['census_max_user_id' => (int) $first->id]);
        $this->assertSame(IdentityVerifyUserOutcome::VERIFIED, $this->statusFor($report, $first));
        $this->assertSame(IdentityVerifyUserOutcome::UNEXPECTED_CANONICAL, $this->statusFor($report, $second));
        $this->assertSame((int) $first->id, $report->metadata['census_max_user_id']);
    }

    public function test_pii_is_absent_from_report_documents(): void
    {
        $user = $this->backfillableUser([
            'email' => 'pii-verify@example.test',
            'first_name' => 'SecretName',
            'last_name' => 'SecretLast',
            'phone' => '+38267999999',
            'address' => 'Secret Street 9',
            'passport_number' => 'SECRETPASS1',
            'pib' => '12345672',
        ]);
        $this->seedProjectedGraph($user);

        $report = $this->runVerify();
        $encoded = json_encode($report->aggregateDocument(), JSON_THROW_ON_ERROR)
            .json_encode($report->outcomes[0]->toArray(), JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('pii-verify@example.test', $encoded);
        $this->assertStringNotContainsString((string) $user->jmb, $encoded);
        $this->assertStringNotContainsString('SECRETPASS1', $encoded);
        $this->assertStringNotContainsString('12345672', $encoded);
        $this->assertStringNotContainsString('SecretName', $encoded);
        $this->assertStringNotContainsString('SecretLast', $encoded);
        $this->assertStringNotContainsString('+38267999999', $encoded);
        $this->assertStringNotContainsString('Secret Street 9', $encoded);
        $this->assertStringNotContainsString($user->password, $encoded);
        $this->assertSame($user->id, $report->outcomes[0]->userId);
    }

    public function test_incomplete_readonly_connection_is_rejected(): void
    {
        config([
            'database.connections.'.IdentityCensusService::READONLY_CONNECTION => [
                'driver' => 'mysql',
                'host' => null,
                'database' => null,
                'username' => null,
            ],
        ]);
        DB::purge(IdentityCensusService::READONLY_CONNECTION);

        try {
            (new IdentityVerifyService)->run(['census_max_user_id' => 1]);
            $this->fail('Incomplete read-only connection must abort Step 5.');
        } catch (IdentityVerifyException $e) {
            $this->assertStringContainsString('incomplete', $e->getMessage());
        }
    }

    public function test_readonly_username_equal_to_app_username_is_rejected(): void
    {
        $mysql = config('database.connections.mysql');
        config(['database.connections.'.IdentityCensusService::READONLY_CONNECTION => $mysql]);
        DB::purge(IdentityCensusService::READONLY_CONNECTION);

        try {
            (new IdentityVerifyService)->run(['census_max_user_id' => 1]);
            $this->fail('Matching application username must abort Step 5.');
        } catch (IdentityVerifyException $e) {
            $this->assertStringContainsString('username', $e->getMessage());
        }
    }

    public function test_default_connection_name_equal_to_readonly_is_rejected(): void
    {
        $original = (string) config('database.default');
        config(['database.default' => IdentityCensusService::READONLY_CONNECTION]);

        try {
            (new IdentityVerifyService)->run(['census_max_user_id' => 1]);
            $this->fail('Default connection must not be the census read-only connection.');
        } catch (IdentityVerifyException $e) {
            $this->assertStringContainsString('default application database connection', $e->getMessage());
        } finally {
            config(['database.default' => $original]);
        }
    }

    public function test_unreadable_readonly_database_fails_closed(): void
    {
        $config = config('database.connections.mysql');
        $config['username'] = 'identity_census_ro';
        $config['database'] = 'this_database_does_not_exist_verify_step5';
        config(['database.connections.'.IdentityCensusService::READONLY_CONNECTION => $config]);
        DB::purge(IdentityCensusService::READONLY_CONNECTION);

        try {
            (new IdentityVerifyService)->run(['census_max_user_id' => 1]);
            $this->fail('Unreadable dedicated connection must abort Step 5.');
        } catch (IdentityVerifyException $e) {
            $this->assertStringContainsString('dedicated read-only connection', $e->getMessage());
        }
    }

    public function test_all_verify_reads_use_dedicated_connection_and_do_not_write(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $beforeUser = $this->usersTableFingerprint();
        $beforeCanonical = $this->canonicalFingerprint();
        $censusMaxUserId = (int) $user->id;

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query;
        });

        (new IdentityVerifyService)->run(['census_max_user_id' => $censusMaxUserId]);

        $this->assertNotEmpty($queries);
        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression('/\\b(insert|update|delete|replace|truncate)\\b/i', $query->sql);
        }

        $watched = [
            'users',
            'roles',
            'platform_identities',
            'physical_person_identities',
            'legal_entity_identities',
            'legal_entity_authorized_persons',
            'foreign_branch_identities',
            'foreign_branch_representatives',
        ];
        foreach ($watched as $table) {
            $saw = false;
            foreach ($queries as $query) {
                if ((bool) preg_match('/\\b'.$table.'\\b/i', $query->sql)) {
                    $saw = true;
                    $this->assertSame(IdentityCensusService::READONLY_CONNECTION, $query->connectionName);
                }
            }
            $this->assertTrue($saw, 'Expected a read of '.$table.' on the dedicated connection.');
        }

        $this->assertSame($beforeUser, $this->usersTableFingerprint());
        $this->assertSame($beforeCanonical, $this->canonicalFingerprint());
    }

    public function test_repeat_verify_produces_the_same_semantic_result(): void
    {
        $verified = $this->backfillableUser();
        $this->seedProjectedGraph($verified);
        $skipped = $this->backfillableUser(['city' => null, 'email' => 'repeat-skip@example.test']);

        $first = $this->runVerify();
        $second = $this->runVerify();

        $this->assertSame($first->aggregates['by_status'], $second->aggregates['by_status']);
        $this->assertSame($first->aggregates['canonical_table_counts'], $second->aggregates['canonical_table_counts']);
        $this->assertSame($this->statusFor($first, $verified), $this->statusFor($second, $verified));
        $this->assertSame($this->statusFor($first, $skipped), $this->statusFor($second, $skipped));
        $this->assertTrue($first->passed());
        $this->assertTrue($second->passed());
    }

    public function test_missing_required_tables_helper_and_source_does_not_write_or_enable_switches(): void
    {
        $this->assertSame([
            'platform_identities',
            'physical_person_identities',
            'legal_entity_identities',
            'legal_entity_authorized_persons',
            'foreign_branch_identities',
            'foreign_branch_representatives',
        ], IdentityVerifyService::REQUIRED_TABLES);
        $this->assertSame(IdentityVerifyService::REQUIRED_TABLES, IdentityVerifyService::missingRequiredTables(static fn (): bool => false));
        $this->assertSame([], IdentityVerifyService::missingRequiredTables(static fn (): bool => true));

        $source = (string) file_get_contents(app_path('Identity/Verify/IdentityVerifyService.php'))
            .(string) file_get_contents(app_path('Console/Commands/IdentityProductionVerifyCommand.php'));
        $this->assertStringNotContainsString('CanonicalIdentityWriter', $source);
        $this->assertStringNotContainsString('IDENTITY_CANONICAL_READ', $source);
        $this->assertStringNotContainsString('canonical_read\' => true', $source);
        $this->assertStringNotContainsString('--apply', $source);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function runVerify(array $metadata = []): \App\Identity\Verify\IdentityVerifyReport
    {
        if (! array_key_exists('census_max_user_id', $metadata)) {
            $metadata['census_max_user_id'] = max(1, (int) User::query()->max('id'));
        }

        return (new IdentityVerifyService)->run($metadata);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function backfillableUser(array $overrides = []): User
    {
        if (! array_key_exists('jmb', $overrides)) {
            $overrides['jmb'] = $this->validJmb($this->jmbSerial++);
        }

        return $this->makeKorisnik(array_merge([
            'email' => 'step5-'.uniqid('', true).'@example.test',
        ], $overrides));
    }

    private function seedProjectedGraph(User $user): void
    {
        $user->load('role');
        $classified = (new IdentityCensusService)->classify($user);
        $snapshot = (new IdentityBackfillProjector)->project($user, $classified);
        $this->assertNotNull($snapshot);
        (new CanonicalIdentityWriter)->createForUser($user, $snapshot);
    }

    private function statusFor(\App\Identity\Verify\IdentityVerifyReport $report, User $user): string
    {
        return $this->outcomeFor($report, $user)->status;
    }

    private function outcomeFor(\App\Identity\Verify\IdentityVerifyReport $report, User $user): IdentityVerifyUserOutcome
    {
        foreach ($report->outcomes as $outcome) {
            if ($outcome->userId === (int) $user->id) {
                return $outcome;
            }
        }

        $this->fail('No verify outcome for user '.$user->id);
    }

    private function bindDedicatedReadOnlyConnection(): void
    {
        $mysql = config('database.connections.mysql');
        $this->assertNotSame('', trim((string) ($mysql['username'] ?? '')));
        $readonly = $mysql;
        $readonly['username'] = 'identity_census_ro';
        $this->assertNotSame($mysql['username'], $readonly['username']);
        config(['database.connections.'.IdentityCensusService::READONLY_CONNECTION => $readonly]);
        DB::purge(IdentityCensusService::READONLY_CONNECTION);
        $mysqlConnection = DB::connection('mysql');
        $readonlyConnection = DB::connection(IdentityCensusService::READONLY_CONNECTION);
        $readonlyConnection->setPdo($mysqlConnection->getPdo());
        $readonlyConnection->setReadPdo($mysqlConnection->getReadPdo());
    }

    private function usersTableFingerprint(): string
    {
        $rows = User::query()
            ->orderBy('id')
            ->get([
                'id',
                'user_type',
                'residential_status',
                'first_name',
                'last_name',
                'company_name',
                'jmb',
                'pib',
                'passport_number',
                'phone',
                'address',
                'city',
                'email',
                'password',
                'role_id',
                'activation_status',
                'updated_at',
            ])
            ->toArray();

        return hash('sha256', json_encode($rows));
    }

    private function canonicalFingerprint(): string
    {
        $payload = [
            PlatformIdentity::query()->orderBy('id')->get()->toArray(),
            PhysicalPersonIdentity::query()->orderBy('id')->get()->toArray(),
            LegalEntityIdentity::query()->orderBy('id')->get()->toArray(),
            LegalEntityAuthorizedPerson::query()->orderBy('id')->get()->toArray(),
            ForeignBranchIdentity::query()->orderBy('id')->get()->toArray(),
            ForeignBranchRepresentative::query()->orderBy('id')->get()->toArray(),
        ];

        return hash('sha256', json_encode($payload));
    }

    private function assertCanonicalTablesEmpty(): void
    {
        $this->assertSame(0, PlatformIdentity::query()->count());
        $this->assertSame(0, PhysicalPersonIdentity::query()->count());
        $this->assertSame(0, LegalEntityIdentity::query()->count());
        $this->assertSame(0, LegalEntityAuthorizedPerson::query()->count());
        $this->assertSame(0, ForeignBranchIdentity::query()->count());
        $this->assertSame(0, ForeignBranchRepresentative::query()->count());
    }
}
