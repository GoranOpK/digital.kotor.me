<?php

namespace App\Identity\Backfill;

/**
 * PII-safe per-user Step 4 result. Not persisted.
 */
final readonly class IdentityBackfillUserOutcome
{
    public const CREATED = 'CREATED';

    public const WOULD_CREATE = 'WOULD_CREATE';

    public const SKIPPED_NOT_ELIGIBLE = 'SKIPPED_NOT_ELIGIBLE';

    public const SKIPPED_IDEMPOTENT = 'SKIPPED_IDEMPOTENT';

    public const SKIPPED_OUTSIDE_CENSUS_BOUNDARY = 'SKIPPED_OUTSIDE_CENSUS_BOUNDARY';

    public const CONFLICT = 'CONFLICT';

    public const FAILED = 'FAILED';

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
