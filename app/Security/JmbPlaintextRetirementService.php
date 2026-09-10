<?php

namespace App\Security;

use Illuminate\Support\Facades\DB;

/**
 * Controlled plaintext JMB/JMBG retirement. Query-builder NULL of the seven
 * plaintext columns only. Encrypted and lookup columns are never written.
 */
final class JmbPlaintextRetirementService
{
    public const SCOPE_ALL = 'all';

    /**
     * Deterministic scope order matching the seven approved plaintext columns.
     *
     * @var array<string, string>
     */
    public const SCOPES = [
        'users' => 'users.jmb',
        'physical-identities' => 'physical_person_identities.jmb',
        'authorized-persons' => 'legal_entity_authorized_persons.jmb',
        'foreign-branch-representatives' => 'foreign_branch_representatives.jmb',
        'applications-physical-person' => 'applications.physical_person_jmbg',
        'applications-applicant' => 'applications.applicant_jmbg',
        'business-plans' => 'business_plans.applicant_jmbg',
    ];

    /**
     * Test-only hook. Invoked after each scope write, still inside the transaction.
     */
    public ?\Closure $afterScopeCallback = null;

    /**
     * Test-only hook. Invoked after all scope writes, before post-verification.
     */
    public ?\Closure $afterWritesCallback = null;

    public function __construct(
        private readonly JmbPlaintextRetirementPrecheckService $precheck,
    ) {
    }

    public function run(string $scope, int $chunk, bool $dryRun): JmbPlaintextRetirementReport
    {
        $this->assertChunk($chunk);
        $selected = $this->resolveMappings($scope);

        if ($dryRun) {
            return $this->dryRun($scope, $selected, $chunk);
        }

        if (! (bool) config('jmb.plaintext_retirement.enabled', false)) {
            throw new JmbPlaintextRetirementException('retirement_disabled');
        }

        return $this->apply($scope, $selected, $chunk);
    }

    /**
     * @param  list<array{scope: string, mapping: array<string, mixed>}>  $selected
     */
    private function dryRun(string $scope, array $selected, int $chunk): JmbPlaintextRetirementReport
    {
        $precheck = $this->executePrecheck($chunk);
        $scopes = [];
        $totalWouldNull = 0;

        foreach ($selected as $item) {
            $plaintextRows = $this->countPresent($item['mapping']);
            $emptyStringRows = $this->countEmptyString($item['mapping']);
            $scopes[] = [
                'scope' => $item['scope'],
                'column' => $item['mapping']['column'],
                'plaintext_rows' => $plaintextRows,
                'would_null' => $plaintextRows,
                'nulled' => 0,
                'post_plaintext_non_null' => $plaintextRows,
                'empty_string_rows' => $emptyStringRows,
                'status' => $precheck->passed() ? 'READY' : 'BLOCKED',
            ];
            $totalWouldNull += $plaintextRows;
        }

        return new JmbPlaintextRetirementReport(
            dryRun: true,
            scope: $scope,
            precheckPassed: $precheck->passed(),
            passed: $precheck->passed(),
            scopes: $scopes,
            totalWouldNull: $totalWouldNull,
        );
    }

    /**
     * @param  list<array{scope: string, mapping: array<string, mixed>}>  $selected
     */
    private function apply(string $requestedScope, array $selected, int $chunk): JmbPlaintextRetirementReport
    {
        try {
            return DB::transaction(function () use ($requestedScope, $selected, $chunk) {
                $precheck = $this->executePrecheck($chunk);
                if (! $precheck->passed()) {
                    throw new JmbPlaintextRetirementException('precheck_failed');
                }

                $beforeEncrypted = $this->encryptedLookupFingerprint($chunk);
                $beforeUnselected = $this->snapshotUnselectedPlaintext($selected, $chunk);
                $scopes = [];
                $totalNulled = 0;

                foreach ($selected as $item) {
                    $nulled = $this->nullPresentPlaintext($item['mapping']);
                    if (is_callable($this->afterScopeCallback)) {
                        ($this->afterScopeCallback)($item['scope']);
                    }

                    $postPresent = $this->countPresent($item['mapping']);
                    $emptyStringRows = $this->countEmptyString($item['mapping']);
                    $status = $postPresent === 0 ? 'PASS' : 'FAIL';
                    $scopes[] = [
                        'scope' => $item['scope'],
                        'column' => $item['mapping']['column'],
                        'plaintext_rows' => $nulled,
                        'would_null' => $nulled,
                        'nulled' => $nulled,
                        'post_plaintext_non_null' => $postPresent,
                        'empty_string_rows' => $emptyStringRows,
                        'status' => $status,
                    ];
                    $totalNulled += $nulled;

                    if ($status !== 'PASS') {
                        throw new JmbPlaintextRetirementException('post_verification_failed');
                    }
                }

                if (is_callable($this->afterWritesCallback)) {
                    ($this->afterWritesCallback)();
                }

                $this->assertIntegrityPreserved($beforeEncrypted, $beforeUnselected, $selected, $chunk);

                return new JmbPlaintextRetirementReport(
                    dryRun: false,
                    scope: $requestedScope,
                    precheckPassed: true,
                    passed: true,
                    scopes: $scopes,
                    totalNulled: $totalNulled,
                );
            });
        } catch (JmbPlaintextRetirementException $e) {
            throw $e;
        } catch (JmbPlaintextRetirementPrecheckException $e) {
            throw new JmbPlaintextRetirementException($e->reason);
        } catch (\Throwable) {
            throw new JmbPlaintextRetirementException('apply_failed');
        }
    }

    private function executePrecheck(int $chunk): JmbPlaintextRetirementPrecheckReport
    {
        $previousChunk = config('jmb.backfill.chunk_default');
        config(['jmb.backfill.chunk_default' => $chunk]);

        try {
            try {
                $encryption = app(JmbEncryptionService::class);
            } catch (JmbEncryptionException) {
                return $this->blockedConfigReport(encryptionAvailable: false, lookupAvailable: true);
            }

            try {
                $lookup = app(JmbLookupService::class);
                $lookup->assertConfigured();
            } catch (JmbLookupException) {
                return $this->blockedConfigReport(encryptionAvailable: true, lookupAvailable: false);
            }

            return $this->precheck->run($encryption, $lookup, true, true);
        } catch (JmbPlaintextRetirementPrecheckException $e) {
            throw new JmbPlaintextRetirementException($e->reason);
        } finally {
            config(['jmb.backfill.chunk_default' => $previousChunk]);
        }
    }

    private function blockedConfigReport(bool $encryptionAvailable, bool $lookupAvailable): JmbPlaintextRetirementPrecheckReport
    {
        return new JmbPlaintextRetirementPrecheckReport(
            appEnv: (string) app()->environment(),
            retirementEnabled: (bool) config('jmb.plaintext_retirement.enabled', false),
            encryptionAvailable: $encryptionAvailable,
            lookupAvailable: $lookupAvailable,
            schemaPlaintextNullable: false,
            schemaUsersJmbUnique: false,
            schemaUsersJmbLookupUnique: false,
            schemaFlLookupIndex: false,
            schemaFlLookupNonUnique: false,
            configBlocked: true,
        );
    }

    /**
     * @return list<array{scope: string, mapping: array<string, mixed>}>
     */
    private function resolveMappings(string $scope): array
    {
        $scope = trim($scope);
        if ($scope === '') {
            $scope = self::SCOPE_ALL;
        }

        $pairsByColumn = [];
        foreach (JmbPlaintextRetirementPrecheckService::PAIRS as $mapping) {
            $pairsByColumn[$mapping['column']] = $mapping;
        }

        if ($scope === self::SCOPE_ALL) {
            $selected = [];
            foreach (self::SCOPES as $scopeName => $column) {
                $selected[] = [
                    'scope' => $scopeName,
                    'mapping' => $pairsByColumn[$column],
                ];
            }

            return $selected;
        }

        if (! isset(self::SCOPES[$scope])) {
            throw new JmbPlaintextRetirementException('invalid_scope');
        }

        return [[
            'scope' => $scope,
            'mapping' => $pairsByColumn[self::SCOPES[$scope]],
        ]];
    }

    private function assertChunk(int $chunk): void
    {
        if ($chunk < 1) {
            throw new JmbPlaintextRetirementException('invalid_chunk');
        }
    }

    /**
     * @param  array<string, mixed>  $mapping
     */
    private function countPresent(array $mapping): int
    {
        $column = $this->quoteColumn((string) $mapping['plaintext']);

        return (int) DB::table((string) $mapping['table'])
            ->whereNotNull((string) $mapping['plaintext'])
            ->whereRaw('TRIM('.$column.') <> ?', [''])
            ->count();
    }

    /**
     * @param  array<string, mixed>  $mapping
     */
    private function countEmptyString(array $mapping): int
    {
        $column = $this->quoteColumn((string) $mapping['plaintext']);

        return (int) DB::table((string) $mapping['table'])
            ->whereNotNull((string) $mapping['plaintext'])
            ->whereRaw('TRIM('.$column.') = ?', [''])
            ->count();
    }

    /**
     * @param  array<string, mixed>  $mapping
     */
    private function nullPresentPlaintext(array $mapping): int
    {
        $plaintext = (string) $mapping['plaintext'];
        $column = $this->quoteColumn($plaintext);

        return (int) DB::table((string) $mapping['table'])
            ->whereNotNull($plaintext)
            ->whereRaw('TRIM('.$column.') <> ?', [''])
            ->update([$plaintext => null]);
    }

    /**
     * @param  list<array{scope: string, mapping: array<string, mixed>}>  $selected
     */
    private function assertIntegrityPreserved(
        string $beforeEncrypted,
        string $beforeUnselected,
        array $selected,
        int $chunk,
    ): void {
        foreach ($selected as $item) {
            if ($this->countPresent($item['mapping']) !== 0) {
                throw new JmbPlaintextRetirementException('post_verification_failed');
            }
        }

        if ($this->encryptedLookupFingerprint($chunk) !== $beforeEncrypted) {
            throw new JmbPlaintextRetirementException('post_verification_failed');
        }

        if ($this->snapshotUnselectedPlaintext($selected, $chunk) !== $beforeUnselected) {
            throw new JmbPlaintextRetirementException('post_verification_failed');
        }
    }

    /**
     * Capture unselected plaintext before apply so post-verify can compare.
     *
     * @param  list<array{scope: string, mapping: array<string, mixed>}>  $selected
     */
    private function snapshotUnselectedPlaintext(array $selected, int $chunk): string
    {
        $selectedColumns = [];
        foreach ($selected as $item) {
            $selectedColumns[$item['mapping']['column']] = true;
        }

        return $this->unselectedPlaintextFingerprint($selectedColumns, $chunk);
    }

    /**
     * @param  array<string, true>  $selectedColumns
     */
    private function unselectedPlaintextFingerprint(array $selectedColumns, int $chunk): string
    {
        $hash = hash_init('sha256');

        foreach (JmbPlaintextRetirementPrecheckService::PAIRS as $mapping) {
            if (isset($selectedColumns[$mapping['column']])) {
                continue;
            }

            $this->appendColumnFingerprint(
                $hash,
                (string) $mapping['table'],
                (string) $mapping['pk'],
                [(string) $mapping['plaintext']],
                $chunk
            );
        }

        return hash_final($hash);
    }

    private function encryptedLookupFingerprint(int $chunk): string
    {
        $hash = hash_init('sha256');

        foreach (JmbPlaintextRetirementPrecheckService::PAIRS as $mapping) {
            $columns = [(string) $mapping['encrypted']];
            if ($mapping['lookup'] !== null) {
                $columns[] = (string) $mapping['lookup'];
            }

            $this->appendColumnFingerprint(
                $hash,
                (string) $mapping['table'],
                (string) $mapping['pk'],
                $columns,
                $chunk
            );
        }

        return hash_final($hash);
    }

    /**
     * @param  list<string>  $columns
     * @param  \HashContext|object  $hash
     */
    private function appendColumnFingerprint(
        object $hash,
        string $table,
        string $pk,
        array $columns,
        int $chunk,
    ): void {
        $select = array_values(array_unique(array_merge([$pk], $columns)));

        DB::table($table)
            ->select($select)
            ->orderBy($pk)
            ->chunkById($chunk, function ($rows) use ($hash, $pk, $columns): void {
                foreach ($rows as $row) {
                    $parts = [(string) $row->{$pk}];
                    foreach ($columns as $column) {
                        $parts[] = (string) ($row->{$column} ?? '');
                    }
                    hash_update($hash, implode("\x1e", $parts)."\n");
                }
            }, $pk);
    }

    private function quoteColumn(string $column): string
    {
        if (preg_match('/^[a-z_]+$/', $column) !== 1) {
            throw new JmbPlaintextRetirementException('invalid_column');
        }

        return '`'.$column.'`';
    }
}
