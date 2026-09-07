<?php

namespace App\Identity\Runtime;

final readonly class ExistingSubjectIdentityEligibilityResult
{
    public const BRANCH_DOO = 'doo';

    public const BRANCH_PREDUZETNIK = 'preduzetnik';

    public const DENY_UNAUTHENTICATED = 'unauthenticated';

    public const DENY_INACTIVE = 'inactive';

    public const DENY_STAFF = 'staff';

    public const DENY_ACCOUNT_ONLY = 'account_only';

    public const DENY_FLAGS = 'flags_denied';

    public const DENY_CURRENT = 'current_graph';

    public const DENY_MALFORMED = 'malformed_graph';

    public const DENY_ORDINARY_FL = 'ordinary_physical_person';

    public const DENY_UNSUPPORTED = 'unsupported_legacy_type';

    public function __construct(
        public bool $eligible,
        public ?string $branch,
        public ?string $denyReason,
    ) {
    }

    public function isCurrent(): bool
    {
        return $this->denyReason === self::DENY_CURRENT;
    }

    public function isMalformed(): bool
    {
        return $this->denyReason === self::DENY_MALFORMED;
    }
}
