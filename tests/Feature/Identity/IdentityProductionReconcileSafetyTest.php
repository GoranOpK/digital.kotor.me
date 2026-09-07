<?php

namespace Tests\Feature\Identity;

use App\Console\Commands\IdentityProductionReconcileCommand;
use App\Identity\Census\IdentityCensusService;
use App\Identity\Reconcile\IdentityReconcileProtectedOutputPath;
use App\Identity\Reconcile\IdentityReconcileUserOutcome;
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

class IdentityProductionReconcileSafetyTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->assertSame(false, config('identity.canonical_write'));
        $this->assertSame(false, config('identity.canonical_read'));
        $this->bindDedicatedReadOnlyConnection();
    }

    public function test_production_command_refuses_non_production_environment(): void
    {
        [$aggregate, $rows] = $this->protectedPaths();

        $this->artisan('identity:reconcile-production', [
            '--dry-run' => true,
            '--census-max-user-id' => '1',
            '--aggregate' => $aggregate,
            '--rows' => $rows,
        ])->expectsOutputToContain('identity:reconcile-production requires the production environment.')
            ->assertFailed();

        $this->assertCanonicalTablesEmpty();
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_command_has_no_bypass_or_final_flags(): void
    {
        $options = array_keys((new IdentityProductionReconcileCommand)->getDefinition()->getOptions());
        $joined = implode(' ', $options);

        $this->assertNotContains('force', $options);
        $this->assertNotContains('final', $options);
        $this->assertNotContains('user', $options);
        $this->assertNotContains('users', $options);
        $this->assertStringNotContainsString('skip-', $joined);
        $this->assertStringNotContainsString('ignore-', $joined);
        $this->assertStringNotContainsString('allow-unprotected', $joined);
    }

    public function test_apply_without_exact_confirmation_token_fails(): void
    {
        $this->app['env'] = 'production';
        [$aggregate, $rows] = $this->protectedPaths();

        $this->artisan('identity:reconcile-production', [
            '--apply' => true,
            '--confirm' => 'FORCE',
            '--census-max-user-id' => '1',
            '--aggregate' => $aggregate,
            '--rows' => $rows,
        ])->assertFailed();

        $this->artisan('identity:reconcile-production', [
            '--apply' => true,
            '--census-max-user-id' => '1',
            '--aggregate' => $aggregate,
            '--rows' => $rows,
        ])->assertFailed();

        $this->assertCanonicalTablesEmpty();
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_dry_run_and_apply_together_or_neither_fails(): void
    {
        $this->app['env'] = 'production';
        [$aggregate, $rows] = $this->protectedPaths();

        $this->artisan('identity:reconcile-production', [
            '--dry-run' => true,
            '--apply' => true,
            '--confirm' => IdentityProductionReconcileCommand::APPLY_CONFIRMATION,
            '--census-max-user-id' => '1',
            '--aggregate' => $aggregate,
            '--rows' => $rows,
        ])->assertFailed();

        $this->artisan('identity:reconcile-production', [
            '--census-max-user-id' => '1',
            '--aggregate' => $aggregate,
            '--rows' => $rows,
        ])->assertFailed();

        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_canonical_switches_on_fail_before_write(): void
    {
        $this->app['env'] = 'production';
        [$aggregate, $rows] = $this->protectedPaths();

        config(['identity.canonical_write' => true]);
        $this->artisan('identity:reconcile-production', [
            '--dry-run' => true,
            '--census-max-user-id' => '1',
            '--aggregate' => $aggregate,
            '--rows' => $rows,
        ])->assertFailed();

        config(['identity.canonical_write' => false, 'identity.canonical_read' => true]);
        $this->artisan('identity:reconcile-production', [
            '--dry-run' => true,
            '--census-max-user-id' => '1',
            '--aggregate' => $aggregate,
            '--rows' => $rows,
        ])->assertFailed();

        $this->assertCanonicalTablesEmpty();
        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_public_and_traversal_output_paths_are_rejected(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('identity:reconcile-production', [
            '--dry-run' => true,
            '--census-max-user-id' => '1',
            '--aggregate' => public_path('reconcile-aggregate.json'),
            '--rows' => public_path('reconcile-rows.jsonl'),
        ])->assertFailed();

        $this->artisan('identity:reconcile-production', [
            '--dry-run' => true,
            '--census-max-user-id' => '1',
            '--aggregate' => storage_path('app/public/reconcile-aggregate.json'),
            '--rows' => storage_path('app/public/reconcile-rows.jsonl'),
        ])->assertFailed();

        $this->artisan('identity:reconcile-production', [
            '--dry-run' => true,
            '--census-max-user-id' => '1',
            '--aggregate' => 'storage/app/private/identity-reconcile/../../../public/reconcile-aggregate.json',
            '--rows' => 'storage/app/private/identity-reconcile/../../../public/reconcile-rows.jsonl',
        ])->assertFailed();
    }

    public function test_identical_aggregate_and_rows_paths_are_rejected_before_overwrite(): void
    {
        $this->app['env'] = 'production';
        IdentityReconcileProtectedOutputPath::ensureBaseDirectory();
        $collision = storage_path('app/private/identity-reconcile/test-collision.json');
        $sentinel = '{"sentinel":true}'."\n";
        file_put_contents($collision, $sentinel);

        $this->artisan('identity:reconcile-production', [
            '--dry-run' => true,
            '--census-max-user-id' => '1',
            '--aggregate' => $collision,
            '--rows' => $collision,
        ])->expectsOutputToContain('--aggregate and --rows must be different protected paths.')
            ->assertFailed();

        $this->assertSame($sentinel, (string) file_get_contents($collision));
        $this->assertCanonicalTablesEmpty();
        @unlink($collision);
    }

    public function test_production_dry_run_writes_protected_pii_safe_output_without_canonical_rows(): void
    {
        $user = $this->makeKorisnik([
            'jmb' => '0000000000000',
            'email' => 'prod-reconcile-pii@example.test',
            'first_name' => 'ProdRecFirst',
            'last_name' => 'ProdRecLast',
            'passport_number' => 'PRODRECPASSPORT1',
            'pib' => '12345672',
            'phone' => '+38267123999',
            'address' => 'Prod Rec Street 1',
            'city' => 'ProdRecCity',
        ]);
        $before = $this->legacyPayload($user);

        $this->app['env'] = 'production';
        [$aggregate, $rows] = $this->protectedPaths();

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query;
        });

        $this->artisan('identity:reconcile-production', [
            '--dry-run' => true,
            '--census-max-user-id' => (string) $user->id,
            '--census-reference-date' => '2026-09-06',
            '--aggregate' => $aggregate,
            '--rows' => $rows,
        ])->assertSuccessful();

        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression('/\\b(insert|update|delete|replace|truncate)\\b/i', $query->sql);
            $this->assertNotSame('mysql', $query->connectionName, $query->sql);
        }

        $this->assertCanonicalTablesEmpty();
        $this->assertSame($before, $this->legacyPayload($user->fresh()));

        $aggregateJson = (string) file_get_contents($aggregate);
        $rowJson = (string) file_get_contents($rows);
        $combined = $aggregateJson.$rowJson;
        foreach ([
            'prod-reconcile-pii@example.test',
            '0000000000000',
            'PRODRECPASSPORT1',
            '12345672',
            'ProdRecFirst',
            'ProdRecLast',
            '+38267123999',
            'Prod Rec Street 1',
            'ProdRecCity',
        ] as $secret) {
            $this->assertStringNotContainsString($secret, $combined);
        }

        $decoded = json_decode($aggregateJson, true);
        $this->assertSame('DK-TS-002 D15 Step 7', $decoded['spec']);
        $this->assertSame('dry-run', $decoded['mode']);
        $this->assertSame('production', $decoded['environment']);
        $this->assertFalse($decoded['cutover_ready']);
        $this->assertFalse($decoded['population_boundary_protected']);
        $this->assertTrue($decoded['reconcile_passed']);
        $this->assertFalse($decoded['graph_readiness_passed']);
        $this->assertSame('OPEN', $decoded['ep_gate']['status']);
        $this->assertSame(IdentityCensusService::READONLY_CONNECTION, $decoded['connection']);
        $this->assertSame(IdentityReconcileUserOutcome::WOULD_CREATE, json_decode(trim($rowJson), true)['status']);

        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_production_apply_with_confirmation_creates_eligible_graph_without_users_identity_writes(): void
    {
        $user = $this->makeKorisnik([
            'jmb' => '0000000000000',
            'email' => 'prod-reconcile-apply@example.test',
        ]);
        $before = $this->legacyPayload($user);

        $this->app['env'] = 'production';
        [$aggregate, $rows] = $this->protectedPaths();

        $this->artisan('identity:reconcile-production', [
            '--apply' => true,
            '--confirm' => IdentityProductionReconcileCommand::APPLY_CONFIRMATION,
            '--census-max-user-id' => (string) $user->id,
            '--aggregate' => $aggregate,
            '--rows' => $rows,
        ])->assertSuccessful();

        $this->assertSame(1, PlatformIdentity::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, PhysicalPersonIdentity::query()->count());
        $this->assertSame($before, $this->legacyPayload($user->fresh()));

        $decoded = json_decode((string) file_get_contents($aggregate), true);
        $this->assertSame('apply', $decoded['mode']);
        $this->assertFalse($decoded['cutover_ready']);
        $this->assertFalse($decoded['population_boundary_protected']);
        $this->assertTrue($decoded['reconcile_passed']);
        $this->assertTrue($decoded['graph_readiness_passed']);
        $row = json_decode(trim((string) file_get_contents($rows)), true);
        $this->assertSame(IdentityReconcileUserOutcome::CREATED, $row['status']);

        $this->cleanupPaths($aggregate, $rows);
    }

    public function test_production_command_rejects_invalid_census_max_user_id(): void
    {
        $this->app['env'] = 'production';
        [$aggregate, $rows] = $this->protectedPaths();

        foreach (['0', '-1', 'abc'] as $invalid) {
            $this->artisan('identity:reconcile-production', [
                '--dry-run' => true,
                '--census-max-user-id' => $invalid,
                '--aggregate' => $aggregate,
                '--rows' => $rows,
            ])->assertFailed();
        }

        $this->assertCanonicalTablesEmpty();
        $this->cleanupPaths($aggregate, $rows);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function protectedPaths(): array
    {
        IdentityReconcileProtectedOutputPath::ensureBaseDirectory();

        return [
            storage_path('app/private/identity-reconcile/test-aggregate.json'),
            storage_path('app/private/identity-reconcile/test-rows.jsonl'),
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
            'role_id',
            'activation_status',
        ]);
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
