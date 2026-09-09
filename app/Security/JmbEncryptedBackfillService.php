<?php

namespace App\Security;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase B2: copy plaintext JMB/JMBG into existing parallel encrypted columns.
 *
 * Plaintext columns are read-only. Each successful encrypted write is committed
 * independently (no giant multi-table transaction). Retry is idempotent.
 *
 * Concurrency: a plaintext change during backfill can leave a stale encrypted
 * copy. There is no locking or dual-write in B2. Production execution must be
 * a separate PO-approved controlled run.
 */
final class JmbEncryptedBackfillService
{
    public const DEFAULT_CHUNK = 100;

    public const MIN_CHUNK = 1;

    public const MAX_CHUNK = 500;

    /**
     * @var array<string, array{table: string, plaintext: string, encrypted: string, pk: string}>
     */
    public const MAPPINGS = [
        'users' => [
            'table' => 'users',
            'plaintext' => 'jmb',
            'encrypted' => 'jmb_encrypted',
            'pk' => 'id',
        ],
        'physical_person_identities' => [
            'table' => 'physical_person_identities',
            'plaintext' => 'jmb',
            'encrypted' => 'jmb_encrypted',
            'pk' => 'id',
        ],
        'legal_entity_authorized_persons' => [
            'table' => 'legal_entity_authorized_persons',
            'plaintext' => 'jmb',
            'encrypted' => 'jmb_encrypted',
            'pk' => 'id',
        ],
        'foreign_branch_representatives' => [
            'table' => 'foreign_branch_representatives',
            'plaintext' => 'jmb',
            'encrypted' => 'jmb_encrypted',
            'pk' => 'id',
        ],
        'applications.physical_person_jmbg' => [
            'table' => 'applications',
            'plaintext' => 'physical_person_jmbg',
            'encrypted' => 'physical_person_jmbg_encrypted',
            'pk' => 'id',
        ],
        'applications.applicant_jmbg' => [
            'table' => 'applications',
            'plaintext' => 'applicant_jmbg',
            'encrypted' => 'applicant_jmbg_encrypted',
            'pk' => 'id',
        ],
        'business_plans' => [
            'table' => 'business_plans',
            'plaintext' => 'applicant_jmbg',
            'encrypted' => 'applicant_jmbg_encrypted',
            'pk' => 'id',
        ],
    ];

    public static function scopeList(): string
    {
        return implode(', ', array_keys(self::MAPPINGS));
    }

    public function run(
        JmbEncryptionService $encryption,
        ?string $scope,
        int $chunk,
        bool $dryRun,
    ): JmbEncryptedBackfillReport {
        $this->assertChunk($chunk);

        $mappings = $this->resolveMappings($scope);
        $report = new JmbEncryptedBackfillReport($dryRun);

        foreach ($mappings as $scopeName => $mapping) {
            $scopeCounters = [
                'scope' => $scopeName,
                'table' => $mapping['table'],
                'scanned' => 0,
                'encrypted' => 0,
                'already_valid' => 0,
                'skipped_no_plaintext' => 0,
                'errors' => 0,
            ];

            try {
                $this->assertMappingColumns($mapping);

                DB::table($mapping['table'])
                    ->select([$mapping['pk'], $mapping['plaintext'], $mapping['encrypted']])
                    ->orderBy($mapping['pk'])
                    ->chunkById($chunk, function ($rows) use ($encryption, $mapping, $scopeName, $dryRun, $report, &$scopeCounters): void {
                        foreach ($rows as $row) {
                            $report->scanned++;
                            $scopeCounters['scanned']++;

                            $category = $this->processRow($encryption, $mapping, $scopeName, $row, $dryRun);
                            $report->{$this->reportProperty($category)}++;
                            $scopeCounters[$category]++;
                        }
                    }, $mapping['pk']);
            } catch (JmbEncryptedBackfillException $e) {
                $report->errors++;
                $scopeCounters['errors']++;
                $report->error = [
                    'scope' => $e->scope !== '' ? $e->scope : $scopeName,
                    'table' => $e->table !== '' ? $e->table : $mapping['table'],
                    'id' => $e->rowId ?? 0,
                    'reason' => $e->reason,
                ];
                $report->scopes[] = $scopeCounters;

                return $report;
            }

            $report->scopes[] = $scopeCounters;
        }

        return $report;
    }

    /**
     * @return array<string, array{table: string, plaintext: string, encrypted: string, pk: string}>
     */
    public function resolveMappings(?string $scope): array
    {
        if ($scope === null || $scope === '') {
            return self::MAPPINGS;
        }

        if (! isset(self::MAPPINGS[$scope])) {
            throw new JmbEncryptedBackfillException(
                'Unknown JMB backfill scope. Allowed: '.self::scopeList().'.'
            );
        }

        return [$scope => self::MAPPINGS[$scope]];
    }

    public function assertChunk(int $chunk): void
    {
        $max = (int) config('jmb.backfill.chunk_max', self::MAX_CHUNK);
        if ($chunk < self::MIN_CHUNK || $chunk > $max) {
            throw new JmbEncryptedBackfillException(
                'Invalid JMB backfill chunk. Expected an integer from '.self::MIN_CHUNK.' to '.$max.'.'
            );
        }
    }

    /**
     * @param  array{table: string, plaintext: string, encrypted: string, pk: string}  $mapping
     */
    private function assertMappingColumns(array $mapping): void
    {
        foreach ([$mapping['pk'], $mapping['plaintext'], $mapping['encrypted']] as $column) {
            if (! Schema::hasColumn($mapping['table'], $column)) {
                throw new JmbEncryptedBackfillException(
                    'JMB backfill mapping is missing a required column.',
                    '',
                    $mapping['table']
                );
            }
        }
    }

    /**
     * @param  array{table: string, plaintext: string, encrypted: string, pk: string}  $mapping
     */
    private function processRow(
        JmbEncryptionService $encryption,
        array $mapping,
        string $scope,
        object $row,
        bool $dryRun,
    ): string {
        $pk = $mapping['pk'];
        $id = (int) $row->{$pk};
        $plaintext = $row->{$mapping['plaintext']};
        $target = $row->{$mapping['encrypted']};
        $hasPlaintext = is_string($plaintext) && $plaintext !== '';
        $hasTarget = is_string($target) && $target !== '';

        if (! $hasPlaintext) {
            return 'skipped_no_plaintext';
        }

        if ($hasTarget) {
            try {
                $decrypted = $encryption->decrypt($target);
            } catch (JmbEncryptionException) {
                throw new JmbEncryptedBackfillException('encrypted_target_unreadable', $scope, $mapping['table'], $id);
            }

            if (! is_string($decrypted) || ! hash_equals($plaintext, $decrypted)) {
                throw new JmbEncryptedBackfillException('encrypted_target_mismatch', $scope, $mapping['table'], $id);
            }

            return 'already_valid';
        }

        try {
            $ciphertext = $encryption->encrypt($plaintext);
            $roundtrip = $encryption->decrypt($ciphertext);
        } catch (JmbEncryptionException) {
            throw new JmbEncryptedBackfillException('encrypt_roundtrip_failed', $scope, $mapping['table'], $id);
        }

        if (! is_string($ciphertext) || ! is_string($roundtrip) || ! hash_equals($plaintext, $roundtrip)) {
            throw new JmbEncryptedBackfillException('encrypt_roundtrip_failed', $scope, $mapping['table'], $id);
        }

        if (! $dryRun) {
            DB::table($mapping['table'])
                ->where($pk, $id)
                ->update([$mapping['encrypted'] => $ciphertext]);
        }

        return 'encrypted';
    }

    private function reportProperty(string $category): string
    {
        return match ($category) {
            'encrypted' => 'encrypted',
            'already_valid' => 'alreadyValid',
            'skipped_no_plaintext' => 'skippedNoPlaintext',
            default => throw new JmbEncryptedBackfillException('Unknown JMB backfill category.'),
        };
    }
}
