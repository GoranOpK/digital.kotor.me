<?php

namespace Tests\Feature\Identity;

use App\Console\Commands\IdentityProductionShadowCommand;
use App\Identity\Backfill\IdentityBackfillProjector;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\Census\IdentityCensusService;
use App\Identity\Shadow\IdentityShadowProtectedOutputPath;
use App\Identity\Shadow\IdentityShadowStatus;
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
use Illuminate\Support\Facades\Schema;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\TestCase;

class IdentityProductionShadowSafetyTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesSyntheticPaymentCatalog;
    use RefreshDatabase;

    private int $jmbSerial = 90;

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
            $this->artisan('identity:shadow-production', [
                '--census-max-user-id' => '1',
                '--aggregate' => $aggregate,
                '--rows' => $rows,
            ])->expectsOutputToContain('identity:shadow-production requires the production environment.')
                ->assertFailed();
        });

        $this->assertNoIdentityTableQueries($queries);
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_command_has_no_apply_force_repair_or_authority_flags(): void
    {
        $options = array_keys((new IdentityProductionShadowCommand)->getDefinition()->getOptions());

        $this->assertNotContains('apply', $options);
        $this->assertNotContains('force', $options);
        $this->assertNotContains('repair', $options);
        $this->assertNotContains('sync', $options);
        $this->assertNotContains('reconcile', $options);
        $this->assertNotContains('enable', $options);
        $this->assertNotContains('fallback', $options);
        $this->assertNotContains('skip-ep', $options);
        $this->assertNotContains('ignore-missing', $options);
        $this->assertNotContains('allow-missing-tables', $options);
        $this->assertContains('scope', $options);
    }

    public function test_invalid_scope_fails_before_query(): void
    {
        $this->app['env'] = 'production';
        $this->bindDedicatedReadOnlyConnection();
        [$aggregate, $rows] = $this->protectedPaths();

        $queries = $this->captureQueries(function () use ($aggregate, $rows): void {
            $this->artisan('identity:shadow-production', [
                '--census-max-user-id' => '1',
                '--aggregate' => $aggregate,
                '--rows' => $rows,
                '--scope' => 'skip-ep',
            ])->expectsOutputToContain('--scope is invalid')
                ->assertFailed();
        });

        $this->assertNoIdentityTableQueries($queries);
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_scoped_command_runs_without_ep_catalog_and_keeps_gate_open(): void
    {
        $user = $this->makeKorisnik([
            'email' => 'prod-scope-pii@example.test',
            'jmb' => $this->validJmb($this->jmbSerial++),
            'first_name' => 'ScopePiiFirst',
            'last_name' => 'ScopePiiLast',
            'phone' => '+38267111888',
            'address' => 'Scope Street 2',
            'city' => 'Kotor',
        ]);
        $user->load('role');
        $classified = (new IdentityCensusService)->classify($user);
        $snapshot = (new IdentityBackfillProjector)->project($user, $classified);
        $this->assertNotNull($snapshot);
        (new CanonicalIdentityWriter)->createForUser($user, $snapshot);

        Schema::disableForeignKeyConstraints();
        foreach ([
            'payment_account_availabilities',
            'payment_type_availabilities',
            'payment_confirmation_deliveries',
            'payment_transaction_events',
            'payment_transactions',
            'payment_initiations',
            'payment_accounts',
            'payment_types',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        $this->app['env'] = 'production';
        $this->bindDedicatedReadOnlyConnection();
        [$aggregate, $rows] = $this->protectedPaths();

        $queries = $this->captureQueries(function () use ($aggregate, $rows, $user): void {
            $this->artisan('identity:shadow-production', [
                '--census-max-user-id' => (string) $user->id,
                '--aggregate' => $aggregate,
                '--rows' => $rows,
                '--scope' => 'active-identity-wave',
            ])->expectsOutputToContain('SCOPED PASS — ACTIVE IDENTITY WAVE')
                ->expectsOutputToContain('EP GATE OPEN / DEFERRED')
                ->assertSuccessful();
        });

        foreach ($queries as $query) {
            $haystack = $query->sql.' '.json_encode($query->bindings);
            foreach (['payment_types', 'payment_accounts', 'payment_type_availabilities', 'payment_account_availabilities'] as $table) {
                $this->assertDoesNotMatchRegularExpression('/\\b'.$table.'\\b/i', $haystack);
            }
            $this->assertDoesNotMatchRegularExpression('/\\b(insert|update|delete|replace|truncate)\\b/i', $query->sql);
        }

        $decoded = json_decode((string) file_get_contents($aggregate), true);
        $this->assertSame('scoped_production_shadow', $decoded['metadata']['mode']);
        $this->assertSame('active-identity-wave', $decoded['metadata']['scope']);
        $this->assertSame(4, $decoded['aggregates']['required_flow_count']);
        $this->assertSame('OPEN', $decoded['metadata']['deferred_gates']['ep_availability']['status']);
        $this->assertSame('ep_module_undeployed', $decoded['metadata']['deferred_gates']['ep_availability']['reason']);
        $this->assertSame('earliest_of', $decoded['metadata']['deferred_gates']['ep_availability']['required_before']['mode']);
        $this->assertSame(
            ['ep_production_activation', 'canonical_writer_authority'],
            $decoded['metadata']['deferred_gates']['ep_availability']['required_before']['events']
        );
        $this->assertFalse($decoded['metadata']['step6_closed']);
        $this->assertFalse($decoded['metadata']['five_flow_closed']);
        $combined = (string) file_get_contents($aggregate).(string) file_get_contents($rows);
        $this->assertStringNotContainsString('prod-scope-pii@example.test', $combined);
        $this->assertStringNotContainsString('ScopePiiFirst', $combined);

        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_missing_dedicated_connection_config_fails_before_query(): void
    {
        $this->app['env'] = 'production';
        [$aggregate, $rows] = $this->protectedPaths();

        $queries = $this->captureQueries(function () use ($aggregate, $rows): void {
            $this->artisan('identity:shadow-production', [
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
            $this->artisan('identity:shadow-production', [
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
            $this->artisan('identity:shadow-production', [
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
            $this->artisan('identity:shadow-production', [
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
            $this->artisan('identity:shadow-production', [
                '--aggregate' => $aggregate,
                '--rows' => $rows,
            ])->expectsOutputToContain('census-max-user-id')
                ->assertFailed();
        });

        $this->assertNoIdentityTableQueries($queries);
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_public_output_path_is_rejected_before_query(): void
    {
        $this->app['env'] = 'production';
        $this->bindDedicatedReadOnlyConnection();

        $queries = $this->captureQueries(function (): void {
            $this->artisan('identity:shadow-production', [
                '--census-max-user-id' => '1',
                '--aggregate' => public_path('shadow-aggregate.json'),
                '--rows' => public_path('shadow-rows.jsonl'),
            ])->assertFailed();
        });

        $this->assertNoIdentityTableQueries($queries);
    }

    public function test_public_storage_output_path_is_rejected_before_query(): void
    {
        $this->app['env'] = 'production';
        $this->bindDedicatedReadOnlyConnection();

        $queries = $this->captureQueries(function (): void {
            $this->artisan('identity:shadow-production', [
                '--census-max-user-id' => '1',
                '--aggregate' => storage_path('app/public/shadow-aggregate.json'),
                '--rows' => storage_path('app/public/shadow-rows.jsonl'),
            ])->assertFailed();
        });

        $this->assertNoIdentityTableQueries($queries);
    }

    public function test_path_traversal_into_public_is_rejected_before_query(): void
    {
        $this->app['env'] = 'production';
        $this->bindDedicatedReadOnlyConnection();

        $queries = $this->captureQueries(function (): void {
            $this->artisan('identity:shadow-production', [
                '--census-max-user-id' => '1',
                '--aggregate' => 'storage/app/private/identity-shadow/../../../public/shadow-aggregate.json',
                '--rows' => 'storage/app/private/identity-shadow/../../../public/shadow-rows.jsonl',
            ])->assertFailed();
        });

        $this->assertNoIdentityTableQueries($queries);
    }

    public function test_production_command_streams_pii_safe_output_on_readonly_connection_without_writes(): void
    {
        $user = $this->makeKorisnik([
            'email' => 'prod-shadow-pii@example.test',
            'jmb' => $this->validJmb($this->jmbSerial++),
            'passport_number' => 'SHADOWPASSPORT1',
            'pib' => '12345672',
            'first_name' => 'ShadowPiiFirst',
            'last_name' => 'ShadowPiiLast',
            'phone' => '+38267111112',
            'address' => 'Pii Street 9',
            'city' => 'Kotor',
        ]);
        $user->load('role');
        $classified = (new IdentityCensusService)->classify($user);
        $snapshot = (new IdentityBackfillProjector)->project($user, $classified);
        $this->assertNotNull($snapshot);
        (new CanonicalIdentityWriter)->createForUser($user, $snapshot);
        $this->syntheticUsablePair($user, 'syn-shadow-prod', 'SYN-SHADOW-PROD-00000001');

        $beforeLegacy = $this->legacyPayload($user);
        $beforeCounts = $this->identityCounts();
        $beforeHash = $this->usersTableFingerprint();

        $this->app['env'] = 'production';
        $this->bindDedicatedReadOnlyConnection();
        [$aggregate, $rows] = $this->protectedPaths();

        $queries = $this->captureQueries(function () use ($aggregate, $rows, $user): void {
            $this->artisan('identity:shadow-production', [
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
        $this->assertStringNotContainsString('prod-shadow-pii@example.test', $combined);
        $this->assertStringNotContainsString((string) $user->jmb, $combined);
        $this->assertStringNotContainsString('SHADOWPASSPORT1', $combined);
        $this->assertStringNotContainsString('12345672', $combined);
        $this->assertStringNotContainsString('ShadowPiiFirst', $combined);
        $this->assertStringNotContainsString('ShadowPiiLast', $combined);
        $this->assertStringNotContainsString('+38267111112', $combined);
        $this->assertStringNotContainsString('Pii Street 9', $combined);

        $decoded = json_decode($aggregateJson, true);
        $this->assertSame('shadow', $decoded['metadata']['mode']);
        $this->assertSame('production', $decoded['metadata']['environment']);
        $this->assertSame('identity_census_readonly', $decoded['metadata']['connection']);
        $this->assertSame('DK-TS-002 D15 Step 6', $decoded['metadata']['spec']);
        $this->assertFalse($decoded['metadata']['canonical_read']);
        $this->assertFalse($decoded['metadata']['canonical_write']);
        $this->assertSame(1, $decoded['aggregates']['eligible_user_count']);
        $this->assertSame(5, $decoded['aggregates']['required_flow_count']);
        $this->assertSame(5, $decoded['aggregates']['evaluable_comparison_count']);
        $this->assertSame(5, $decoded['aggregates']['expected_match_comparisons']);
        $this->assertGreaterThan(0, $decoded['aggregates']['ep_compared_decision_count']);

        foreach (explode(PHP_EOL, trim($rowJson)) as $line) {
            $row = json_decode($line, true);
            $this->assertSame(IdentityShadowStatus::MATCH, $row['status']);
            $this->assertArrayHasKey('flow_code', $row);
            $this->assertArrayNotHasKey('jmb', $row);
            $this->assertArrayNotHasKey('phone', $row);
        }

        $this->cleanupPaths($aggregate, $rows);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function protectedPaths(): array
    {
        IdentityShadowProtectedOutputPath::ensureBaseDirectory();

        return [
            storage_path('app/private/identity-shadow/test-aggregate.json'),
            storage_path('app/private/identity-shadow/test-rows.jsonl'),
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
            'payment_types',
            'payment_accounts',
            'payment_type_availabilities',
            'payment_account_availabilities',
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

    private function cleanupPaths(string $aggregate, string $rows): void
    {
        @unlink($aggregate);
        @unlink($rows);
    }
}
