<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Support\KotorAddress;

/**
 * FINAL territorial gate. Canonical reads Obrazac P1a adresa / P1b sjedište; other profiles mirror
 * the ApplicationController Obrazac 1a/1b snapshot rules (no identity/profile fallback).
 */
final class FinalKotorAddressCheck
{
    public function errorFor(Application $application): ?string
    {
        if ($application->isCanonicalProfile()) {
            return app(CanonicalApplicantFormService::class)->kotorAddressError($application);
        }

        $type = $application->applicant_type;

        if (in_array($type, ['doo', 'ostalo', 'privredno_drustvo'], true)) {
            if ($application->is_registered) {
                if (! KotorAddress::isInKotorMunicipality($application->company_seat)) {
                    return 'Sjedište društva mora biti na teritoriji Opštine Kotor.';
                }
            } elseif (! KotorAddress::isInKotorMunicipality($application->doo_address)) {
                return KotorAddress::validationMessage();
            }

            return null;
        }

        if ($type === 'preduzetnica' || $type === 'preduzetnik') {
            if (! KotorAddress::isInKotorMunicipality($application->preduzetnik_address)) {
                return KotorAddress::validationMessage();
            }

            return null;
        }

        if ($type === 'fizicko_lice') {
            if (! KotorAddress::isInKotorMunicipality($application->physical_person_address)) {
                return KotorAddress::validationMessage();
            }

            return null;
        }

        return null;
    }
}
