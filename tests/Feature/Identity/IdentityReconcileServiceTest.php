<?php

namespace Tests\Feature\Identity;

use App\Identity\Backfill\IdentityBackfillProjector;
use App\Identity\Backfill\IdentityBackfillService;
use App\Identity\Backfill\IdentityBackfillUserOutcome;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\Census\IdentityCensusRow;
use App\Identity\Census\IdentityCensusService;
use App\Identity\IdentitySnapshot;
use App\Identity\Reconcile\IdentityReconcileException;
use App\Identity\Reconcile\IdentityReconcileService;
use App\Identity\Reconcile\IdentityReconcileUserOutcome;
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
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class IdentityReconcileServiceTest extends TestCase
{
    use MakesCanonicalUsers;
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

    public function test_dry_run_create_candidate_is_would_create(): void
    {
        $user = $this->backfillableUser();

        $report = $this->runReconcile(false);

        $this->assertSame(IdentityReconcileUserOutcome::WOULD_CREATE, $this->statusFor($report, $user));
        $this->assertTrue($report->reconcilePassed());
        $this->assertFalse($report->graphReadinessPassed());
        $this->assertCanonicalTablesEmpty();
        $this->assertCutoverInvariants($report);
    }

    public function test_apply_create_then_second_apply_is_skipped_idempotent(): void
    {
        $user = $this->backfillableUser();
        $before = $this->legacyPayload($user);

        $created = $this->runReconcile(true);
        $this->assertSame(IdentityReconcileUserOutcome::CREATED, $this->statusFor($created, $user));
        $this->assertSame(1, PlatformIdentity::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, PhysicalPersonIdentity::query()->count());
        $this->assertSame($before, $this->legacyPayload($user->fresh()));

        $again = $this->runReconcile(true);
        $this->assertSame(IdentityReconcileUserOutcome::SKIPPED_IDEMPOTENT, $this->statusFor($again, $user));
        $this->assertSame(1, PlatformIdentity::query()->count());
        $this->assertTrue($again->reconcilePassed());
        $this->assertTrue($again->graphReadinessPassed());
        $this->assertCutoverInvariants($again);
    }

    public function test_dry_run_changed_same_branch_fl_is_would_update_then_apply_updates(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $user->update(['first_name' => 'Bruno', 'address' => 'Njegoševa 88']);

        $dry = $this->runReconcile(false);
        $this->assertSame(IdentityReconcileUserOutcome::WOULD_UPDATE, $this->statusFor($dry, $user));
        $this->assertTrue($this->statusFor($dry, $user) === IdentityReconcileUserOutcome::WOULD_UPDATE);
        $this->assertTrue($dry->reconcilePassed());
        $this->assertFalse($dry->graphReadinessPassed());
        $this->assertSame('Ana', PhysicalPersonIdentity::query()->first()->first_name);

        $applied = $this->runReconcile(true);
        $this->assertSame(IdentityReconcileUserOutcome::UPDATED, $this->statusFor($applied, $user));
        $this->assertSame('Bruno', PhysicalPersonIdentity::query()->first()->first_name);
        $this->assertSame('Njegoševa 88', PhysicalPersonIdentity::query()->first()->street_and_number);
        $this->assertSame(1, PhysicalPersonIdentity::query()->count());

        $again = $this->runReconcile(true);
        $this->assertSame(IdentityReconcileUserOutcome::SKIPPED_IDEMPOTENT, $this->statusFor($again, $user));
        $this->assertTrue($again->graphReadinessPassed());
    }

    public function test_valid_jmb_change_is_same_branch_update(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $newJmb = $this->validJmb($this->jmbSerial++);
        $user->update(['jmb' => $newJmb]);

        $report = $this->runReconcile(true);

        $this->assertSame(IdentityReconcileUserOutcome::UPDATED, $this->statusFor($report, $user));
        $this->assertSame($newJmb, PhysicalPersonIdentity::query()->first()->jmb);
        $this->assertSame($newJmb, $user->fresh()->jmb);
        $this->assertSame(1, PlatformIdentity::query()->count());
    }

    public function test_leftover_fl_projector_null_fields_are_cleared_on_update(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $beforeUsers = $this->legacyPayload($user);
        $platformId = (int) PlatformIdentity::query()->where('user_id', $user->id)->value('id');
        $flId = (int) PhysicalPersonIdentity::query()->value('id');
        $firstName = PhysicalPersonIdentity::query()->first()->first_name;

        PhysicalPersonIdentity::query()->whereKey($flId)->update([
            'pib' => '12345672',
            'passport_number' => 'LEFTOVERPASS1',
            'crps_number' => '10000001',
            'residence_country_code' => 'ME',
            'entrepreneur_business_name' => 'Leftover Shop',
        ]);

        $updated = $this->runReconcile(true);
        $this->assertSame(IdentityReconcileUserOutcome::UPDATED, $this->statusFor($updated, $user));

        $fl = PhysicalPersonIdentity::query()->first();
        $this->assertSame($flId, (int) $fl->id);
        $this->assertSame($platformId, (int) $fl->platform_identity_id);
        $this->assertSame($firstName, $fl->first_name);
        $this->assertSame(PhysicalPersonIdentity::RESIDENTIAL_RESIDENT, $fl->residential_status);
        $this->assertSame(PhysicalPersonIdentity::DOCUMENT_JMB, $fl->id_document_type);
        $this->assertFalse($fl->is_entrepreneur);
        $this->assertNull($fl->pib);
        $this->assertNull($fl->passport_number);
        $this->assertNull($fl->crps_number);
        $this->assertNull($fl->residence_country_code);
        $this->assertNull($fl->entrepreneur_business_name);
        $this->assertSame(1, PlatformIdentity::query()->count());
        $this->assertSame(1, PhysicalPersonIdentity::query()->count());
        $this->assertSame(0, LegalEntityIdentity::query()->count());
        $this->assertSame($beforeUsers, $this->legacyPayload($user->fresh()));

        $again = $this->runReconcile(true);
        $this->assertSame(IdentityReconcileUserOutcome::SKIPPED_IDEMPOTENT, $this->statusFor($again, $user));
        $this->assertNull(PhysicalPersonIdentity::query()->first()->pib);
        $this->assertSame($beforeUsers, $this->legacyPayload($user->fresh()));
    }

    public function test_invalid_jmb_does_not_write(): void
    {
        $user = $this->backfillableUser(['jmb' => '0000000000001', 'email' => 'invalid-jmb-r7@example.test']);

        $report = $this->runReconcile(true);

        $this->assertSame(IdentityReconcileUserOutcome::SKIPPED_NOT_ELIGIBLE, $this->statusFor($report, $user));
        $this->assertSame(IdentityCensusRow::INVALID_LEGACY, $this->outcomeFor($report, $user)->liveRowStatus);
        $this->assertCanonicalTablesEmpty();
        $this->assertContains('not_eligible_subject_without_use_gate', $report->aggregateDocument()['cutover_blockers']);
    }

    public function test_missing_required_data_does_not_write(): void
    {
        $user = $this->backfillableUser(['city' => null, 'email' => 'missing-city-r7@example.test']);

        $report = $this->runReconcile(true);

        $this->assertSame(IdentityReconcileUserOutcome::SKIPPED_NOT_ELIGIBLE, $this->statusFor($report, $user));
        $this->assertSame(IdentityCensusRow::MISSING_REQUIRED, $this->outcomeFor($report, $user)->liveRowStatus);
        $this->assertCanonicalTablesEmpty();
        $this->assertContains('not_eligible_subject_without_use_gate', $report->aggregateDocument()['cutover_blockers']);
    }

    public function test_structurally_invalid_canonical_graph_is_canonical_invalid(): void
    {
        $user = $this->backfillableUser();
        PlatformIdentity::query()->create([
            'user_id' => $user->id,
            'subject_type' => PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            'mobile_phone' => $user->phone,
        ]);

        $report = $this->runReconcile(true);

        $this->assertSame(IdentityReconcileUserOutcome::CANONICAL_INVALID, $this->statusFor($report, $user));
        $this->assertFalse($report->reconcilePassed());
        $this->assertSame(0, PhysicalPersonIdentity::query()->count());
        $this->assertSame(1, PlatformIdentity::query()->count());
    }

    public function test_canonical_wrong_branch_is_conflict_and_retry_is_safe(): void
    {
        $user = $this->backfillableUser();
        $platform = PlatformIdentity::query()->create([
            'user_id' => $user->id,
            'subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY,
            'mobile_phone' => $user->phone,
        ]);
        LegalEntityIdentity::query()->create([
            'platform_identity_id' => $platform->id,
            'legal_form' => LegalEntityIdentity::FORM_DOO,
            'legal_name' => 'Wrong Branch DOO',
            'street_and_number' => 'Slobode 1',
            'city' => 'Kotor',
        ]);

        $first = $this->runReconcile(true);
        $second = $this->runReconcile(true);

        $this->assertSame(IdentityReconcileUserOutcome::CONFLICT, $this->statusFor($first, $user));
        $this->assertSame(IdentityReconcileUserOutcome::CONFLICT, $this->statusFor($second, $user));
        $this->assertFalse($first->reconcilePassed());
        $this->assertSame(1, PlatformIdentity::query()->count());
        $this->assertSame(1, LegalEntityIdentity::query()->count());
        $this->assertSame(0, PhysicalPersonIdentity::query()->count());
    }

    public function test_fl_to_entrepreneur_is_source_drift_without_overwrite(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $before = PhysicalPersonIdentity::query()->first()->toArray();
        $user->update([
            'user_type' => UserType::ENTREPRENEUR,
            'company_name' => 'Radnja Ana',
            'pib' => '12345672',
        ]);

        $report = $this->runReconcile(true);

        $this->assertSame(IdentityReconcileUserOutcome::SOURCE_DRIFT, $this->statusFor($report, $user));
        $this->assertTrue($report->reconcilePassed());
        $this->assertTrue($report->graphReadinessPassed());
        $this->assertSame($before, PhysicalPersonIdentity::query()->first()->toArray());
        $this->assertContains('source_drift_without_use_gate', $report->aggregateDocument()['cutover_blockers']);
        $this->assertCutoverInvariants($report);
    }

    public function test_fl_to_legal_is_source_drift_without_overwrite(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $before = PhysicalPersonIdentity::query()->first()->toArray();
        $user->update([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'company_name' => 'Primjer DOO',
            'pib' => '12345672',
            'jmb' => null,
            'residential_status' => null,
        ]);

        $report = $this->runReconcile(true);

        $this->assertSame(IdentityReconcileUserOutcome::SOURCE_DRIFT, $this->statusFor($report, $user));
        $this->assertTrue($report->reconcilePassed());
        $this->assertSame($before, PhysicalPersonIdentity::query()->first()->toArray());
        $this->assertSame(0, LegalEntityIdentity::query()->count());
    }

    public function test_resident_to_nonresident_is_source_drift_without_overwrite(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $before = PhysicalPersonIdentity::query()->first()->toArray();
        $user->update([
            'residential_status' => 'non-resident',
            'jmb' => null,
            'passport_number' => 'AB123456',
        ]);

        $report = $this->runReconcile(true);

        $this->assertSame(IdentityReconcileUserOutcome::SOURCE_DRIFT, $this->statusFor($report, $user));
        $this->assertTrue($report->reconcilePassed());
        $this->assertSame($before, PhysicalPersonIdentity::query()->first()->toArray());
    }

    public function test_non_subject_without_graph_is_skipped_not_eligible_and_not_identity_blocker(): void
    {
        $user = $this->makeKorisnik([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'jmb' => '0000000000000',
            'email' => 'staff-reconcile@example.test',
        ]);

        $report = $this->runReconcile(true);
        $document = $report->aggregateDocument();

        $this->assertSame(IdentityReconcileUserOutcome::SKIPPED_NOT_ELIGIBLE, $this->statusFor($report, $user));
        $this->assertSame(IdentityCensusRow::NON_SUBJECT_ACCOUNT, $this->outcomeFor($report, $user)->liveRowStatus);
        $this->assertCanonicalTablesEmpty();
        $this->assertNotContains('not_eligible_subject_without_use_gate', $document['cutover_blockers']);
        $this->assertTrue($report->reconcilePassed());
        $this->assertCutoverInvariants($report);
    }

    public function test_user_above_old_wave_but_inside_supplied_boundary_is_create_candidate(): void
    {
        $user = $this->backfillableUser(['email' => 'high-id-r7@example.test']);
        if ((int) $user->id <= 67) {
            DB::table('users')->where('id', $user->id)->update(['id' => 80]);
            $user = User::query()->findOrFail(80);
        }
        $this->assertGreaterThan(67, (int) $user->id);

        $report = $this->runReconcile(false, ['census_max_user_id' => (int) $user->id]);

        $this->assertSame(IdentityReconcileUserOutcome::WOULD_CREATE, $this->statusFor($report, $user));
        $source = (string) file_get_contents(app_path('Identity/Reconcile/IdentityReconcileService.php'));
        $this->assertDoesNotMatchRegularExpression('/census_max_user_id\\s*=\\s*17/', $source);
        $this->assertDoesNotMatchRegularExpression('/census_max_user_id\\s*=\\s*48/', $source);
        $this->assertDoesNotMatchRegularExpression('/census_max_user_id\\s*=\\s*67/', $source);
    }

    public function test_live_max_above_supplied_boundary_globally_aborts(): void
    {
        $inside = $this->backfillableUser(['email' => 'inside-r7@example.test']);
        $this->backfillableUser(['email' => 'outside-r7@example.test']);

        try {
            $this->runReconcile(false, ['census_max_user_id' => (int) $inside->id]);
            $this->fail('live_max above boundary must globally abort.');
        } catch (IdentityReconcileException $e) {
            $this->assertStringContainsString('live_max_user_id exceeds', $e->getMessage());
            $this->assertCanonicalTablesEmpty();
        }
    }

    public function test_dry_run_performs_zero_writes_and_zero_default_mysql_queries(): void
    {
        $user = $this->backfillableUser();
        $beforeUser = $this->usersTableFingerprint();
        $beforeCanonical = $this->canonicalFingerprint();
        $censusMaxUserId = max(1, (int) User::query()->max('id'));

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query;
        });

        $report = $this->runReconcile(false, ['census_max_user_id' => $censusMaxUserId]);
        $this->assertSame(IdentityReconcileUserOutcome::WOULD_CREATE, $this->statusFor($report, $user));

        $this->assertNotEmpty($queries);
        $default = (string) config('database.default');
        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression('/\\b(insert|update|delete|replace|truncate)\\b/i', $query->sql);
            $this->assertNotSame($default, $query->connectionName, $query->sql);
            $this->assertNotSame('mysql', $query->connectionName, $query->sql);
        }

        $this->assertSame($beforeUser, $this->usersTableFingerprint());
        $this->assertSame($beforeCanonical, $this->canonicalFingerprint());
    }

    public function test_apply_writes_zero_users_identity_columns(): void
    {
        $user = $this->backfillableUser();
        $before = $this->legacyPayload($user);
        $beforeHash = $this->usersTableFingerprint();

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query;
        });

        $report = $this->runReconcile(true);
        $this->assertSame(IdentityReconcileUserOutcome::CREATED, $this->statusFor($report, $user));

        foreach ($queries as $query) {
            $sql = $query->sql;
            if ((bool) preg_match('/\\bselect\\b/i', $sql) && (bool) preg_match('/for update/i', $sql)) {
                continue;
            }
            if ((bool) preg_match('/\\busers\\b/i', $sql) && (bool) preg_match('/\\b(insert|update|delete|replace|truncate)\\b/i', $sql)) {
                $this->fail('Apply mutated users: '.$sql);
            }
        }

        $this->assertSame($before, $this->legacyPayload($user->fresh()));
        $this->assertSame($beforeHash, $this->usersTableFingerprint());
    }

    public function test_apply_uses_latest_projection_after_locked_reread(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        $user->update(['first_name' => 'LockedName']);

        $inner = new IdentityBackfillProjector;
        $targetId = (int) $user->id;
        $projector = new class($inner, $targetId) extends IdentityBackfillProjector
        {
            public array $seen = [];

            public function __construct(
                private IdentityBackfillProjector $inner,
                private int $targetId,
            ) {
                parent::__construct();
            }

            public function project(User $user, IdentityCensusRow $row): ?IdentitySnapshot
            {
                if ((int) $user->id === $this->targetId) {
                    $this->seen[] = $user->first_name;
                }

                return $this->inner->project($user, $row);
            }
        };

        $report = (new IdentityReconcileService(
            new IdentityCensusService,
            $projector,
            new CanonicalIdentityWriter,
        ))->run(true, ['census_max_user_id' => max(1, (int) User::query()->max('id'))]);

        $this->assertSame(IdentityReconcileUserOutcome::UPDATED, $this->statusFor($report, $user));
        $this->assertSame(['LockedName'], $projector->seen);
        $this->assertSame('LockedName', PhysicalPersonIdentity::query()->first()->first_name);
    }

    public function test_aggregate_and_rows_are_pii_safe_and_ep_gate_is_open_without_ep_tables(): void
    {
        $user = $this->backfillableUser([
            'first_name' => 'RecPiiFirst',
            'last_name' => 'RecPiiLast',
            'name' => 'RecPiiFirst RecPiiLast',
            'email' => 'reconcile-pii@example.test',
            'phone' => '+38267999991',
            'address' => 'Reconcile Pii Street 42',
            'city' => 'PiiCityX',
            'passport_number' => 'RECPIIPASSPORT1',
            'pib' => '12345672',
        ]);
        $this->seedProjectedGraph($user);

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query;
        });

        $report = $this->runReconcile(false);
        $encoded = json_encode($report->aggregateDocument(), JSON_UNESCAPED_UNICODE);
        foreach ($report->outcomes as $outcome) {
            $encoded .= json_encode($outcome->toArray(), JSON_UNESCAPED_UNICODE);
        }

        $this->assertIsString($encoded);
        foreach ([
            'RecPiiFirst',
            'RecPiiLast',
            'reconcile-pii@example.test',
            (string) $user->jmb,
            '+38267999991',
            'Reconcile Pii Street 42',
            'PiiCityX',
            'RECPIIPASSPORT1',
            '12345672',
        ] as $secret) {
            $this->assertStringNotContainsString($secret, $encoded);
        }

        $row = $this->outcomeFor($report, $user)->toArray();
        $this->assertArrayHasKey('projection_fingerprint_sha256', $row);
        $this->assertArrayHasKey('canonical_fingerprint_sha256', $row);
        $this->assertArrayNotHasKey('jmb', $row);
        $this->assertArrayNotHasKey('first_name', $row);
        $this->assertArrayNotHasKey('email', $row);

        $document = $report->aggregateDocument();
        $this->assertSame('OPEN', $document['ep_gate']['status']);
        $this->assertTrue($document['ep_gate']['deferred']);
        $this->assertSame('ep_module_undeployed', $document['ep_gate']['reason']);
        $this->assertFalse($document['cutover_ready']);
        $this->assertFalse($document['population_boundary_protected']);
        $this->assertContains('ep_gate_open', $document['cutover_blockers']);
        $this->assertContains('step8_not_authorized', $document['cutover_blockers']);

        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression(
                '/\\b(payment_types|payment_accounts|payment_type_availabilities|payment_account_availabilities)\\b/i',
                $query->sql
            );
        }
    }

    public function test_dry_run_twice_is_stable(): void
    {
        $this->backfillableUser();
        $this->makeKorisnik([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'email' => 'stable-staff-r7@example.test',
            'jmb' => '0000000000000',
        ]);

        $first = $this->runReconcile(false);
        $second = $this->runReconcile(false);

        $this->assertSame($first->aggregates['by_status'], $second->aggregates['by_status']);
        $this->assertSame($first->aggregates['live_row_status'], $second->aggregates['live_row_status']);
    }

    public function test_step4_mismatch_remains_conflict_not_update(): void
    {
        $user = $this->backfillableUser();
        $this->seedProjectedGraph($user);
        PhysicalPersonIdentity::query()->first()->update(['first_name' => 'Other']);

        $step4 = (new IdentityBackfillService)->run(true, [
            'census_max_user_id' => max(1, (int) User::query()->max('id')),
        ]);

        $this->assertSame(IdentityBackfillUserOutcome::CONFLICT, $step4->outcomes[0]->status);
        $this->assertSame('Other', PhysicalPersonIdentity::query()->first()->first_name);
    }

    public function test_canonical_write_or_read_on_aborts(): void
    {
        $this->backfillableUser();
        config(['identity.canonical_write' => true]);
        try {
            $this->runReconcile(false);
            $this->fail('canonical_write ON must abort.');
        } catch (IdentityReconcileException $e) {
            $this->assertStringContainsString('canonical identity authority', $e->getMessage());
            $this->assertCanonicalTablesEmpty();
        }

        config(['identity.canonical_write' => false, 'identity.canonical_read' => true]);
        try {
            $this->runReconcile(false);
            $this->fail('canonical_read ON must abort.');
        } catch (IdentityReconcileException $e) {
            $this->assertStringContainsString('canonical identity authority', $e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function runReconcile(bool $apply, array $metadata = []): \App\Identity\Reconcile\IdentityReconcileReport
    {
        if (! array_key_exists('census_max_user_id', $metadata)) {
            $metadata['census_max_user_id'] = max(1, (int) User::query()->max('id'));
        }

        return (new IdentityReconcileService)->run($apply, $metadata);
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
            'email' => 'step7-'.uniqid('', true).'@example.test',
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

    private function statusFor(\App\Identity\Reconcile\IdentityReconcileReport $report, User $user): string
    {
        return $this->outcomeFor($report, $user)->status;
    }

    private function outcomeFor(\App\Identity\Reconcile\IdentityReconcileReport $report, User $user): IdentityReconcileUserOutcome
    {
        foreach ($report->outcomes as $outcome) {
            if ($outcome->userId === (int) $user->id) {
                return $outcome;
            }
        }

        $this->fail('No reconcile outcome for user '.$user->id);
    }

    private function assertCutoverInvariants(\App\Identity\Reconcile\IdentityReconcileReport $report): void
    {
        $document = $report->aggregateDocument();
        $this->assertFalse($document['cutover_ready']);
        $this->assertFalse($document['population_boundary_protected']);
        $this->assertFalse($document['metadata']['cutover_ready']);
        $this->assertFalse($document['metadata']['population_boundary_protected']);
        $this->assertSame('DK-TS-002 D15 Step 7', $document['spec']);
        $this->assertContains('population_boundary_unprotected', $document['cutover_blockers']);
        $this->assertContains('incompatible_legacy_readers_authoritative', $document['cutover_blockers']);
        $this->assertContains('legacy_identity_writers_active', $document['cutover_blockers']);
        $source = (string) file_get_contents(app_path('Identity/Reconcile/IdentityReconcileReport.php'))
            .(string) file_get_contents(app_path('Console/Commands/IdentityProductionReconcileCommand.php'));
        $this->assertStringNotContainsString('cutover_ready = true', $source);
        $this->assertStringNotContainsString('cutover_ready=true', $source);
        $this->assertStringNotContainsString('--final', $source);
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

    /**
     * @return array<string, mixed>
     */
    private function legacyPayload(User $user): array
    {
        $payload = $user->only([
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
        ]);
        $payload['email_verified_at'] = $user->email_verified_at?->getTimestamp();

        return $payload;
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
        $rows = [
            PlatformIdentity::query()->orderBy('id')->get()->toArray(),
            PhysicalPersonIdentity::query()->orderBy('id')->get()->toArray(),
            LegalEntityIdentity::query()->orderBy('id')->get()->toArray(),
            LegalEntityAuthorizedPerson::query()->orderBy('id')->get()->toArray(),
            ForeignBranchIdentity::query()->orderBy('id')->get()->toArray(),
            ForeignBranchRepresentative::query()->orderBy('id')->get()->toArray(),
        ];

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
}
