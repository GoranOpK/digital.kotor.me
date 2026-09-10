<?php

namespace App\Security;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copy plaintext JMB into parallel lookup digest columns.
 * Does not change plaintext or encrypted columns. Does not cut over uniqueness.
 * Each successful lookup write is committed independently.
 */
final class JmbLookupBackfillService
{
    public const DEFAULT_CHUNK = 100;

    public const MIN_CHUNK = 1;

    public const MAX_CHUNK = 500;

    public const SCOPE_ALL = 'all';

    public const SCOPE_USERS = 'users';

    public const SCOPE_PHYSICAL_IDENTITIES = 'physical-identities';

    /**
     * @var array<string, array{table: string, plaintext: string, lookup: string, pk: string}>
     */
    public const MAPPINGS = [
        self::SCOPE_USERS => [
            'table' => 'users',
            'plaintext' => 'jmb',
            'lookup' => 'jmb_lookup',
            'pk' => 'id',
        ],
        self::SCOPE_PHYSICAL_IDENTITIES => [
            'table' => 'physical_person_identities',
            'plaintext' => 'jmb',
            'lookup' => 'jmb_lookup',
            'pk' => 'id',
        ],
    ];

    /**
     * @var array<string, array<string, int>>
     */
    private array $seenDigests = [];

    public static function scopeList(): string
    {
        return implode(', ', array_merge([self::SCOPE_ALL], array_keys(self::MAPPINGS)));
    }

    public function run(
        JmbLookupService $lookup,
        ?string $scope,
        int $chunk,
        bool $dryRun,
    ): JmbLookupBackfillReport {
        $lookup->assertConfigured();
        $this->assertChunk($chunk);
        $this->seenDigests = [];

        $mappings = $this->resolveMappings($scope);
        $report = new JmbLookupBackfillReport($dryRun);

        foreach ($mappings as $scopeName => $mapping) {
            $scopeCounters = [
                'scope' => $scopeName,
                'table' => $mapping['table'],
                'scanned' => 0,
                'backfilled' => 0,
                'already_valid' => 0,
                'empty' => 0,
                'collisions' => 0,
                'errors' => 0,
            ];

            try {
                $this->assertMappingColumns($mapping);

                DB::table($mapping['table'])
                    ->select([$mapping['pk'], $mapping['plaintext'], $mapping['lookup']])
                    ->orderBy($mapping['pk'])
                    ->chunkById($chunk, function ($rows) use ($lookup, $mapping, $scopeName, $dryRun, $report, &$scopeCounters): void {
                        foreach ($rows as $row) {
                            $report->scanned++;
                            $scopeCounters['scanned']++;

                            $category = $this->processRow($lookup, $mapping, $scopeName, $row, $dryRun);
                            $report->{$this->reportProperty($category)}++;
                            $scopeCounters[$category]++;
                        }
                    }, $mapping['pk']);
            } catch (JmbLookupBackfillException $e) {
                $report->errors++;
                $scopeCounters['errors']++;
                if ($e->reason === 'lookup_digest_collision') {
                    $report->collisions++;
                    $scopeCounters['collisions']++;
                }
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
     * @return array<string, array{table: string, plaintext: string, lookup: string, pk: string}>
     */
    public function resolveMappings(?string $scope): array
    {
        if ($scope === null || $scope === '' || $scope === self::SCOPE_ALL) {
            return self::MAPPINGS;
        }

        if (! isset(self::MAPPINGS[$scope])) {
            throw new JmbLookupBackfillException(
                'Unknown JMB lookup backfill scope. Allowed: '.self::scopeList().'.'
            );
        }

        return [$scope => self::MAPPINGS[$scope]];
    }

    public function assertChunk(int $chunk): void
    {
        $max = (int) config('jmb.backfill.chunk_max', self::MAX_CHUNK);
        if ($chunk < self::MIN_CHUNK || $chunk > $max) {
            throw new JmbLookupBackfillException(
                'Invalid JMB lookup backfill chunk. Expected an integer from '.self::MIN_CHUNK.' to '.$max.'.'
            );
        }
    }

    /**
     * @param  array{table: string, plaintext: string, lookup: string, pk: string}  $mapping
     */
    private function assertMappingColumns(array $mapping): void
    {
        foreach ([$mapping['pk'], $mapping['plaintext'], $mapping['lookup']] as $column) {
            if (! Schema::hasColumn($mapping['table'], $column)) {
                throw new JmbLookupBackfillException(
                    'JMB lookup backfill mapping is missing a required column.',
                    '',
                    $mapping['table']
                );
            }
        }
    }

    /**
     * @param  array{table: string, plaintext: string, lookup: string, pk: string}  $mapping
     */
    private function processRow(
        JmbLookupService $lookup,
        array $mapping,
        string $scope,
        object $row,
        bool $dryRun,
    ): string {
        $pk = $mapping['pk'];
        $id = (int) $row->{$pk};
        $plaintext = $row->{$mapping['plaintext']};
        $storedLookup = $this->storedLookup($row->{$mapping['lookup']});
        $hasPlaintext = is_string($plaintext) && trim($plaintext) !== '';
        $hasLookup = $storedLookup !== null;

        if (! $hasPlaintext) {
            if ($hasLookup) {
                throw new JmbLookupBackfillException('empty_plaintext_with_lookup', $scope, $mapping['table'], $id);
            }

            return 'empty';
        }

        try {
            $digest = $lookup->digest($plaintext);
        } catch (JmbLookupException) {
            throw new JmbLookupBackfillException('invalid_plaintext', $scope, $mapping['table'], $id);
        }

        if (! is_string($digest) || $digest === '') {
            throw new JmbLookupBackfillException('invalid_plaintext', $scope, $mapping['table'], $id);
        }

        $this->assertDigestUnused($mapping['table'], $pk, $id, $digest, $scope);

        if ($hasLookup) {
            if (! hash_equals($digest, $storedLookup)) {
                throw new JmbLookupBackfillException('lookup_digest_mismatch', $scope, $mapping['table'], $id);
            }

            return 'already_valid';
        }

        if (! $dryRun) {
            $this->writeAndVerify($mapping, $id, $digest, $scope);
        }

        return 'backfilled';
    }

    /**
     * @param  array{table: string, plaintext: string, lookup: string, pk: string}  $mapping
     */
    private function writeAndVerify(array $mapping, int $id, string $digest, string $scope): void
    {
        try {
            DB::transaction(function () use ($mapping, $id, $digest, $scope): void {
                DB::table($mapping['table'])
                    ->where($mapping['pk'], $id)
                    ->update([$mapping['lookup'] => $digest]);

                $fresh = $this->storedLookup(
                    DB::table($mapping['table'])->where($mapping['pk'], $id)->value($mapping['lookup'])
                );

                if ($fresh === null || ! hash_equals($digest, $fresh)) {
                    throw new JmbLookupBackfillException('lookup_write_verify_failed', $scope, $mapping['table'], $id);
                }
            });
        } catch (JmbLookupBackfillException $e) {
            throw $e;
        } catch (QueryException) {
            throw new JmbLookupBackfillException('lookup_digest_collision', $scope, $mapping['table'], $id);
        }
    }

    private function assertDigestUnused(string $table, string $pk, int $id, string $digest, string $scope): void
    {
        if (isset($this->seenDigests[$table][$digest]) && $this->seenDigests[$table][$digest] !== $id) {
            throw new JmbLookupBackfillException('lookup_digest_collision', $scope, $table, $id);
        }

        $otherId = DB::table($table)
            ->where($pk, '!=', $id)
            ->whereNotNull('jmb_lookup')
            ->where('jmb_lookup', $digest)
            ->orderBy($pk)
            ->value($pk);

        if ($otherId !== null) {
            throw new JmbLookupBackfillException('lookup_digest_collision', $scope, $table, $id);
        }

        $this->seenDigests[$table][$digest] = $id;
    }

    private function storedLookup(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = rtrim($value);
        if ($trimmed === '') {
            return null;
        }

        return $trimmed;
    }

    private function reportProperty(string $category): string
    {
        return match ($category) {
            'backfilled' => 'backfilled',
            'already_valid' => 'alreadyValid',
            'empty' => 'empty',
            default => throw new JmbLookupBackfillException('Unknown JMB lookup backfill category.'),
        };
    }
}
