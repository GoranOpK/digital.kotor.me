<?php

namespace App\Identity\Shadow\Comparators;

use App\Identity\IdentitySnapshot;
use App\Identity\Shadow\IdentityShadowCanonicalFacts;
use App\Identity\Shadow\IdentityShadowCompareResult;
use App\Identity\Shadow\IdentityShadowExcludedLegacyFields;
use App\Models\User;

/**
 * profile/edit.blade.php contracted identity fields. Raw form values. No extra trim.
 */
final class ProfileDisplayComparator
{
    public function compare(User $user, IdentitySnapshot $canonical): IdentityShadowCompareResult
    {
        $fl = $canonical->physicalPerson;
        $legacy = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'user_type' => $user->user_type,
            'residential_status' => $user->residential_status,
            'jmb' => $user->jmb,
        ];
        $canonicalDto = [
            'first_name' => $fl?->firstName,
            'last_name' => $fl?->lastName,
            'phone' => $canonical->mobilePhone,
            'address' => $fl?->streetAndNumber ?? $canonical->streetAndNumber,
            'city' => $fl?->city ?? $canonical->city,
            'user_type' => IdentityShadowCanonicalFacts::legacyUserType($canonical),
            'residential_status' => IdentityShadowCanonicalFacts::legacyResidentialStatus($canonical),
            'jmb' => $fl?->jmb,
        ];

        $categoryByKey = [
            'first_name' => 'name',
            'last_name' => 'name',
            'phone' => 'phone',
            'address' => 'address',
            'city' => 'city',
            'user_type' => 'classification',
            'residential_status' => 'residency',
            'jmb' => 'jmb',
        ];

        $mismatched = [];
        foreach ($categoryByKey as $key => $category) {
            if ($legacy[$key] !== $canonicalDto[$key]) {
                $mismatched[] = $category;
            }
        }
        $mismatched = array_values(array_unique($mismatched));
        $reasons = IdentityShadowExcludedLegacyFields::reasonCodes($user);
        $categories = array_values(array_unique(array_values($categoryByKey)));

        if ($mismatched === []) {
            return IdentityShadowCompareResult::match($categories, $reasons);
        }

        return IdentityShadowCompareResult::mismatch(
            array_values(array_unique([...$reasons, ...$mismatched])),
            $mismatched,
        );
    }
}
