<?php

namespace App\Support;

use App\Models\Application;

/**
 * Prikaz podnosioca na PM obrascu Komisije. Ne upisuje podatke i ne uzima ime sa korisničkog naloga.
 */
final class PmCommissionApplicantPresentation
{
    public static function line(Application $application): string
    {
        return self::subjectName($application) ?? '—';
    }

    public static function categoryLabel(Application $application): ?string
    {
        return match ((string) $application->applicant_type) {
            KnApplicationClassification::FORM_FIZICKO_LICE => 'Fizičko lice',
            KnApplicationClassification::FORM_PREDUZETNIK => 'Preduzetnik',
            KnApplicationClassification::FORM_PRIVREDNO_DRUSTVO => self::commercialCode($application) ?? 'Privredno društvo',
            KnApplicationClassification::FORM_OSTALO => self::commercialCode($application),
            default => null,
        };
    }

    public static function subjectName(Application $application): ?string
    {
        return match ((string) $application->applicant_type) {
            KnApplicationClassification::FORM_FIZICKO_LICE => self::clean($application->physical_person_name),
            KnApplicationClassification::FORM_PREDUZETNIK => self::clean($application->preduzetnik_name),
            KnApplicationClassification::FORM_PRIVREDNO_DRUSTVO => $application->is_registered
                ? self::registeredCompanyName($application)
                : self::clean($application->doo_name),
            KnApplicationClassification::FORM_OSTALO => self::registeredCompanyName($application),
            default => null,
        };
    }

    private static function commercialCode(Application $application): ?string
    {
        $code = KnCommercialCompanyForm::isValid($application->company_legal_form)
            ? (string) $application->company_legal_form
            : null;

        if ($code === null) {
            $code = KnCommercialCompanyForm::fromRegistrationForm(
                is_string($application->registration_form) ? $application->registration_form : null
            );
        }

        if ($code === null && is_string($application->registration_form)) {
            $lower = strtolower(trim($application->registration_form));
            $code = KnCommercialCompanyForm::isValid($lower) ? $lower : null;
        }

        return KnCommercialCompanyForm::headingCode($code);
    }

    private static function registeredCompanyName(Application $application): ?string
    {
        if (! $application->is_registered) {
            return null;
        }

        $application->loadMissing(['user.identity.legalEntity']);

        return self::clean($application->user?->identity?->legalEntity?->legal_name);
    }

    private static function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $name = trim($value);

        return $name === '' ? null : $name;
    }
}
