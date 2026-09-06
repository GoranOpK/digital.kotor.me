<?php

namespace App\Identity\Shadow\Comparators;

use App\Identity\IdentitySnapshot;
use App\Identity\Shadow\IdentityShadowCanonicalFacts;
use App\Identity\Shadow\IdentityShadowCompareResult;
use App\Identity\Shadow\IdentityShadowExcludedLegacyFields;
use App\Models\User;
use App\Support\UserType;

/**
 * dashboard.blade.php contracted identity display for korisnik FL. Exact Blade expressions.
 */
final class DashboardDisplayComparator
{
    public function compare(User $user, IdentitySnapshot $canonical): IdentityShadowCompareResult
    {
        $legacy = $this->displayDtoFromLegacy($user);
        $canonicalDto = $this->displayDtoFromCanonical($canonical);
        $categoryByKey = [
            'user_type_label' => 'classification',
            'phone_display' => 'phone',
            'address_display' => 'address',
            'city_display' => 'city',
            'jmb_shown' => 'jmb',
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

    /**
     * @return array<string, mixed>
     */
    private function displayDtoFromLegacy(User $user): array
    {
        return [
            'user_type_label' => $this->userTypeLabel(
                $user->user_type,
                $user->residential_status,
            ),
            'phone_display' => $user->phone ?? 'N/A',
            'address_display' => $user->address ?? 'N/A',
            'city_display' => $user->city ?? 'N/A',
            'jmb_shown' => (bool) $user->jmb,
            'jmb' => $user->jmb,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function displayDtoFromCanonical(IdentitySnapshot $canonical): array
    {
        $fl = $canonical->physicalPerson;
        $userType = IdentityShadowCanonicalFacts::legacyUserType($canonical);
        $residentialStatus = IdentityShadowCanonicalFacts::legacyResidentialStatus($canonical);
        $jmb = $fl?->jmb;

        return [
            'user_type_label' => $this->userTypeLabel($userType, $residentialStatus),
            'phone_display' => $canonical->mobilePhone ?? 'N/A',
            'address_display' => ($fl?->streetAndNumber ?? $canonical->streetAndNumber) ?? 'N/A',
            'city_display' => ($fl?->city ?? $canonical->city) ?? 'N/A',
            'jmb_shown' => (bool) $jmb,
            'jmb' => $jmb,
        ];
    }

    private function userTypeLabel(mixed $userType, mixed $residentialStatus): string
    {
        $type = is_string($userType) ? $userType : null;
        $isPhysicalPerson = UserType::isNaturalPerson($type);
        $isResident = $residentialStatus === 'resident';
        $isNonResident = $residentialStatus === 'non-resident';
        $isEntrepreneur = UserType::isEntrepreneur($type);

        if ($isEntrepreneur) {
            return 'Preduzetnik';
        }
        if ($isPhysicalPerson && $isResident) {
            return 'Fizičko lice (Rezident)';
        }
        if ($isPhysicalPerson && $isNonResident) {
            return 'Fizičko lice (Nerezident)';
        }

        return $userType ?? 'Pravno lice';
    }
}
