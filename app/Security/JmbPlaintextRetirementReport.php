<?php

namespace App\Security;

final class JmbPlaintextRetirementReport
{
    /**
     * @param  list<array{
     *     scope: string,
     *     column: string,
     *     plaintext_rows: int,
     *     would_null: int,
     *     nulled: int,
     *     post_plaintext_non_null: int,
     *     empty_string_rows: int,
     *     status: string
     * }>  $scopes
     */
    public function __construct(
        public readonly bool $dryRun,
        public readonly string $scope,
        public readonly bool $precheckPassed,
        public readonly bool $passed,
        public array $scopes = [],
        public int $totalWouldNull = 0,
        public int $totalNulled = 0,
        public ?string $reason = null,
    ) {
    }
}
