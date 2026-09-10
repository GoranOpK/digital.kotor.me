<?php

namespace App\Security;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only pre-retirement verification for the seven plaintext JMB/JMBG pairs.
 * SELECT / schema metadata / in-process decrypt+digest only. No writes.
 */
final class JmbPlaintextRetirementPrecheckService
{
    public const DEFAULT_CHUNK = 100;

    public const USERS_LOOKUP_UNIQUE = 'users_jmb_lookup_unique';

    public const PHYSICAL_PERSON_LOOKUP_INDEX = 'physical_person_identities_jmb_lookup_index';

    /**
     * @var list<array{
     *     column: string,
     *     table: string,
     *     plaintext: string,
     *     encrypted: string,
     *     lookup: string|null,
     *     pk: string
     * }>
     */
    public const PAIRS = [
        [
            'column' => 'users.jmb',
            'table' => 'users',
            'plaintext' => 'jmb',
            'encrypted' => 'jmb_encrypted',
            'lookup' => 'jmb_lookup',
            'pk' => 'id',
        ],
        [
            'column' => 'physical_person_identities.jmb',
            'table' => 'physical_person_identities',
            'plaintext' => 'jmb',
            'encrypted' => 'jmb_encrypted',
            'lookup' => 'jmb_lookup',
            'pk' => 'id',
        ],
        [
            'column' => 'legal_entity_authorized_persons.jmb',
            'table' => 'legal_entity_authorized_persons',
            'plaintext' => 'jmb',
            'encrypted' => 'jmb_encrypted',
            'lookup' => null,
            'pk' => 'id',
        ],
        [
            'column' => 'foreign_branch_representatives.jmb',
            'table' => 'foreign_branch_representatives',
            'plaintext' => 'jmb',
            'encrypted' => 'jmb_encrypted',
            'lookup' => null,
            'pk' => 'id',
        ],
        [
            'column' => 'applications.physical_person_jmbg',
            'table' => 'applications',
            'plaintext' => 'physical_person_jmbg',
            'encrypted' => 'physical_person_jmbg_encrypted',
            'lookup' => null,
            'pk' => 'id',
        ],
        [
            'column' => 'applications.applicant_jmbg',
            'table' => 'applications',
            'plaintext' => 'applicant_jmbg',
            'encrypted' => 'applicant_jmbg_encrypted',
            'lookup' => null,
            'pk' => 'id',
        ],
        [
            'column' => 'business_plans.applicant_jmbg',
            'table' => 'business_plans',
            'plaintext' => 'applicant_jmbg',
            'encrypted' => 'applicant_jmbg_encrypted',
            'lookup' => null,
            'pk' => 'id',
        ],
    ];

    /**
     * @var list<string>
     */
    private const TABLES = [
        'users',
        'physical_person_identities',
        'legal_entity_authorized_persons',
        'foreign_branch_representatives',
        'applications',
        'business_plans',
    ];

    public function run(
        JmbEncryptionService $encryption,
        JmbLookupService $lookup,
        bool $encryptionAvailable,
        bool $lookupAvailable,
    ): JmbPlaintextRetirementPrecheckReport {
        $schema = $this->inspectSchema();
        $collisions = $this->usersLookupCollisions();

        $pairs = [];
        foreach (self::PAIRS as $mapping) {
            $pairs[] = $this->inspectPair(
                $mapping,
                $encryption,
                $lookup,
                $collisions['duplicate_digest_groups']
            );
        }

        return new JmbPlaintextRetirementPrecheckReport(
            appEnv: (string) app()->environment(),
            retirementEnabled: (bool) config('jmb.plaintext_retirement.enabled', false),
            encryptionAvailable: $encryptionAvailable,
            lookupAvailable: $lookupAvailable,
            schemaPlaintextNullable: $schema['plaintext_nullable'],
            schemaUsersJmbUnique: $schema['users_jmb_unique'],
            schemaUsersJmbLookupUnique: $schema['users_jmb_lookup_unique'],
            schemaFlLookupIndex: $schema['fl_lookup_index'],
            schemaFlLookupNonUnique: $schema['fl_lookup_non_unique'],
            pairs: $pairs,
            usersNonNullLookupRows: $collisions['non_null_lookup_rows'],
            duplicateDigestGroups: $collisions['duplicate_digest_groups'],
            rowsInDuplicateGroups: $collisions['rows_in_duplicate_groups'],
        );
    }

    /**
     * @param  array{
     *     column: string,
     *     table: string,
     *     plaintext: string,
     *     encrypted: string,
     *     lookup: string|null,
     *     pk: string
     * }  $mapping
     * @return array{
     *     column: string,
     *     table: string,
     *     plaintext: string,
     *     encrypted: string,
     *     has_lookup: bool,
     *     total: int,
     *     plaintext_non_null: int,
     *     encrypted_non_null: int,
     *     both_present: int,
     *     plaintext_only: int,
     *     encrypted_only: int,
     *     neither: int,
     *     rows_checked: int,
     *     matches: int,
     *     mismatches: int,
     *     decrypt_failures: int,
     *     malformed_plaintext: int,
     *     expected_lookup_rows: int,
     *     lookup_non_null: int,
     *     lookup_missing: int,
     *     digest_matches: int,
     *     digest_mismatches: int,
     *     status: string
     * }
     */
    private function inspectPair(
        array $mapping,
        JmbEncryptionService $encryption,
        JmbLookupService $lookup,
        int $duplicateDigestGroups,
    ): array {
        $this->assertMappingColumns($mapping);

        $counts = [
            'total' => 0,
            'plaintext_non_null' => 0,
            'encrypted_non_null' => 0,
            'both_present' => 0,
            'plaintext_only' => 0,
            'encrypted_only' => 0,
            'neither' => 0,
            'rows_checked' => 0,
            'matches' => 0,
            'mismatches' => 0,
            'decrypt_failures' => 0,
            'malformed_plaintext' => 0,
            'expected_lookup_rows' => 0,
            'lookup_non_null' => 0,
            'lookup_missing' => 0,
            'digest_matches' => 0,
            'digest_mismatches' => 0,
        ];

        $columns = [$mapping['pk'], $mapping['plaintext'], $mapping['encrypted']];
        if ($mapping['lookup'] !== null) {
            $columns[] = $mapping['lookup'];
        }

        $chunk = (int) config('jmb.backfill.chunk_default', self::DEFAULT_CHUNK);
        if ($chunk < 1) {
            $chunk = self::DEFAULT_CHUNK;
        }

        DB::table($mapping['table'])
            ->select($columns)
            ->orderBy($mapping['pk'])
            ->chunkById($chunk, function ($rows) use ($mapping, $encryption, $lookup, &$counts): void {
                foreach ($rows as $row) {
                    $this->inspectRow($row, $mapping, $encryption, $lookup, $counts);
                }
            }, $mapping['pk']);

        $ready = $counts['plaintext_only'] === 0
            && $counts['mismatches'] === 0
            && $counts['decrypt_failures'] === 0
            && $counts['lookup_missing'] === 0
            && $counts['digest_mismatches'] === 0;

        if ($mapping['column'] === 'users.jmb' && $duplicateDigestGroups > 0) {
            $ready = false;
        }

        return [
            'column' => $mapping['column'],
            'table' => $mapping['table'],
            'plaintext' => $mapping['plaintext'],
            'encrypted' => $mapping['encrypted'],
            'has_lookup' => $mapping['lookup'] !== null,
            ...$counts,
            'status' => $ready ? 'READY' : 'BLOCKED',
        ];
    }

    /**
     * @param  array{
     *     column: string,
     *     table: string,
     *     plaintext: string,
     *     encrypted: string,
     *     lookup: string|null,
     *     pk: string
     * }  $mapping
     * @param  array<string, int>  $counts
     */
    private function inspectRow(
        object $row,
        array $mapping,
        JmbEncryptionService $encryption,
        JmbLookupService $lookup,
        array &$counts,
    ): void {
        $counts['total']++;

        $plaintextLogical = $this->normalize($row->{$mapping['plaintext']} ?? null);
        $encryptedStored = $this->normalize($row->{$mapping['encrypted']} ?? null);
        $hasPlaintext = $plaintextLogical !== null;
        $hasEncrypted = $encryptedStored !== null;

        if ($hasPlaintext) {
            $counts['plaintext_non_null']++;
            if (! $this->isThirteenDigit($plaintextLogical)) {
                $counts['malformed_plaintext']++;
            }
        }
        if ($hasEncrypted) {
            $counts['encrypted_non_null']++;
        }

        if ($hasPlaintext && $hasEncrypted) {
            $counts['both_present']++;
            $counts['rows_checked']++;
            $decrypted = $this->decryptOrNull($encryption, $encryptedStored, $counts);
            if ($decrypted === false) {
                return;
            }

            $decryptedLogical = $this->normalize($decrypted);
            if ($decryptedLogical !== null && hash_equals($plaintextLogical, $decryptedLogical)) {
                $counts['matches']++;
            } else {
                $counts['mismatches']++;
            }

            $this->inspectLookup(
                $mapping,
                $row,
                $decryptedLogical,
                $lookup,
                $counts
            );

            return;
        }

        if ($hasPlaintext) {
            $counts['plaintext_only']++;

            return;
        }

        if ($hasEncrypted) {
            $counts['encrypted_only']++;
            $decrypted = $this->decryptOrNull($encryption, $encryptedStored, $counts);
            if ($decrypted === false) {
                return;
            }

            $this->inspectLookup(
                $mapping,
                $row,
                $this->normalize($decrypted),
                $lookup,
                $counts
            );

            return;
        }

        $counts['neither']++;
    }

    /**
     * @param  array{
     *     column: string,
     *     table: string,
     *     plaintext: string,
     *     encrypted: string,
     *     lookup: string|null,
     *     pk: string
     * }  $mapping
     * @param  array<string, int>  $counts
     */
    private function inspectLookup(
        array $mapping,
        object $row,
        ?string $logical,
        JmbLookupService $lookup,
        array &$counts,
    ): void {
        if ($mapping['lookup'] === null) {
            return;
        }

        if (! $this->isThirteenDigit($logical)) {
            return;
        }

        $counts['expected_lookup_rows']++;

        $storedLookup = $this->normalize($row->{$mapping['lookup']} ?? null);
        if ($storedLookup === null) {
            $counts['lookup_missing']++;

            return;
        }

        $counts['lookup_non_null']++;

        try {
            $expected = $lookup->digest($logical);
        } catch (JmbLookupException) {
            $counts['digest_mismatches']++;

            return;
        }

        if (is_string($expected) && hash_equals($storedLookup, $expected)) {
            $counts['digest_matches']++;

            return;
        }

        $counts['digest_mismatches']++;
    }

    /**
     * @param  array<string, int>  $counts
     * @return string|false|null  false = decrypt failure already counted
     */
    private function decryptOrNull(
        JmbEncryptionService $encryption,
        string $ciphertext,
        array &$counts,
    ): string|false|null {
        try {
            return $encryption->decrypt($ciphertext);
        } catch (JmbEncryptionException) {
            $counts['decrypt_failures']++;

            return false;
        }
    }

    /**
     * @return array{
     *     plaintext_nullable: bool,
     *     users_jmb_unique: bool,
     *     users_jmb_lookup_unique: bool,
     *     fl_lookup_index: bool,
     *     fl_lookup_non_unique: bool
     * }
     */
    private function inspectSchema(): array
    {
        $nullable = true;
        foreach (self::PAIRS as $mapping) {
            if (! $this->columnIsNullable($mapping['table'], $mapping['plaintext'])) {
                $nullable = false;
                break;
            }
        }

        $usersJmbUnique = $this->columnHasUniqueIndex('users', 'jmb');
        $usersLookupUnique = $this->indexIsUnique('users', self::USERS_LOOKUP_UNIQUE, 'jmb_lookup')
            || $this->columnHasUniqueIndex('users', 'jmb_lookup');
        $flIndex = $this->indexExists(
            'physical_person_identities',
            self::PHYSICAL_PERSON_LOOKUP_INDEX,
            'jmb_lookup'
        ) || $this->columnHasIndex('physical_person_identities', 'jmb_lookup');
        $flNonUnique = $flIndex && ! $this->columnHasUniqueIndex('physical_person_identities', 'jmb_lookup');

        return [
            'plaintext_nullable' => $nullable,
            'users_jmb_unique' => $usersJmbUnique,
            'users_jmb_lookup_unique' => $usersLookupUnique,
            'fl_lookup_index' => $flIndex,
            'fl_lookup_non_unique' => $flNonUnique,
        ];
    }

    /**
     * @return array{
     *     non_null_lookup_rows: int,
     *     duplicate_digest_groups: int,
     *     rows_in_duplicate_groups: int
     * }
     */
    private function usersLookupCollisions(): array
    {
        $nonNull = (int) DB::table('users')
            ->whereNotNull('jmb_lookup')
            ->where('jmb_lookup', '<>', '')
            ->count();

        $groups = DB::table('users')
            ->selectRaw('COUNT(*) as aggregate_cnt')
            ->whereNotNull('jmb_lookup')
            ->where('jmb_lookup', '<>', '')
            ->groupBy('jmb_lookup')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $duplicateGroups = $groups->count();
        $rowsInGroups = (int) $groups->sum('aggregate_cnt');

        return [
            'non_null_lookup_rows' => $nonNull,
            'duplicate_digest_groups' => $duplicateGroups,
            'rows_in_duplicate_groups' => $rowsInGroups,
        ];
    }

    /**
     * @param  array{
     *     column: string,
     *     table: string,
     *     plaintext: string,
     *     encrypted: string,
     *     lookup: string|null,
     *     pk: string
     * }  $mapping
     */
    private function assertMappingColumns(array $mapping): void
    {
        $this->assertAllowedTable($mapping['table']);

        $required = [$mapping['pk'], $mapping['plaintext'], $mapping['encrypted']];
        if ($mapping['lookup'] !== null) {
            $required[] = $mapping['lookup'];
        }

        foreach ($required as $column) {
            if (! Schema::hasColumn($mapping['table'], $column)) {
                throw new JmbPlaintextRetirementPrecheckException('missing_column');
            }
        }
    }

    private function assertAllowedTable(string $table): void
    {
        if (! in_array($table, self::TABLES, true)) {
            throw new JmbPlaintextRetirementPrecheckException('invalid_table');
        }
    }

    private function columnIsNullable(string $table, string $column): bool
    {
        $this->assertAllowedTable($table);

        $row = DB::selectOne(
            'SELECT IS_NULLABLE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );

        return is_object($row) && strtoupper((string) $row->IS_NULLABLE) === 'YES';
    }

    private function columnHasUniqueIndex(string $table, string $column): bool
    {
        foreach ($this->showIndex($table) as $index) {
            if ((string) $index->Column_name !== $column) {
                continue;
            }
            if ((string) ($index->Key_name ?? '') === 'PRIMARY') {
                continue;
            }
            if ((int) $index->Non_unique === 0) {
                return true;
            }
        }

        return false;
    }

    private function columnHasIndex(string $table, string $column): bool
    {
        foreach ($this->showIndex($table) as $index) {
            if ((string) $index->Column_name === $column) {
                return true;
            }
        }

        return false;
    }

    private function indexExists(string $table, string $indexName, string $column): bool
    {
        foreach ($this->showIndex($table) as $index) {
            if ((string) $index->Key_name === $indexName && (string) $index->Column_name === $column) {
                return true;
            }
        }

        return false;
    }

    private function indexIsUnique(string $table, string $indexName, string $column): bool
    {
        foreach ($this->showIndex($table) as $index) {
            if ((string) $index->Key_name === $indexName && (string) $index->Column_name === $column) {
                return (int) $index->Non_unique === 0;
            }
        }

        return false;
    }

    /**
     * @return list<object>
     */
    private function showIndex(string $table): array
    {
        $this->assertAllowedTable($table);

        return DB::select('SHOW INDEX FROM `'.$table.'`');
    }

    private function normalize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function isThirteenDigit(?string $value): bool
    {
        return $value !== null && preg_match('/^[0-9]{13}$/', $value) === 1;
    }
}
