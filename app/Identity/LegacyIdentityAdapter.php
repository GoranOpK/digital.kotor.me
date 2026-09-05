<?php

namespace App\Identity;

use App\Models\User;

/**
 * Direct-test legacy users.* adapter. No D3/D14 inference. Not runtime-wired.
 */
final class LegacyIdentityAdapter
{
    public function forUser(User $user): ?IdentitySnapshot
    {
        if ($user->isStaffAccount() && $user->user_type === null) {
            return null;
        }

        $isRegisteredSubject = ! $user->isStaffAccount() && $user->user_type !== null;

        return new IdentitySnapshot(
            userId: (int) $user->id,
            isRegisteredSubject: $isRegisteredSubject,
            subjectType: null,
            mobilePhone: $user->phone,
            streetAndNumber: $user->address,
            city: $user->city,
            legacyFacts: new LegacyUserFacts(
                userType: $user->user_type,
                residentialStatus: $user->residential_status,
                firstName: $user->first_name,
                lastName: $user->last_name,
                companyName: $user->company_name,
                jmb: $user->jmb,
                pib: $user->pib,
                passportNumber: $user->passport_number,
                phone: $user->phone,
                address: $user->address,
                city: $user->city,
            ),
        );
    }
}
