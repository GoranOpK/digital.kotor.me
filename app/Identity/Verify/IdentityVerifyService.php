<?php

namespace App\Identity\Verify;

use App\Identity\Backfill\IdentityBackfillProjector;
use App\Identity\Census\IdentityCensusRow;
use App\Identity\Census\IdentityCensusService;
use App\Identity\IdentityCanonicalGraphFingerprint;
use App\Models\ForeignBranchIdentity;
use App\Models\ForeignBranchRepresentative;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * D15 Step 5 read-only canonical identity verify. R0. No writes. No repair.
 */
final class IdentityVerifyService
{
    public const CHUNK_SIZE = 100;

    public const LIVE_UNCLASSIFIED = 'unclassified';

    /**
     * @var list<string>
     */
    public const REQUIRED_TABLES = [
        'platform_identities',
        'physical_person_identities',
        'legal_entity_identities',
        'legal_entity_authorized_persons',
        'foreign_branch_identities',
        'foreign_branch_representatives',
    ];

    /**
     * @var list<string>
     */
    private const SOURCE_TABLES = [
        'users',
        'roles',
    ];

    private string $connection = IdentityCensusService::READONLY_CONNECTION;

    public function __construct(
        private readonly IdentityCensusService $census = new IdentityCensusService,
        private readonly IdentityBackfillProjector $projector = new IdentityBackfillProjector,
    ) {
    }

    /**
     * @param  array{census_reference_date?: ?string, census_max_user_id?: int|string|null}  $metadata
     * @param  callable(IdentityVerifyUserOutcome): void|null  $onRow
     */
    public function run(array $metadata = [], ?callable $onRow = null): IdentityVerifyReport
    {
        $this->assertSwitchesOff();
        $this->assertReadOnlyConnection();
        $this->assertReadableTables();
        $censusMaxUserId = self::requireCensusMaxUserId($metadata['census_max_user_id'] ?? null);

        $started = now()->toIso8601String();
        $outcomes = [];
        $maxId = 0;
        $rowCount = 0;
        $aggregates = $this->emptyAggregates();

        try {
            User::on($this->connection)
                ->with(['role' => function ($roleQuery): void {
                    $roleQuery->getModel()->setConnection($this->connection);
                }])
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($users) use ($censusMaxUserId, &$outcomes, &$maxId, &$rowCount, &$aggregates, $onRow): void {
                    $graphs = $this->loadGraphsForUserIds($users->pluck('id')->map(static fn ($id): int => (int) $id)->all());

                    foreach ($users as $user) {
                        $user->setConnection($this->connection);
                        if ($user->relationLoaded('role') && $user->role !== null) {
                            $user->role->setConnection($this->connection);
                        }

                        $rowCount++;
                        $maxId = max($maxId, (int) $user->id);
                        $outcome = $this->processUser($user, $censusMaxUserId, $graphs[(int) $user->id] ?? $this->emptyGraph());
                        $this->absorb($aggregates, $outcome);
                        $outcomes[] = $outcome;
                        if ($onRow !== null) {
                            $onRow($outcome);
                        }
                    }
                });

            $this->absorbGlobalScan($aggregates);
        } catch (IdentityVerifyException $e) {
            throw $e;
        } catch (QueryException) {
            throw new IdentityVerifyException(
                'Step 5 verify cannot read required tables on the dedicated read-only connection.'
            );
        }

        $ended = now()->toIso8601String();

        return new IdentityVerifyReport(
            $outcomes,
            [
                'started_at' => $started,
                'ended_at' => $ended,
                'finished_at' => $ended,
                'environment' => (string) app()->environment(),
                'commit_hash' => $this->commitHash(),
                'spec' => 'DK-TS-002 D15 Step 5',
                'mode' => 'verify',
                'row_count' => $rowCount,
                'live_max_user_id' => $maxId === 0 ? null : $maxId,
                'census_reference_date' => $this->optionalString($metadata['census_reference_date'] ?? null),
                'census_max_user_id' => $censusMaxUserId,
                'connection' => $this->connection,
                'canonical_read' => false,
                'canonical_write' => false,
            ],
            $aggregates,
        );
    }

    /**
     * @param  array<string, mixed>  $graph
     */
    private function processUser(User $user, int $censusMaxUserId, array $graph): IdentityVerifyUserOutcome
    {
        $userId = (int) $user->id;

        try {
            $classified = $this->census->classify($user);
        } catch (Throwable) {
            return new IdentityVerifyUserOutcome(
                $userId,
                IdentityVerifyUserOutcome::FAILED,
                self::LIVE_UNCLASSIFIED,
                ['verify_failed'],
            );
        }

        $liveRowStatus = $classified->rowStatus;

        try {
            $outside = $userId > $censusMaxUserId;
            $kind = $graph['kind'];

            if ($outside) {
                if ($kind === 'none') {
                    return new IdentityVerifyUserOutcome(
                        $userId,
                        IdentityVerifyUserOutcome::SKIPPED_OUTSIDE_CENSUS_BOUNDARY,
                        $liveRowStatus,
                        ['outside_census_boundary'],
                    );
                }

                return new IdentityVerifyUserOutcome(
                    $userId,
                    IdentityVerifyUserOutcome::UNEXPECTED_CANONICAL,
                    $liveRowStatus,
                    $this->mergeReasons(['outside_census_boundary', 'unexpected_canonical_graph'], $graph['reason_codes']),
                );
            }

            $backfillable = $liveRowStatus === IdentityCensusRow::BACKFILLABLE;

            if ($backfillable) {
                return match ($kind) {
                    'none' => new IdentityVerifyUserOutcome(
                        $userId,
                        IdentityVerifyUserOutcome::MISSING_CANONICAL,
                        $liveRowStatus,
                        ['missing_canonical_graph'],
                    ),
                    'invalid' => new IdentityVerifyUserOutcome(
                        $userId,
                        IdentityVerifyUserOutcome::CANONICAL_GRAPH_INVALID,
                        $liveRowStatus,
                        $this->mergeReasons(['canonical_graph_invalid'], $graph['reason_codes']),
                    ),
                    'unexpected' => new IdentityVerifyUserOutcome(
                        $userId,
                        IdentityVerifyUserOutcome::UNEXPECTED_CANONICAL,
                        $liveRowStatus,
                        $this->mergeReasons(['unexpected_canonical_graph'], $graph['reason_codes']),
                    ),
                    default => $this->compareProjectedGraph($user, $classified, $graph),
                };
            }

            if ($kind === 'none') {
                return new IdentityVerifyUserOutcome(
                    $userId,
                    IdentityVerifyUserOutcome::SKIPPED_NOT_ELIGIBLE,
                    $liveRowStatus,
                    $classified->reasonCodes === [] ? ['live_not_backfillable'] : $classified->reasonCodes,
                );
            }

            if ($kind === 'valid_step4_fl') {
                return new IdentityVerifyUserOutcome(
                    $userId,
                    IdentityVerifyUserOutcome::SOURCE_DRIFT,
                    $liveRowStatus,
                    ['source_drift'],
                );
            }

            if ($kind === 'invalid') {
                return new IdentityVerifyUserOutcome(
                    $userId,
                    IdentityVerifyUserOutcome::CANONICAL_GRAPH_INVALID,
                    $liveRowStatus,
                    $this->mergeReasons(['canonical_graph_invalid'], $graph['reason_codes']),
                );
            }

            return new IdentityVerifyUserOutcome(
                $userId,
                IdentityVerifyUserOutcome::UNEXPECTED_CANONICAL,
                $liveRowStatus,
                $this->mergeReasons(['unexpected_canonical_graph'], $graph['reason_codes']),
            );
        } catch (Throwable) {
            return new IdentityVerifyUserOutcome(
                $userId,
                IdentityVerifyUserOutcome::FAILED,
                $liveRowStatus,
                ['verify_failed'],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $graph
     */
    private function compareProjectedGraph(User $user, IdentityCensusRow $classified, array $graph): IdentityVerifyUserOutcome
    {
        $snapshot = $this->projector->project($user, $classified);
        if ($snapshot === null) {
            return new IdentityVerifyUserOutcome(
                (int) $user->id,
                IdentityVerifyUserOutcome::FAILED,
                $classified->rowStatus,
                ['projection_unavailable'],
            );
        }

        /** @var PlatformIdentity $platform */
        $platform = $graph['platform'];
        /** @var PhysicalPersonIdentity $fl */
        $fl = $graph['physical_person'];

        $projected = IdentityCanonicalGraphFingerprint::fromSnapshot($snapshot);
        $persisted = IdentityCanonicalGraphFingerprint::fromPersistedPhysicalPersonGraph($platform, $fl);

        if ($projected === $persisted) {
            return new IdentityVerifyUserOutcome(
                (int) $user->id,
                IdentityVerifyUserOutcome::VERIFIED,
                $classified->rowStatus,
                ['canonical_matches_projection'],
            );
        }

        return new IdentityVerifyUserOutcome(
            (int) $user->id,
            IdentityVerifyUserOutcome::CANONICAL_MISMATCH,
            $classified->rowStatus,
            ['canonical_differs_from_projection'],
        );
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, array<string, mixed>>
     */
    private function loadGraphsForUserIds(array $userIds): array
    {
        $graphs = [];
        foreach ($userIds as $userId) {
            $graphs[$userId] = $this->emptyGraph();
        }

        if ($userIds === []) {
            return $graphs;
        }

        $platforms = PlatformIdentity::on($this->connection)
            ->whereIn('user_id', $userIds)
            ->orderBy('id')
            ->get();

        $byUser = $platforms->groupBy(static fn (PlatformIdentity $platform): int => (int) $platform->user_id);
        $platformIds = $platforms->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        $flByPlatform = $this->loadByPlatform(PhysicalPersonIdentity::class, $platformIds);
        $leByPlatform = $this->loadByPlatform(LegalEntityIdentity::class, $platformIds);
        $fbByPlatform = $this->loadByPlatform(ForeignBranchIdentity::class, $platformIds);

        $legalEntityIds = $leByPlatform->flatten()->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $foreignBranchIds = $fbByPlatform->flatten()->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        $apByLegalEntity = $this->loadChildren(LegalEntityAuthorizedPerson::class, 'legal_entity_identity_id', $legalEntityIds);
        $repByForeignBranch = $this->loadChildren(ForeignBranchRepresentative::class, 'foreign_branch_identity_id', $foreignBranchIds);

        foreach ($userIds as $userId) {
            $userPlatforms = $byUser->get($userId, collect());
            $graphs[$userId] = $this->inspectGraph(
                $userPlatforms,
                $flByPlatform,
                $leByPlatform,
                $fbByPlatform,
                $apByLegalEntity,
                $repByForeignBranch,
            );
        }

        return $graphs;
    }

    /**
     * @param  list<int>  $platformIds
     * @return Collection<string, Collection<int, mixed>>
     */
    private function loadByPlatform(string $modelClass, array $platformIds): Collection
    {
        if ($platformIds === []) {
            return collect();
        }

        return $modelClass::on($this->connection)
            ->whereIn('platform_identity_id', $platformIds)
            ->orderBy('id')
            ->get()
            ->groupBy(static fn ($row): int => (int) $row->platform_identity_id);
    }

    /**
     * @param  list<int>  $parentIds
     * @return Collection<string, Collection<int, mixed>>
     */
    private function loadChildren(string $modelClass, string $foreignKey, array $parentIds): Collection
    {
        if ($parentIds === []) {
            return collect();
        }

        return $modelClass::on($this->connection)
            ->whereIn($foreignKey, $parentIds)
            ->orderBy('id')
            ->get()
            ->groupBy(static fn ($row): int => (int) $row->{$foreignKey});
    }

    /**
     * @param  Collection<int, PlatformIdentity>  $platforms
     * @param  Collection<string, Collection<int, mixed>>  $flByPlatform
     * @param  Collection<string, Collection<int, mixed>>  $leByPlatform
     * @param  Collection<string, Collection<int, mixed>>  $fbByPlatform
     * @param  Collection<string, Collection<int, mixed>>  $apByLegalEntity
     * @param  Collection<string, Collection<int, mixed>>  $repByForeignBranch
     * @return array<string, mixed>
     */
    private function inspectGraph(
        Collection $platforms,
        Collection $flByPlatform,
        Collection $leByPlatform,
        Collection $fbByPlatform,
        Collection $apByLegalEntity,
        Collection $repByForeignBranch,
    ): array {
        if ($platforms->isEmpty()) {
            return $this->emptyGraph();
        }

        if ($platforms->count() !== 1) {
            return $this->graphResult('invalid', ['multiple_platform_identities']);
        }

        /** @var PlatformIdentity $platform */
        $platform = $platforms->first();
        $platformId = (int) $platform->id;
        $flRows = $flByPlatform->get($platformId, collect());
        $leRows = $leByPlatform->get($platformId, collect());
        $fbRows = $fbByPlatform->get($platformId, collect());

        $apCount = 0;
        foreach ($leRows as $legalEntity) {
            $apCount += $apByLegalEntity->get((int) $legalEntity->id, collect())->count();
        }
        $repCount = 0;
        foreach ($fbRows as $foreignBranch) {
            $repCount += $repByForeignBranch->get((int) $foreignBranch->id, collect())->count();
        }

        $flCount = $flRows->count();
        $leCount = $leRows->count();
        $fbCount = $fbRows->count();
        $branchCount = (int) ($flCount > 0) + (int) ($leCount > 0) + (int) ($fbCount > 0);

        if ($branchCount === 0) {
            return $this->graphResult('invalid', ['missing_subtype'], $platform);
        }

        if ($branchCount > 1 || $flCount > 1 || $leCount > 1 || $fbCount > 1) {
            return $this->graphResult('invalid', ['multiple_branches'], $platform);
        }

        if ($flCount === 1) {
            if ($platform->subject_type !== PlatformIdentity::SUBJECT_PHYSICAL_PERSON) {
                return $this->graphResult('invalid', ['subject_type_mismatch'], $platform, $flRows->first());
            }

            if ($apCount > 0 || $repCount > 0) {
                return $this->graphResult('invalid', ['inconsistent_child'], $platform, $flRows->first());
            }

            return [
                'kind' => 'valid_step4_fl',
                'reason_codes' => [],
                'platform' => $platform,
                'physical_person' => $flRows->first(),
            ];
        }

        if ($leCount === 1) {
            $reasons = ['unexpected_legal_entity_graph'];
            if ($platform->subject_type !== PlatformIdentity::SUBJECT_LEGAL_ENTITY) {
                $reasons[] = 'subject_type_mismatch';
            }

            return $this->graphResult('unexpected', $reasons, $platform);
        }

        $reasons = ['unexpected_foreign_branch_graph'];
        if ($platform->subject_type !== PlatformIdentity::SUBJECT_FOREIGN_BRANCH) {
            $reasons[] = 'subject_type_mismatch';
        }

        return $this->graphResult('unexpected', $reasons, $platform);
    }

    /**
     * @param  list<string>  $reasonCodes
     * @return array<string, mixed>
     */
    private function graphResult(
        string $kind,
        array $reasonCodes,
        ?PlatformIdentity $platform = null,
        mixed $physicalPerson = null,
    ): array {
        return [
            'kind' => $kind,
            'reason_codes' => $reasonCodes,
            'platform' => $platform,
            'physical_person' => $physicalPerson,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyGraph(): array
    {
        return $this->graphResult('none', []);
    }

    /**
     * @param  array<string, mixed>  $aggregates
     */
    private function absorbGlobalScan(array &$aggregates): void
    {
        $counts = [];
        foreach (self::REQUIRED_TABLES as $table) {
            $counts[$table] = (int) DB::connection($this->connection)->table($table)->count();
        }
        $aggregates['canonical_table_counts'] = $counts;

        $findings = [];
        $unassigned = 0;

        $orphanFl = $this->orphanCount('physical_person_identities', 'platform_identity_id', 'platform_identities');
        if ($orphanFl > 0) {
            $findings[] = ['reason_code' => 'orphan_physical_person_identities', 'count' => $orphanFl];
            $unassigned += $orphanFl;
        }

        $orphanLe = $this->orphanCount('legal_entity_identities', 'platform_identity_id', 'platform_identities');
        if ($orphanLe > 0) {
            $findings[] = ['reason_code' => 'orphan_legal_entity_identities', 'count' => $orphanLe];
            $unassigned += $orphanLe;
        }

        $orphanFb = $this->orphanCount('foreign_branch_identities', 'platform_identity_id', 'platform_identities');
        if ($orphanFb > 0) {
            $findings[] = ['reason_code' => 'orphan_foreign_branch_identities', 'count' => $orphanFb];
            $unassigned += $orphanFb;
        }

        $orphanAp = $this->orphanCount('legal_entity_authorized_persons', 'legal_entity_identity_id', 'legal_entity_identities');
        if ($orphanAp > 0) {
            $findings[] = ['reason_code' => 'orphan_legal_entity_authorized_persons', 'count' => $orphanAp];
            $unassigned += $orphanAp;
        }

        $orphanRep = $this->orphanCount('foreign_branch_representatives', 'foreign_branch_identity_id', 'foreign_branch_identities');
        if ($orphanRep > 0) {
            $findings[] = ['reason_code' => 'orphan_foreign_branch_representatives', 'count' => $orphanRep];
            $unassigned += $orphanRep;
        }

        $missingUsers = (int) DB::connection($this->connection)
            ->table('platform_identities')
            ->leftJoin('users', 'users.id', '=', 'platform_identities.user_id')
            ->whereNull('users.id')
            ->count();
        if ($missingUsers > 0) {
            $findings[] = ['reason_code' => 'platform_identity_without_user', 'count' => $missingUsers];
            $unassigned += $missingUsers;
        }

        $aggregates['global_findings'] = $findings;
        $aggregates['global_unassigned_failures'] = $unassigned;
    }

    private function orphanCount(string $childTable, string $foreignKey, string $parentTable): int
    {
        return (int) DB::connection($this->connection)
            ->table($childTable)
            ->leftJoin($parentTable, $parentTable.'.id', '=', $childTable.'.'.$foreignKey)
            ->whereNull($parentTable.'.id')
            ->count();
    }

    private function assertSwitchesOff(): void
    {
        if (config('identity.canonical_write') || config('identity.canonical_read')) {
            throw new IdentityVerifyException('Step 5 verify refuses execution while canonical identity authority is enabled.');
        }
    }

    private function assertReadOnlyConnection(): void
    {
        if ($this->connection !== IdentityCensusService::READONLY_CONNECTION) {
            throw new IdentityVerifyException('Step 5 verify requires the dedicated census read-only connection.');
        }

        $default = (string) config('database.default');
        if ($default === '' || $default === $this->connection) {
            throw new IdentityVerifyException('Step 5 verify refuses the default application database connection.');
        }

        $config = config('database.connections.'.$this->connection);
        if (! is_array($config)) {
            throw new IdentityVerifyException('Dedicated census read-only connection is not configured.');
        }

        foreach (['driver', 'host', 'database', 'username'] as $key) {
            $value = $config[$key] ?? null;
            if (! is_string($value) || trim($value) === '') {
                throw new IdentityVerifyException('Dedicated census read-only connection is incomplete.');
            }
        }

        $app = config('database.connections.'.$default);
        $roUser = trim((string) ($config['username'] ?? ''));
        $appUser = is_array($app) ? trim((string) ($app['username'] ?? '')) : '';
        if ($roUser === '' || $appUser === '' || $roUser === $appUser) {
            throw new IdentityVerifyException('Dedicated census read-only username must differ from the application database username.');
        }
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

    private function assertReadableTables(): void
    {
        try {
            $missing = self::missingRequiredTables(
                fn (string $table): bool => Schema::connection($this->connection)->hasTable($table)
            );
        } catch (QueryException) {
            throw new IdentityVerifyException(
                'Step 5 verify cannot read required tables on the dedicated read-only connection.'
            );
        }

        if ($missing !== []) {
            throw new IdentityVerifyException(
                'Step 5 verify requires canonical identity tables: '.implode(', ', $missing)
            );
        }

        foreach (array_merge(self::SOURCE_TABLES, self::REQUIRED_TABLES) as $table) {
            try {
                DB::connection($this->connection)->table($table)->limit(1)->get();
            } catch (QueryException) {
                throw new IdentityVerifyException(
                    'Step 5 verify cannot SELECT required identity tables on the dedicated read-only connection.'
                );
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyAggregates(): array
    {
        return [
            'by_status' => [
                IdentityVerifyUserOutcome::VERIFIED => 0,
                IdentityVerifyUserOutcome::SKIPPED_NOT_ELIGIBLE => 0,
                IdentityVerifyUserOutcome::SKIPPED_OUTSIDE_CENSUS_BOUNDARY => 0,
                IdentityVerifyUserOutcome::MISSING_CANONICAL => 0,
                IdentityVerifyUserOutcome::CANONICAL_MISMATCH => 0,
                IdentityVerifyUserOutcome::SOURCE_DRIFT => 0,
                IdentityVerifyUserOutcome::CANONICAL_GRAPH_INVALID => 0,
                IdentityVerifyUserOutcome::UNEXPECTED_CANONICAL => 0,
                IdentityVerifyUserOutcome::FAILED => 0,
            ],
            'by_live_row_status' => [
                IdentityCensusRow::NON_SUBJECT_ACCOUNT => 0,
                IdentityCensusRow::UNSUPPORTED => 0,
                IdentityCensusRow::AMBIGUOUS_MAPPING => 0,
                IdentityCensusRow::INVALID_LEGACY => 0,
                IdentityCensusRow::MISSING_REQUIRED => 0,
                IdentityCensusRow::BACKFILLABLE => 0,
            ],
            'live_backfillable_in_boundary' => 0,
            'canonical_table_counts' => [
                'platform_identities' => 0,
                'physical_person_identities' => 0,
                'legal_entity_identities' => 0,
                'legal_entity_authorized_persons' => 0,
                'foreign_branch_identities' => 0,
                'foreign_branch_representatives' => 0,
            ],
            'global_findings' => [],
            'global_unassigned_failures' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $aggregates
     */
    private function absorb(array &$aggregates, IdentityVerifyUserOutcome $outcome): void
    {
        $aggregates['by_status'][$outcome->status] = ($aggregates['by_status'][$outcome->status] ?? 0) + 1;
        if (isset($aggregates['by_live_row_status'][$outcome->liveRowStatus])) {
            $aggregates['by_live_row_status'][$outcome->liveRowStatus]++;
        }

        if ($outcome->liveRowStatus === IdentityCensusRow::BACKFILLABLE
            && $outcome->status !== IdentityVerifyUserOutcome::SKIPPED_OUTSIDE_CENSUS_BOUNDARY) {
            $aggregates['live_backfillable_in_boundary']++;
        }
    }

    /**
     * @param  list<string>  $prefix
     * @param  list<string>  $extra
     * @return list<string>
     */
    private function mergeReasons(array $prefix, array $extra): array
    {
        return array_values(array_unique([...$prefix, ...$extra]));
    }

    private function optionalString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
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

        throw new IdentityVerifyException('Step 5 verify requires a positive census_max_user_id boundary.');
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
