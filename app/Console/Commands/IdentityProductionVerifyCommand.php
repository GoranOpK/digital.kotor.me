<?php

namespace App\Console\Commands;

use App\Identity\Census\IdentityCensusService;
use App\Identity\Verify\IdentityVerifyException;
use App\Identity\Verify\IdentityVerifyProtectedOutputPath;
use App\Identity\Verify\IdentityVerifyService;
use App\Identity\Verify\IdentityVerifyUserOutcome;
use Illuminate\Console\Command;

class IdentityProductionVerifyCommand extends Command
{
    protected $signature = 'identity:verify-production
                            {--aggregate= : Required protected path for aggregate JSON}
                            {--rows= : Required protected path for streamed row JSONL}
                            {--census-reference-date= : Optional census snapshot date metadata only}
                            {--census-max-user-id= : Required census population boundary}';

    protected $description = 'D15 Step 5 production identity verify. Read-only. Dedicated census connection. Fail-closed.';

    public function handle(IdentityVerifyService $service): int
    {
        if (! app()->environment('production')) {
            $this->error('identity:verify-production requires the production environment.');

            return self::FAILURE;
        }

        if (config('identity.canonical_write') || config('identity.canonical_read')) {
            $this->error('identity:verify-production refuses execution while canonical identity authority is enabled.');

            return self::FAILURE;
        }

        $connectionError = $this->dedicatedConnectionError();
        if ($connectionError !== null) {
            $this->error($connectionError);

            return self::FAILURE;
        }

        try {
            $censusMaxUserId = IdentityVerifyService::requireCensusMaxUserId($this->option('census-max-user-id'));
        } catch (IdentityVerifyException $e) {
            $this->error('identity:verify-production requires a positive --census-max-user-id census boundary.');

            return self::FAILURE;
        }

        $aggregateOption = $this->option('aggregate');
        $rowsOption = $this->option('rows');
        if (! is_string($aggregateOption) || $aggregateOption === '' || ! is_string($rowsOption) || $rowsOption === '') {
            $this->error('identity:verify-production requires --aggregate and --rows protected output paths.');

            return self::FAILURE;
        }

        try {
            $aggregatePath = IdentityVerifyProtectedOutputPath::resolve($aggregateOption);
            $rowsPath = IdentityVerifyProtectedOutputPath::resolve($rowsOption);
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
                ],
                static function (IdentityVerifyUserOutcome $outcome) use ($rowsHandle): void {
                    fwrite(
                        $rowsHandle,
                        json_encode($outcome->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL
                    );
                }
            );
        } catch (IdentityVerifyException $e) {
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

        $this->info('Step 5 production identity verify complete.');
        $this->line('mode='.$report->metadata['mode']);
        $this->line('census_max_user_id='.$report->metadata['census_max_user_id']);
        $this->line('row_count='.$report->metadata['row_count']);
        $this->line('verified='.($report->aggregates['by_status'][IdentityVerifyUserOutcome::VERIFIED] ?? 0));
        $this->line('skipped_not_eligible='.($report->aggregates['by_status'][IdentityVerifyUserOutcome::SKIPPED_NOT_ELIGIBLE] ?? 0));
        $this->line('skipped_outside_census_boundary='.($report->aggregates['by_status'][IdentityVerifyUserOutcome::SKIPPED_OUTSIDE_CENSUS_BOUNDARY] ?? 0));
        $this->line('missing_canonical='.($report->aggregates['by_status'][IdentityVerifyUserOutcome::MISSING_CANONICAL] ?? 0));
        $this->line('canonical_mismatch='.($report->aggregates['by_status'][IdentityVerifyUserOutcome::CANONICAL_MISMATCH] ?? 0));
        $this->line('source_drift='.($report->aggregates['by_status'][IdentityVerifyUserOutcome::SOURCE_DRIFT] ?? 0));
        $this->line('canonical_graph_invalid='.($report->aggregates['by_status'][IdentityVerifyUserOutcome::CANONICAL_GRAPH_INVALID] ?? 0));
        $this->line('unexpected_canonical='.($report->aggregates['by_status'][IdentityVerifyUserOutcome::UNEXPECTED_CANONICAL] ?? 0));
        $this->line('failed='.($report->aggregates['by_status'][IdentityVerifyUserOutcome::FAILED] ?? 0));
        $this->line('aggregate='.$aggregatePath);
        $this->line('rows='.$rowsPath);

        return $report->passed() ? self::SUCCESS : self::FAILURE;
    }

    private function dedicatedConnectionError(): ?string
    {
        $name = IdentityCensusService::READONLY_CONNECTION;
        $default = (string) config('database.default');
        if ($default === '' || $default === $name) {
            return 'identity:verify-production refuses the default application database connection.';
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
