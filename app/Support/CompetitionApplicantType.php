<?php

namespace App\Support;

/**
 * Live Konkurs competition-detail applicant_type mapping.
 * Authority: CompetitionsController::show. Not the create-form default.
 */
final class CompetitionApplicantType
{
    public static function fromUserType(?string $userType): string
    {
        if ($userType === 'Preduzetnik' || $userType === 'Preduzetnica') {
            return 'preduzetnica';
        }
        if ($userType === 'Društvo sa ograničenom odgovornošću' || $userType === 'DOO') {
            return 'doo';
        }
        if ($userType === 'Fizičko lice' || $userType === 'Rezident') {
            return 'fizicko_lice';
        }

        return 'ostalo';
    }
}
