<?php

namespace App\Security;

final class JmbPlaintextRetirementPrecheckReport
{
    /**
     * @param  list<array{
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
     * }>  $pairs
     */
    public function __construct(
        public readonly string $appEnv,
        public readonly bool $retirementEnabled,
        public readonly bool $encryptionAvailable,
        public readonly bool $lookupAvailable,
        public readonly bool $schemaPlaintextNullable,
        public readonly bool $schemaUsersJmbUnique,
        public readonly bool $schemaUsersJmbLookupUnique,
        public readonly bool $schemaFlLookupIndex,
        public readonly bool $schemaFlLookupNonUnique,
        public array $pairs = [],
        public int $usersNonNullLookupRows = 0,
        public int $duplicateDigestGroups = 0,
        public int $rowsInDuplicateGroups = 0,
        public bool $configBlocked = false,
        public ?string $systemFailureReason = null,
    ) {
    }

    public function schemaPassed(): bool
    {
        return $this->schemaPlaintextNullable
            && $this->schemaUsersJmbUnique
            && $this->schemaUsersJmbLookupUnique
            && $this->schemaFlLookupIndex
            && $this->schemaFlLookupNonUnique;
    }

    public function passed(): bool
    {
        if ($this->configBlocked || $this->systemFailureReason !== null) {
            return false;
        }

        if (! $this->encryptionAvailable || ! $this->lookupAvailable) {
            return false;
        }

        if (! $this->schemaPassed()) {
            return false;
        }

        if ($this->duplicateDigestGroups > 0) {
            return false;
        }

        if (count($this->pairs) !== 7) {
            return false;
        }

        foreach ($this->pairs as $pair) {
            if ($pair['status'] !== 'READY') {
                return false;
            }
        }

        return true;
    }
}
