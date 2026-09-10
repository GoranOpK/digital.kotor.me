<?php

namespace App\Console\Commands;

use App\Security\JmbPlaintextRetirementException;
use App\Security\JmbPlaintextRetirementReport;
use App\Security\JmbPlaintextRetirementService;
use Illuminate\Console\Command;

class JmbPlaintextRetirementCommand extends Command
{
    protected $signature = 'jmb:retire-plaintext
                            {--dry-run : Verify readiness and count rows that would be nulled. No writes.}
                            {--scope=all : users, physical-identities, authorized-persons, foreign-branch-representatives, applications-physical-person, applications-applicant, business-plans, or all}
                            {--chunk=100 : Rows per read batch for verification fingerprints}';

    protected $description = 'Set the seven plaintext JMB/JMBG columns to NULL. Does not modify encrypted or lookup columns. Apply requires retirement mode and a passing precheck.';

    public const PASS_LINE = 'PASS — JMB plaintext retirement completed successfully';

    public const FAIL_LINE = 'FAIL — JMB plaintext retirement was not completed';

    public const READY_LINE = 'READY — plaintext retirement can proceed after retirement mode is enabled';

    public const BLOCKED_LINE = 'BLOCKED — plaintext retirement must not proceed';

    public function handle(JmbPlaintextRetirementService $retirement): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $scopeOption = $this->option('scope');
        $scope = is_string($scopeOption) && trim($scopeOption) !== ''
            ? trim($scopeOption)
            : JmbPlaintextRetirementService::SCOPE_ALL;

        try {
            $chunk = $this->resolveChunk();
        } catch (JmbPlaintextRetirementException $e) {
            return $this->failSanitized($e->reason);
        }

        if (! $dryRun && ! (bool) config('jmb.plaintext_retirement.enabled', false)) {
            $this->error('JMB plaintext retirement apply is refused while plaintext retirement is disabled.');
            $this->line(self::FAIL_LINE);

            return self::FAILURE;
        }

        try {
            $report = $retirement->run($scope, $chunk, $dryRun);
        } catch (JmbPlaintextRetirementException $e) {
            return $this->failSanitized($e->reason);
        }

        $this->writeReport($report);

        if ($report->passed) {
            $this->line($dryRun ? self::READY_LINE : self::PASS_LINE);

            return self::SUCCESS;
        }

        $this->line(self::BLOCKED_LINE);

        return $this->failSanitized($report->reason ?? 'precheck_failed');
    }

    private function resolveChunk(): int
    {
        $raw = $this->option('chunk');
        if ($raw === null || $raw === '') {
            return 100;
        }

        if (! is_numeric($raw) || (string) (int) $raw !== (string) $raw || (int) $raw < 1) {
            throw new JmbPlaintextRetirementException('invalid_chunk');
        }

        return (int) $raw;
    }

    private function writeReport(JmbPlaintextRetirementReport $report): void
    {
        $this->line('jmb:retire-plaintext');
        $this->line('mode='.($report->dryRun ? 'dry-run' : 'apply'));
        $this->line('scope='.$report->scope);
        $this->line('precheck='.($report->precheckPassed ? 'PASS' : 'FAIL'));
        $this->line('empty_string_rows_are_absent=1');

        foreach ($report->scopes as $scope) {
            if ($report->dryRun) {
                $this->line(sprintf(
                    'scope=%s plaintext_rows=%d would_null=%d empty_string_rows=%d',
                    $scope['scope'],
                    $scope['plaintext_rows'],
                    $scope['would_null'],
                    $scope['empty_string_rows']
                ));

                continue;
            }

            $this->line(sprintf(
                'scope=%s nulled=%d post_plaintext_non_null=%d empty_string_rows=%d status=%s',
                $scope['scope'],
                $scope['nulled'],
                $scope['post_plaintext_non_null'],
                $scope['empty_string_rows'],
                $scope['status']
            ));
        }

        if ($report->dryRun) {
            $this->line('total_would_null='.$report->totalWouldNull);

            return;
        }

        $this->line('total_nulled='.$report->totalNulled);
    }

    private function failSanitized(string $reason): int
    {
        $this->error('reason='.$reason);
        $this->line(self::FAIL_LINE);

        return self::FAILURE;
    }
}
