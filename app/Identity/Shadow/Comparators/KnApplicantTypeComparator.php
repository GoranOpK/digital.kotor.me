<?php

namespace App\Identity\Shadow\Comparators;

use App\Identity\IdentitySnapshot;
use App\Identity\Shadow\IdentityShadowCanonicalFacts;
use App\Identity\Shadow\IdentityShadowCompareResult;
use App\Identity\Shadow\IdentityShadowExcludedLegacyFields;
use App\Models\User;
use App\Support\CompetitionApplicantType;

/**
 * Compares CompetitionsController::show applicant_type using the live helper.
 */
final class KnApplicantTypeComparator
{
    public function compare(User $user, IdentitySnapshot $canonical): IdentityShadowCompareResult
    {
        $legacy = CompetitionApplicantType::fromUserType($user->user_type ?? null);
        $canonicalType = CompetitionApplicantType::fromUserType(IdentityShadowCanonicalFacts::legacyUserType($canonical));
        $categories = ['classification'];
        $reasons = IdentityShadowExcludedLegacyFields::reasonCodes($user);

        if ($legacy === $canonicalType) {
            return IdentityShadowCompareResult::match($categories, $reasons);
        }

        return IdentityShadowCompareResult::mismatch(
            array_values(array_unique([...$reasons, 'classification'])),
            $categories,
        );
    }
}
