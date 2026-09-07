<?php

namespace App\Console\Commands;

use App\Identity\Backfill\IdentityBackfillException;
use App\Identity\Backfill\IdentityBackfillProtectedOutputPath;
use App\Identity\Backfill\IdentityBackfillService;
use App\Identity\Backfill\IdentityBackfillUserOutcome;
use Illuminate\Console\Command;

class IdentityProductionBackfillCommand extends Command
{
    public const APPLY_CONFIRMATION = 'STEP4-BACKFILL-APPLY';

    protected $signature = 'identity:backfill-production
                            {--dry-run : Classify live rows and report intended writes without persisting}
                            {--apply : Persist eligible canonical identities}
                            {--confirm= : Required confirmation token for --apply}
                            {--aggregate= : Required protected path for aggregate JSON}
                            {--rows= : Required protected path for streamed row JSONL}
                            {--census-reference-date= : Optional census snapshot date metadata only}
                            {--census-max-user-id= : Required census population boundary; users above this wait for Step 7}';

    protected $description = 'D15 Step 4 production identity backfill. Fail-closed. Switches remain OFF.';

    public function handle(IdentityBackfillService $service): int
    {
        if (! app()->environment('production')) {
            $this->error('identity:backfill-production requires the production environment.');

            return self::FAILURE;
        }

        if (config('identity.canonical_write') || config('identity.canonical_read')) {
            $this->error('identity:backfill-production refuses execution while canonical identity authority is enabled.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $apply = (bool) $this->option('apply');
        if ($dryRun === $apply) {
            $this->error('identity:backfill-production requires exactly one of --dry-run or --apply.');

            return self::FAILURE;
        }

        if ($apply) {
            $confirm = $this->option('confirm');
            if (! is_string($confirm) || $confirm !== self::APPLY_CONFIRMATION) {
                $this->error('identity:backfill-production --apply requires --confirm='.self::APPLY_CONFIRMATION);

                return self::FAILURE;
            }
        }

        try {
            $censusMaxUserId = IdentityBackfillService::requireCensusMaxUserId($this->option('census-max-user-id'));
        } catch (IdentityBackfillException $e) {
            $this->error('identity:backfill-production requires a positive --census-max-user-id census boundary.');

            return self::FAILURE;
        }

        $aggregateOption = $this->option('aggregate');
        $rowsOption = $this->option('rows');
        if (! is_string($aggregateOption) || $aggregateOption === '' || ! is_string($rowsOption) || $rowsOption === '') {
            $this->error('identity:backfill-production requires --aggregate and --rows protected output paths.');

            return self::FAILURE;
        }

        try {
            $aggregatePath = IdentityBackfillProtectedOutputPath::resolve($aggregateOption);
            $rowsPath = IdentityBackfillProtectedOutputPath::resolve($rowsOption);
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
                $apply,
                [
                    'census_reference_date' => $this->option('census-reference-date'),
                    'census_max_user_id' => $censusMaxUserId,
                ],
                static function (IdentityBackfillUserOutcome $outcome) use ($rowsHandle): void {
                    fwrite(
                        $rowsHandle,
                        json_encode($outcome->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL
                    );
                }
            );
        } catch (IdentityBackfillException $e) {
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

        $this->info($apply ? 'Step 4 production backfill apply complete.' : 'Step 4 production backfill dry-run complete.');
        $this->line('mode='.$report->metadata['mode']);
        $this->line('census_max_user_id='.$report->metadata['census_max_user_id']);
        $this->line('row_count='.$report->metadata['row_count']);
        $this->line('created='.($report->aggregates['by_status'][IdentityBackfillUserOutcome::CREATED] ?? 0));
        $this->line('would_create='.($report->aggregates['by_status'][IdentityBackfillUserOutcome::WOULD_CREATE] ?? 0));
        $this->line('skipped_not_eligible='.($report->aggregates['by_status'][IdentityBackfillUserOutcome::SKIPPED_NOT_ELIGIBLE] ?? 0));
        $this->line('skipped_idempotent='.($report->aggregates['by_status'][IdentityBackfillUserOutcome::SKIPPED_IDEMPOTENT] ?? 0));
        $this->line('skipped_outside_census_boundary='.($report->aggregates['by_status'][IdentityBackfillUserOutcome::SKIPPED_OUTSIDE_CENSUS_BOUNDARY] ?? 0));
        $this->line('conflict='.($report->aggregates['by_status'][IdentityBackfillUserOutcome::CONFLICT] ?? 0));
        $this->line('failed='.($report->aggregates['by_status'][IdentityBackfillUserOutcome::FAILED] ?? 0));
        $this->line('aggregate='.$aggregatePath);
        $this->line('rows='.$rowsPath);

        return $report->hasBlockingOutcomes() ? self::FAILURE : self::SUCCESS;
    }
}
