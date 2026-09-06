<?php

namespace Tests\Feature\Identity;

use App\Console\Commands\IdentityProductionVerifyCommand;
use App\Identity\Backfill\IdentityBackfillProjector;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\Census\IdentityCensusService;
use App\Identity\Verify\IdentityVerifyProtectedOutputPath;
use App\Identity\Verify\IdentityVerifyUserOutcome;
use App\Models\ForeignBranchIdentity;
use App\Models\ForeignBranchRepresentative;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class IdentityProductionVerifySafetyTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    private int $jmbSerial = 40;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->assertSame(false, config('identity.canonical_write'));
        $this->assertSame(false, config('identity.canonical_read'));
        $this->assertNotSame(
            IdentityCensusService::READONLY_CONNECTION,
            (string) config('database.default')
        );
    }

    public function test_production_command_refuses_non_production_environment(): void
    {
        $this->bindDedicatedReadOnlyConnection();
        [$aggregate, $rows] = $this->protectedPaths();

        $queries = $this->captureQueries(function () use ($aggregate, $rows): void {
            $this->artisan('identity:verify-production', [
                '--census-max-user-id' => '1',
                '--aggregate' => $aggregate,
                '--rows' => $rows,
            ])->expectsOutputToContain('identity:verify-production requires the production environment.')
                ->assertFailed();
        });

        $this->assertNoIdentityTableQueries($queries);
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_command_has_no_apply_force_or_user_bypass_flags(): void
    {
        $options = array_keys((new IdentityProductionVerifyCommand)->getDefinition()->getOptions());

        $this->assertNotContains('apply', $options);
        $this->assertNotContains('dry-run', $options);
        $this->assertNotContains('confirm', $options);
        $this->assertNotContains('force', $options);
        $this->assertNotContains('user', $options);
        $this->assertNotContains('write', $options);
    }

    public function test_missing_dedicated_connection_config_fails_before_query(): void
    {
        $this->app['env'] = 'production';
        [$aggregate, $rows] = $this->protectedPaths();

        $queries = $this->captureQueries(function () use ($aggregate, $rows): void {
            $this->artisan('identity:verify-production', [
                '--census-max-user-id' => '1',
                '--aggregate' => $aggregate,
                '--rows' => $rows,
            ])->expectsOutputToContain('Dedicated census read-only connection is incomplete.')
                ->assertFailed();
        });

        $this->assertNoIdentityTableQueries($queries);
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_readonly_username_matching_app_username_fails_before_query(): void
    {
        $this->app['env'] = 'production';
        config([
            'database.connections.'.IdentityCensusService::READONLY_CONNECTION => config('database.connections.mysql'),
        ]);
        DB::purge(IdentityCensusService::READONLY_CONNECTION);
        [$aggregate, $rows] = $this->protectedPaths();

        $queries = $this->captureQueries(function () use ($aggregate, $rows): void {
            $this->artisan('identity:verify-production', [
                '--census-max-user-id' => '1',
                '--aggregate' => $aggregate,
                '--rows' => $rows,
            ])->expectsOutputToContain('username')
                ->assertFailed();
        });

        $this->assertNoIdentityTableQueries($queries);
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_canonical_write_on_fails_before_query(): void
    {
        $this->app['env'] = 'production';
        $this->bindDedicatedReadOnlyConnection();
        config(['identity.canonical_write' => true]);
        [$aggregate, $rows] = $this->protectedPaths();

        $queries = $this->captureQueries(function () use ($aggregate, $rows): void {
            $this->artisan('identity:verify-production', [
                '--census-max-user-id' => '1',
                '--aggregate' => $aggregate,
                '--rows' => $rows,
            ])->assertFailed();
        });

        $this->assertNoIdentityTableQueries($queries);
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_canonical_read_on_fails_before_query(): void
    {
        $this->app['env'] = 'production';
        $this->bindDedicatedReadOnlyConnection();
        config(['identity.canonical_read' => true]);
        [$aggregate, $rows] = $this->protectedPaths();

        $queries = $this->captureQueries(function () use ($aggregate, $rows): void {
            $this->artisan('identity:verify-production', [
                '--census-max-user-id' => '1',
                '--aggregate' => $aggregate,
                '--rows' => $rows,
            ])->assertFailed();
        });

        $this->assertNoIdentityTableQueries($queries);
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_missing_census_max_user_id_fails_before_query(): void
    {
        $this->app['env'] = 'production';
        $this->bindDedicatedReadOnlyConnection();
        [$aggregate, $rows] = $this->protectedPaths();

        $queries = $this->captureQueries(function () use ($aggregate, $rows): void {
            $this->artisan('identity:verify-production', [
                '--aggregate' => $aggregate,
                '--rows' => $rows,
            ])->expectsOutputToContain('census-max-user-id')
                ->assertFailed();
        });

        $this->assertNoIdentityTableQueries($queries);
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_invalid_census_max_user_id_fails_before_query(): void
    {
        $this->app['env'] = 'production';
        $this->bindDedicatedReadOnlyConnection();
        [$aggregate, $rows] = $this->protectedPaths();

        foreach (['0', '-1', 'abc'] as $invalid) {
            $this->artisan('identity:verify-production', [
                '--census-max-user-id' => $invalid,
                '--aggregate' => $aggregate,
                '--rows' => $rows,
            ])->assertFailed();
        }

        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_public_output_path_is_rejected_before_query(): void
    {
        $this->app['env'] = 'production';
        $this->bindDedicatedReadOnlyConnection();

        $queries = $this->captureQueries(function (): void {
            $this->artisan('identity:verify-production', [
                '--census-max-user-id' => '1',
                '--aggregate' => public_path('verify-aggregate.json'),
                '--rows' => public_path('verify-rows.jsonl'),
            ])->assertFailed();
        });

        $this->assertNoIdentityTableQueries($queries);
    }

    public function test_public_storage_output_path_is_rejected_before_query(): void
    {
        $this->app['env'] = 'production';
        $this->bindDedicatedReadOnlyConnection();

        $queries = $this->captureQueries(function (): void {
            $this->artisan('identity:verify-production', [
                '--census-max-user-id' => '1',
                '--aggregate' => storage_path('app/public/verify-aggregate.json'),
                '--rows' => storage_path('app/public/verify-rows.jsonl'),
            ])->assertFailed();
        });

        $this->assertNoIdentityTableQueries($queries);
    }

    public function test_path_traversal_into_public_is_rejected_before_query(): void
    {
        $this->app['env'] = 'production';
        $this->bindDedicatedReadOnlyConnection();

        $queries = $this->captureQueries(function (): void {
            $this->artisan('identity:verify-production', [
                '--census-max-user-id' => '1',
                '--aggregate' => 'storage/app/private/identity-verify/../../../public/verify-aggregate.json',
                '--rows' => 'storage/app/private/identity-verify/../../../public/verify-rows.jsonl',
            ])->assertFailed();
        });

        $this->assertNoIdentityTableQueries($queries);
    }

    public function test_production_command_streams_pii_safe_output_on_readonly_connection_without_writes(): void
    {
        $user = $this->makeKorisnik([
            'email' => 'prod-verify-pii@example.test',
            'jmb' => $this->validJmb($this->jmbSerial++),
            'passport_number' => 'PRODPASSPORT1',
            'pib' => '12345672',
            'first_name' => 'PiiFirst',
            'phone' => '+38267111111',
        ]);
        $user->load('role');
        $classified = (new IdentityCensusService)->classify($user);
        $snapshot = (new IdentityBackfillProjector)->project($user, $classified);
        $this->assertNotNull($snapshot);
        (new CanonicalIdentityWriter)->createForUser($user, $snapshot);

        $beforeLegacy = $this->legacyPayload($user);
        $beforeCounts = $this->identityCounts();
        $beforeHash = $this->usersTableFingerprint();

        $this->app['env'] = 'production';
        $this->bindDedicatedReadOnlyConnection();
        [$aggregate, $rows] = $this->protectedPaths();

        $queries = $this->captureQueries(function () use ($aggregate, $rows, $user): void {
            $this->artisan('identity:verify-production', [
                '--census-max-user-id' => (string) $user->id,
                '--census-reference-date' => '2026-09-06',
                '--aggregate' => $aggregate,
                '--rows' => $rows,
            ])->assertSuccessful();
        });

        $this->assertNotEmpty($queries);
        foreach ($this->identityTableQueries($queries) as $query) {
            $this->assertSame(IdentityCensusService::READONLY_CONNECTION, $query->connectionName);
            $this->assertDoesNotMatchRegularExpression('/\\b(insert|update|delete|replace|truncate)\\b/i', $query->sql);
        }

        $this->assertSame($beforeCounts, $this->identityCounts());
        $this->assertSame($beforeLegacy, $this->legacyPayload($user->fresh()));
        $this->assertSame($beforeHash, $this->usersTableFingerprint());

        $aggregateJson = (string) file_get_contents($aggregate);
        $rowJson = (string) file_get_contents($rows);
        $combined = $aggregateJson.$rowJson;
        $this->assertStringNotContainsString('prod-verify-pii@example.test', $combined);
        $this->assertStringNotContainsString((string) $user->jmb, $combined);
        $this->assertStringNotContainsString('PRODPASSPORT1', $combined);
        $this->assertStringNotContainsString('12345672', $combined);
        $this->assertStringNotContainsString('PiiFirst', $combined);
        $this->assertStringNotContainsString('+38267111111', $combined);

        $decoded = json_decode($aggregateJson, true);
        $this->assertSame('verify', $decoded['metadata']['mode']);
        $this->assertSame('production', $decoded['metadata']['environment']);
        $this->assertSame('identity_census_readonly', $decoded['metadata']['connection']);
        $this->assertSame('DK-TS-002 D15 Step 5', $decoded['metadata']['spec']);
        $this->assertSame('2026-09-06', $decoded['metadata']['census_reference_date']);
        $this->assertFalse($decoded['metadata']['canonical_read']);
        $this->assertFalse($decoded['metadata']['canonical_write']);
        $this->assertSame(IdentityVerifyUserOutcome::VERIFIED, json_decode(trim($rowJson), true)['status']);

        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_production_command_fails_closed_when_canonical_is_missing(): void
    {
        $user = $this->makeKorisnik([
            'jmb' => $this->validJmb($this->jmbSerial++),
            'email' => 'prod-verify-missing@example.test',
        ]);

        $this->app['env'] = 'production';
        $this->bindDedicatedReadOnlyConnection();
        [$aggregate, $rows] = $this->protectedPaths();

        $this->artisan('identity:verify-production', [
            '--census-max-user-id' => (string) $user->id,
            '--aggregate' => $aggregate,
            '--rows' => $rows,
        ])->assertFailed();

        $this->assertCanonicalTablesEmpty();
        $row = json_decode(trim((string) file_get_contents($rows)), true);
        $this->assertSame(IdentityVerifyUserOutcome::MISSING_CANONICAL, $row['status']);

        $this->cleanupPaths($aggregate, $rows);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function protectedPaths(): array
    {
        IdentityVerifyProtectedOutputPath::ensureBaseDirectory();

        return [
            storage_path('app/private/identity-verify/test-aggregate.json'),
            storage_path('app/private/identity-verify/test-rows.jsonl'),
        ];
    }

    private function bindDedicatedReadOnlyConnection(): void
    {
        $mysql = config('database.connections.mysql');
        $readonly = $mysql;
        $readonly['username'] = 'identity_census_ro';
        config(['database.connections.'.IdentityCensusService::READONLY_CONNECTION => $readonly]);
        DB::purge(IdentityCensusService::READONLY_CONNECTION);
        $mysqlConnection = DB::connection('mysql');
        $readonlyConnection = DB::connection(IdentityCensusService::READONLY_CONNECTION);
        $readonlyConnection->setPdo($mysqlConnection->getPdo());
        $readonlyConnection->setReadPdo($mysqlConnection->getReadPdo());
    }

    /**
     * @param  callable(): void  $callback
     * @return list<QueryExecuted>
     */
    private function captureQueries(callable $callback): array
    {
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query;
        });
        $callback();

        return $queries;
    }

    /**
     * @param  list<QueryExecuted>  $queries
     */
    private function assertNoIdentityTableQueries(array $queries): void
    {
        $this->assertSame([], $this->identityTableQueries($queries));
    }

    /**
     * @param  list<QueryExecuted>  $queries
     * @return list<QueryExecuted>
     */
    private function identityTableQueries(array $queries): array
    {
        $tables = [
            'users',
            'roles',
            'platform_identities',
            'physical_person_identities',
            'legal_entity_identities',
            'legal_entity_authorized_persons',
            'foreign_branch_identities',
            'foreign_branch_representatives',
        ];

        return array_values(array_filter(
            $queries,
            function (QueryExecuted $query) use ($tables): bool {
                foreach ($tables as $table) {
                    if ((bool) preg_match('/\\b'.$table.'\\b/i', $query->sql)) {
                        return true;
                    }
                }

                return false;
            }
        ));
    }

    /**
     * @return array<string, int>
     */
    private function identityCounts(): array
    {
        return [
            'users' => User::query()->count(),
            'platform_identities' => PlatformIdentity::query()->count(),
            'physical_person_identities' => PhysicalPersonIdentity::query()->count(),
            'legal_entity_identities' => LegalEntityIdentity::query()->count(),
            'legal_entity_authorized_persons' => LegalEntityAuthorizedPerson::query()->count(),
            'foreign_branch_identities' => ForeignBranchIdentity::query()->count(),
            'foreign_branch_representatives' => ForeignBranchRepresentative::query()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyPayload(User $user): array
    {
        return $user->only([
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
        ]);
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
                'updated_at',
            ])
            ->toArray();

        return hash('sha256', json_encode($rows));
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

    private function cleanupPaths(string $aggregate, string $rows): void
    {
        @unlink($aggregate);
        @unlink($rows);
    }
}
