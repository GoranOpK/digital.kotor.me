<?php

namespace App\Console\Commands;

use App\Security\JmbLookupBackfillException;
use App\Security\JmbLookupBackfillReport;
use App\Security\JmbLookupBackfillService;
use App\Security\JmbLookupException;
use App\Security\JmbLookupService;
use Illuminate\Console\Command;

class JmbLookupBackfillCommand extends Command
{
    protected $signature = 'jmb:backfill-lookup
                            {--dry-run : Read and validate without writing lookup columns}
                            {--scope=all : users, physical-identities, or all}
                            {--chunk= : Rows per batch (default 100, max 500)}';

    protected $description = 'Copy plaintext JMB into parallel lookup digest columns. Does not change plaintext, encrypted columns, or uniqueness. Not a cron job.';

    public function handle(JmbLookupBackfillService $backfill): int
    {
        try {
            $lookup = app(JmbLookupService::class);
            $lookup->assertConfigured();
        } catch (JmbLookupException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $scopeOption = $this->option('scope');
        $scope = is_string($scopeOption) ? trim($scopeOption) : '';
        $scope = $scope === '' ? JmbLookupBackfillService::SCOPE_ALL : $scope;

        try {
            $chunk = $this->resolveChunk();
            $report = $backfill->run($lookup, $scope, $chunk, (bool) $this->option('dry-run'));
        } catch (JmbLookupBackfillException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->writeReport($report);

        return $report->failed() ? self::FAILURE : self::SUCCESS;
    }

    private function resolveChunk(): int
    {
        $raw = $this->option('chunk');
        if ($raw === null || $raw === '') {
            return (int) config('jmb.backfill.chunk_default', JmbLookupBackfillService::DEFAULT_CHUNK);
        }

        if (! is_numeric($raw) || (string) (int) $raw !== (string) $raw) {
            throw new JmbLookupBackfillException(
                'Invalid JMB lookup backfill chunk. Expected an integer from '
                .JmbLookupBackfillService::MIN_CHUNK.' to '
                .(int) config('jmb.backfill.chunk_max', JmbLookupBackfillService::MAX_CHUNK).'.'
            );
        }

        return (int) $raw;
    }

    private function writeReport(JmbLookupBackfillReport $report): void
    {
        $this->line('jmb:backfill-lookup');
        $this->line('mode='.($report->dryRun ? 'dry-run' : 'apply'));

        $backfillLabel = $report->dryRun ? 'would_backfill' : 'backfilled';

        foreach ($report->scopes as $scope) {
            $this->line(sprintf(
                'scope=%s table=%s scanned=%d %s=%d already_valid=%d empty=%d collisions=%d errors=%d',
                $scope['scope'],
                $scope['table'],
                $scope['scanned'],
                $backfillLabel,
                $scope['backfilled'],
                $scope['already_valid'],
                $scope['empty'],
                $scope['collisions'],
                $scope['errors']
            ));
        }

        $this->line(sprintf(
            'totals scanned=%d %s=%d already_valid=%d empty=%d collisions=%d errors=%d',
            $report->scanned,
            $backfillLabel,
            $report->backfilled,
            $report->alreadyValid,
            $report->empty,
            $report->collisions,
            $report->errors
        ));

        if ($report->error !== null) {
            $this->error(sprintf(
                'ERROR table=%s id=%d reason=%s',
                $report->error['table'],
                $report->error['id'],
                $report->error['reason']
            ));
        }
    }
}
