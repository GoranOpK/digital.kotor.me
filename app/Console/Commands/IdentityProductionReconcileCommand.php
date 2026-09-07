<?php

namespace App\Console\Commands;

use App\Identity\Census\IdentityCensusService;
use App\Identity\Reconcile\IdentityReconcileException;
use App\Identity\Reconcile\IdentityReconcileProtectedOutputPath;
use App\Identity\Reconcile\IdentityReconcileService;
use App\Identity\Reconcile\IdentityReconcileUserOutcome;
use Illuminate\Console\Command;

class IdentityProductionReconcileCommand extends Command
{
    public const APPLY_CONFIRMATION = 'STEP7-RECONCILE-APPLY';

    protected $signature = 'identity:reconcile-production
                            {--dry-run : Classify live rows and report intended canonical writes without persisting}
                            {--apply : Persist missing or drifted same-branch Step-4 FL canonical identities}
                            {--confirm= : Required confirmation token for --apply}
                            {--aggregate= : Required protected path for aggregate JSON}
                            {--rows= : Required protected path for streamed row JSONL}
                            {--census-reference-date= : Optional census snapshot date metadata only}
                            {--census-max-user-id= : Required census population boundary}';

    protected $description = 'D15 Step 7 production identity reconcile. Fail-closed. Switches remain OFF. Does not authorize cutover.';

    public function handle(IdentityReconcileService $service): int
    {
        if (! app()->environment('production')) {
            $this->error('identity:reconcile-production requires the production environment.');

            return self::FAILURE;
        }

        if (config('identity.canonical_write') || config('identity.canonical_read')) {
            $this->error('identity:reconcile-production refuses execution while canonical identity authority is enabled.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $apply = (bool) $this->option('apply');
        if ($dryRun === $apply) {
            $this->error('identity:reconcile-production requires exactly one of --dry-run or --apply.');

            return self::FAILURE;
        }

        if ($apply) {
            $confirm = $this->option('confirm');
            if (! is_string($confirm) || $confirm !== self::APPLY_CONFIRMATION) {
                $this->error('identity:reconcile-production --apply requires --confirm='.self::APPLY_CONFIRMATION);

                return self::FAILURE;
            }
        } else {
            $connectionError = $this->dedicatedConnectionError();
            if ($connectionError !== null) {
                $this->error($connectionError);

                return self::FAILURE;
            }
        }

        try {
            $censusMaxUserId = IdentityReconcileService::requireCensusMaxUserId($this->option('census-max-user-id'));
        } catch (IdentityReconcileException $e) {
            $this->error('identity:reconcile-production requires a positive --census-max-user-id census boundary.');

            return self::FAILURE;
        }

        $aggregateOption = $this->option('aggregate');
        $rowsOption = $this->option('rows');
        if (! is_string($aggregateOption) || $aggregateOption === '' || ! is_string($rowsOption) || $rowsOption === '') {
            $this->error('identity:reconcile-production requires --aggregate and --rows protected output paths.');

            return self::FAILURE;
        }

        try {
            $aggregatePath = IdentityReconcileProtectedOutputPath::resolve($aggregateOption);
            $rowsPath = IdentityReconcileProtectedOutputPath::resolve($rowsOption);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($this->sameResolvedPath($aggregatePath, $rowsPath)) {
            $this->error('identity:reconcile-production --aggregate and --rows must be different protected paths.');

            return self::FAILURE;
        }

        $rowsHandle = fopen($rowsPath, 'w');
        if ($rowsHandle === false) {
            $this->error('Unable to open protected row output path.');

            return self::FAILURE;
        }

        try {
            $report = $service->run(
                $apply,
                [
                    'census_reference_date' => $this->option('census-reference-date'),
                    'census_max_user_id' => $censusMaxUserId,
                ],
                static function (IdentityReconcileUserOutcome $outcome) use ($rowsHandle): void {
                    fwrite(
                        $rowsHandle,
                        json_encode($outcome->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL
                    );
                }
            );
        } catch (IdentityReconcileException $e) {
            fclose($rowsHandle);
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            if (is_resource($rowsHandle)) {
                fclose($rowsHandle);
            }
        }

        $document = $report->aggregateDocument();
        $aggregateJson = json_encode(
            $document,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
        );
        file_put_contents($aggregatePath, $aggregateJson.PHP_EOL);

        $this->info($apply ? 'Step 7 production reconcile apply complete.' : 'Step 7 production reconcile dry-run complete.');
        $this->line('mode='.$document['mode']);
        $this->line('census_max_user_id='.$document['census_max_user_id']);
        $this->line('live_max_user_id='.($document['live_max_user_id'] ?? ''));
        $this->line('row_count='.$document['row_count']);
        $this->line('reconcile_passed='.($document['reconcile_passed'] ? 'true' : 'false'));
        $this->line('graph_readiness_passed='.($document['graph_readiness_passed'] ? 'true' : 'false'));
        $this->line('cutover_ready=false');
        $this->line('population_boundary_protected=false');
        $this->line('would_create='.$document['would_create']);
        $this->line('created='.$document['created']);
        $this->line('would_update='.$document['would_update']);
        $this->line('updated='.$document['updated']);
        $this->line('skipped_idempotent='.$document['skipped_idempotent']);
        $this->line('source_drift='.$document['source_drift']);
        $this->line('conflict='.$document['conflict']);
        $this->line('canonical_invalid='.$document['canonical_invalid']);
        $this->line('failed='.$document['failed']);
        $this->line('ep_gate='.$document['ep_gate']['status']);
        $this->line('aggregate='.$aggregatePath);
        $this->line('rows='.$rowsPath);

        return $report->reconcilePassed() ? self::SUCCESS : self::FAILURE;
    }

    private function dedicatedConnectionError(): ?string
    {
        $name = IdentityCensusService::READONLY_CONNECTION;
        $default = (string) config('database.default');
        if ($default === '' || $default === $name) {
            return 'identity:reconcile-production refuses the default application database connection.';
        }

        $config = config('database.connections.'.$name);
        if (! is_array($config)) {
            return 'Dedicated census read-only connection is not configured.';
        }

        foreach (['driver', 'host', 'database', 'username'] as $key) {
            $value = $config[$key] ?? null;
            if (! is_string($value) || trim($value) === '') {
                return 'Dedicated census read-only connection is incomplete.';
            }
        }

        $app = config('database.connections.'.$default);
        $roUser = trim((string) ($config['username'] ?? ''));
        $appUser = is_array($app) ? trim((string) ($app['username'] ?? '')) : '';
        if ($roUser === '' || $appUser === '' || $roUser === $appUser) {
            return 'Dedicated census read-only username must differ from the application database username.';
        }

        return null;
    }

    private function sameResolvedPath(string $left, string $right): bool
    {
        $left = rtrim(str_replace('\\', '/', $left), '/');
        $right = rtrim(str_replace('\\', '/', $right), '/');
        if (DIRECTORY_SEPARATOR === '\\') {
            return strtolower($left) === strtolower($right);
        }

        return $left === $right;
    }
}
