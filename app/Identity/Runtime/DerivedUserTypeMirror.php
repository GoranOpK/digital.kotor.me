<?php

namespace App\Identity\Runtime;

use App\Identity\IdentitySnapshot;
use App\Models\LegalEntityIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Support\UserType;

/**
 * Temporary derived users.user_type compatibility mirror.
 * Representable canonical categories only. Not an identity SSOT.
 */
final class DerivedUserTypeMirror
{
    public function representableType(IdentitySnapshot $snapshot): ?string
    {
        if ($snapshot->subjectType === PlatformIdentity::SUBJECT_PHYSICAL_PERSON && $snapshot->physicalPerson !== null) {
            return $snapshot->physicalPerson->isEntrepreneur
                ? UserType::ENTREPRENEUR
                : UserType::PHYSICAL_PERSON;
        }

        if ($snapshot->subjectType === PlatformIdentity::SUBJECT_LEGAL_ENTITY && $snapshot->legalEntity !== null) {
            return match ($snapshot->legalEntity->legalForm) {
                LegalEntityIdentity::FORM_DOO => UserType::LIMITED_LIABILITY_COMPANY,
                LegalEntityIdentity::FORM_AD => UserType::JOINT_STOCK_COMPANY,
                LegalEntityIdentity::FORM_OD => UserType::GENERAL_PARTNERSHIP,
                LegalEntityIdentity::FORM_KD => UserType::LIMITED_PARTNERSHIP,
                LegalEntityIdentity::FORM_NVO_ASSOCIATION => UserType::NGO_ASSOCIATION,
                LegalEntityIdentity::FORM_SPORTS_ORGANIZATION => UserType::SPORTS_ORGANIZATION,
                default => null,
            };
        }

        return null;
    }

    public function sync(User $user, IdentitySnapshot $snapshot): void
    {
        $type = $this->representableType($snapshot);
        if ($type === null) {
            return;
        }

        if ($user->user_type === $type) {
            return;
        }

        $user->user_type = $type;
        $user->save();
    }
}
