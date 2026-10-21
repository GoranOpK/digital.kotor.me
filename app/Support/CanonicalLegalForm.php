<?php

namespace App\Support;

/**
 * Canonical MMSP applicant legal form (čl.14 / čl.16): explicitly chosen by the applicant,
 * never inferred from gender, business stage or Women/Youth applicant_type values.
 */
final class CanonicalLegalForm
{
    public const PREDUZETNIK = 'preduzetnik';

    public const DRUSTVO = 'drustvo';

    private const DEFINITIONS = [
        self::PREDUZETNIK => [
            'label' => 'Preduzetnik',
            'form' => 'P1a',
            'applicant_type' => 'preduzetnik',
        ],
        self::DRUSTVO => [
            'label' => 'Društvo (DOO – mikro, malo ili srednje preduzeće)',
            'form' => 'P1b',
            'applicant_type' => 'privredno_drustvo',
        ],
    ];

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::DEFINITIONS);
    }

    public static function isValid(mixed $code): bool
    {
        return is_string($code) && array_key_exists($code, self::DEFINITIONS);
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_map(fn (array $definition) => $definition['label'], self::DEFINITIONS);
    }

    public static function label(string $code): string
    {
        return self::definition($code)['label'];
    }

    /**
     * Obrazac code: P1a (preduzetnik) or P1b (društvo).
     */
    public static function formCode(string $code): string
    {
        return self::definition($code)['form'];
    }

    /**
     * Neutral applications.applicant_type value kept in sync with the explicit choice so shared
     * readers (encrypted JMB freeze, listings) see a registered-subject type, never a Women value.
     */
    public static function applicantType(string $code): string
    {
        return self::definition($code)['applicant_type'];
    }

    /**
     * MMSP prijavni obrazac se određuje iz registrovanog tipa korisnika.
     * Odluka poznaje podnosioca kao preduzetnika ili društvo; neregistrovano
     * fizičko lice i ostali tipovi nijesu automatski svrstani u P1a/P1b.
     */
    public static function forUserType(?string $userType): ?string
    {
        if (UserType::isEntrepreneur($userType) || $userType === 'Preduzetnica') {
            return self::PREDUZETNIK;
        }

        if (KnCommercialCompanyForm::fromUserType($userType) !== null) {
            return self::DRUSTVO;
        }

        return null;
    }

    public static function eligibilityMessage(): string
    {
        return 'Prema MMSP Odluci prijavu može podnijeti registrovani preduzetnik ili privredno društvo. '
            .'Tip korisnika na vašem nalogu ne odgovara prihvatljivom podnosiocu za ovaj javni poziv.';
    }

    /**
     * @return array{label: string, form: string, applicant_type: string}
     */
    private static function definition(string $code): array
    {
        if (! self::isValid($code)) {
            throw new \InvalidArgumentException('Nepoznata pravna forma kanonskog podnosioca: '.$code);
        }

        return self::DEFINITIONS[$code];
    }
}
