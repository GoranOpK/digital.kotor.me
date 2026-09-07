<?php

namespace App\Identity\Reconcile;

/**
 * PII-safe per-user Step 7 result. Not persisted.
 * Fingerprints are SHA-256 hashes only; never raw identity data.
 */
final readonly class IdentityReconcileUserOutcome
{
    public const WOULD_CREATE = 'WOULD_CREATE';

    public const CREATED = 'CREATED';

    public const WOULD_UPDATE = 'WOULD_UPDATE';

    public const UPDATED = 'UPDATED';

    public const SKIPPED_IDEMPOTENT = 'SKIPPED_IDEMPOTENT';

    public const SKIPPED_NOT_ELIGIBLE = 'SKIPPED_NOT_ELIGIBLE';

    public const SKIPPED_OUTSIDE_BOUNDARY = 'SKIPPED_OUTSIDE_BOUNDARY';

    public const SOURCE_DRIFT = 'SOURCE_DRIFT';

    public const CONFLICT = 'CONFLICT';

    public const CANONICAL_INVALID = 'CANONICAL_INVALID';

    public const FAILED = 'FAILED';

    /**
     * @param  list<string>  $reasonCodes
     */
    public function __construct(
        public int $userId,
        public string $status,
        public string $liveRowStatus,
        public array $reasonCodes,
        public ?string $projectionFingerprintSha256 = null,
        public ?string $canonicalFingerprintSha256 = null,
        public ?bool $fingerprintMismatch = null,
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
            'projection_fingerprint_sha256' => $this->projectionFingerprintSha256,
            'canonical_fingerprint_sha256' => $this->canonicalFingerprintSha256,
            'fingerprint_mismatch' => $this->fingerprintMismatch,
        ];
    }
}
