<?php

namespace App\Support;

use App\Models\User;

/**
 * Reusable declare-on-use contract za residential_status.
 *
 * UI aktivacija je odložena do prvog stvarnog module consumera.
 */
final class ResidentialStatusDeclaration
{
    public static function isApplicable(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->isStaffAccount() && $user->user_type === null) {
            return false;
        }

        if (! $user->isNaturalPerson()) {
            return false;
        }

        return $user->residential_status === null;
    }

    public static function isSatisfied(?User $user): bool
    {
        if ($user === null || ! $user->isNaturalPerson()) {
            return true;
        }

        return in_array($user->residential_status, ['resident', 'non-resident'], true);
    }
}
