<?php

namespace App\Identity;

use App\Models\User;
use App\Security\JmbEncryptedReadService;

/**
 * Direct-test legacy users.* adapter. No D3/D14 inference. Not runtime-wired.
 * JMB value is encrypted-first via JmbEncryptedReadService.
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
                jmb: $this->readUserJmb($user),
                pib: $user->pib,
                passportNumber: $user->passport_number,
                phone: $user->phone,
                address: $user->address,
                city: $user->city,
            ),
        );
    }

    private function readUserJmb(User $user): ?string
    {
        return app(JmbEncryptedReadService::class)->readValue(
            $user->jmb_encrypted,
            $user->jmb,
            'users',
            $user->id,
            'jmb/jmb_encrypted',
        );
    }
}
