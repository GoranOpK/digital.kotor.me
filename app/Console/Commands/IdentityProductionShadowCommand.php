<?php

namespace App\Console\Commands;

use App\Identity\Census\IdentityCensusService;
use App\Identity\Shadow\IdentityShadowException;
use App\Identity\Shadow\IdentityShadowFlow;
use App\Identity\Shadow\IdentityShadowProtectedOutputPath;
use App\Identity\Shadow\IdentityShadowScope;
use App\Identity\Shadow\IdentityShadowService;
use App\Identity\Shadow\IdentityShadowStatus;
use App\Identity\Shadow\IdentityShadowUserOutcome;
use Illuminate\Console\Command;

class IdentityProductionShadowCommand extends Command
{
    protected $signature = 'identity:shadow-production
                            {--aggregate= : Required protected path for aggregate JSON}
                            {--rows= : Required protected path for streamed row JSONL}
                            {--census-reference-date= : Optional census snapshot date metadata only}
                            {--census-max-user-id= : Required census population boundary}
                            {--scope= : Omit for full five-flow shadow. PO-adopted scoped wave: active-identity-wave}';

    protected $description = 'D15 Step 6 production identity shadow compare. Read-only. Default: full five-flow (EP schema required). Explicit --scope=active-identity-wave: four active identity flows with EP deferred hard gate.';

    public function handle(IdentityShadowService $service): int
    {
        if (! app()->environment('production')) {
            $this->error('identity:shadow-production requires the production environment.');

            return self::FAILURE;
        }

        if (config('identity.canonical_write') || config('identity.canonical_read')) {
            $this->error('identity:shadow-production refuses execution while canonical identity authority is enabled.');

            return self::FAILURE;
        }

        $connectionError = $this->dedicatedConnectionError();
        if ($connectionError !== null) {
            $this->error($connectionError);

            return self::FAILURE;
        }

        try {
            $scope = IdentityShadowScope::resolve($this->option('scope'));
        } catch (IdentityShadowException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        try {
            $censusMaxUserId = IdentityShadowService::requireCensusMaxUserId($this->option('census-max-user-id'));
        } catch (IdentityShadowException $e) {
            $this->error('identity:shadow-production requires a positive --census-max-user-id census boundary.');

            return self::FAILURE;
        }

        $aggregateOption = $this->option('aggregate');
        $rowsOption = $this->option('rows');
        if (! is_string($aggregateOption) || $aggregateOption === '' || ! is_string($rowsOption) || $rowsOption === '') {
            $this->error('identity:shadow-production requires --aggregate and --rows protected output paths.');

            return self::FAILURE;
        }

        try {
            $aggregatePath = IdentityShadowProtectedOutputPath::resolve($aggregateOption);
            $rowsPath = IdentityShadowProtectedOutputPath::resolve($rowsOption);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $rowsHandle = fopen($rowsPath, 'w');
        if ($rowsHandle === false) {
            $this->error('Unable to open protected row output path.');

            return self::FAILURE;
        }

        try {
            $report = $service->run(
                [
                    'census_reference_date' => $this->option('census-reference-date'),
                    'census_max_user_id' => $censusMaxUserId,
                    'scope' => $scope,
                ],
                static function (IdentityShadowUserOutcome $outcome) use ($rowsHandle): void {
                    fwrite(
                        $rowsHandle,
                        json_encode($outcome->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL
                    );
                }
            );
        } catch (IdentityShadowException $e) {
            fclose($rowsHandle);
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            if (is_resource($rowsHandle)) {
                fclose($rowsHandle);
            }
        }

        $aggregateJson = json_encode(
            $report->aggregateDocument(),
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
        );
        file_put_contents($aggregatePath, $aggregateJson.PHP_EOL);

        $byStatus = $report->aggregates['by_status'];
        $scoped = IdentityShadowScope::isActiveIdentityWave((string) ($report->metadata['scope'] ?? IdentityShadowScope::FULL));
        if ($scoped) {
            if ($report->passed()) {
                $this->info('SCOPED PASS — ACTIVE IDENTITY WAVE');
            } else {
                $this->error('SCOPED FAIL — ACTIVE IDENTITY WAVE');
            }
            $this->line('EP GATE OPEN / DEFERRED');
            $this->line('step6_closed=false');
            $this->line('five_flow_closed=false');
        } else {
            $this->info('Step 6 production identity shadow compare complete.');
        }
        $this->line('mode='.$report->metadata['mode']);
        $this->line('scope='.$report->metadata['scope']);
        $this->line('census_max_user_id='.$report->metadata['census_max_user_id']);
        $this->line('row_count='.$report->metadata['row_count']);
        $this->line('eligible_user_count='.$report->aggregates['eligible_user_count']);
        $this->line('required_flow_count='.$report->aggregates['required_flow_count']);
        $this->line('evaluable_comparison_count='.$report->aggregates['evaluable_comparison_count']);
        $this->line('expected_match_comparisons='.$report->aggregates['expected_match_comparisons']);
        $this->line('ep_evaluated_type_count='.$report->aggregates['ep_evaluated_type_count']);
        $this->line('ep_evaluated_account_count='.$report->aggregates['ep_evaluated_account_count']);
        $this->line('ep_compared_decision_count='.$report->aggregates['ep_compared_decision_count']);
        $this->line('match='.($byStatus[IdentityShadowStatus::MATCH] ?? 0));
        $this->line('mismatch='.($byStatus[IdentityShadowStatus::MISMATCH] ?? 0));
        $this->line('not_evaluable='.($byStatus[IdentityShadowStatus::NOT_EVALUABLE] ?? 0));
        $this->line('not_shadow_eligible='.($byStatus[IdentityShadowStatus::NOT_SHADOW_ELIGIBLE] ?? 0));
        $this->line('missing_canonical='.($byStatus[IdentityShadowStatus::MISSING_CANONICAL] ?? 0));
        $this->line('canonical_invalid='.($byStatus[IdentityShadowStatus::CANONICAL_INVALID] ?? 0));
        $this->line('canonical_read_failed='.($byStatus[IdentityShadowStatus::CANONICAL_READ_FAILED] ?? 0));
        $this->line('legacy_read_failed='.($byStatus[IdentityShadowStatus::LEGACY_READ_FAILED] ?? 0));
        $requiredFlows = $report->metadata['required_flows'] ?? IdentityShadowFlow::REQUIRED;
        foreach ($requiredFlows as $flow) {
            $this->line('flow_'.$flow.'_match='.($report->aggregates['by_flow'][$flow][IdentityShadowStatus::MATCH] ?? 0));
        }
        if ($scoped) {
            $gate = $report->metadata['deferred_gates'][IdentityShadowFlow::EP_AVAILABILITY] ?? [];
            $this->line('deferred_gate_ep_availability_status='.($gate['status'] ?? ''));
            $this->line('deferred_gate_ep_availability_reason='.($gate['reason'] ?? ''));
        }
        $this->line('aggregate='.$aggregatePath);
        $this->line('rows='.$rowsPath);

        return $report->passed() ? self::SUCCESS : self::FAILURE;
    }

    private function dedicatedConnectionError(): ?string
    {
        $name = IdentityCensusService::READONLY_CONNECTION;
        $default = (string) config('database.default');
        if ($default === '' || $default === $name) {
            return 'identity:shadow-production refuses the default application database connection.';
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
        if ($roUser === $appUser) {
            return 'Dedicated census read-only username must differ from the application database username.';
        }

        return null;
    }
}
