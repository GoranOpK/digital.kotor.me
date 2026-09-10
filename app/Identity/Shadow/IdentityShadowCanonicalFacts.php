<?php

namespace App\Identity\Shadow;

use App\Identity\IdentitySnapshot;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Security\JmbEncryptedReadException;
use App\Security\JmbEncryptedReadService;
use App\Support\UserType;

/**
 * Deterministic canonical → legacy identity facts for Step 4 physical-person graphs.
 * Does not invent entrepreneur / legal-entity / foreign-branch mappings.
 */
final class IdentityShadowCanonicalFacts
{
    public static function legacyUserType(IdentitySnapshot $snapshot): string
    {
        $fl = $snapshot->physicalPerson;
        if ($snapshot->subjectType !== PlatformIdentity::SUBJECT_PHYSICAL_PERSON || $fl === null) {
            throw new IdentityShadowException('Canonical snapshot is not a physical-person graph.');
        }

        if ($fl->isEntrepreneur) {
            throw new IdentityShadowException('Canonical snapshot is not a Step 4 non-entrepreneur physical person.');
        }

        return UserType::PHYSICAL_PERSON;
    }

    public static function legacyResidentialStatus(IdentitySnapshot $snapshot): ?string
    {
        $fl = $snapshot->physicalPerson;
        if ($fl === null) {
            throw new IdentityShadowException('Canonical snapshot is missing physical-person data.');
        }

        if ($fl->residentialStatus === PhysicalPersonIdentity::RESIDENTIAL_RESIDENT) {
            return 'resident';
        }

        if ($fl->residentialStatus === PhysicalPersonIdentity::RESIDENTIAL_NON_RESIDENT) {
            return 'non-resident';
        }

        return $fl->residentialStatus;
    }

    public static function leftoverUserJmb(User $user): ?string
    {
        try {
            return app(JmbEncryptedReadService::class)->readValue(
                $user->jmb_encrypted,
                $user->jmb,
                'users',
                $user->id,
                'jmb/jmb_encrypted',
            );
        } catch (JmbEncryptedReadException) {
            return null;
        }
    }
}
