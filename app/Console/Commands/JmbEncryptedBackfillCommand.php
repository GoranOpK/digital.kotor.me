<?php

namespace App\Console\Commands;

use App\Security\JmbEncryptedBackfillException;
use App\Security\JmbEncryptedBackfillReport;
use App\Security\JmbEncryptedBackfillService;
use App\Security\JmbEncryptionException;
use App\Security\JmbEncryptionService;
use Illuminate\Console\Command;

class JmbEncryptedBackfillCommand extends Command
{
    protected $signature = 'jmb:backfill-encrypted
                            {--dry-run : Read and validate without writing encrypted columns}
                            {--scope= : One mapping: users, physical_person_identities, legal_entity_authorized_persons, foreign_branch_representatives, applications.physical_person_jmbg, applications.applicant_jmbg, business_plans}
                            {--chunk= : Rows per batch (default 100, max 500)}';

    protected $description = 'Phase B2: copy plaintext JMB/JMBG into parallel encrypted columns. Does not change plaintext. Production run is a separate PO-approved action.';

    public function handle(JmbEncryptedBackfillService $backfill): int
    {
        if ((bool) config('jmb.plaintext_retirement.enabled', false)) {
            $this->error('JMB plaintext encrypted backfill is refused while plaintext retirement is enabled.');

            return self::FAILURE;
        }

        try {
            $encryption = app(JmbEncryptionService::class);
        } catch (JmbEncryptionException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $scopeOption = $this->option('scope');
        $scope = is_string($scopeOption) ? trim($scopeOption) : '';
        $scope = $scope === '' ? null : $scope;

        try {
            $chunk = $this->resolveChunk();
            $report = $backfill->run($encryption, $scope, $chunk, (bool) $this->option('dry-run'));
        } catch (JmbEncryptedBackfillException $e) {
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
            return (int) config('jmb.backfill.chunk_default', JmbEncryptedBackfillService::DEFAULT_CHUNK);
        }

        if (! is_numeric($raw) || (string) (int) $raw !== (string) $raw) {
            throw new JmbEncryptedBackfillException(
                'Invalid JMB backfill chunk. Expected an integer from '
                .JmbEncryptedBackfillService::MIN_CHUNK.' to '
                .(int) config('jmb.backfill.chunk_max', JmbEncryptedBackfillService::MAX_CHUNK).'.'
            );
        }

        return (int) $raw;
    }

    private function writeReport(JmbEncryptedBackfillReport $report): void
    {
        $this->line('jmb:backfill-encrypted');
        $this->line('mode='.($report->dryRun ? 'dry-run' : 'apply'));

        $encryptedLabel = $report->dryRun ? 'would_encrypt' : 'encrypted';

        foreach ($report->scopes as $scope) {
            $this->line(sprintf(
                'scope=%s table=%s scanned=%d %s=%d already_valid=%d skipped_no_plaintext=%d errors=%d',
                $scope['scope'],
                $scope['table'],
                $scope['scanned'],
                $encryptedLabel,
                $scope['encrypted'],
                $scope['already_valid'],
                $scope['skipped_no_plaintext'],
                $scope['errors']
            ));
        }

        $this->line(sprintf(
            'totals scanned=%d %s=%d already_valid=%d skipped_no_plaintext=%d errors=%d',
            $report->scanned,
            $encryptedLabel,
            $report->encrypted,
            $report->alreadyValid,
            $report->skippedNoPlaintext,
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
