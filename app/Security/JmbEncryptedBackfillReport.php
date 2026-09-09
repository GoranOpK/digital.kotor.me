<?php

namespace App\Security;

final class JmbEncryptedBackfillReport
{
    /**
     * @param  list<array{
     *     scope: string,
     *     table: string,
     *     scanned: int,
     *     encrypted: int,
     *     already_valid: int,
     *     skipped_no_plaintext: int,
     *     errors: int
     * }>  $scopes
     * @param  array{scope: string, table: string, id: int, reason: string}|null  $error
     */
    public function __construct(
        public readonly bool $dryRun,
        public array $scopes = [],
        public int $scanned = 0,
        public int $encrypted = 0,
        public int $alreadyValid = 0,
        public int $skippedNoPlaintext = 0,
        public int $errors = 0,
        public ?array $error = null,
    ) {
    }

    public function failed(): bool
    {
        return $this->errors > 0 || $this->error !== null;
    }
}
