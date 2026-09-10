<?php

namespace App\Identity\Shadow\Comparators;

use App\Identity\IdentitySnapshot;
use App\Identity\Shadow\IdentityShadowCanonicalFacts;
use App\Identity\Shadow\IdentityShadowCompareResult;
use App\Identity\Shadow\IdentityShadowExcludedLegacyFields;
use App\Models\User;
use App\Support\ApplicationCreateApplicantTypeDefault;
use App\Support\KotorAddress;

/**
 * Compares live Obrazac 1a/1b identity prefill (new application, no snapshot, no query param).
 */
final class KnApplicationPrefillComparator
{
    public function compare(User $user, IdentitySnapshot $canonical): IdentityShadowCompareResult
    {
        $legacy = $this->legacyDto($user);
        $canonicalDto = $this->canonicalDto($canonical);
        $categories = ['classification', 'residency', 'jmb', 'phone', 'address', 'city'];
        $reasons = IdentityShadowExcludedLegacyFields::reasonCodes($user);
        $mismatched = $this->mismatchedCategories($legacy, $canonicalDto);

        if ($mismatched === []) {
            return IdentityShadowCompareResult::match($categories, $reasons);
        }

        return IdentityShadowCompareResult::mismatch(
            array_values(array_unique([...$reasons, 'prefill_dto', ...$mismatched])),
            $mismatched,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyDto(User $user): array
    {
        $userType = $user->user_type ?? '';
        $residentialStatus = $user->residential_status ?? '';

        return [
            'default_applicant_type' => ApplicationCreateApplicantTypeDefault::forUser($userType, $residentialStatus, null),
            'is_fizicko_lice_rezident' => ApplicationCreateApplicantTypeDefault::isFizickoLiceRezident($userType, $residentialStatus),
            'user_type' => $user->user_type,
            'residential_status' => $user->residential_status,
            'jmb' => IdentityShadowCanonicalFacts::leftoverUserJmb($user),
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'formatted_address' => $user->formattedAddress(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function canonicalDto(IdentitySnapshot $canonical): array
    {
        $fl = $canonical->physicalPerson;
        $userType = IdentityShadowCanonicalFacts::legacyUserType($canonical);
        $residentialStatus = IdentityShadowCanonicalFacts::legacyResidentialStatus($canonical);
        $address = $fl?->streetAndNumber ?? $canonical->streetAndNumber;
        $city = $fl?->city ?? $canonical->city;

        return [
            'default_applicant_type' => ApplicationCreateApplicantTypeDefault::forUser($userType, $residentialStatus ?? '', null),
            'is_fizicko_lice_rezident' => ApplicationCreateApplicantTypeDefault::isFizickoLiceRezident($userType, $residentialStatus),
            'user_type' => $userType,
            'residential_status' => $residentialStatus,
            'jmb' => $fl?->jmb,
            'phone' => $canonical->mobilePhone,
            'address' => $address,
            'city' => $city,
            'formatted_address' => KotorAddress::formatStreetAndCity($address, $city),
        ];
    }

    /**
     * @param  array<string, mixed>  $legacy
     * @param  array<string, mixed>  $canonical
     * @return list<string>
     */
    private function mismatchedCategories(array $legacy, array $canonical): array
    {
        $map = [
            'default_applicant_type' => 'classification',
            'is_fizicko_lice_rezident' => 'classification',
            'user_type' => 'classification',
            'residential_status' => 'residency',
            'jmb' => 'jmb',
            'phone' => 'phone',
            'address' => 'address',
            'city' => 'city',
            'formatted_address' => 'address',
        ];

        $mismatched = [];
        foreach ($map as $key => $category) {
            if ($legacy[$key] !== $canonical[$key]) {
                $mismatched[] = $category;
            }
        }

        return array_values(array_unique($mismatched));
    }
}
