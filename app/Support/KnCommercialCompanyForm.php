<?php

namespace App\Support;

/**
 * KN-layer mapping of privredna društva (DOO / AD / OD / KD).
 * Does not write identity. Does not treat historical `ostalo` as a commercial company.
 */
final class KnCommercialCompanyForm
{
    public const DOO = 'doo';

    public const AD = 'ad';

    public const OD = 'od';

    public const KD = 'kd';

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return [self::DOO, self::AD, self::OD, self::KD];
    }

    public static function isValid(?string $code): bool
    {
        return is_string($code) && in_array($code, self::codes(), true);
    }

    public static function registrationFormLabel(string $code): string
    {
        return match ($code) {
            self::DOO => UserType::LIMITED_LIABILITY_COMPANY,
            self::AD => UserType::JOINT_STOCK_COMPANY,
            self::OD => UserType::GENERAL_PARTNERSHIP,
            self::KD => UserType::LIMITED_PARTNERSHIP,
            default => throw new \InvalidArgumentException('Unknown commercial company form: '.$code),
        };
    }

    public static function fromUserType(?string $userType): ?string
    {
        return match ($userType) {
            UserType::LIMITED_LIABILITY_COMPANY, 'DOO' => self::DOO,
            UserType::JOINT_STOCK_COMPANY => self::AD,
            UserType::GENERAL_PARTNERSHIP => self::OD,
            UserType::LIMITED_PARTNERSHIP => self::KD,
            default => null,
        };
    }

    public static function fromRegistrationForm(?string $label): ?string
    {
        return match ($label) {
            UserType::LIMITED_LIABILITY_COMPANY => self::DOO,
            UserType::JOINT_STOCK_COMPANY => self::AD,
            UserType::GENERAL_PARTNERSHIP => self::OD,
            UserType::LIMITED_PARTNERSHIP => self::KD,
            default => null,
        };
    }

    /**
     * Runtime applicant_type for a commercial company.
     * DOO keeps `doo`. AD/OD/KD stay on historical schema value `ostalo`
     * while registration_form preserves the concrete oblik.
     */
    public static function applicantTypeFor(string $code): string
    {
        return $code === self::DOO
            ? KnApplicationClassification::FORM_DOO
            : KnApplicationClassification::FORM_OSTALO;
    }

    public static function headingCode(?string $code): ?string
    {
        return self::isValid($code) ? strtoupper($code) : null;
    }

    /**
     * Visible Obrazac 1a/1b registration subtitle.
     * Concrete OD/KD/AD/DOO wins over internal applicant_type=ostalo.
     * Unrelated historical ostalo keeps the legacy subtitle.
     */
    public static function obrazacRegistracijaHeading(
        ?string $commercialForm,
        ?string $applicantType,
        ?string $registrationForm = null
    ): string {
        $code = self::headingCode($commercialForm)
            ?? self::headingCode(self::fromRegistrationForm($registrationForm));

        if ($code !== null) {
            return '(za oblik registracije '.$code.')';
        }

        return match ($applicantType) {
            KnApplicationClassification::FORM_PREDUZETNICA,
            KnApplicationClassification::FORM_FIZICKO_LICE => '(za oblik registracije PREDUZETNIK)',
            KnApplicationClassification::FORM_DOO => '(za oblik registracije DOO)',
            KnApplicationClassification::FORM_OSTALO => '(za ostale pravne subjekte)',
            default => '(za oblik registracije PREDUZETNIK)',
        };
    }
}
