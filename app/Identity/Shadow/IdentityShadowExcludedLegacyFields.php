<?php

namespace App\Identity\Shadow;

use App\Models\User;

/**
 * Leftover legacy identifiers that Step 4 did not copy onto non-entrepreneur FL graphs.
 */
final class IdentityShadowExcludedLegacyFields
{
    /**
     * @return list<string>
     */
    public static function reasonCodes(User $user): array
    {
        $codes = [];
        if (filled($user->pib)) {
            $codes[] = 'not_in_canonical_contract';
        }
        if (filled($user->passport_number)) {
            $codes[] = 'not_in_canonical_contract';
        }
        if (filled($user->company_name)) {
            $codes[] = 'not_in_canonical_contract';
        }

        return array_values(array_unique($codes));
    }
}
