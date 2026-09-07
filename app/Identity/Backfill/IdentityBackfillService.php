<?php

namespace App\Identity\Backfill;

use App\Identity\CanonicalIdentityWriteException;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\Census\IdentityCensusRow;
use App\Identity\Census\IdentityCensusService;
use App\Identity\IdentityCanonicalGraphFingerprint;
use App\Identity\IdentitySnapshot;
use App\Models\PlatformIdentity;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * D15 Step 4 first-wave backfill. R0. Per-user transactions.
 * Eligibility is live BACKFILLABLE inside an explicit census max user id boundary.
 */
final class IdentityBackfillService
{
    public const CHUNK_SIZE = 100;

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

    public function __construct(
        private readonly IdentityCensusService $census = new IdentityCensusService,
        private readonly IdentityBackfillProjector $projector = new IdentityBackfillProjector,
        private readonly CanonicalIdentityWriter $writer = new CanonicalIdentityWriter,
    ) {
    }

    /**
     * @param  array{census_reference_date?: ?string, census_max_user_id?: int|string|null}  $metadata
     * @param  callable(IdentityBackfillUserOutcome): void|null  $onRow
     */
    public function run(bool $apply, array $metadata = [], ?callable $onRow = null): IdentityBackfillReport
    {
        $this->assertSwitchesOff();
        $connection = $this->assertWriteConnection();
        $this->assertRequiredTables($connection);
        $censusMaxUserId = self::requireCensusMaxUserId($metadata['census_max_user_id'] ?? null);

        $started = now()->toIso8601String();
        $outcomes = [];
        $maxId = 0;
        $rowCount = 0;
        $aggregates = $this->emptyAggregates();

        User::query()
            ->with('role')
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($users) use ($apply, $censusMaxUserId, &$outcomes, &$maxId, &$rowCount, &$aggregates, $onRow): void {
                foreach ($users as $chunkUser) {
                    $user = User::query()->with('role')->find($chunkUser->id);
                    if ($user === null) {
                        continue;
                    }

                    $rowCount++;
                    $maxId = max($maxId, (int) $user->id);
                    $outcome = $this->processUser($user, $apply, $censusMaxUserId);
                    $this->absorb($aggregates, $outcome);
                    $outcomes[] = $outcome;
                    if ($onRow !== null) {
                        $onRow($outcome);
                    }
                }
            });

        $ended = now()->toIso8601String();

        return new IdentityBackfillReport(
            $outcomes,
            [
                'started_at' => $started,
                'ended_at' => $ended,
                'finished_at' => $ended,
                'environment' => (string) app()->environment(),
                'commit_hash' => $this->commitHash(),
                'spec' => 'DK-TS-002 D15 Step 4',
                'mode' => $apply ? 'apply' : 'dry-run',
                'row_count' => $rowCount,
                'live_max_user_id' => $maxId === 0 ? null : $maxId,
                'census_reference_date' => $this->optionalString($metadata['census_reference_date'] ?? null),
                'census_max_user_id' => $censusMaxUserId,
                'connection' => $connection,
                'canonical_read' => false,
                'canonical_write' => false,
            ],
            $aggregates,
        );
    }

    private function processUser(User $user, bool $apply, int $censusMaxUserId): IdentityBackfillUserOutcome
    {
        $classified = $this->census->classify($user);
        $userId = (int) $user->id;

        if ($userId > $censusMaxUserId) {
            return new IdentityBackfillUserOutcome(
                $userId,
                IdentityBackfillUserOutcome::SKIPPED_OUTSIDE_CENSUS_BOUNDARY,
                $classified->rowStatus,
                ['outside_census_boundary'],
            );
        }

        if ($classified->rowStatus !== IdentityCensusRow::BACKFILLABLE) {
            return new IdentityBackfillUserOutcome(
                $userId,
                IdentityBackfillUserOutcome::SKIPPED_NOT_ELIGIBLE,
                $classified->rowStatus,
                $classified->reasonCodes === [] ? ['live_not_backfillable'] : $classified->reasonCodes,
            );
        }

        $snapshot = $this->projector->project($user, $classified);
        if ($snapshot === null) {
            return new IdentityBackfillUserOutcome(
                $userId,
                IdentityBackfillUserOutcome::CONFLICT,
                $classified->rowStatus,
                ['projection_unavailable'],
            );
        }

        $platform = PlatformIdentity::query()
            ->with(['physicalPerson', 'legalEntity', 'foreignBranch'])
            ->where('user_id', $userId)
            ->first();

        if ($platform !== null) {
            $persisted = $this->persistedFingerprint($platform);
            if ($persisted === null) {
                return new IdentityBackfillUserOutcome(
                    $userId,
                    IdentityBackfillUserOutcome::CONFLICT,
                    $classified->rowStatus,
                    ['incomplete_canonical_graph'],
                );
            }

            if ($persisted === $this->projectionFingerprint($snapshot)) {
                return new IdentityBackfillUserOutcome(
                    $userId,
                    IdentityBackfillUserOutcome::SKIPPED_IDEMPOTENT,
                    $classified->rowStatus,
                    ['canonical_matches_projection'],
                );
            }

            return new IdentityBackfillUserOutcome(
                $userId,
                IdentityBackfillUserOutcome::CONFLICT,
                $classified->rowStatus,
                ['canonical_differs_from_projection'],
            );
        }

        if (! $apply) {
            return new IdentityBackfillUserOutcome(
                $userId,
                IdentityBackfillUserOutcome::WOULD_CREATE,
                $classified->rowStatus,
                ['eligible_backfillable'],
            );
        }

        try {
            $this->writer->createForUser($user, $snapshot);
        } catch (CanonicalIdentityWriteException|Throwable) {
            return new IdentityBackfillUserOutcome(
                $userId,
                IdentityBackfillUserOutcome::FAILED,
                $classified->rowStatus,
                ['write_failed'],
            );
        }

        return new IdentityBackfillUserOutcome(
            $userId,
            IdentityBackfillUserOutcome::CREATED,
            $classified->rowStatus,
            ['eligible_backfillable'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function projectionFingerprint(IdentitySnapshot $snapshot): array
    {
        return IdentityCanonicalGraphFingerprint::fromSnapshot($snapshot);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function persistedFingerprint(PlatformIdentity $platform): ?array
    {
        $fl = $platform->physicalPerson;
        if ($fl === null || $platform->legalEntity !== null || $platform->foreignBranch !== null) {
            return null;
        }

        return IdentityCanonicalGraphFingerprint::fromPersistedPhysicalPersonGraph($platform, $fl);
    }

    private function assertSwitchesOff(): void
    {
        if (config('identity.canonical_write') || config('identity.canonical_read')) {
            throw new IdentityBackfillException('Step 4 backfill refuses execution while canonical identity authority is enabled.');
        }
    }

    private function assertWriteConnection(): string
    {
        $default = (string) config('database.default');
        if ($default === '' || $default === IdentityCensusService::READONLY_CONNECTION) {
            throw new IdentityBackfillException('Step 4 backfill refuses the census read-only connection.');
        }

        $write = config('database.connections.'.$default);
        $census = config('database.connections.'.IdentityCensusService::READONLY_CONNECTION);
        if (is_array($write) && is_array($census)) {
            $censusUser = trim((string) ($census['username'] ?? ''));
            $writeUser = trim((string) ($write['username'] ?? ''));
            if ($censusUser !== '' && $writeUser !== '' && $censusUser === $writeUser) {
                throw new IdentityBackfillException('Step 4 backfill refuses a write connection that reuses census read-only credentials.');
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
            throw new IdentityBackfillException(
                'Step 4 backfill requires canonical identity tables: '.implode(', ', $missing)
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyAggregates(): array
    {
        return [
            'by_status' => [
                IdentityBackfillUserOutcome::CREATED => 0,
                IdentityBackfillUserOutcome::WOULD_CREATE => 0,
                IdentityBackfillUserOutcome::SKIPPED_NOT_ELIGIBLE => 0,
                IdentityBackfillUserOutcome::SKIPPED_IDEMPOTENT => 0,
                IdentityBackfillUserOutcome::SKIPPED_OUTSIDE_CENSUS_BOUNDARY => 0,
                IdentityBackfillUserOutcome::CONFLICT => 0,
                IdentityBackfillUserOutcome::FAILED => 0,
            ],
            'by_live_row_status' => [
                IdentityCensusRow::NON_SUBJECT_ACCOUNT => 0,
                IdentityCensusRow::UNSUPPORTED => 0,
                IdentityCensusRow::AMBIGUOUS_MAPPING => 0,
                IdentityCensusRow::INVALID_LEGACY => 0,
                IdentityCensusRow::MISSING_REQUIRED => 0,
                IdentityCensusRow::BACKFILLABLE => 0,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $aggregates
     */
    private function absorb(array &$aggregates, IdentityBackfillUserOutcome $outcome): void
    {
        $aggregates['by_status'][$outcome->status] = ($aggregates['by_status'][$outcome->status] ?? 0) + 1;
        $aggregates['by_live_row_status'][$outcome->liveRowStatus] = ($aggregates['by_live_row_status'][$outcome->liveRowStatus] ?? 0) + 1;
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

        throw new IdentityBackfillException('Step 4 backfill requires a positive census_max_user_id boundary.');
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
