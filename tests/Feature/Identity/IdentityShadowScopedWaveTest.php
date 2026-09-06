<?php

namespace Tests\Feature\Identity;

use App\Identity\Backfill\IdentityBackfillProjector;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\Census\IdentityCensusService;
use App\Identity\Census\IdentityCensusRow;
use App\Identity\IdentitySnapshot;
use App\Identity\Shadow\IdentityShadowCanonicalLoader;
use App\Identity\Shadow\IdentityShadowException;
use App\Identity\Shadow\IdentityShadowFlow;
use App\Identity\Shadow\IdentityShadowScope;
use App\Identity\Shadow\IdentityShadowService;
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

class IdentityShadowScopedWaveTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesSyntheticPaymentCatalog;
    use RefreshDatabase;

    private int $jmbSerial = 110;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->assertSame(false, config('identity.canonical_write'));
        $this->assertSame(false, config('identity.canonical_read'));
        $this->bindDedicatedReadOnlyConnection();
    }

    public function test_default_is_full_five_flow_and_does_not_auto_scope(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $this->syntheticUsablePair($user, 'syn-scope-default', 'SYN-SCOPE-DEFAULT-00000001');

        $report = $this->runShadow();

        $this->assertSame(IdentityShadowScope::FULL, $report->metadata['scope']);
        $this->assertSame('shadow', $report->metadata['mode']);
        $this->assertSame(IdentityShadowFlow::REQUIRED, $report->metadata['required_flows']);
        $this->assertSame(5, $report->aggregates['required_flow_count']);
        $this->assertArrayNotHasKey('deferred_gates', $report->metadata);
        $this->assertTrue($report->fiveFlowClosed());
        $this->assertNotSame(17, $report->aggregates['eligible_user_count']);
    }

    public function test_full_mode_missing_ep_schema_hard_aborts(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $this->dropEpCatalogTables();

        try {
            $this->runShadow();
            $this->fail('Full shadow must abort when EP catalog tables are missing.');
        } catch (IdentityShadowException $e) {
            $this->assertStringContainsString('identity and catalog tables', $e->getMessage());
            $this->assertStringContainsString('payment_types', $e->getMessage());
        }
    }

    public function test_scoped_missing_ep_schema_runs_four_flows_without_ep_queries(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $this->dropEpCatalogTables();
        $censusMaxUserId = max(1, (int) User::query()->max('id'));

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query;
        });

        $report = $this->runShadow([
            'census_max_user_id' => $censusMaxUserId,
            'scope' => IdentityShadowScope::ACTIVE_IDENTITY_WAVE,
        ]);

        $this->assertTrue($report->passed());
        $this->assertFalse($report->fiveFlowClosed());
        $this->assertSame('scoped_production_shadow', $report->metadata['mode']);
        $this->assertSame(IdentityShadowScope::ACTIVE_IDENTITY_WAVE, $report->metadata['scope']);
        $this->assertSame(IdentityShadowFlow::ACTIVE_IDENTITY_WAVE, $report->metadata['required_flows']);
        $this->assertSame(4, $report->aggregates['required_flow_count']);
        $this->assertSame(4, $report->aggregates['expected_match_comparisons']);
        $this->assertSame(4, $report->aggregates['by_status'][IdentityShadowStatus::MATCH]);
        $this->assertFalse($report->metadata['step6_closed']);
        $this->assertFalse($report->metadata['five_flow_closed']);
        $this->assertSame('OPEN', $report->metadata['deferred_gates'][IdentityShadowFlow::EP_AVAILABILITY]['status']);
        $this->assertSame('ep_module_undeployed', $report->metadata['deferred_gates'][IdentityShadowFlow::EP_AVAILABILITY]['reason']);
        $gate = $report->metadata['deferred_gates'][IdentityShadowFlow::EP_AVAILABILITY];
        $this->assertSame('earliest_of', $gate['required_before']['mode']);
        $this->assertSame(
            ['ep_production_activation', 'canonical_writer_authority'],
            $gate['required_before']['events']
        );
        $this->assertFalse($report->fiveFlowClosed());
        $this->assertArrayNotHasKey(IdentityShadowFlow::EP_AVAILABILITY, $report->aggregates['by_flow']);
        foreach ($report->outcomes as $outcome) {
            $this->assertNotSame(IdentityShadowFlow::EP_AVAILABILITY, $outcome->flowCode);
        }
        foreach (IdentityShadowFlow::ACTIVE_IDENTITY_WAVE as $flow) {
            $this->assertSame(IdentityShadowStatus::MATCH, $this->statusFor($report, $user, $flow));
        }
        $this->assertSame(0, $report->aggregates['ep_compared_decision_count']);
        $this->assertNoEpCatalogQueries($queries);
        $this->assertNotSame(17, $report->aggregates['eligible_user_count']);
    }

    public function test_scoped_does_not_query_ep_tables_even_when_present(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $this->syntheticUsablePair($user, 'syn-scope-present', 'SYN-SCOPE-PRESENT-00000001');
        $censusMaxUserId = max(1, (int) User::query()->max('id'));

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query;
        });

        $report = $this->runShadow([
            'census_max_user_id' => $censusMaxUserId,
            'scope' => IdentityShadowScope::ACTIVE_IDENTITY_WAVE,
        ]);

        $this->assertTrue($report->passed());
        $this->assertNoEpCatalogQueries($queries);
        $this->assertSame(0, $report->aggregates['ep_compared_decision_count']);
    }

    public function test_scoped_mismatch_missing_invalid_and_read_failures_fail(): void
    {
        $mismatchUser = $this->backfillableUser(['email' => 'scope-mm@example.test']);
        $this->seedProjectedGraph($mismatchUser);
        $mismatchUser->update(['first_name' => 'Changed']);
        $mismatch = $this->runShadow(['scope' => IdentityShadowScope::ACTIVE_IDENTITY_WAVE]);
        $this->assertSame(IdentityShadowStatus::MISMATCH, $this->statusFor($mismatch, $mismatchUser, IdentityShadowFlow::PROFILE_DISPLAY));
        $this->assertFalse($mismatch->passed());

        $missingUser = $this->backfillableUser(['email' => 'scope-missing@example.test']);
        $missing = $this->runShadow(['scope' => IdentityShadowScope::ACTIVE_IDENTITY_WAVE]);
        $this->assertSame(IdentityShadowStatus::MISSING_CANONICAL, $this->statusFor($missing, $missingUser, IdentityShadowFlow::DASHBOARD_DISPLAY));
        $this->assertFalse($missing->passed());

        $invalidUser = $this->backfillableUser(['email' => 'scope-invalid@example.test']);
        $this->seedProjectedGraph($invalidUser);
        PlatformIdentity::query()->where('user_id', $invalidUser->id)->update([
            'subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY,
        ]);
        $invalid = $this->runShadow(['scope' => IdentityShadowScope::ACTIVE_IDENTITY_WAVE]);
        $this->assertSame(IdentityShadowStatus::CANONICAL_INVALID, $this->statusFor($invalid, $invalidUser, IdentityShadowFlow::KN_APPLICANT_TYPE));
        $this->assertFalse($invalid->passed());

        $readUser = $this->backfillableUser(['email' => 'scope-read@example.test']);
        $this->seedProjectedGraph($readUser);
        $loader = new class extends IdentityShadowCanonicalLoader
        {
            public function snapshotFromValidStep4Graph(array $graph): IdentitySnapshot
            {
                throw new IdentityShadowException('canonical loader failed');
            }
        };
        $readFailed = (new IdentityShadowService(loader: $loader))->run([
            'census_max_user_id' => max(1, (int) User::query()->max('id')),
            'scope' => IdentityShadowScope::ACTIVE_IDENTITY_WAVE,
        ]);
        $this->assertSame(IdentityShadowStatus::CANONICAL_READ_FAILED, $this->statusFor($readFailed, $readUser, IdentityShadowFlow::KN_APPLICATION_PREFILL));
        $this->assertFalse($readFailed->passed());

        $legacyUser = $this->backfillableUser(['email' => 'scope-legacy@example.test']);
        $this->seedProjectedGraph($legacyUser);
        $projector = new class extends IdentityBackfillProjector
        {
            public function project(User $user, IdentityCensusRow $row): ?IdentitySnapshot
            {
                return null;
            }
        };
        $legacyFailed = (new IdentityShadowService(projector: $projector))->run([
            'census_max_user_id' => max(1, (int) User::query()->max('id')),
            'scope' => IdentityShadowScope::ACTIVE_IDENTITY_WAVE,
        ]);
        $this->assertSame(IdentityShadowStatus::LEGACY_READ_FAILED, $this->statusFor($legacyFailed, $legacyUser, IdentityShadowFlow::PROFILE_DISPLAY));
        $this->assertFalse($legacyFailed->passed());
    }

    public function test_scoped_repeat_is_deterministic_and_pii_safe(): void
    {
        $user = $this->backfillableUser([
            'email' => 'scope-pii@example.test',
            'jmb' => $this->validJmb($this->jmbSerial++),
            'first_name' => 'ScopedPiiFirst',
            'last_name' => 'ScopedPiiLast',
            'phone' => '+38267111999',
            'address' => 'Scoped Street 1',
        ]);
        $this->seedProjectedGraph($user);

        $first = $this->runShadow(['scope' => IdentityShadowScope::ACTIVE_IDENTITY_WAVE]);
        $second = $this->runShadow(['scope' => IdentityShadowScope::ACTIVE_IDENTITY_WAVE]);

        $this->assertSame($first->aggregates['by_status'], $second->aggregates['by_status']);
        $this->assertTrue($first->passed());
        $this->assertTrue($second->passed());
        $encoded = json_encode($first->aggregateDocument());
        $this->assertStringNotContainsString('scope-pii@example.test', $encoded);
        $this->assertStringNotContainsString((string) $user->jmb, $encoded);
        $this->assertStringNotContainsString('ScopedPiiFirst', $encoded);
        $this->assertStringNotContainsString('+38267111999', $encoded);
        $this->assertStringNotContainsString('Scoped Street 1', $encoded);
        foreach ($first->outcomes as $outcome) {
            $row = $outcome->toArray();
            $this->assertArrayNotHasKey('jmb', $row);
            $this->assertArrayNotHasKey('phone', $row);
            $this->assertArrayNotHasKey('address', $row);
        }
    }

    public function test_scoped_does_not_write_and_does_not_use_default_mysql(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $beforeUser = $this->usersTableFingerprint();
        $beforeCanonical = $this->canonicalFingerprint();
        $censusMaxUserId = max(1, (int) User::query()->max('id'));
        $default = (string) config('database.default');

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query;
        });

        $report = $this->runShadow([
            'census_max_user_id' => $censusMaxUserId,
            'scope' => IdentityShadowScope::ACTIVE_IDENTITY_WAVE,
        ]);
        $this->assertTrue($report->passed());

        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression('/\\b(insert|update|delete|replace|truncate)\\b/i', $query->sql);
            $this->assertNotSame($default, $query->connectionName, $query->sql);
            $this->assertNotSame('mysql', $query->connectionName, $query->sql);
        }
        $this->assertNoEpCatalogQueries($queries);
        $this->assertSame($beforeUser, $this->usersTableFingerprint());
        $this->assertSame($beforeCanonical, $this->canonicalFingerprint());
    }

    public function test_invalid_scope_aborts_without_running(): void
    {
        $this->expectException(IdentityShadowException::class);
        $this->runShadow(['scope' => 'skip-ep']);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function runShadow(array $metadata = []): \App\Identity\Shadow\IdentityShadowReport
    {
        if (! array_key_exists('census_max_user_id', $metadata)) {
            $metadata['census_max_user_id'] = max(1, (int) User::query()->max('id'));
        }

        return (new IdentityShadowService)->run($metadata);
    }

    private function backfillableUser(array $overrides = []): User
    {
        if (! array_key_exists('jmb', $overrides)) {
            $overrides['jmb'] = $this->validJmb($this->jmbSerial++);
        }

        return $this->makeKorisnik(array_merge([
            'email' => 'step6-scope-'.uniqid('', true).'@example.test',
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

    private function statusFor(\App\Identity\Shadow\IdentityShadowReport $report, User $user, string $flow): string
    {
        foreach ($report->outcomes as $outcome) {
            if ($outcome->userId === (int) $user->id && $outcome->flowCode === $flow) {
                return $outcome->status;
            }
        }

        $this->fail('No shadow outcome for user '.$user->id.' flow '.$flow);
    }

    private function dropEpCatalogTables(): void
    {
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
    }

    /**
     * @param  list<QueryExecuted>  $queries
     */
    private function assertNoEpCatalogQueries(array $queries): void
    {
        foreach ($queries as $query) {
            $haystack = $query->sql.' '.json_encode($query->bindings);
            foreach (IdentityShadowService::CATALOG_TABLES as $table) {
                $this->assertDoesNotMatchRegularExpression(
                    '/\\b'.$table.'\\b/i',
                    $haystack,
                    'Scoped shadow must not touch '.$table.': '.$haystack
                );
            }
        }
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
                'jmb',
                'phone',
                'address',
                'city',
                'email',
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
}
