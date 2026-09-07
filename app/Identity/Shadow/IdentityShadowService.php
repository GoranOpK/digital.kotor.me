<?php

namespace App\Identity\Shadow;

use App\Identity\Backfill\IdentityBackfillProjector;
use App\Identity\Census\IdentityCensusRow;
use App\Identity\Census\IdentityCensusService;
use App\Identity\IdentityCanonicalGraphFingerprint;
use App\Identity\IdentitySnapshot;
use App\Identity\Shadow\Comparators\DashboardDisplayComparator;
use App\Identity\Shadow\Comparators\EpAvailabilityComparator;
use App\Identity\Shadow\Comparators\KnApplicantTypeComparator;
use App\Identity\Shadow\Comparators\KnApplicationPrefillComparator;
use App\Identity\Shadow\Comparators\ProfileDisplayComparator;
use App\Models\PaymentAccount;
use App\Models\PaymentAccountAvailability;
use App\Models\PaymentType;
use App\Models\PaymentTypeAvailability;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * D15 Step 6 read-only semantic shadow compare. R0. No writes. No authority.
 */
final class IdentityShadowService
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
    public const SOURCE_TABLES = [
        'users',
        'roles',
    ];

    /**
     * @var list<string>
     */
    public const CATALOG_TABLES = [
        'payment_types',
        'payment_accounts',
        'payment_type_availabilities',
        'payment_account_availabilities',
    ];

    private string $connection = IdentityCensusService::READONLY_CONNECTION;

    private string $scope = IdentityShadowScope::FULL;

    /**
     * @var list<string>
     */
    private array $requiredFlows = IdentityShadowFlow::REQUIRED;

    public function __construct(
        private readonly IdentityCensusService $census = new IdentityCensusService,
        private readonly IdentityBackfillProjector $projector = new IdentityBackfillProjector,
        private readonly IdentityShadowCanonicalLoader $loader = new IdentityShadowCanonicalLoader,
        private readonly EpAvailabilityComparator $epAvailability = new EpAvailabilityComparator,
        private readonly KnApplicantTypeComparator $knApplicantType = new KnApplicantTypeComparator,
        private readonly KnApplicationPrefillComparator $knApplicationPrefill = new KnApplicationPrefillComparator,
        private readonly ProfileDisplayComparator $profileDisplay = new ProfileDisplayComparator,
        private readonly DashboardDisplayComparator $dashboardDisplay = new DashboardDisplayComparator,
    ) {
    }

    /**
     * @param  array{census_reference_date?: ?string, census_max_user_id?: int|string|null, scope?: string|null}  $metadata
     * @param  callable(IdentityShadowUserOutcome): void|null  $onRow
     */
    public function run(array $metadata = [], ?callable $onRow = null): IdentityShadowReport
    {
        $this->scope = IdentityShadowScope::resolve($metadata['scope'] ?? null);
        $this->requiredFlows = IdentityShadowScope::requiredFlows($this->scope);
        $this->assertSwitchesOff();
        $this->assertReadOnlyConnection();
        $this->assertReadableTables();
        $censusMaxUserId = self::requireCensusMaxUserId($metadata['census_max_user_id'] ?? null);

        $started = now()->toIso8601String();
        $outcomes = [];
        $maxId = 0;
        $rowCount = 0;
        $eligibleUserIds = [];
        $aggregates = $this->emptyAggregates();
        $catalog = IdentityShadowScope::includesCatalog($this->scope)
            ? $this->loadPaymentCatalog()
            : collect();

        try {
            User::on($this->connection)
                ->with(['role' => function ($roleQuery): void {
                    $roleQuery->getModel()->setConnection($this->connection);
                }])
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($users) use (
                    $censusMaxUserId,
                    $catalog,
                    &$outcomes,
                    &$maxId,
                    &$rowCount,
                    &$eligibleUserIds,
                    &$aggregates,
                    $onRow,
                ): void {
                    $graphs = $this->loader->loadGraphsForUserIds(
                        $this->connection,
                        $users->pluck('id')->map(static fn ($id): int => (int) $id)->all()
                    );

                    foreach ($users as $user) {
                        $user->setConnection($this->connection);
                        if ($user->relationLoaded('role') && $user->role !== null) {
                            $user->role->setConnection($this->connection);
                        }

                        $rowCount++;
                        $maxId = max($maxId, (int) $user->id);
                        $processed = $this->processUser(
                            $user,
                            $censusMaxUserId,
                            $graphs[(int) $user->id] ?? ['kind' => 'none', 'reason_codes' => []],
                            $catalog,
                        );
                        if ($processed['eligible']) {
                            $eligibleUserIds[(int) $user->id] = true;
                        }
                        foreach ($processed['outcomes'] as $outcome) {
                            $this->absorb($aggregates, $outcome);
                            $outcomes[] = $outcome;
                            if ($onRow !== null) {
                                $onRow($outcome);
                            }
                        }
                    }
                });
        } catch (IdentityShadowException $e) {
            throw $e;
        } catch (QueryException) {
            throw new IdentityShadowException(
                'Step 6 shadow cannot read required tables on the dedicated read-only connection.'
            );
        }

        $ended = now()->toIso8601String();
        $eligible = count($eligibleUserIds);
        $requiredFlows = count($this->requiredFlows);
        $evaluable = 0;
        foreach ($outcomes as $outcome) {
            if (isset($eligibleUserIds[$outcome->userId]) && $outcome->status !== IdentityShadowStatus::NOT_EVALUABLE) {
                $evaluable++;
            }
        }
        $includeCatalog = IdentityShadowScope::includesCatalog($this->scope);
        $accountCount = $includeCatalog
            ? $catalog->sum(static fn (PaymentType $type): int => $type->accounts->count())
            : 0;
        $aggregates['eligible_user_count'] = $eligible;
        $aggregates['required_flow_count'] = $requiredFlows;
        $aggregates['evaluable_comparison_count'] = $evaluable;
        $aggregates['expected_match_comparisons'] = IdentityShadowScope::isActiveIdentityWave($this->scope)
            ? $eligible * $requiredFlows
            : $evaluable;
        $aggregates['catalog_type_count'] = $includeCatalog ? $catalog->count() : 0;
        $aggregates['catalog_account_count'] = $accountCount;
        $aggregates['ep_evaluated_type_count'] = $aggregates['catalog_type_count'];
        $aggregates['ep_evaluated_account_count'] = $accountCount;
        $aggregates['ep_compared_decision_count'] = $includeCatalog
            ? $catalog->count() + $accountCount
            : 0;

        $deferredGates = IdentityShadowScope::deferredGates($this->scope);
        if ($deferredGates !== []) {
            $aggregates['deferred_gates'] = $deferredGates;
        }

        $scoped = IdentityShadowScope::isActiveIdentityWave($this->scope);
        $reportMetadata = [
            'started_at' => $started,
            'ended_at' => $ended,
            'finished_at' => $ended,
            'environment' => (string) app()->environment(),
            'commit_hash' => $this->commitHash(),
            'spec' => 'DK-TS-002 D15 Step 6',
            'mode' => $scoped ? 'scoped_production_shadow' : 'shadow',
            'scope' => $this->scope,
            'required_flows' => $this->requiredFlows,
            'row_count' => $rowCount,
            'comparison_count' => count($outcomes),
            'live_max_user_id' => $maxId === 0 ? null : $maxId,
            'census_reference_date' => $this->optionalString($metadata['census_reference_date'] ?? null),
            'census_max_user_id' => $censusMaxUserId,
            'connection' => $this->connection,
            'canonical_read' => false,
            'canonical_write' => false,
        ];
        if ($scoped) {
            $reportMetadata['wave'] = 'active_identity_wave';
            $reportMetadata['deferred_gates'] = $deferredGates;
            $reportMetadata['step6_closed'] = false;
            $reportMetadata['five_flow_closed'] = false;
        }

        return new IdentityShadowReport(
            $outcomes,
            $reportMetadata,
            $aggregates,
        );
    }

    /**
     * @param  array<string, mixed>  $graph
     * @param  Collection<int, PaymentType>  $catalog
     * @return array{eligible: bool, outcomes: list<IdentityShadowUserOutcome>}
     */
    private function processUser(User $user, int $censusMaxUserId, array $graph, Collection $catalog): array
    {
        $userId = (int) $user->id;

        try {
            $classified = $this->census->classify($user);
        } catch (Throwable) {
            return $this->bundle(false, $this->uniform($userId, IdentityShadowStatus::LEGACY_READ_FAILED, ['legacy_classify_failed']));
        }

        try {
            if ($userId > $censusMaxUserId) {
                return $this->bundle(false, $this->uniform($userId, IdentityShadowStatus::NOT_SHADOW_ELIGIBLE, ['outside_census_boundary']));
            }

            if ($classified->rowStatus !== IdentityCensusRow::BACKFILLABLE) {
                $reasons = $classified->reasonCodes === [] ? ['live_not_backfillable'] : $classified->reasonCodes;

                return $this->bundle(false, $this->uniform($userId, IdentityShadowStatus::NOT_SHADOW_ELIGIBLE, $reasons));
            }

            $kind = $graph['kind'] ?? 'none';

            if ($kind === 'none') {
                return $this->bundle(false, $this->uniform($userId, IdentityShadowStatus::MISSING_CANONICAL, ['missing_canonical_graph']));
            }

            if ($kind !== 'valid_step4_fl') {
                return $this->bundle(false, $this->uniform(
                    $userId,
                    IdentityShadowStatus::CANONICAL_INVALID,
                    $this->mergeReasons(['canonical_graph_invalid'], $graph['reason_codes'] ?? []),
                ));
            }

            $projected = $this->projector->project($user, $classified);
            if ($projected === null) {
                return $this->bundle(true, $this->uniform($userId, IdentityShadowStatus::LEGACY_READ_FAILED, ['projection_unavailable']));
            }

            /** @var PlatformIdentity $platform */
            $platform = $graph['platform'];
            /** @var PhysicalPersonIdentity $fl */
            $fl = $graph['physical_person'];
            $projectedFingerprint = IdentityCanonicalGraphFingerprint::fromSnapshot($projected);
            $persistedFingerprint = IdentityCanonicalGraphFingerprint::fromPersistedPhysicalPersonGraph($platform, $fl);
            if ($projectedFingerprint !== $persistedFingerprint) {
                return $this->bundle(true, $this->uniform($userId, IdentityShadowStatus::MISMATCH, ['source_drift']));
            }

            try {
                $canonical = $this->loader->snapshotFromValidStep4Graph($graph);
            } catch (Throwable) {
                return $this->bundle(true, $this->uniform($userId, IdentityShadowStatus::CANONICAL_READ_FAILED, ['canonical_read_failed']));
            }

            return $this->bundle(true, $this->compareRequiredFlows($user, $canonical, $catalog));
        } catch (Throwable) {
            return $this->bundle(false, $this->uniform($userId, IdentityShadowStatus::LEGACY_READ_FAILED, ['shadow_failed']));
        }
    }

    /**
     * @param  Collection<int, PaymentType>  $catalog
     * @return list<IdentityShadowUserOutcome>
     */
    private function compareRequiredFlows(User $user, IdentitySnapshot $canonical, Collection $catalog): array
    {
        $userId = (int) $user->id;
        $outcomes = [];

        foreach ($this->requiredFlows as $flow) {
            try {
                $result = match ($flow) {
                    IdentityShadowFlow::EP_AVAILABILITY => $this->epAvailability->compare($user, $canonical, $catalog),
                    IdentityShadowFlow::KN_APPLICANT_TYPE => $this->knApplicantType->compare($user, $canonical),
                    IdentityShadowFlow::KN_APPLICATION_PREFILL => $this->knApplicationPrefill->compare($user, $canonical),
                    IdentityShadowFlow::PROFILE_DISPLAY => $this->profileDisplay->compare($user, $canonical),
                    IdentityShadowFlow::DASHBOARD_DISPLAY => $this->dashboardDisplay->compare($user, $canonical),
                    default => throw new IdentityShadowException('Unknown required shadow flow.'),
                };
                $outcomes[] = new IdentityShadowUserOutcome(
                    $userId,
                    $flow,
                    $result->status,
                    $result->reasonCodes,
                    $result->fieldCategories,
                    $result->coverage,
                );
            } catch (IdentityShadowException) {
                $outcomes[] = new IdentityShadowUserOutcome(
                    $userId,
                    $flow,
                    IdentityShadowStatus::CANONICAL_READ_FAILED,
                    ['canonical_read_failed'],
                    [],
                );
            } catch (Throwable) {
                $outcomes[] = new IdentityShadowUserOutcome(
                    $userId,
                    $flow,
                    IdentityShadowStatus::LEGACY_READ_FAILED,
                    ['legacy_compare_failed'],
                    [],
                );
            }
        }

        return $outcomes;
    }

    /**
     * @param  list<string>  $reasonCodes
     * @return list<IdentityShadowUserOutcome>
     */
    private function uniform(int $userId, string $status, array $reasonCodes): array
    {
        $outcomes = [];
        foreach ($this->requiredFlows as $flow) {
            $outcomes[] = new IdentityShadowUserOutcome($userId, $flow, $status, $reasonCodes, []);
        }

        return $outcomes;
    }

    /**
     * @param  list<IdentityShadowUserOutcome>  $outcomes
     * @return array{eligible: bool, outcomes: list<IdentityShadowUserOutcome>}
     */
    private function bundle(bool $eligible, array $outcomes): array
    {
        return [
            'eligible' => $eligible,
            'outcomes' => $outcomes,
        ];
    }

    /**
     * @return Collection<int, PaymentType>
     */
    private function loadPaymentCatalog(): Collection
    {
        $types = PaymentType::on($this->connection)->orderBy('id')->get();
        foreach ($types as $type) {
            $type->setConnection($this->connection);
        }

        $typeIds = $types->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $typeRules = $typeIds === []
            ? collect()
            : PaymentTypeAvailability::on($this->connection)
                ->whereIn('payment_type_id', $typeIds)
                ->orderBy('id')
                ->get();
        foreach ($typeRules as $rule) {
            $rule->setConnection($this->connection);
        }
        $typeRulesByType = $typeRules->groupBy(static fn ($row): int => (int) $row->payment_type_id);

        $accounts = $typeIds === []
            ? collect()
            : PaymentAccount::on($this->connection)
                ->whereIn('payment_type_id', $typeIds)
                ->orderBy('id')
                ->get();
        foreach ($accounts as $account) {
            $account->setConnection($this->connection);
        }
        $accountsByType = $accounts->groupBy(static fn ($row): int => (int) $row->payment_type_id);
        $accountIds = $accounts->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        $accountRules = $accountIds === []
            ? collect()
            : PaymentAccountAvailability::on($this->connection)
                ->whereIn('payment_account_id', $accountIds)
                ->orderBy('id')
                ->get();
        foreach ($accountRules as $rule) {
            $rule->setConnection($this->connection);
        }
        $accountRulesByAccount = $accountRules->groupBy(static fn ($row): int => (int) $row->payment_account_id);

        foreach ($types as $type) {
            $typeAccounts = $accountsByType->get((int) $type->id, collect())->values();
            foreach ($typeAccounts as $account) {
                $account->setRelation('paymentType', $type);
                $account->setRelation(
                    'availabilities',
                    $accountRulesByAccount->get((int) $account->id, collect())->values()
                );
            }
            $type->setRelation('accounts', $typeAccounts);
            $type->setRelation(
                'availabilities',
                $typeRulesByType->get((int) $type->id, collect())->values()
            );
        }

        return $types;
    }

    private function assertSwitchesOff(): void
    {
        if (config('identity.canonical_write') || config('identity.canonical_read')) {
            throw new IdentityShadowException('Step 6 shadow refuses execution while canonical identity authority is enabled.');
        }
    }

    private function assertReadOnlyConnection(): void
    {
        if ($this->connection !== IdentityCensusService::READONLY_CONNECTION) {
            throw new IdentityShadowException('Step 6 shadow requires the dedicated census read-only connection.');
        }

        $default = (string) config('database.default');
        if ($default === '' || $default === $this->connection) {
            throw new IdentityShadowException('Step 6 shadow refuses the default application database connection.');
        }

        $config = config('database.connections.'.$this->connection);
        if (! is_array($config)) {
            throw new IdentityShadowException('Dedicated census read-only connection is not configured.');
        }

        foreach (['driver', 'host', 'database', 'username'] as $key) {
            $value = $config[$key] ?? null;
            if (! is_string($value) || trim($value) === '') {
                throw new IdentityShadowException('Dedicated census read-only connection is incomplete.');
            }
        }

        $app = config('database.connections.'.$default);
        $roUser = trim((string) ($config['username'] ?? ''));
        $appUser = is_array($app) ? trim((string) ($app['username'] ?? '')) : '';
        if ($roUser === '' || $appUser === '' || $roUser === $appUser) {
            throw new IdentityShadowException('Dedicated census read-only username must differ from the application database username.');
        }
    }

    /**
     * @param  callable(string): bool  $hasTable
     * @return list<string>
     */
    public static function missingRequiredTables(callable $hasTable, bool $includeCatalog = true): array
    {
        $tables = $includeCatalog
            ? array_merge(self::REQUIRED_TABLES, self::CATALOG_TABLES)
            : self::REQUIRED_TABLES;
        $missing = [];
        foreach ($tables as $table) {
            if (! $hasTable($table)) {
                $missing[] = $table;
            }
        }

        return $missing;
    }

    private function assertReadableTables(): void
    {
        $required = IdentityShadowScope::includesCatalog($this->scope)
            ? array_merge(self::REQUIRED_TABLES, self::CATALOG_TABLES)
            : self::REQUIRED_TABLES;
        try {
            $missing = [];
            foreach ($required as $table) {
                if (! Schema::connection($this->connection)->hasTable($table)) {
                    $missing[] = $table;
                }
            }
        } catch (QueryException) {
            throw new IdentityShadowException(
                'Step 6 shadow cannot read required tables on the dedicated read-only connection.'
            );
        }

        if ($missing !== []) {
            $kind = IdentityShadowScope::includesCatalog($this->scope)
                ? 'identity and catalog tables'
                : 'identity tables';
            throw new IdentityShadowException(
                'Step 6 shadow requires '.$kind.': '.implode(', ', $missing)
            );
        }

        foreach (array_merge(self::SOURCE_TABLES, $required) as $table) {
            try {
                DB::connection($this->connection)->table($table)->limit(1)->get();
            } catch (QueryException) {
                throw new IdentityShadowException(
                    'Step 6 shadow cannot SELECT required tables on the dedicated read-only connection.'
                );
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyAggregates(): array
    {
        $byStatus = [
            IdentityShadowStatus::MATCH => 0,
            IdentityShadowStatus::MISMATCH => 0,
            IdentityShadowStatus::NOT_SHADOW_ELIGIBLE => 0,
            IdentityShadowStatus::MISSING_CANONICAL => 0,
            IdentityShadowStatus::CANONICAL_INVALID => 0,
            IdentityShadowStatus::CANONICAL_READ_FAILED => 0,
            IdentityShadowStatus::LEGACY_READ_FAILED => 0,
            IdentityShadowStatus::NOT_EVALUABLE => 0,
        ];
        $byFlow = [];
        foreach ($this->requiredFlows as $flow) {
            $byFlow[$flow] = $byStatus;
        }

        return [
            'by_status' => $byStatus,
            'by_flow' => $byFlow,
            'eligible_user_count' => 0,
            'required_flow_count' => count($this->requiredFlows),
            'evaluable_comparison_count' => 0,
            'expected_match_comparisons' => 0,
            'catalog_type_count' => 0,
            'catalog_account_count' => 0,
            'ep_evaluated_type_count' => 0,
            'ep_evaluated_account_count' => 0,
            'ep_compared_decision_count' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $aggregates
     */
    private function absorb(array &$aggregates, IdentityShadowUserOutcome $outcome): void
    {
        $aggregates['by_status'][$outcome->status] = ($aggregates['by_status'][$outcome->status] ?? 0) + 1;
        if (isset($aggregates['by_flow'][$outcome->flowCode][$outcome->status])) {
            $aggregates['by_flow'][$outcome->flowCode][$outcome->status]++;
        } else {
            $aggregates['by_flow'][$outcome->flowCode][$outcome->status] =
                ($aggregates['by_flow'][$outcome->flowCode][$outcome->status] ?? 0) + 1;
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

        throw new IdentityShadowException('Step 6 shadow requires a positive census_max_user_id boundary.');
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
