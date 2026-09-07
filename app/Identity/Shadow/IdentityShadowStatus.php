<?php

namespace App\Identity\Shadow;

final class IdentityShadowStatus
{
    public const MATCH = 'MATCH';

    public const MISMATCH = 'MISMATCH';

    public const NOT_SHADOW_ELIGIBLE = 'NOT_SHADOW_ELIGIBLE';

    public const MISSING_CANONICAL = 'MISSING_CANONICAL';

    public const CANONICAL_INVALID = 'CANONICAL_INVALID';

    public const CANONICAL_READ_FAILED = 'CANONICAL_READ_FAILED';

    public const LEGACY_READ_FAILED = 'LEGACY_READ_FAILED';

    public const NOT_EVALUABLE = 'NOT_EVALUABLE';

    /**
     * @var list<string>
     */
    public const BLOCKING_STATUSES = [
        self::MISMATCH,
        self::MISSING_CANONICAL,
        self::CANONICAL_INVALID,
        self::CANONICAL_READ_FAILED,
        self::LEGACY_READ_FAILED,
    ];
}
