<?php

namespace App\Console\Commands;

use App\Security\JmbEncryptionException;
use App\Security\JmbEncryptionService;
use App\Security\JmbLookupException;
use App\Security\JmbLookupService;
use App\Security\JmbPlaintextRetirementPrecheckException;
use App\Security\JmbPlaintextRetirementPrecheckReport;
use App\Security\JmbPlaintextRetirementPrecheckService;
use Illuminate\Console\Command;

class JmbPlaintextRetirementPrecheckCommand extends Command
{
    protected $signature = 'jmb:precheck-plaintext-retirement';

    protected $description = 'Read-only pre-retirement verification of the seven plaintext JMB/JMBG pairs. Counts and status only.';

    public const PASS_LINE = 'PASS — all 7 plaintext columns are production-data ready for controlled retirement';

    public const FAIL_LINE = 'FAIL — plaintext retirement must not proceed';

    public function handle(JmbPlaintextRetirementPrecheckService $precheck): int
    {
        if ((bool) config('jmb.plaintext_retirement.enabled', false)) {
            $this->error('JMB plaintext retirement precheck is refused while plaintext retirement is enabled.');
            $this->line(self::FAIL_LINE);

            return self::FAILURE;
        }

        $this->line('jmb:precheck-plaintext-retirement');
        $this->line('app_env='.(string) app()->environment());
        $this->line('plaintext_retirement=OFF');

        $encryptionAvailable = false;
        $lookupAvailable = false;
        $encryption = null;
        $lookup = null;

        try {
            $encryption = app(JmbEncryptionService::class);
            $encryptionAvailable = true;
        } catch (JmbEncryptionException) {
            $encryptionAvailable = false;
        }

        try {
            $lookup = app(JmbLookupService::class);
            $lookup->assertConfigured();
            $lookupAvailable = true;
        } catch (JmbLookupException) {
            $lookupAvailable = false;
        }

        $this->line('encryption_config='.($encryptionAvailable ? 'AVAILABLE' : 'UNAVAILABLE'));
        $this->line('lookup_config='.($lookupAvailable ? 'AVAILABLE' : 'UNAVAILABLE'));

        if (! $encryptionAvailable || ! $lookupAvailable || $encryption === null || $lookup === null) {
            $this->line(self::FAIL_LINE);

            return self::FAILURE;
        }

        try {
            $report = $precheck->run($encryption, $lookup, true, true);
        } catch (JmbPlaintextRetirementPrecheckException $e) {
            $this->error('reason='.$e->reason);
            $this->line(self::FAIL_LINE);

            return self::FAILURE;
        }

        $this->writeReport($report);

        $passed = $report->passed();
        $this->line($passed ? self::PASS_LINE : self::FAIL_LINE);

        return $passed ? self::SUCCESS : self::FAILURE;
    }

    private function writeReport(JmbPlaintextRetirementPrecheckReport $report): void
    {
        $this->line('schema all_plaintext_nullable='.$this->passFail($report->schemaPlaintextNullable));
        $this->line('schema users_jmb_unique='.$this->passFail($report->schemaUsersJmbUnique));
        $this->line('schema users_jmb_lookup_unique='.$this->passFail($report->schemaUsersJmbLookupUnique));
        $this->line('schema physical_person_identities_jmb_lookup_index='.$this->passFail($report->schemaFlLookupIndex));
        $this->line('schema physical_person_identities_jmb_lookup_non_unique='.$this->passFail($report->schemaFlLookupNonUnique));

        foreach ($report->pairs as $pair) {
            $this->line(sprintf(
                'column=%s total=%d plaintext_non_null=%d encrypted_non_null=%d both_present=%d plaintext_only=%d encrypted_only=%d neither=%d',
                $pair['column'],
                $pair['total'],
                $pair['plaintext_non_null'],
                $pair['encrypted_non_null'],
                $pair['both_present'],
                $pair['plaintext_only'],
                $pair['encrypted_only'],
                $pair['neither']
            ));
            $this->line(sprintf(
                'column=%s round_trip rows_checked=%d matches=%d mismatches=%d decrypt_failures=%d malformed_plaintext=%d',
                $pair['column'],
                $pair['rows_checked'],
                $pair['matches'],
                $pair['mismatches'],
                $pair['decrypt_failures'],
                $pair['malformed_plaintext']
            ));

            if ($pair['has_lookup']) {
                $this->line(sprintf(
                    'column=%s lookup expected_lookup_rows=%d lookup_non_null=%d lookup_missing=%d digest_matches=%d digest_mismatches=%d',
                    $pair['column'],
                    $pair['expected_lookup_rows'],
                    $pair['lookup_non_null'],
                    $pair['lookup_missing'],
                    $pair['digest_matches'],
                    $pair['digest_mismatches']
                ));
            }

            if ($pair['column'] === 'users.jmb') {
                $this->line(sprintf(
                    'column=%s collisions non_null_lookup_rows=%d duplicate_digest_groups=%d rows_in_duplicate_groups=%d',
                    $pair['column'],
                    $report->usersNonNullLookupRows,
                    $report->duplicateDigestGroups,
                    $report->rowsInDuplicateGroups
                ));
            }

            $this->line('column='.$pair['column'].' status='.$pair['status']);
        }
    }

    private function passFail(bool $ok): string
    {
        return $ok ? 'PASS' : 'FAIL';
    }
}
