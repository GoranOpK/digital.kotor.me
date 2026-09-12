<?php

namespace App\Identity\Runtime;

use App\Identity\CanonicalIdentityReadException;
use App\Identity\CanonicalIdentityReader;
use App\Identity\IdentityRollout;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Support\UserType;

final class ExistingSubjectIdentityEligibility
{
    public function __construct(
        private readonly IdentityRollout $rollout = new IdentityRollout,
        private readonly IdentityMutationGuard $guard = new IdentityMutationGuard,
        private readonly CanonicalIdentityReader $reader = new CanonicalIdentityReader,
    ) {
    }

    public function inspect(?User $user): ExistingSubjectIdentityEligibilityResult
    {
        if ($user === null) {
            return $this->deny(ExistingSubjectIdentityEligibilityResult::DENY_UNAUTHENTICATED);
        }

        if ($user->activation_status !== 'active') {
            return $this->deny(ExistingSubjectIdentityEligibilityResult::DENY_INACTIVE);
        }

        $roleName = $user->role?->name;
        if ($roleName !== 'korisnik' || $user->isStaffAccount()) {
            return $this->deny(ExistingSubjectIdentityEligibilityResult::DENY_STAFF);
        }

        if ($this->rollout->identityWriteFrozen() || ! $this->guard->canonicalHttpWriteAllowed()) {
            return $this->deny(ExistingSubjectIdentityEligibilityResult::DENY_FLAGS);
        }

        $hasRow = PlatformIdentity::query()->where('user_id', $user->id)->exists();
        if ($hasRow) {
            try {
                $this->reader->forUser($user);

                return $this->deny(ExistingSubjectIdentityEligibilityResult::DENY_CURRENT);
            } catch (CanonicalIdentityReadException) {
                return $this->deny(ExistingSubjectIdentityEligibilityResult::DENY_MALFORMED);
            }
        }

        return match ($user->user_type) {
            UserType::LIMITED_LIABILITY_COMPANY => new ExistingSubjectIdentityEligibilityResult(
                true,
                ExistingSubjectIdentityEligibilityResult::BRANCH_DOO,
                null,
            ),
            UserType::ENTREPRENEUR => new ExistingSubjectIdentityEligibilityResult(
                true,
                ExistingSubjectIdentityEligibilityResult::BRANCH_PREDUZETNIK,
                null,
            ),
            UserType::PHYSICAL_PERSON => new ExistingSubjectIdentityEligibilityResult(
                true,
                ExistingSubjectIdentityEligibilityResult::BRANCH_PHYSICAL_PERSON,
                null,
            ),
            null, '' => $this->deny(ExistingSubjectIdentityEligibilityResult::DENY_ACCOUNT_ONLY),
            default => $this->deny(ExistingSubjectIdentityEligibilityResult::DENY_UNSUPPORTED),
        };
    }

    public function isEligible(?User $user): bool
    {
        return $this->inspect($user)->eligible;
    }

    private function deny(string $reason): ExistingSubjectIdentityEligibilityResult
    {
        return new ExistingSubjectIdentityEligibilityResult(false, null, $reason);
    }
}
