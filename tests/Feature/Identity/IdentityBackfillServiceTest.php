<?php

namespace Tests\Feature\Identity;

use App\Identity\Backfill\IdentityBackfillException;
use App\Identity\Backfill\IdentityBackfillProjector;
use App\Identity\Backfill\IdentityBackfillService;
use App\Identity\Backfill\IdentityBackfillUserOutcome;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\Census\IdentityCensusRow;
use App\Identity\Census\IdentityCensusService;
use App\Identity\IdentitySnapshot;
use App\Identity\PhysicalPersonSnapshot;
use App\Models\Application;
use App\Models\Competition;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class IdentityBackfillServiceTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    private IdentityBackfillService $service;

    private int $jmbSerial = 10;

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
        $this->service = new IdentityBackfillService;
    }

    public function test_backfillable_resident_fl_creates_platform_and_physical_person_only(): void
    {
        $user = $this->backfillableUser();
        $before = $this->legacyPayload($user);

        $report = $this->runBackfill(true);

        $this->assertSame(IdentityBackfillUserOutcome::CREATED, $report->outcomes[0]->status);
        $this->assertSame(1, PlatformIdentity::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, PhysicalPersonIdentity::query()->count());
        $this->assertSame(0, LegalEntityIdentity::query()->count());
        $this->assertSame(0, LegalEntityAuthorizedPerson::query()->count());
        $this->assertSame(0, ForeignBranchIdentity::query()->count());
        $this->assertSame(0, ForeignBranchRepresentative::query()->count());

        $platform = PlatformIdentity::query()->where('user_id', $user->id)->first();
        $this->assertSame(PlatformIdentity::SUBJECT_PHYSICAL_PERSON, $platform->subject_type);
        $this->assertSame('+38267000001', $platform->mobile_phone);

        $fl = PhysicalPersonIdentity::query()->first();
        $this->assertSame($platform->id, $fl->platform_identity_id);
        $this->assertSame('Ana', $fl->first_name);
        $this->assertSame('Anić', $fl->last_name);
        $this->assertSame(PhysicalPersonIdentity::RESIDENTIAL_RESIDENT, $fl->residential_status);
        $this->assertSame(PhysicalPersonIdentity::DOCUMENT_JMB, $fl->id_document_type);
        $this->assertSame($user->jmb, $fl->jmb);
        $this->assertNull($fl->passport_number);
        $this->assertNull($fl->residence_country_code);
        $this->assertFalse($fl->is_entrepreneur);
        $this->assertNull($fl->entrepreneur_business_name);
        $this->assertNull($fl->pib);
        $this->assertNull($fl->crps_number);
        $this->assertSame('Njegoševa 12', $fl->street_and_number);
        $this->assertSame('Kotor', $fl->city);
        $this->assertSame($before, $this->legacyPayload($user->fresh()));
    }

    public function test_users_identity_fields_remain_unchanged(): void
    {
        $user = $this->backfillableUser([
            'pib' => '12345672',
            'passport_number' => 'AB123456',
        ]);
        $before = $this->legacyPayload($user);
        $beforeHash = $this->usersTableFingerprint();

        $this->runBackfill(true);

        $this->assertSame($before, $this->legacyPayload($user->fresh()));
        $this->assertSame($beforeHash, $this->usersTableFingerprint());
        $this->assertNull(PhysicalPersonIdentity::query()->first()->pib);
        $this->assertNull(PhysicalPersonIdentity::query()->first()->passport_number);
    }

    public function test_staff_account_receives_zero_canonical_rows(): void
    {
        $this->makeKorisnik([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'jmb' => '0000000000000',
            'email' => 'staff-backfill@example.test',
        ]);

        $report = $this->runBackfill(true);

        $this->assertSame(IdentityBackfillUserOutcome::SKIPPED_NOT_ELIGIBLE, $report->outcomes[0]->status);
        $this->assertSame(IdentityCensusRow::NON_SUBJECT_ACCOUNT, $report->outcomes[0]->liveRowStatus);
        $this->assertCanonicalTablesEmpty();
    }

    public function test_city_missing_receives_zero_canonical_rows(): void
    {
        $this->backfillableUser(['city' => null, 'email' => 'city-missing@example.test']);

        $report = $this->runBackfill(true);

        $this->assertSame(IdentityBackfillUserOutcome::SKIPPED_NOT_ELIGIBLE, $report->outcomes[0]->status);
        $this->assertSame(IdentityCensusRow::MISSING_REQUIRED, $report->outcomes[0]->liveRowStatus);
        $this->assertCanonicalTablesEmpty();
    }

    public function test_invalid_jmb_receives_zero_canonical_rows(): void
    {
        $this->backfillableUser(['jmb' => '0000000000001', 'email' => 'invalid-jmb@example.test']);

        $report = $this->runBackfill(true);

        $this->assertSame(IdentityBackfillUserOutcome::SKIPPED_NOT_ELIGIBLE, $report->outcomes[0]->status);
        $this->assertSame(IdentityCensusRow::INVALID_LEGACY, $report->outcomes[0]->liveRowStatus);
        $this->assertCanonicalTablesEmpty();
    }

    public function test_nonresident_without_country_receives_zero_canonical_rows(): void
    {
        $this->backfillableUser([
            'residential_status' => 'non-resident',
            'jmb' => null,
            'passport_number' => 'AB123456',
            'email' => 'nonresident@example.test',
        ]);

        $report = $this->runBackfill(true);

        $this->assertSame(IdentityBackfillUserOutcome::SKIPPED_NOT_ELIGIBLE, $report->outcomes[0]->status);
        $this->assertSame(IdentityCensusRow::MISSING_REQUIRED, $report->outcomes[0]->liveRowStatus);
        $this->assertCanonicalTablesEmpty();
    }

    public function test_incomplete_doo_receives_zero_canonical_rows(): void
    {
        $this->backfillableUser([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'company_name' => 'Primjer DOO',
            'pib' => '12345672',
            'jmb' => null,
            'residential_status' => null,
            'email' => 'doo-backfill@example.test',
        ]);

        $report = $this->runBackfill(true);

        $this->assertSame(IdentityBackfillUserOutcome::SKIPPED_NOT_ELIGIBLE, $report->outcomes[0]->status);
        $this->assertCanonicalTablesEmpty();
    }

    public function test_incomplete_entrepreneur_receives_zero_canonical_rows(): void
    {
        $this->backfillableUser([
            'user_type' => UserType::ENTREPRENEUR,
            'company_name' => 'Radnja Ana',
            'pib' => '12345670',
            'email' => 'entrepreneur-backfill@example.test',
        ]);

        $report = $this->runBackfill(true);

        $this->assertSame(IdentityBackfillUserOutcome::SKIPPED_NOT_ELIGIBLE, $report->outcomes[0]->status);
        $this->assertCanonicalTablesEmpty();
    }

    public function test_idempotent_rerun_skips_equal_canonical_graph(): void
    {
        $user = $this->backfillableUser();
        $this->runBackfill(true);
        $before = PhysicalPersonIdentity::query()->first()->toArray();

        $report = $this->runBackfill(true);

        $this->assertSame(IdentityBackfillUserOutcome::SKIPPED_IDEMPOTENT, $report->outcomes[0]->status);
        $this->assertSame(1, PlatformIdentity::query()->count());
        $this->assertSame(1, PhysicalPersonIdentity::query()->count());
        $this->assertSame($before, PhysicalPersonIdentity::query()->first()->toArray());
        $this->assertSame($user->id, PlatformIdentity::query()->first()->user_id);
    }

    public function test_conflict_does_not_overwrite_differing_canonical_graph(): void
    {
        $user = $this->backfillableUser();
        $this->runBackfill(true);
        PhysicalPersonIdentity::query()->first()->update(['first_name' => 'Other']);

        $report = $this->runBackfill(true);

        $this->assertSame(IdentityBackfillUserOutcome::CONFLICT, $report->outcomes[0]->status);
        $this->assertContains('canonical_differs_from_projection', $report->outcomes[0]->reasonCodes);
        $this->assertSame('Other', PhysicalPersonIdentity::query()->first()->first_name);
        $this->assertTrue($report->hasBlockingOutcomes());
    }

    public function test_incomplete_existing_graph_is_conflict_and_is_not_healed(): void
    {
        $user = $this->backfillableUser();
        PlatformIdentity::query()->create([
            'user_id' => $user->id,
            'subject_type' => PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            'mobile_phone' => '+38267000001',
        ]);

        $report = $this->runBackfill(true);

        $this->assertSame(IdentityBackfillUserOutcome::CONFLICT, $report->outcomes[0]->status);
        $this->assertContains('incomplete_canonical_graph', $report->outcomes[0]->reasonCodes);
        $this->assertSame(1, PlatformIdentity::query()->count());
        $this->assertSame(0, PhysicalPersonIdentity::query()->count());
    }

    public function test_child_insert_failure_does_not_leave_orphan_or_rollback_prior_user(): void
    {
        $ok = $this->backfillableUser(['email' => 'ok-backfill@example.test']);
        $bad = $this->backfillableUser(['email' => 'bad-backfill@example.test']);

        $inner = new IdentityBackfillProjector;
        $projector = new class($inner, (int) $bad->id) extends IdentityBackfillProjector
        {
            public function __construct(
                private IdentityBackfillProjector $inner,
                private int $badId,
            ) {
                parent::__construct();
            }

            public function project(User $user, IdentityCensusRow $row): ?IdentitySnapshot
            {
                $snapshot = $this->inner->project($user, $row);
                if ($snapshot === null || (int) $user->id !== $this->badId) {
                    return $snapshot;
                }

                $person = $snapshot->physicalPerson;
                $oversized = str_repeat('X', 300);

                return new IdentitySnapshot(
                    userId: $snapshot->userId,
                    isRegisteredSubject: true,
                    subjectType: $snapshot->subjectType,
                    mobilePhone: $snapshot->mobilePhone,
                    streetAndNumber: $snapshot->streetAndNumber,
                    city: $oversized,
                    physicalPerson: new PhysicalPersonSnapshot(
                        firstName: $person->firstName,
                        lastName: $person->lastName,
                        residentialStatus: $person->residentialStatus,
                        streetAndNumber: $person->streetAndNumber,
                        city: $oversized,
                        idDocumentType: $person->idDocumentType,
                        jmb: $person->jmb,
                    ),
                );
            }
        };

        $report = (new IdentityBackfillService(
            new IdentityCensusService,
            $projector,
            new CanonicalIdentityWriter,
        ))->run(true, ['census_max_user_id' => max((int) $ok->id, (int) $bad->id)]);

        $byUser = [];
        foreach ($report->outcomes as $outcome) {
            $byUser[$outcome->userId] = $outcome->status;
        }

        $this->assertSame(IdentityBackfillUserOutcome::CREATED, $byUser[$ok->id]);
        $this->assertSame(IdentityBackfillUserOutcome::FAILED, $byUser[$bad->id]);
        $this->assertSame(1, PlatformIdentity::query()->where('user_id', $ok->id)->count());
        $this->assertSame(0, PlatformIdentity::query()->where('user_id', $bad->id)->count());
        $this->assertSame(1, PhysicalPersonIdentity::query()->count());
    }

    public function test_dry_run_writes_zero_canonical_rows(): void
    {
        $user = $this->backfillableUser();
        $before = $this->legacyPayload($user);

        $report = $this->runBackfill(false);

        $this->assertSame(IdentityBackfillUserOutcome::WOULD_CREATE, $report->outcomes[0]->status);
        $this->assertSame('dry-run', $report->metadata['mode']);
        $this->assertCanonicalTablesEmpty();
        $this->assertSame($before, $this->legacyPayload($user->fresh()));
    }

    public function test_live_reclassification_skips_user_who_is_no_longer_backfillable(): void
    {
        $user = $this->backfillableUser();
        $user->update(['city' => null]);

        $report = $this->runBackfill(true);

        $this->assertSame(IdentityBackfillUserOutcome::SKIPPED_NOT_ELIGIBLE, $report->outcomes[0]->status);
        $this->assertSame(IdentityCensusRow::MISSING_REQUIRED, $report->outcomes[0]->liveRowStatus);
        $this->assertCanonicalTablesEmpty();
    }

    public function test_kn_application_snapshot_fields_are_untouched(): void
    {
        $user = $this->backfillableUser();
        $competition = Competition::create([
            'title' => 'Step4 KN snapshot',
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
        ]);
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Test plan',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'draft',
        ]);
        $before = $application->fresh()->toArray();

        $this->runBackfill(true);

        $this->assertSame($before, $application->fresh()->toArray());
        $this->assertSame(1, PlatformIdentity::query()->where('user_id', $user->id)->count());
    }

    public function test_canonical_write_switch_on_aborts_before_write(): void
    {
        $this->backfillableUser();
        config(['identity.canonical_write' => true]);

        try {
            $this->service->run(true);
            $this->fail('Canonical write switch must abort Step 4.');
        } catch (IdentityBackfillException $e) {
            $this->assertStringContainsString('canonical identity authority', $e->getMessage());
            $this->assertCanonicalTablesEmpty();
        }
    }

    public function test_canonical_read_switch_on_aborts_before_write(): void
    {
        $this->backfillableUser();
        config(['identity.canonical_read' => true]);

        try {
            $this->service->run(true);
            $this->fail('Canonical read switch must abort Step 4.');
        } catch (IdentityBackfillException $e) {
            $this->assertStringContainsString('canonical identity authority', $e->getMessage());
            $this->assertCanonicalTablesEmpty();
        }
    }

    public function test_census_readonly_connection_as_default_is_refused(): void
    {
        $original = (string) config('database.default');
        config(['database.default' => IdentityCensusService::READONLY_CONNECTION]);

        try {
            $this->service->run(true);
            $this->fail('Census read-only connection must be refused.');
        } catch (IdentityBackfillException $e) {
            $this->assertStringContainsString('census read-only connection', $e->getMessage());
        } finally {
            config(['database.default' => $original]);
        }
    }

    public function test_missing_canonical_tables_are_detected_before_user_processing(): void
    {
        $this->assertSame([
            'platform_identities',
            'physical_person_identities',
            'legal_entity_identities',
            'legal_entity_authorized_persons',
            'foreign_branch_identities',
            'foreign_branch_representatives',
        ], IdentityBackfillService::REQUIRED_TABLES);

        $missing = IdentityBackfillService::missingRequiredTables(static fn (string $table): bool => false);
        $this->assertSame(IdentityBackfillService::REQUIRED_TABLES, $missing);

        $none = IdentityBackfillService::missingRequiredTables(static fn (string $table): bool => true);
        $this->assertSame([], $none);

        $this->backfillableUser();
        $this->assertCanonicalTablesEmpty();
        $this->assertStringContainsString(
            'platform_identities',
            'Step 4 backfill requires canonical identity tables: '.implode(', ', $missing)
        );
    }

    public function test_missing_census_max_user_id_aborts_before_write(): void
    {
        $this->backfillableUser();

        try {
            $this->service->run(true);
            $this->fail('Missing census_max_user_id must abort Step 4.');
        } catch (IdentityBackfillException $e) {
            $this->assertStringContainsString('census_max_user_id', $e->getMessage());
            $this->assertCanonicalTablesEmpty();
        }
    }

    public function test_invalid_census_max_user_id_values_abort_before_write(): void
    {
        $this->backfillableUser();

        foreach ([0, -1, '0', '-3', '1.5', 'abc', ''] as $invalid) {
            try {
                $this->service->run(true, ['census_max_user_id' => $invalid]);
                $this->fail('Invalid census_max_user_id '.var_export($invalid, true).' must abort Step 4.');
            } catch (IdentityBackfillException $e) {
                $this->assertStringContainsString('census_max_user_id', $e->getMessage());
            }
        }

        $this->assertCanonicalTablesEmpty();
    }

    public function test_user_within_boundary_and_live_backfillable_is_created(): void
    {
        $user = $this->backfillableUser();

        $report = $this->runBackfill(true, ['census_max_user_id' => (int) $user->id]);

        $this->assertSame(IdentityBackfillUserOutcome::CREATED, $report->outcomes[0]->status);
        $this->assertSame((int) $user->id, $report->metadata['census_max_user_id']);
        $this->assertSame(1, PlatformIdentity::query()->where('user_id', $user->id)->count());
    }

    public function test_user_within_boundary_but_not_backfillable_is_skipped(): void
    {
        $user = $this->backfillableUser(['city' => null]);

        $report = $this->runBackfill(true, ['census_max_user_id' => (int) $user->id]);

        $this->assertSame(IdentityBackfillUserOutcome::SKIPPED_NOT_ELIGIBLE, $report->outcomes[0]->status);
        $this->assertCanonicalTablesEmpty();
    }

    public function test_backfillable_user_above_census_boundary_is_skipped(): void
    {
        $inside = $this->backfillableUser();
        $outside = $this->backfillableUser();
        $this->assertGreaterThan($inside->id, $outside->id);

        $report = $this->runBackfill(true, ['census_max_user_id' => (int) $inside->id]);
        $byUser = [];
        foreach ($report->outcomes as $outcome) {
            $byUser[$outcome->userId] = $outcome;
        }

        $this->assertSame(IdentityBackfillUserOutcome::CREATED, $byUser[$inside->id]->status);
        $this->assertSame(IdentityBackfillUserOutcome::SKIPPED_OUTSIDE_CENSUS_BOUNDARY, $byUser[$outside->id]->status);
        $this->assertContains('outside_census_boundary', $byUser[$outside->id]->reasonCodes);
        $this->assertSame(IdentityCensusRow::BACKFILLABLE, $byUser[$outside->id]->liveRowStatus);
        $this->assertSame(1, PlatformIdentity::query()->where('user_id', $inside->id)->count());
        $this->assertSame(0, PlatformIdentity::query()->where('user_id', $outside->id)->count());
        $this->assertSame(1, $report->aggregates['by_status'][IdentityBackfillUserOutcome::SKIPPED_OUTSIDE_CENSUS_BOUNDARY]);
    }

    public function test_previously_blocked_user_now_backfillable_within_boundary_is_eligible(): void
    {
        $user = $this->backfillableUser(['city' => null]);
        $user->update(['city' => 'Kotor']);

        $report = $this->runBackfill(true, ['census_max_user_id' => (int) $user->id]);

        $this->assertSame(IdentityBackfillUserOutcome::CREATED, $report->outcomes[0]->status);
        $this->assertSame(1, PlatformIdentity::query()->where('user_id', $user->id)->count());
        $this->assertSame('Kotor', PhysicalPersonIdentity::query()->first()->city);
    }

    public function test_census_boundary_is_not_hardcoded_and_reference_date_is_metadata_only(): void
    {
        $command = (string) file_get_contents(app_path('Console/Commands/IdentityProductionBackfillCommand.php'));
        $service = (string) file_get_contents(app_path('Identity/Backfill/IdentityBackfillService.php'));
        $this->assertStringNotContainsString('67', $command);
        $this->assertStringNotContainsString('67', $service);

        $first = $this->backfillableUser();
        $second = $this->backfillableUser();
        $altBoundary = (int) $first->id;

        $report = $this->runBackfill(true, [
            'census_max_user_id' => $altBoundary,
            'census_reference_date' => '2026-09-06',
        ]);
        $byUser = [];
        foreach ($report->outcomes as $outcome) {
            $byUser[$outcome->userId] = $outcome->status;
        }

        $this->assertSame('2026-09-06', $report->metadata['census_reference_date']);
        $this->assertSame($altBoundary, $report->metadata['census_max_user_id']);
        $this->assertSame(IdentityBackfillUserOutcome::CREATED, $byUser[$first->id]);
        $this->assertSame(IdentityBackfillUserOutcome::SKIPPED_OUTSIDE_CENSUS_BOUNDARY, $byUser[$second->id]);
        $this->assertSame(0, PlatformIdentity::query()->where('user_id', $second->id)->count());
    }

    public function test_pii_is_absent_from_report_documents(): void
    {
        $user = $this->backfillableUser([
            'email' => 'pii-backfill@example.test',
            'jmb' => '0000000000000',
            'passport_number' => 'SECRETPASS1',
            'pib' => '12345672',
        ]);

        $report = $this->runBackfill(true);
        $encoded = json_encode($report->aggregateDocument(), JSON_THROW_ON_ERROR)
            .json_encode($report->outcomes[0]->toArray(), JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('pii-backfill@example.test', $encoded);
        $this->assertStringNotContainsString('0000000000000', $encoded);
        $this->assertStringNotContainsString('SECRETPASS1', $encoded);
        $this->assertStringNotContainsString('12345672', $encoded);
        $this->assertStringNotContainsString($user->password, $encoded);
        $this->assertSame($user->id, $report->outcomes[0]->userId);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function runBackfill(bool $apply = true, array $metadata = []): \App\Identity\Backfill\IdentityBackfillReport
    {
        if (! array_key_exists('census_max_user_id', $metadata)) {
            $metadata['census_max_user_id'] = max(1, (int) User::query()->max('id'));
        }

        return $this->service->run($apply, $metadata);
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
            'email' => 'step4-'.uniqid('', true).'@example.test',
        ], $overrides));
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
