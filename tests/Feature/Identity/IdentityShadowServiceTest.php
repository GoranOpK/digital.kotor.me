<?php

namespace Tests\Feature\Identity;

use App\Identity\Backfill\IdentityBackfillProjector;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\Census\IdentityCensusService;
use App\Identity\IdentitySnapshot;
use App\Identity\PhysicalPersonSnapshot;
use App\Identity\Shadow\Comparators\EpAvailabilityComparator;
use App\Identity\Shadow\IdentityShadowCanonicalLoader;
use App\Identity\Shadow\IdentityShadowException;
use App\Identity\Shadow\IdentityShadowFlow;
use App\Identity\Shadow\IdentityShadowService;
use App\Identity\Shadow\IdentityShadowStatus;
use App\Models\ForeignBranchIdentity;
use App\Models\ForeignBranchRepresentative;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\TestCase;

class IdentityShadowServiceTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesSyntheticPaymentCatalog;
    use RefreshDatabase;

    private int $jmbSerial = 80;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->assertSame(false, config('identity.canonical_write'));
        $this->assertSame(false, config('identity.canonical_read'));
        $this->bindDedicatedReadOnlyConnection();
    }

    public function test_eligible_fl_matches_all_required_flows_and_denominator_is_derived(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $this->syntheticUsablePair($user, 'syn-shadow-svc', 'SYN-SHADOW-SVC-00000001');
        $skipped = $this->backfillableUser(['city' => null, 'email' => 'shadow-skip@example.test']);
        $beforeUser = $this->usersTableFingerprint();
        $beforeCanonical = $this->canonicalFingerprint();

        $report = $this->runShadow();

        $this->assertTrue($report->passed());
        $this->assertSame(1, $report->aggregates['eligible_user_count']);
        $this->assertSame(count(IdentityShadowFlow::REQUIRED), $report->aggregates['required_flow_count']);
        $this->assertSame(
            $report->aggregates['evaluable_comparison_count'],
            $report->aggregates['expected_match_comparisons']
        );
        $this->assertSame(
            $report->aggregates['eligible_user_count'] * $report->aggregates['required_flow_count'],
            $report->aggregates['expected_match_comparisons']
        );
        $this->assertGreaterThan(0, $report->aggregates['ep_compared_decision_count']);
        $this->assertSame(
            $report->aggregates['expected_match_comparisons'],
            $report->aggregates['by_status'][IdentityShadowStatus::MATCH]
        );
        $ep = $this->outcomeFor($report, $user, IdentityShadowFlow::EP_AVAILABILITY);
        $this->assertSame(IdentityShadowStatus::MATCH, $ep->status);
        $this->assertGreaterThan(0, $ep->coverage['compared_decision_count']);
        $this->assertNotSame(17, $report->aggregates['eligible_user_count']);
        foreach (IdentityShadowFlow::REQUIRED as $flow) {
            $this->assertSame(IdentityShadowStatus::MATCH, $this->statusFor($report, $user, $flow));
            $this->assertSame(1, $report->aggregates['by_flow'][$flow][IdentityShadowStatus::MATCH]);
        }
        $this->assertSame(IdentityShadowStatus::NOT_SHADOW_ELIGIBLE, $this->statusFor($report, $skipped, IdentityShadowFlow::EP_AVAILABILITY));
        $this->assertSame($beforeUser, $this->usersTableFingerprint());
        $this->assertSame($beforeCanonical, $this->canonicalFingerprint());
        $this->assertFalse($report->metadata['canonical_read']);
        $this->assertFalse($report->metadata['canonical_write']);
        $this->assertSame('identity_census_readonly', $report->metadata['connection']);
        $this->assertSame('shadow', $report->metadata['mode']);
    }

    public function test_leftover_pib_on_fl_does_not_fail(): void
    {
        $user = $this->backfillableUser(['pib' => '12345672']);
        $this->seedProjectedGraph($user);
        $this->syntheticUsablePair($user, 'syn-shadow-pib', 'SYN-SHADOW-PIB-00000001');

        $report = $this->runShadow();

        $this->assertTrue($report->passed());
        foreach (IdentityShadowFlow::REQUIRED as $flow) {
            $outcome = $this->outcomeFor($report, $user, $flow);
            $this->assertSame(IdentityShadowStatus::MATCH, $outcome->status);
            $this->assertContains('not_in_canonical_contract', $outcome->reasonCodes);
        }
    }

    public function test_forced_ep_semantic_mismatch_via_canonical_snapshot(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $this->syntheticUsablePair($user, 'syn-shadow-ep-mm', 'SYN-SHADOW-EP-MM-00000001');

        $loader = new class extends IdentityShadowCanonicalLoader
        {
            public function snapshotFromValidStep4Graph(array $graph): IdentitySnapshot
            {
                $snapshot = parent::snapshotFromValidStep4Graph($graph);
                $fl = $snapshot->physicalPerson;
                assert($fl !== null);

                return new IdentitySnapshot(
                    userId: $snapshot->userId,
                    isRegisteredSubject: $snapshot->isRegisteredSubject,
                    subjectType: $snapshot->subjectType,
                    mobilePhone: $snapshot->mobilePhone,
                    streetAndNumber: $snapshot->streetAndNumber,
                    city: $snapshot->city,
                    physicalPerson: new PhysicalPersonSnapshot(
                        firstName: $fl->firstName,
                        lastName: $fl->lastName,
                        residentialStatus: PhysicalPersonIdentity::RESIDENTIAL_NON_RESIDENT,
                        streetAndNumber: $fl->streetAndNumber,
                        city: $fl->city,
                        idDocumentType: $fl->idDocumentType,
                        jmb: $fl->jmb,
                        passportNumber: $fl->passportNumber,
                        residenceCountryCode: $fl->residenceCountryCode,
                        isEntrepreneur: $fl->isEntrepreneur,
                        entrepreneurBusinessName: $fl->entrepreneurBusinessName,
                        pib: $fl->pib,
                        crpsNumber: $fl->crpsNumber,
                    ),
                );
            }
        };

        $report = (new IdentityShadowService(loader: $loader))->run([
            'census_max_user_id' => max(1, (int) User::query()->max('id')),
        ]);

        $this->assertSame(IdentityShadowStatus::MISMATCH, $this->statusFor($report, $user, IdentityShadowFlow::EP_AVAILABILITY));
        $this->assertContains('eligibility', $this->outcomeFor($report, $user, IdentityShadowFlow::EP_AVAILABILITY)->reasonCodes);
        $this->assertFalse($report->passed());
        $this->assertSame(1, PlatformIdentity::query()->count());
    }

    public function test_in_boundary_backfillable_missing_graph_is_missing_canonical(): void
    {
        $user = $this->backfillableUser();

        $report = $this->runShadow();

        $this->assertSame(IdentityShadowStatus::MISSING_CANONICAL, $this->statusFor($report, $user, IdentityShadowFlow::PROFILE_DISPLAY));
        $this->assertFalse($report->passed());
        $this->assertSame(0, $report->aggregates['eligible_user_count']);
        $this->assertCanonicalTablesEmpty();
    }

    public function test_invalid_graph_is_canonical_invalid(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        PlatformIdentity::query()->where('user_id', $user->id)->update([
            'subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY,
        ]);

        $report = $this->runShadow();

        $this->assertSame(IdentityShadowStatus::CANONICAL_INVALID, $this->statusFor($report, $user, IdentityShadowFlow::KN_APPLICANT_TYPE));
        $this->assertFalse($report->passed());
    }

    public function test_canonical_loader_exception_is_canonical_read_failed(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);

        $loader = new class extends IdentityShadowCanonicalLoader
        {
            public function snapshotFromValidStep4Graph(array $graph): IdentitySnapshot
            {
                throw new IdentityShadowException('canonical loader failed');
            }
        };

        $report = (new IdentityShadowService(loader: $loader))->run([
            'census_max_user_id' => max(1, (int) User::query()->max('id')),
        ]);

        $this->assertSame(IdentityShadowStatus::CANONICAL_READ_FAILED, $this->statusFor($report, $user, IdentityShadowFlow::DASHBOARD_DISPLAY));
        $this->assertFalse($report->passed());
    }

    public function test_legacy_comparator_exception_is_legacy_read_failed(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);

        $ep = new class extends EpAvailabilityComparator
        {
            public function compare(User $user, IdentitySnapshot $canonical, Collection $types): \App\Identity\Shadow\IdentityShadowCompareResult
            {
                throw new \RuntimeException('legacy comparator failed');
            }
        };

        $report = (new IdentityShadowService(epAvailability: $ep))->run([
            'census_max_user_id' => max(1, (int) User::query()->max('id')),
        ]);

        $this->assertSame(IdentityShadowStatus::LEGACY_READ_FAILED, $this->statusFor($report, $user, IdentityShadowFlow::EP_AVAILABILITY));
        $this->assertSame(IdentityShadowStatus::MATCH, $this->statusFor($report, $user, IdentityShadowFlow::KN_APPLICANT_TYPE));
        $this->assertFalse($report->passed());
    }

    public function test_non_eligible_and_staff_are_not_shadow_eligible(): void
    {
        $this->backfillableUser(['city' => null, 'email' => 'shadow-missing-req@example.test']);
        $this->makeKorisnik([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'jmb' => '0000000000000',
            'email' => 'staff-shadow@example.test',
        ]);

        $report = $this->runShadow();

        $this->assertTrue($report->passed());
        $this->assertSame(0, $report->aggregates['eligible_user_count']);
        foreach ($report->outcomes as $outcome) {
            $this->assertSame(IdentityShadowStatus::NOT_SHADOW_ELIGIBLE, $outcome->status);
        }
        $this->assertSame(0, $report->aggregates['eligible_user_count']);
    }

    public function test_outside_boundary_is_not_shadow_eligible(): void
    {
        $inside = $this->backfillableUser();
        $this->seedProjectedGraph($inside);
        $outside = $this->backfillableUser();

        $report = $this->runShadow(['census_max_user_id' => (int) $inside->id]);

        $this->assertSame(IdentityShadowStatus::MATCH, $this->statusFor($report, $inside, IdentityShadowFlow::PROFILE_DISPLAY));
        $this->assertSame(IdentityShadowStatus::NOT_EVALUABLE, $this->statusFor($report, $inside, IdentityShadowFlow::EP_AVAILABILITY));
        $this->assertContains('no_catalog_decisions', $this->outcomeFor($report, $inside, IdentityShadowFlow::EP_AVAILABILITY)->reasonCodes);
        $this->assertSame(0, $this->outcomeFor($report, $inside, IdentityShadowFlow::EP_AVAILABILITY)->coverage['compared_decision_count']);
        $this->assertSame(4, $report->aggregates['expected_match_comparisons']);
        $this->assertSame(4, $report->aggregates['evaluable_comparison_count']);
        $this->assertSame(0, $report->aggregates['ep_compared_decision_count']);
        $this->assertSame(IdentityShadowStatus::NOT_SHADOW_ELIGIBLE, $this->statusFor($report, $outside, IdentityShadowFlow::PROFILE_DISPLAY));
        $this->assertContains('outside_census_boundary', $this->outcomeFor($report, $outside, IdentityShadowFlow::PROFILE_DISPLAY)->reasonCodes);
        $this->assertTrue($report->passed());
    }

    public function test_source_drift_is_mismatch(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $user->update(['first_name' => 'Changed']);

        $report = $this->runShadow();

        foreach (IdentityShadowFlow::REQUIRED as $flow) {
            $outcome = $this->outcomeFor($report, $user, $flow);
            $this->assertSame(IdentityShadowStatus::MISMATCH, $outcome->status);
            $this->assertContains('source_drift', $outcome->reasonCodes);
        }
        $this->assertFalse($report->passed());
        $this->assertSame(1, $report->aggregates['eligible_user_count']);
        $this->assertSame(5, $report->aggregates['evaluable_comparison_count']);
        $this->assertSame(5, $report->aggregates['expected_match_comparisons']);
        $this->assertSame(0, $report->aggregates['by_status'][IdentityShadowStatus::MATCH]);
    }

    public function test_empty_catalog_ep_is_not_evaluable_and_excluded_from_pass_denominator(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);

        $report = $this->runShadow();

        $ep = $this->outcomeFor($report, $user, IdentityShadowFlow::EP_AVAILABILITY);
        $this->assertSame(IdentityShadowStatus::NOT_EVALUABLE, $ep->status);
        $this->assertContains('no_catalog_decisions', $ep->reasonCodes);
        $this->assertSame(0, $ep->coverage['evaluated_type_count']);
        $this->assertSame(0, $ep->coverage['evaluated_account_count']);
        $this->assertSame(0, $ep->coverage['compared_decision_count']);
        $this->assertSame(1, $report->aggregates['by_status'][IdentityShadowStatus::NOT_EVALUABLE]);
        $this->assertSame(4, $report->aggregates['by_status'][IdentityShadowStatus::MATCH]);
        $this->assertSame(4, $report->aggregates['expected_match_comparisons']);
        $this->assertSame(4, $report->aggregates['evaluable_comparison_count']);
        $this->assertSame(0, $report->aggregates['ep_compared_decision_count']);
        $this->assertNotSame(
            $report->aggregates['eligible_user_count'] * $report->aggregates['required_flow_count'],
            $report->aggregates['expected_match_comparisons']
        );
        foreach ([
            IdentityShadowFlow::KN_APPLICANT_TYPE,
            IdentityShadowFlow::KN_APPLICATION_PREFILL,
            IdentityShadowFlow::PROFILE_DISPLAY,
            IdentityShadowFlow::DASHBOARD_DISPLAY,
        ] as $flow) {
            $this->assertSame(IdentityShadowStatus::MATCH, $this->statusFor($report, $user, $flow));
        }
        $this->assertTrue($report->passed());
    }

    public function test_repeat_shadow_produces_the_same_semantic_result(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $this->syntheticUsablePair($user, 'syn-shadow-rep', 'SYN-SHADOW-REP-00000001');
        $skipped = $this->backfillableUser(['city' => null, 'email' => 'shadow-repeat-skip@example.test']);

        $first = $this->runShadow();
        $second = $this->runShadow();

        $this->assertSame($first->aggregates['by_status'], $second->aggregates['by_status']);
        $this->assertSame($first->aggregates['eligible_user_count'], $second->aggregates['eligible_user_count']);
        $this->assertTrue($first->passed());
        $this->assertTrue($second->passed());
        $this->assertSame($this->statusFor($first, $skipped, IdentityShadowFlow::EP_AVAILABILITY), $this->statusFor($second, $skipped, IdentityShadowFlow::EP_AVAILABILITY));
    }

    public function test_all_identity_and_catalog_reads_use_readonly_connection_without_writes(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $this->syntheticUsablePair($user, 'syn-shadow-ro', 'SYN-SHADOW-RO-00000001');
        $beforeUser = $this->usersTableFingerprint();
        $beforeCanonical = $this->canonicalFingerprint();
        $censusMaxUserId = max(1, (int) User::query()->max('id'));

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query;
        });

        $report = $this->runShadow(['census_max_user_id' => $censusMaxUserId]);
        $this->assertTrue($report->passed());

        $this->assertNotEmpty($queries);
        $default = (string) config('database.default');
        $this->assertNotSame('', $default);
        $this->assertNotSame(IdentityCensusService::READONLY_CONNECTION, $default);

        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression('/\\b(insert|update|delete|replace|truncate)\\b/i', $query->sql);
            $this->assertNotSame(
                $default,
                $query->connectionName,
                'Step 6 shadow performed an application query on database.default: '.$query->sql
            );
            $this->assertNotSame(
                'mysql',
                $query->connectionName,
                'Step 6 shadow performed an application query on mysql: '.$query->sql
            );
        }

        $watched = array_merge(IdentityShadowService::SOURCE_TABLES, IdentityShadowService::REQUIRED_TABLES, IdentityShadowService::CATALOG_TABLES);
        foreach ($watched as $table) {
            $saw = false;
            foreach ($queries as $query) {
                if ((bool) preg_match('/\\b'.$table.'\\b/i', $query->sql)) {
                    $saw = true;
                    $this->assertSame(
                        IdentityCensusService::READONLY_CONNECTION,
                        $query->connectionName,
                        $table.': '.$query->sql
                    );
                }
            }
            $this->assertTrue($saw, 'Expected a read of '.$table.' on the dedicated connection.');
        }

        $this->assertSame($beforeUser, $this->usersTableFingerprint());
        $this->assertSame($beforeCanonical, $this->canonicalFingerprint());
    }

    public function test_authority_on_aborts_and_missing_boundary_helper(): void
    {
        config(['identity.canonical_read' => true]);
        $this->expectException(IdentityShadowException::class);
        $this->runShadow();
    }

    public function test_canonical_write_on_aborts(): void
    {
        config(['identity.canonical_write' => true]);
        $this->expectException(IdentityShadowException::class);
        $this->runShadow();
    }

    public function test_missing_required_tables_helper_and_source_does_not_write_or_enable_switches(): void
    {
        $this->assertSame(
            array_merge(IdentityShadowService::REQUIRED_TABLES, IdentityShadowService::CATALOG_TABLES),
            IdentityShadowService::missingRequiredTables(static fn (): bool => false)
        );
        $this->assertSame([], IdentityShadowService::missingRequiredTables(static fn (): bool => true));
        $this->assertSame(
            IdentityShadowService::REQUIRED_TABLES,
            IdentityShadowService::missingRequiredTables(static fn (): bool => false, false)
        );

        $source = (string) file_get_contents(app_path('Identity/Shadow/IdentityShadowService.php'))
            .(string) file_get_contents(app_path('Console/Commands/IdentityProductionShadowCommand.php'));
        $this->assertStringNotContainsString('CanonicalIdentityWriter', $source);
        $this->assertStringNotContainsString('IDENTITY_CANONICAL_READ', $source);
        $this->assertStringNotContainsString('IDENTITY_CANONICAL_SHADOW', $source);
        $this->assertStringNotContainsString('--apply', $source);
        $this->assertStringNotContainsString('--repair', $source);
        $this->assertStringNotContainsString('--reconcile', $source);
        $this->assertStringNotContainsString('--skip-ep', $source);
        $this->assertStringNotContainsString('--ignore-missing', $source);
        $this->assertStringNotContainsString('--allow-missing-tables', $source);
    }

    public function test_no_http_wiring_of_shadow_or_canonical_reader(): void
    {
        $roots = ['app/Http', 'app/Services', 'app/Support', 'routes'];
        $tokens = [
            'IdentityShadow',
            'identity:shadow-production',
            'CanonicalIdentityReader',
            'IdentitySnapshot',
        ];
        foreach ($roots as $root) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(base_path($root), \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                $contents = (string) file_get_contents($file->getPathname());
                foreach ($tokens as $token) {
                    $this->assertStringNotContainsString(
                        $token,
                        $contents,
                        $file->getPathname().' must not reference '.$token
                    );
                }
            }
        }
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function backfillableUser(array $overrides = []): User
    {
        if (! array_key_exists('jmb', $overrides)) {
            $overrides['jmb'] = $this->validJmb($this->jmbSerial++);
        }

        return $this->makeKorisnik(array_merge([
            'email' => 'step6-'.uniqid('', true).'@example.test',
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
        return $this->outcomeFor($report, $user, $flow)->status;
    }

    private function outcomeFor(\App\Identity\Shadow\IdentityShadowReport $report, User $user, string $flow): \App\Identity\Shadow\IdentityShadowUserOutcome
    {
        foreach ($report->outcomes as $outcome) {
            if ($outcome->userId === (int) $user->id && $outcome->flowCode === $flow) {
                return $outcome;
            }
        }

        $this->fail('No shadow outcome for user '.$user->id.' flow '.$flow);
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
