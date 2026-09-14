<?php

namespace App\Support;

/**
 * KN V1 application facts derived from canonical identity presentation.
 * Does not write identity. Does not treat applicant_type as proof of registration.
 */
final class KnApplicationClassification
{
    public const FORM_FIZICKO_LICE = 'fizicko_lice';

    public const FORM_PREDUZETNICA = 'preduzetnica';

    public const FORM_PREDUZETNIK = 'preduzetnik';

    public const FORM_DOO = 'doo';

    public const FORM_PRIVREDNO_DRUSTVO = 'privredno_drustvo';

    public const FORM_OSTALO = 'ostalo';

    public const HISTORICAL_FIZICKO_LICE = self::FORM_FIZICKO_LICE;

    public const STAGE_ZAPOCINJANJE = 'započinjanje';

    public const STAGE_RAZVOJ = 'razvoj';

    /**
     * @param  list<string>  $allowedApplicantTypes
     * @param  list<string>  $allowedBusinessStages
     */
    public function __construct(
        public readonly bool $hasIdentity,
        public readonly bool $isUnregisteredPhysicalPerson,
        public readonly bool $isExistingEntrepreneur,
        public readonly bool $isExistingDoo,
        public readonly bool $isOtherLegal,
        public readonly bool $isRegisteredBusiness,
        public readonly bool $canChoosePlannedForm,
        public readonly array $allowedApplicantTypes,
        public readonly array $allowedBusinessStages,
        public readonly ?string $lockedApplicantType,
    ) {
    }

    public static function fromUserType(?string $userType, ?string $competitionType = null): self
    {
        $isOmladinsko = $competitionType === 'omladinsko';

        if ($userType === null || trim($userType) === '') {
            return new self(
                hasIdentity: false,
                isUnregisteredPhysicalPerson: false,
                isExistingEntrepreneur: false,
                isExistingDoo: false,
                isOtherLegal: false,
                isRegisteredBusiness: false,
                canChoosePlannedForm: false,
                allowedApplicantTypes: [],
                allowedBusinessStages: [self::STAGE_ZAPOCINJANJE],
                lockedApplicantType: null,
            );
        }

        $isUnregisteredPhysicalPerson = $userType === UserType::PHYSICAL_PERSON
            || $userType === 'Rezident';
        $isExistingEntrepreneur = UserType::isEntrepreneur($userType)
            || $userType === 'Preduzetnica';
        $isExistingDoo = $userType === UserType::LIMITED_LIABILITY_COMPANY
            || $userType === 'DOO';
        $commercial = KnCommercialCompanyForm::fromUserType($userType);

        if ($isUnregisteredPhysicalPerson) {
            return new self(
                hasIdentity: true,
                isUnregisteredPhysicalPerson: true,
                isExistingEntrepreneur: false,
                isExistingDoo: false,
                isOtherLegal: false,
                isRegisteredBusiness: false,
                canChoosePlannedForm: true,
                allowedApplicantTypes: $isOmladinsko
                    ? [self::FORM_FIZICKO_LICE, self::FORM_PRIVREDNO_DRUSTVO]
                    : [self::FORM_FIZICKO_LICE, self::FORM_DOO],
                allowedBusinessStages: [self::STAGE_ZAPOCINJANJE],
                lockedApplicantType: null,
            );
        }

        if ($isExistingEntrepreneur) {
            $locked = $isOmladinsko ? self::FORM_PREDUZETNIK : self::FORM_PREDUZETNICA;

            return new self(
                hasIdentity: true,
                isUnregisteredPhysicalPerson: false,
                isExistingEntrepreneur: true,
                isExistingDoo: false,
                isOtherLegal: false,
                isRegisteredBusiness: true,
                canChoosePlannedForm: false,
                allowedApplicantTypes: [$locked],
                allowedBusinessStages: [self::STAGE_ZAPOCINJANJE, self::STAGE_RAZVOJ],
                lockedApplicantType: $locked,
            );
        }

        if ($isOmladinsko && $commercial !== null) {
            return new self(
                hasIdentity: true,
                isUnregisteredPhysicalPerson: false,
                isExistingEntrepreneur: false,
                isExistingDoo: $commercial === KnCommercialCompanyForm::DOO,
                isOtherLegal: false,
                isRegisteredBusiness: true,
                canChoosePlannedForm: false,
                allowedApplicantTypes: [self::FORM_PRIVREDNO_DRUSTVO],
                allowedBusinessStages: [self::STAGE_ZAPOCINJANJE, self::STAGE_RAZVOJ],
                lockedApplicantType: self::FORM_PRIVREDNO_DRUSTVO,
            );
        }

        if ($isExistingDoo) {
            return new self(
                hasIdentity: true,
                isUnregisteredPhysicalPerson: false,
                isExistingEntrepreneur: false,
                isExistingDoo: true,
                isOtherLegal: false,
                isRegisteredBusiness: true,
                canChoosePlannedForm: false,
                allowedApplicantTypes: [self::FORM_DOO],
                allowedBusinessStages: [self::STAGE_ZAPOCINJANJE, self::STAGE_RAZVOJ],
                lockedApplicantType: self::FORM_DOO,
            );
        }

        if ($isOmladinsko) {
            return new self(
                hasIdentity: true,
                isUnregisteredPhysicalPerson: false,
                isExistingEntrepreneur: false,
                isExistingDoo: false,
                isOtherLegal: true,
                isRegisteredBusiness: true,
                canChoosePlannedForm: false,
                allowedApplicantTypes: [],
                allowedBusinessStages: [self::STAGE_ZAPOCINJANJE],
                lockedApplicantType: null,
            );
        }

        return new self(
            hasIdentity: true,
            isUnregisteredPhysicalPerson: false,
            isExistingEntrepreneur: false,
            isExistingDoo: false,
            isOtherLegal: true,
            isRegisteredBusiness: true,
            canChoosePlannedForm: false,
            allowedApplicantTypes: [self::FORM_OSTALO],
            allowedBusinessStages: [self::STAGE_ZAPOCINJANJE, self::STAGE_RAZVOJ],
            lockedApplicantType: self::FORM_OSTALO,
        );
    }

    /**
     * @return list<string>
     */
    public static function mysqlEnumValues(): array
    {
        return [
            self::FORM_PREDUZETNICA,
            self::FORM_DOO,
            self::FORM_FIZICKO_LICE,
            self::FORM_OSTALO,
            self::FORM_PREDUZETNIK,
            self::FORM_PRIVREDNO_DRUSTVO,
        ];
    }

    public static function isM1a(?string $type): bool
    {
        return in_array($type, [self::FORM_FIZICKO_LICE, self::FORM_PREDUZETNICA, self::FORM_PREDUZETNIK], true);
    }

    public static function isM1b(?string $type): bool
    {
        return in_array($type, [self::FORM_DOO, self::FORM_OSTALO, self::FORM_PRIVREDNO_DRUSTVO], true);
    }

    public static function isRegisteredEntrepreneurType(?string $type): bool
    {
        return in_array($type, [self::FORM_PREDUZETNICA, self::FORM_PREDUZETNIK], true);
    }

    public function supportsOmladinskoDraft(): bool
    {
        return $this->hasIdentity && $this->allowedApplicantTypes !== [];
    }

    public function defaultFormApplicantType(): string
    {
        if ($this->lockedApplicantType !== null) {
            return $this->lockedApplicantType;
        }

        return $this->allowedApplicantTypes[0] ?? self::FORM_FIZICKO_LICE;
    }

    public function allowsApplicantType(?string $type): bool
    {
        return is_string($type) && in_array($type, $this->allowedApplicantTypes, true);
    }

    public function allowsStage(?string $stage): bool
    {
        return is_string($stage) && in_array($stage, $this->allowedBusinessStages, true);
    }

    /**
     * Live Obrazac 1 form for a new application.
     * Unregistered FL default is fizicko_lice (1a). Requested doo is planned company founding (1b), not canonical DOO.
     */
    public function resolveApplicantType(?string $requested): string
    {
        if ($this->allowsApplicantType($requested)) {
            return $requested;
        }

        return $this->defaultFormApplicantType();
    }

    public function resolveBusinessStage(?string $requested): string
    {
        if ($this->allowsStage($requested)) {
            return $requested;
        }

        return self::STAGE_ZAPOCINJANJE;
    }

    /**
     * @return list<string>
     */
    public static function registrationConditionedDocumentTypes(): array
    {
        return [
            'crps_resenje',
            'pib_resenje',
            'pdv_resenje',
            'dokaz_ziro_racun',
            'statut',
            'karton_potpisa',
        ];
    }

    public static function isHistoricalFizickoLice(?string $applicantType): bool
    {
        return $applicantType === self::FORM_FIZICKO_LICE;
    }
}
