<?php

namespace App\Security;

final class JmbLookupBackfillReport
{
    /**
     * @param  list<array{
     *     scope: string,
     *     table: string,
     *     scanned: int,
     *     backfilled: int,
     *     already_valid: int,
     *     empty: int,
     *     collisions: int,
     *     errors: int
     * }>  $scopes
     * @param  array{scope: string, table: string, id: int, reason: string}|null  $error
     */
    public function __construct(
        public readonly bool $dryRun,
        public array $scopes = [],
        public int $scanned = 0,
        public int $backfilled = 0,
        public int $alreadyValid = 0,
        public int $empty = 0,
        public int $collisions = 0,
        public int $errors = 0,
        public ?array $error = null,
    ) {
    }

    public function failed(): bool
    {
        return $this->errors > 0 || $this->error !== null;
    }
}
