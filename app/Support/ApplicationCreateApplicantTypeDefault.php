<?php

namespace App\Support;

/**
 * Live Obrazac 1a/1b create-form default applicant_type.
 * Authority: resources/views/applications/create.blade.php.
 * Distinct from CompetitionsController::show mapping.
 *
 * For new applications this is the Obrazac 1 form (fizicko_lice/preduzetnica/doo),
 * not proof of registration and not a replacement for canonical identity.
 */
final class ApplicationCreateApplicantTypeDefault
{
    public static function isFizickoLiceRezident(mixed $userType, mixed $residentialStatus): bool
    {
        return $userType === 'Fizičko lice' && $residentialStatus === 'resident';
    }

    public static function forUser(mixed $userType, mixed $residentialStatus, mixed $preferredApplicantType = null): string
    {
        $userType = $userType ?? '';
        $kn = KnApplicationClassification::fromUserType(is_string($userType) ? $userType : null);

        if (is_string($preferredApplicantType) && $kn->allowsApplicantType($preferredApplicantType)) {
            return $preferredApplicantType;
        }

        if ($kn->hasIdentity) {
            return $kn->defaultFormApplicantType();
        }

        if ($preferredApplicantType && in_array($preferredApplicantType, ['preduzetnica', 'doo', 'fizicko_lice', 'ostalo'])) {
            return $preferredApplicantType;
        }

        $defaultType = 'fizicko_lice';
        if ($userType === 'Društvo sa ograničenom odgovornošću' || $userType === 'DOO') {
            $defaultType = 'doo';
        } elseif ($userType === 'Fizičko lice' || $userType === 'Rezident') {
            $defaultType = 'fizicko_lice';
        } elseif (in_array($userType, ['Preduzetnik', 'Preduzetnica'])) {
            $defaultType = 'preduzetnica';
        } elseif ($userType && $userType !== 'Fizičko lice' && $userType !== 'Preduzetnik' && $userType !== 'Preduzetnica') {
            $defaultType = 'ostalo';
        }

        return $defaultType;
    }
}
