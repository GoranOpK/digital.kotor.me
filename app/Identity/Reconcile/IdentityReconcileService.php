<?php

namespace App\Identity\Reconcile;

use App\Identity\Backfill\IdentityBackfillProjector;
use App\Identity\CanonicalIdentityWriteException;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\Census\IdentityCensusRow;
use App\Identity\Census\IdentityCensusService;
use App\Identity\IdentityCanonicalGraphFingerprint;
use App\Identity\IdentitySnapshot;
use App\Models\ForeignBranchIdentity;
use App\Models\ForeignBranchRepresentative;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * D15 Step 7 first-wave identity reconcile. R0. Per-user apply transactions.
 * Does not change Step 4 create-only semantics. Does not authorize cutover.
 */
final class IdentityReconcileService
{
    public const CHUNK_SIZE = 100;

    public const GRAPH_NONE = 'none';

    public const GRAPH_VALID_STEP4_FL = 'valid_step4_fl';

    public const GRAPH_INVALID = 'invalid';

    public const GRAPH_UNEXPECTED = 'unexpected';

    /**
     * @var list<string>
     */
    public const REQUIRED_TABLES = [
        'users',
        'roles',
        'platform_identities',
        'physical_person_identities',
        'legal_entity_identities',
        'legal_entity_authorized_persons',
        'foreign_branch_identities',
        'foreign_branch_representatives',
    ];

    /**
     * Observational cutover blocker inputs. Step 7 never closes these.
     *
     * @var list<string>
     */
    public const STANDING_CUTOVER_BLOCKERS = [
        'ep_gate_open',
        'incompatible_legacy_readers_authoritative',
        'legacy_identity_writers_active',
        'population_boundary_unprotected',
        'step8_not_authorized',
    ];

    /**
     * @var list<string>
     */
    private const READY_APPLY_STATUSES = [
        IdentityReconcileUserOutcome::CREATED,
        IdentityReconcileUserOutcome::UPDATED,
        IdentityReconcileUserOutcome::SKIPPED_IDEMPOTENT,
    ];

    /**
     * @var list<string>
     */
    private const READY_DRY_RUN_STATUSES = [
        IdentityReconcileUserOutcome::SKIPPED_IDEMPOTENT,
    ];

    public function __construct(
        private readonly IdentityCensusService $census = new IdentityCensusService,
        private readonly IdentityBackfillProjector $projector = new IdentityBackfillProjector,
        private readonly CanonicalIdentityWriter $writer = new CanonicalIdentityWriter,
    ) {
    }

    /**
     * @param  array{census_reference_date?: ?string, census_max_user_id?: int|string|null, prior_census_max_user_id?: int|string|null}  $metadata
     * @param  callable(IdentityReconcileUserOutcome): void|null  $onRow
     */
    public function run(bool $apply, array $metadata = [], ?callable $onRow = null): IdentityReconcileReport
    {
        $this->assertSwitchesOff();
        $connection = $apply ? $this->assertWriteConnection() : $this->assertReadOnlyConnection();
        $this->assertRequiredTables($connection);
        $censusMaxUserId = self::requireCensusMaxUserId($metadata['census_max_user_id'] ?? null);

        $liveMaxUserId = $this->liveMaxUserId($connection);
        if ($liveMaxUserId > $censusMaxUserId) {
            throw new IdentityReconcileException(
                'Step 7 reconcile globally aborts when live_max_user_id exceeds the supplied census_max_user_id boundary.'
            );
        }

        $started = now()->toIso8601String();
        $outcomes = [];
        $rowCount = 0;
        $aggregates = $this->emptyAggregates();

        $query = $apply
            ? User::query()->select('id')
            : User::on($connection)->with(['role' => function ($roleQuery) use ($connection): void {
                $roleQuery->getModel()->setConnection($connection);
            }]);

        $query
            ->where('id', '<=', $censusMaxUserId)
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($users) use (
                $apply,
                $connection,
                $censusMaxUserId,
                &$outcomes,
                &$rowCount,
                &$aggregates,
                $onRow,
            ): void {
                foreach ($users as $chunkUser) {
                    $userId = (int) $chunkUser->id;
                    $outcome = $apply
                        ? $this->processApplyUser($userId, $censusMaxUserId)
                        : $this->processDryRunUser($chunkUser, $connection, $censusMaxUserId);

                    $rowCount++;
                    $this->absorb($aggregates, $outcome);
                    $outcomes[] = $outcome;
                    if ($onRow !== null) {
                        $onRow($outcome);
                    }
                }
            });

        $ended = now()->toIso8601String();
        $this->finalizeReadiness($aggregates, $apply);
        $aggregates['cutover_blockers'] = $this->cutoverBlockers($outcomes);

        return new IdentityReconcileReport(
            $outcomes,
            [
                'started_at' => $started,
                'ended_at' => $ended,
                'finished_at' => $ended,
                'environment' => (string) app()->environment(),
                'commit_hash' => $this->commitHash(),
                'deploy_revision' => $this->deployRevision(),
                'spec' => 'DK-TS-002 D15 Step 7',
                'mode' => $apply ? 'apply' : 'dry-run',
                'row_count' => $rowCount,
                'live_max_user_id' => $liveMaxUserId === 0 ? null : $liveMaxUserId,
                'census_reference_date' => $this->optionalString($metadata['census_reference_date'] ?? null),
                'census_max_user_id' => $censusMaxUserId,
                'prior_census_max_user_id' => $this->optionalPositiveInt($metadata['prior_census_max_user_id'] ?? null),
                'connection' => $connection,
                'canonical_read' => false,
                'canonical_write' => false,
                'cutover_ready' => false,
                'population_boundary_protected' => false,
            ],
            $aggregates,
        );
    }

    private function processDryRunUser(User $user, string $connection, int $censusMaxUserId): IdentityReconcileUserOutcome
    {
        try {
            return $this->decide($user, $connection, $censusMaxUserId, apply: false);
        } catch (Throwable) {
            return $this->failedOutcome((int) $user->id, IdentityCensusRow::UNSUPPORTED);
        }
    }

    private function processApplyUser(int $userId, int $censusMaxUserId): IdentityReconcileUserOutcome
    {
        try {
            return DB::transaction(function () use ($userId, $censusMaxUserId) {
                $user = User::query()
                    ->with('role')
                    ->where('id', $userId)
                    ->lockForUpdate()
                    ->first();

                if ($user === null) {
                    return new IdentityReconcileUserOutcome(
                        $userId,
                        IdentityReconcileUserOutcome::FAILED,
                        IdentityCensusRow::UNSUPPORTED,
                        ['user_missing_after_lock'],
                    );
                }

                return $this->decide($user, (string) config('database.default'), $censusMaxUserId, apply: true);
            });
        } catch (Throwable) {
            return new IdentityReconcileUserOutcome(
                $userId,
                IdentityReconcileUserOutcome::FAILED,
                IdentityCensusRow::UNSUPPORTED,
                ['write_failed'],
            );
        }
    }

    private function decide(User $user, string $connection, int $censusMaxUserId, bool $apply): IdentityReconcileUserOutcome
    {
        $userId = (int) $user->id;

        try {
            $classified = $this->census->classify($user);
        } catch (Throwable) {
            return $this->failedOutcome($userId, IdentityCensusRow::UNSUPPORTED);
        }

        if ($userId > $censusMaxUserId) {
            return new IdentityReconcileUserOutcome(
                $userId,
                IdentityReconcileUserOutcome::SKIPPED_OUTSIDE_BOUNDARY,
                $classified->rowStatus,
                ['outside_census_boundary'],
            );
        }

        $graph = $this->inspectGraph($userId, $connection);
        $liveBackfillable = $classified->rowStatus === IdentityCensusRow::BACKFILLABLE;

        if ($graph['kind'] === self::GRAPH_INVALID) {
            return new IdentityReconcileUserOutcome(
                $userId,
                IdentityReconcileUserOutcome::CANONICAL_INVALID,
                $classified->rowStatus,
                $graph['reason_codes'] === [] ? ['canonical_invalid'] : $graph['reason_codes'],
            );
        }

        if (! $liveBackfillable) {
            if ($graph['kind'] === self::GRAPH_NONE) {
                return new IdentityReconcileUserOutcome(
                    $userId,
                    IdentityReconcileUserOutcome::SKIPPED_NOT_ELIGIBLE,
                    $classified->rowStatus,
                    $classified->reasonCodes === [] ? ['live_not_backfillable'] : $classified->reasonCodes,
                );
            }

            return new IdentityReconcileUserOutcome(
                $userId,
                IdentityReconcileUserOutcome::SOURCE_DRIFT,
                $classified->rowStatus,
                array_values(array_unique(array_merge(
                    ['source_no_longer_step4_projectable'],
                    $classified->reasonCodes,
                    $graph['reason_codes'],
                ))),
                null,
                $this->hashFingerprint($graph['fingerprint']),
                null,
            );
        }

        try {
            $snapshot = $this->projector->project($user, $classified);
        } catch (Throwable) {
            return $this->failedOutcome($userId, $classified->rowStatus);
        }

        if ($snapshot === null) {
            return new IdentityReconcileUserOutcome(
                $userId,
                IdentityReconcileUserOutcome::CONFLICT,
                $classified->rowStatus,
                ['projection_unavailable'],
            );
        }

        $projectionFingerprint = IdentityCanonicalGraphFingerprint::fromSnapshot($snapshot);
        $projectionHash = $this->hashFingerprint($projectionFingerprint);

        if ($graph['kind'] === self::GRAPH_NONE) {
            if (! $apply) {
                return new IdentityReconcileUserOutcome(
                    $userId,
                    IdentityReconcileUserOutcome::WOULD_CREATE,
                    $classified->rowStatus,
                    ['eligible_backfillable'],
                    $projectionHash,
                    null,
                    null,
                );
            }

            try {
                $this->writer->createForUser($user, $snapshot);
            } catch (CanonicalIdentityWriteException|Throwable) {
                return new IdentityReconcileUserOutcome(
                    $userId,
                    IdentityReconcileUserOutcome::FAILED,
                    $classified->rowStatus,
                    ['write_failed'],
                    $projectionHash,
                    null,
                    null,
                );
            }

            return new IdentityReconcileUserOutcome(
                $userId,
                IdentityReconcileUserOutcome::CREATED,
                $classified->rowStatus,
                ['eligible_backfillable'],
                $projectionHash,
                $projectionHash,
                false,
            );
        }

        if ($graph['kind'] !== self::GRAPH_VALID_STEP4_FL) {
            return new IdentityReconcileUserOutcome(
                $userId,
                IdentityReconcileUserOutcome::CONFLICT,
                $classified->rowStatus,
                $graph['reason_codes'] === [] ? ['canonical_wrong_branch'] : $graph['reason_codes'],
                $projectionHash,
                $this->hashFingerprint($graph['fingerprint']),
                true,
            );
        }

        $canonicalFingerprint = $graph['fingerprint'];
        $canonicalHash = $this->hashFingerprint($canonicalFingerprint);
        $mismatch = $projectionFingerprint !== $canonicalFingerprint;

        if (! $mismatch) {
            return new IdentityReconcileUserOutcome(
                $userId,
                IdentityReconcileUserOutcome::SKIPPED_IDEMPOTENT,
                $classified->rowStatus,
                ['canonical_matches_projection'],
                $projectionHash,
                $canonicalHash,
                false,
            );
        }

        if (! $apply) {
            return new IdentityReconcileUserOutcome(
                $userId,
                IdentityReconcileUserOutcome::WOULD_UPDATE,
                $classified->rowStatus,
                ['same_branch_fingerprint_mismatch'],
                $projectionHash,
                $canonicalHash,
                true,
            );
        }

        try {
            $this->writer->updatePhysicalPersonGraph($user, $snapshot);
        } catch (CanonicalIdentityWriteException|Throwable) {
            return new IdentityReconcileUserOutcome(
                $userId,
                IdentityReconcileUserOutcome::FAILED,
                $classified->rowStatus,
                ['write_failed'],
                $projectionHash,
                $canonicalHash,
                true,
            );
        }

        return new IdentityReconcileUserOutcome(
            $userId,
            IdentityReconcileUserOutcome::UPDATED,
            $classified->rowStatus,
            ['same_branch_fingerprint_mismatch'],
            $projectionHash,
            $projectionHash,
            false,
        );
    }

    /**
     * @return array{kind: string, reason_codes: list<string>, fingerprint: array<string, mixed>|null}
     */
    private function inspectGraph(int $userId, string $connection): array
    {
        $platforms = PlatformIdentity::on($connection)->where('user_id', $userId)->orderBy('id')->get();
        if ($platforms->isEmpty()) {
            return ['kind' => self::GRAPH_NONE, 'reason_codes' => [], 'fingerprint' => null];
        }

        if ($platforms->count() !== 1) {
            return ['kind' => self::GRAPH_INVALID, 'reason_codes' => ['multiple_platform_identities'], 'fingerprint' => null];
        }

        /** @var PlatformIdentity $platform */
        $platform = $platforms->first();
        $platformId = (int) $platform->id;

        $flRows = PhysicalPersonIdentity::on($connection)->where('platform_identity_id', $platformId)->orderBy('id')->get();
        $leRows = LegalEntityIdentity::on($connection)->where('platform_identity_id', $platformId)->orderBy('id')->get();
        $fbRows = ForeignBranchIdentity::on($connection)->where('platform_identity_id', $platformId)->orderBy('id')->get();

        $apCount = 0;
        foreach ($leRows as $legalEntity) {
            $apCount += LegalEntityAuthorizedPerson::on($connection)
                ->where('legal_entity_identity_id', $legalEntity->id)
                ->count();
        }
        $repCount = 0;
        foreach ($fbRows as $foreignBranch) {
            $repCount += ForeignBranchRepresentative::on($connection)
                ->where('foreign_branch_identity_id', $foreignBranch->id)
                ->count();
        }

        $flCount = $flRows->count();
        $leCount = $leRows->count();
        $fbCount = $fbRows->count();
        $branchCount = (int) ($flCount > 0) + (int) ($leCount > 0) + (int) ($fbCount > 0);

        if ($branchCount === 0) {
            return ['kind' => self::GRAPH_INVALID, 'reason_codes' => ['missing_subtype'], 'fingerprint' => null];
        }

        if ($branchCount > 1 || $flCount > 1 || $leCount > 1 || $fbCount > 1) {
            return ['kind' => self::GRAPH_INVALID, 'reason_codes' => ['multiple_branches'], 'fingerprint' => null];
        }

        if ($flCount === 1) {
            if ($platform->subject_type !== PlatformIdentity::SUBJECT_PHYSICAL_PERSON) {
                return ['kind' => self::GRAPH_INVALID, 'reason_codes' => ['subject_type_mismatch'], 'fingerprint' => null];
            }

            if ($apCount > 0 || $repCount > 0) {
                return ['kind' => self::GRAPH_INVALID, 'reason_codes' => ['inconsistent_child'], 'fingerprint' => null];
            }

            /** @var PhysicalPersonIdentity $fl */
            $fl = $flRows->first();
            $fingerprint = IdentityCanonicalGraphFingerprint::fromPersistedPhysicalPersonGraph($platform, $fl);

            if (
                $fl->is_entrepreneur === true
                || $fl->residential_status !== PhysicalPersonIdentity::RESIDENTIAL_RESIDENT
                || $fl->id_document_type !== PhysicalPersonIdentity::DOCUMENT_JMB
            ) {
                return [
                    'kind' => self::GRAPH_UNEXPECTED,
                    'reason_codes' => ['canonical_wrong_branch'],
                    'fingerprint' => $fingerprint,
                ];
            }

            return [
                'kind' => self::GRAPH_VALID_STEP4_FL,
                'reason_codes' => [],
                'fingerprint' => $fingerprint,
            ];
        }

        if ($leCount === 1) {
            return [
                'kind' => self::GRAPH_UNEXPECTED,
                'reason_codes' => ['unexpected_legal_entity_graph'],
                'fingerprint' => null,
            ];
        }

        return [
            'kind' => self::GRAPH_UNEXPECTED,
            'reason_codes' => ['unexpected_foreign_branch_graph'],
            'fingerprint' => null,
        ];
    }

    private function failedOutcome(int $userId, string $liveRowStatus): IdentityReconcileUserOutcome
    {
        return new IdentityReconcileUserOutcome(
            $userId,
            IdentityReconcileUserOutcome::FAILED,
            $liveRowStatus,
            ['failed'],
        );
    }

    /**
     * @param  array<string, mixed>|null  $fingerprint
     */
    private function hashFingerprint(?array $fingerprint): ?string
    {
        if ($fingerprint === null) {
            return null;
        }

        return hash('sha256', json_encode($fingerprint, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function assertSwitchesOff(): void
    {
        if (config('identity.canonical_write') || config('identity.canonical_read')) {
            throw new IdentityReconcileException('Step 7 reconcile refuses execution while canonical identity authority is enabled.');
        }
    }

    private function assertReadOnlyConnection(): string
    {
        $name = IdentityCensusService::READONLY_CONNECTION;
        $default = (string) config('database.default');
        if ($default === '' || $default === $name) {
            throw new IdentityReconcileException('Step 7 reconcile dry-run refuses the default application database connection.');
        }

        $config = config('database.connections.'.$name);
        if (! is_array($config)) {
            throw new IdentityReconcileException('Dedicated census read-only connection is not configured.');
        }

        foreach (['driver', 'host', 'database', 'username'] as $key) {
            $value = $config[$key] ?? null;
            if (! is_string($value) || trim($value) === '') {
                throw new IdentityReconcileException('Dedicated census read-only connection is incomplete.');
            }
        }

        $app = config('database.connections.'.$default);
        $roUser = trim((string) ($config['username'] ?? ''));
        $appUser = is_array($app) ? trim((string) ($app['username'] ?? '')) : '';
        if ($roUser === '' || $appUser === '' || $roUser === $appUser) {
            throw new IdentityReconcileException('Dedicated census read-only username must differ from the application database username.');
        }

        return $name;
    }

    private function assertWriteConnection(): string
    {
        $default = (string) config('database.default');
        if ($default === '' || $default === IdentityCensusService::READONLY_CONNECTION) {
            throw new IdentityReconcileException('Step 7 reconcile apply refuses the census read-only connection.');
        }

        $write = config('database.connections.'.$default);
        $census = config('database.connections.'.IdentityCensusService::READONLY_CONNECTION);
        if (is_array($write) && is_array($census)) {
            $censusUser = trim((string) ($census['username'] ?? ''));
            $writeUser = trim((string) ($write['username'] ?? ''));
            if ($censusUser !== '' && $writeUser !== '' && $censusUser === $writeUser) {
                throw new IdentityReconcileException('Step 7 reconcile apply refuses a write connection that reuses census read-only credentials.');
            }
        }

        return $default;
    }

    /**
     * @param  callable(string): bool  $hasTable
     * @return list<string>
     */
    public static function missingRequiredTables(callable $hasTable): array
    {
        $missing = [];
        foreach (self::REQUIRED_TABLES as $table) {
            if (! $hasTable($table)) {
                $missing[] = $table;
            }
        }

        return $missing;
    }

    private function assertRequiredTables(string $connection): void
    {
        $missing = self::missingRequiredTables(
            static fn (string $table): bool => Schema::connection($connection)->hasTable($table)
        );

        if ($missing !== []) {
            throw new IdentityReconcileException(
                'Step 7 reconcile requires identity tables: '.implode(', ', $missing)
            );
        }
    }

    private function liveMaxUserId(string $connection): int
    {
        $max = User::on($connection)->max('id');

        return is_numeric($max) ? (int) $max : 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyAggregates(): array
    {
        return [
            'by_status' => [
                IdentityReconcileUserOutcome::WOULD_CREATE => 0,
                IdentityReconcileUserOutcome::CREATED => 0,
                IdentityReconcileUserOutcome::WOULD_UPDATE => 0,
                IdentityReconcileUserOutcome::UPDATED => 0,
                IdentityReconcileUserOutcome::SKIPPED_IDEMPOTENT => 0,
                IdentityReconcileUserOutcome::SKIPPED_NOT_ELIGIBLE => 0,
                IdentityReconcileUserOutcome::SKIPPED_OUTSIDE_BOUNDARY => 0,
                IdentityReconcileUserOutcome::SOURCE_DRIFT => 0,
                IdentityReconcileUserOutcome::CONFLICT => 0,
                IdentityReconcileUserOutcome::CANONICAL_INVALID => 0,
                IdentityReconcileUserOutcome::FAILED => 0,
            ],
            'live_row_status' => [
                IdentityCensusRow::NON_SUBJECT_ACCOUNT => 0,
                IdentityCensusRow::UNSUPPORTED => 0,
                IdentityCensusRow::AMBIGUOUS_MAPPING => 0,
                IdentityCensusRow::INVALID_LEGACY => 0,
                IdentityCensusRow::MISSING_REQUIRED => 0,
                IdentityCensusRow::BACKFILLABLE => 0,
            ],
            'backfillable_in_boundary' => 0,
            'graphs_ready_count' => 0,
            'expected_ready' => 0,
            'cutover_blockers' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $aggregates
     */
    private function absorb(array &$aggregates, IdentityReconcileUserOutcome $outcome): void
    {
        $aggregates['by_status'][$outcome->status] = ($aggregates['by_status'][$outcome->status] ?? 0) + 1;
        $aggregates['live_row_status'][$outcome->liveRowStatus] = ($aggregates['live_row_status'][$outcome->liveRowStatus] ?? 0) + 1;

        if ($outcome->liveRowStatus === IdentityCensusRow::BACKFILLABLE) {
            $aggregates['backfillable_in_boundary']++;
        }
    }

    /**
     * @param  array<string, mixed>  $aggregates
     */
    private function finalizeReadiness(array &$aggregates, bool $apply): void
    {
        $readyStatuses = $apply ? self::READY_APPLY_STATUSES : self::READY_DRY_RUN_STATUSES;
        $ready = 0;
        foreach ($readyStatuses as $status) {
            $ready += (int) ($aggregates['by_status'][$status] ?? 0);
        }

        $aggregates['expected_ready'] = (int) $aggregates['backfillable_in_boundary'];
        $aggregates['graphs_ready_count'] = min($ready, (int) $aggregates['backfillable_in_boundary']);
    }

    /**
     * @param  list<IdentityReconcileUserOutcome>  $outcomes
     * @return list<string>
     */
    private function cutoverBlockers(array $outcomes): array
    {
        $blockers = self::STANDING_CUTOVER_BLOCKERS;

        $hasSourceDrift = false;
        $hasSubjectNotEligible = false;
        foreach ($outcomes as $outcome) {
            if ($outcome->status === IdentityReconcileUserOutcome::SOURCE_DRIFT) {
                $hasSourceDrift = true;
            }
            if (
                $outcome->status === IdentityReconcileUserOutcome::SKIPPED_NOT_ELIGIBLE
                && in_array($outcome->liveRowStatus, IdentityReconcileReport::subjectNotEligibleBlockerStatuses(), true)
            ) {
                $hasSubjectNotEligible = true;
            }
        }

        if ($hasSourceDrift) {
            $blockers[] = 'source_drift_without_use_gate';
        }
        if ($hasSubjectNotEligible) {
            $blockers[] = 'not_eligible_subject_without_use_gate';
        }

        return $blockers;
    }

    private function optionalString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function optionalPositiveInt(mixed $value): ?int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && preg_match('/^[1-9][0-9]*$/', trim($value)) === 1) {
            return (int) $value;
        }

        return null;
    }

    /**
     * @param  mixed  $value
     */
    public static function requireCensusMaxUserId(mixed $value): int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if (preg_match('/^[1-9][0-9]*$/', $trimmed) === 1) {
                return (int) $trimmed;
            }
        }

        throw new IdentityReconcileException('Step 7 reconcile requires a positive census_max_user_id boundary.');
    }

    private function deployRevision(): ?string
    {
        $revision = env('APP_REVISION');
        if (! is_string($revision)) {
            return null;
        }

        $trimmed = trim($revision);

        return $trimmed === '' ? null : $trimmed;
    }

    private function commitHash(): ?string
    {
        $gitDir = $this->gitDirectory();
        if ($gitDir === null) {
            return null;
        }

        $headPath = $gitDir.DIRECTORY_SEPARATOR.'HEAD';
        if (! is_file($headPath)) {
            return null;
        }

        $head = trim((string) file_get_contents($headPath));
        if ($head === '') {
            return null;
        }

        if (str_starts_with($head, 'ref: ')) {
            $refPath = $gitDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, substr($head, 5));
            if (! is_file($refPath)) {
                return null;
            }

            $hash = trim((string) file_get_contents($refPath));

            return $hash === '' ? null : $hash;
        }

        return $head;
    }

    private function gitDirectory(): ?string
    {
        $git = base_path('.git');
        if (is_dir($git)) {
            return $git;
        }

        if (! is_file($git)) {
            return null;
        }

        $contents = trim((string) file_get_contents($git));
        if (! str_starts_with($contents, 'gitdir:')) {
            return null;
        }

        $path = trim(substr($contents, strlen('gitdir:')));
        if ($path === '') {
            return null;
        }

        if (! preg_match('/^(?:[A-Za-z]:[\\\\\\/]|\\/)/', $path)) {
            $path = base_path($path);
        }

        return is_dir($path) ? $path : null;
    }
}
