<?php

namespace App\Identity\Verify;

/**
 * PII-safe per-user Step 5 result. Not persisted.
 */
final readonly class IdentityVerifyUserOutcome
{
    public const VERIFIED = 'VERIFIED';

    public const SKIPPED_NOT_ELIGIBLE = 'SKIPPED_NOT_ELIGIBLE';

    public const SKIPPED_OUTSIDE_CENSUS_BOUNDARY = 'SKIPPED_OUTSIDE_CENSUS_BOUNDARY';

    public const MISSING_CANONICAL = 'MISSING_CANONICAL';

    public const CANONICAL_MISMATCH = 'CANONICAL_MISMATCH';

    public const SOURCE_DRIFT = 'SOURCE_DRIFT';

    public const CANONICAL_GRAPH_INVALID = 'CANONICAL_GRAPH_INVALID';

    public const UNEXPECTED_CANONICAL = 'UNEXPECTED_CANONICAL';

    public const FAILED = 'FAILED';

    /**
     * @var list<string>
     */
    public const BLOCKING_STATUSES = [
        self::MISSING_CANONICAL,
        self::CANONICAL_MISMATCH,
        self::SOURCE_DRIFT,
        self::CANONICAL_GRAPH_INVALID,
        self::UNEXPECTED_CANONICAL,
        self::FAILED,
    ];

    /**
     * @param  list<string>  $reasonCodes
     */
    public function __construct(
        public int $userId,
        public string $status,
        public string $liveRowStatus,
        public array $reasonCodes,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'status' => $this->status,
            'live_row_status' => $this->liveRowStatus,
            'reason_codes' => $this->reasonCodes,
        ];
    }
}
