<?php

namespace Tests\Feature\Identity;

use App\Console\Commands\IdentityProductionCensusCommand;
use App\Identity\Census\IdentityCensusProtectedOutputPath;
use App\Identity\Census\IdentityCensusService;
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

class IdentityProductionCensusSafetyTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->assertSame(false, config('identity.canonical_write'));
        $this->assertSame(false, config('identity.canonical_read'));
        $this->assertNull(config('database.connections.'.IdentityCensusService::READONLY_CONNECTION.'.host'));
        $this->assertNotEquals(
            config('database.connections.mysql.username'),
            config('database.connections.'.IdentityCensusService::READONLY_CONNECTION.'.username')
        );
    }

    public function test_production_command_refuses_non_production_environment(): void
    {
        $this->bindDedicatedTestingConnection();
        [$aggregate, $rows] = $this->protectedPaths();

        $queries = $this->captureQueries(function () use ($aggregate, $rows): void {
            $this->artisan('identity:census-production', [
                '--aggregate' => $aggregate,
                '--rows' => $rows,
            ])->expectsOutputToContain('identity:census-production requires the production environment.')
                ->assertFailed();
        });

        $this->assertNoCensusTableQueries($queries);
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_existing_census_command_still_refuses_production(): void
    {
        $this->app['env'] = 'production';
        $this->bindDedicatedTestingConnection();

        $this->artisan('identity:census')
            ->expectsOutputToContain('identity:census refuses execution in production.')
            ->assertFailed();
    }

    public function test_production_command_has_no_generic_bypass_flags(): void
    {
        $definition = implode(' ', array_keys((new IdentityProductionCensusCommand)->getDefinition()->getOptions()));

        foreach (['apply', 'write', 'migrate', 'backfill', 'force'] as $flag) {
            $this->assertStringNotContainsString($flag, $definition);
        }
        $this->assertArrayNotHasKey('production', (new IdentityProductionCensusCommand)->getDefinition()->getOptions());
    }

    public function test_missing_dedicated_connection_config_fails_before_query(): void
    {
        $this->app['env'] = 'production';
        [$aggregate, $rows] = $this->protectedPaths();

        $queries = $this->captureQueries(function () use ($aggregate, $rows): void {
            $this->artisan('identity:census-production', [
                '--aggregate' => $aggregate,
                '--rows' => $rows,
            ])->expectsOutputToContain('Dedicated census read-only connection is incomplete.')
                ->assertFailed();
        });

        $this->assertNoCensusTableQueries($queries);
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_canonical_writer_on_fails_before_query(): void
    {
        $this->app['env'] = 'production';
        $this->bindDedicatedTestingConnection();
        config(['identity.canonical_write' => true]);
        [$aggregate, $rows] = $this->protectedPaths();

        $queries = $this->captureQueries(function () use ($aggregate, $rows): void {
            $this->artisan('identity:census-production', [
                '--aggregate' => $aggregate,
                '--rows' => $rows,
            ])->assertFailed();
        });

        $this->assertNoCensusTableQueries($queries);
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_canonical_read_on_fails_before_query(): void
    {
        $this->app['env'] = 'production';
        $this->bindDedicatedTestingConnection();
        config(['identity.canonical_read' => true]);
        [$aggregate, $rows] = $this->protectedPaths();

        $queries = $this->captureQueries(function () use ($aggregate, $rows): void {
            $this->artisan('identity:census-production', [
                '--aggregate' => $aggregate,
                '--rows' => $rows,
            ])->assertFailed();
        });

        $this->assertNoCensusTableQueries($queries);
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_public_output_path_is_rejected_before_query(): void
    {
        $this->app['env'] = 'production';
        $this->bindDedicatedTestingConnection();

        $queries = $this->captureQueries(function (): void {
            $this->artisan('identity:census-production', [
                '--aggregate' => public_path('census-aggregate.json'),
                '--rows' => public_path('census-rows.jsonl'),
            ])->assertFailed();
        });

        $this->assertNoCensusTableQueries($queries);
    }

    public function test_public_storage_output_path_is_rejected_before_query(): void
    {
        $this->app['env'] = 'production';
        $this->bindDedicatedTestingConnection();

        $queries = $this->captureQueries(function (): void {
            $this->artisan('identity:census-production', [
                '--aggregate' => storage_path('app/public/census-aggregate.json'),
                '--rows' => storage_path('app/public/census-rows.jsonl'),
            ])->assertFailed();
        });

        $this->assertNoCensusTableQueries($queries);
    }

    public function test_path_traversal_into_public_is_rejected_before_query(): void
    {
        $this->app['env'] = 'production';
        $this->bindDedicatedTestingConnection();

        $queries = $this->captureQueries(function (): void {
            $this->artisan('identity:census-production', [
                '--aggregate' => 'storage/app/private/identity-census/../../../public/census-aggregate.json',
                '--rows' => 'storage/app/private/identity-census/../../../public/census-rows.jsonl',
            ])->assertFailed();
        });

        $this->assertNoCensusTableQueries($queries);
    }

    public function test_production_command_streams_rows_on_dedicated_connection_without_pii_or_writes(): void
    {
        $user = $this->makeKorisnik([
            'email' => 'prod-census-pii@example.test',
            'jmb' => '0000000000000',
            'passport_number' => 'PRODPASSPORT1',
            'pib' => null,
        ]);
        $beforeLegacy = $this->legacyPayload($user);
        $beforeCounts = $this->identityCounts();
        $beforeHash = $this->usersTableFingerprint();

        $this->app['env'] = 'production';
        $this->bindDedicatedTestingConnection();

        [$aggregate, $rows] = $this->protectedPaths();
        $queries = $this->captureQueries(function () use ($aggregate, $rows): void {
            $this->artisan('identity:census-production', [
                '--aggregate' => $aggregate,
                '--rows' => $rows,
            ])->assertSuccessful();
        });

        $this->assertNotEmpty($queries);
        foreach ($this->censusTableQueries($queries) as $query) {
            $this->assertSame(IdentityCensusService::READONLY_CONNECTION, $query->connectionName);
            $this->assertDoesNotMatchRegularExpression('/\\b(insert|update|delete|replace|truncate)\\b/i', $query->sql);
        }
        $this->assertTrue($this->sawTable($queries, 'users'));
        $this->assertTrue($this->sawTable($queries, 'roles'));
        $this->assertSame(1, $this->tableQueryCount($queries, 'roles'));

        $this->assertSame($beforeCounts, $this->identityCounts());
        $this->assertSame($beforeLegacy, $this->legacyPayload($user->fresh()));
        $this->assertSame($beforeHash, $this->usersTableFingerprint());

        $this->assertFileExists($aggregate);
        $this->assertFileExists($rows);
        $aggregateJson = (string) file_get_contents($aggregate);
        $rowJson = (string) file_get_contents($rows);
        $this->assertStringNotContainsString('prod-census-pii@example.test', $aggregateJson.$rowJson);
        $this->assertStringNotContainsString('0000000000000', $aggregateJson.$rowJson);
        $this->assertStringNotContainsString('PRODPASSPORT1', $aggregateJson.$rowJson);

        $decoded = json_decode($aggregateJson, true);
        $this->assertSame('production', $decoded['metadata']['environment']);
        $this->assertGreaterThan(0, $decoded['aggregates']['total_users']);
        $this->assertNotSame('', trim($rowJson));

        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_streamed_classification_matches_buffered_census(): void
    {
        $this->makeKorisnik(['email' => 'parity-a@example.test', 'jmb' => '0000000000000']);
        $this->bindDedicatedTestingConnection();

        $buffered = (new IdentityCensusService)->run();
        $streamed = [];
        $report = (new IdentityCensusService)->runOnConnection(
            IdentityCensusService::READONLY_CONNECTION,
            function ($row) use (&$streamed): void {
                $streamed[] = $row->toArray();
            }
        );

        $this->assertSame([], $report->rows);
        $this->assertSame(
            array_map(static fn ($row) => $row->toArray(), $buffered->rows),
            $streamed
        );
        $this->assertSame($buffered->aggregates, $report->aggregates);
    }

    public function test_production_command_rejects_missing_output_options_before_query(): void
    {
        $this->app['env'] = 'production';
        $this->bindDedicatedTestingConnection();

        $queries = $this->captureQueries(function (): void {
            $this->artisan('identity:census-production')->assertFailed();
        });

        $this->assertNoCensusTableQueries($queries);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function protectedPaths(): array
    {
        IdentityCensusProtectedOutputPath::ensureBaseDirectory();

        return [
            storage_path('app/private/identity-census/test-aggregate.json'),
            storage_path('app/private/identity-census/test-rows.jsonl'),
        ];
    }

    private function bindDedicatedTestingConnection(): void
    {
        config([
            'database.connections.'.IdentityCensusService::READONLY_CONNECTION => config('database.connections.mysql'),
        ]);
        DB::purge(IdentityCensusService::READONLY_CONNECTION);
        $mysql = DB::connection('mysql');
        $readonly = DB::connection(IdentityCensusService::READONLY_CONNECTION);
        $readonly->setPdo($mysql->getPdo());
        $readonly->setReadPdo($mysql->getReadPdo());
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
    private function assertNoCensusTableQueries(array $queries): void
    {
        $this->assertSame([], $this->censusTableQueries($queries));
    }

    /**
     * @param  list<QueryExecuted>  $queries
     * @return list<QueryExecuted>
     */
    private function censusTableQueries(array $queries): array
    {
        return array_values(array_filter(
            $queries,
            fn (QueryExecuted $query): bool => $this->sqlTouchesTable($query->sql, 'users')
                || $this->sqlTouchesTable($query->sql, 'roles')
        ));
    }

    /**
     * @param  list<QueryExecuted>  $queries
     */
    private function sawTable(array $queries, string $table): bool
    {
        foreach ($queries as $query) {
            if ($this->sqlTouchesTable($query->sql, $table)
                && $query->connectionName === IdentityCensusService::READONLY_CONNECTION) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<QueryExecuted>  $queries
     */
    private function tableQueryCount(array $queries, string $table): int
    {
        $count = 0;
        foreach ($this->censusTableQueries($queries) as $query) {
            if ($this->sqlTouchesTable($query->sql, $table)) {
                $count++;
            }
        }

        return $count;
    }

    private function sqlTouchesTable(string $sql, string $table): bool
    {
        return (bool) preg_match('/\\b'.$table.'\\b/i', $sql);
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
