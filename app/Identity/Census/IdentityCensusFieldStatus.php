<?php

namespace App\Identity\Census;

/**
 * Report-only field statuses. Not persisted.
 */
final class IdentityCensusFieldStatus
{
    public const PRESENT_VALID = 'PRESENT_VALID';

    public const MISSING = 'MISSING';

    public const INVALID = 'INVALID';

    public const NOT_APPLICABLE = 'NOT_APPLICABLE';

    public const AMBIGUOUS = 'AMBIGUOUS';

    public const SOURCE_UNAVAILABLE = 'SOURCE_UNAVAILABLE';
}
