<?php

namespace App\Support;

/**
 * Platformski SSOT za osnovne kategorije korisnika (identitet / oblik).
 *
 * Ne modeluje konkursni eligibility sloj.
 */
final class UserType
{
    public const PHYSICAL_PERSON = 'Fizičko lice';

    public const ENTREPRENEUR = 'Preduzetnik';

    public const LIMITED_LIABILITY_COMPANY = 'Društvo sa ograničenom odgovornošću';

    public const JOINT_STOCK_COMPANY = 'Akcionarsko društvo';

    public const GENERAL_PARTNERSHIP = 'Ortačko društvo';

    public const LIMITED_PARTNERSHIP = 'Komanditno društvo';

    public const NGO_ASSOCIATION = 'Nevladino udruženje';

    public const SPORTS_ORGANIZATION = 'Sportska organizacija';

    public const REGISTRATION_GROUP_BUSINESS = 'Registrovan privredni subjekt';

    public const LEGACY_FOREIGN_BRANCH = 'Dio stranog društva (predstavništvo ili poslovna jedinica)';

    public const LEGACY_ASSOCIATION_BUNDLE = 'Udruženje (nvo, fondacije, sportske organizacije)';

    public const LEGACY_INSTITUTION_BUNDLE = 'Ustanova (državne i privatne)';

    public const LEGACY_OTHER_ORGANIZATIONS = 'Druge organizacije (Političke partije, Vjerske zajednice, Komore, Sindikati)';

    /**
     * @return list<string>
     */
    public static function canonicalStorageValues(): array
    {
        return [
            self::PHYSICAL_PERSON,
            self::ENTREPRENEUR,
            self::LIMITED_LIABILITY_COMPANY,
            self::JOINT_STOCK_COMPANY,
            self::GENERAL_PARTNERSHIP,
            self::LIMITED_PARTNERSHIP,
            self::NGO_ASSOCIATION,
            self::SPORTS_ORGANIZATION,
        ];
    }

    /**
     * @return list<string>
     */
    public static function naturalPersonStorageValues(): array
    {
        return [
            self::PHYSICAL_PERSON,
            self::ENTREPRENEUR,
        ];
    }

    /**
     * @return list<string>
     */
    public static function canonicalLegalEntityStorageValues(): array
    {
        return [
            self::LIMITED_LIABILITY_COMPANY,
            self::JOINT_STOCK_COMPANY,
            self::GENERAL_PARTNERSHIP,
            self::LIMITED_PARTNERSHIP,
            self::NGO_ASSOCIATION,
            self::SPORTS_ORGANIZATION,
        ];
    }

    /**
     * @return list<string>
     */
    public static function retainedLegacyStorageValues(): array
    {
        return [
            self::LEGACY_FOREIGN_BRANCH,
            self::LEGACY_ASSOCIATION_BUNDLE,
            self::LEGACY_INSTITUTION_BUNDLE,
            self::LEGACY_OTHER_ORGANIZATIONS,
        ];
    }

    /**
     * @return list<string>
     */
    public static function allLegalEntityStorageValues(): array
    {
        return array_values(array_unique(array_merge(
            self::canonicalLegalEntityStorageValues(),
            self::retainedLegacyStorageValues()
        )));
    }

    /**
     * Vrijednosti koje smiju ući u novi zapis (registracija / novi izbor na profilu).
     *
     * @return list<string>
     */
    public static function allowedNewWriteValues(): array
    {
        return self::canonicalStorageValues();
    }

    /**
     * @return list<string>
     */
    public static function registrationBusinessStorageValues(): array
    {
        return array_values(array_filter(
            self::canonicalStorageValues(),
            fn (string $value): bool => $value !== self::PHYSICAL_PERSON
        ));
    }

    /**
     * @return list<string>
     */
    public static function mysqlEnumValues(): array
    {
        return array_values(array_unique(array_merge(
            self::canonicalStorageValues(),
            self::retainedLegacyStorageValues()
        )));
    }

    /**
     * @return list<string>
     */
    public static function allowedProfileWriteValues(?string $current): array
    {
        $allowed = self::allowedNewWriteValues();

        if (is_string($current) && $current !== '' && self::isRetainedLegacy($current)) {
            $allowed[] = $current;
        }

        return array_values(array_unique($allowed));
    }

    public static function isNaturalPerson(?string $type): bool
    {
        return in_array($type, self::naturalPersonStorageValues(), true);
    }

    public static function isEntrepreneur(?string $type): bool
    {
        return $type === self::ENTREPRENEUR;
    }

    public static function isLegalEntity(?string $type): bool
    {
        return in_array($type, self::allLegalEntityStorageValues(), true);
    }

    public static function isCanonical(?string $type): bool
    {
        return in_array($type, self::canonicalStorageValues(), true);
    }

    public static function isRetainedLegacy(?string $type): bool
    {
        return in_array($type, self::retainedLegacyStorageValues(), true);
    }

    public static function requiresResidentialStatus(?string $type): bool
    {
        return self::isNaturalPerson($type);
    }

    public static function canonicalCode(?string $type): ?string
    {
        return match ($type) {
            self::PHYSICAL_PERSON => 'FL',
            self::ENTREPRENEUR => 'PREDUZETNIK',
            self::LIMITED_LIABILITY_COMPANY => 'DOO',
            self::JOINT_STOCK_COMPANY => 'AD',
            self::GENERAL_PARTNERSHIP => 'OD',
            self::LIMITED_PARTNERSHIP => 'KD',
            self::NGO_ASSOCIATION => 'NVO',
            self::SPORTS_ORGANIZATION => 'SPORTSKA_ORGANIZACIJA',
            default => null,
        };
    }

    public static function displayLabel(?string $type): string
    {
        if (! is_string($type) || $type === '') {
            return '';
        }

        $code = self::canonicalCode($type);

        return match ($code) {
            'DOO', 'AD', 'OD', 'KD' => $code.' — '.$type,
            default => $type,
        };
    }

    /**
     * @return array<string, string> storage value => label
     */
    public static function registrationBusinessOptions(): array
    {
        $options = [];
        foreach (self::registrationBusinessStorageValues() as $value) {
            $options[$value] = self::displayLabel($value);
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public static function profileSelectOptions(?string $current): array
    {
        $options = [
            self::PHYSICAL_PERSON => self::PHYSICAL_PERSON,
        ];

        foreach (self::registrationBusinessOptions() as $value => $label) {
            $options[$value] = $label;
        }

        if (is_string($current) && self::isRetainedLegacy($current) && ! isset($options[$current])) {
            $options[$current] = $current.' (naslijeđena kategorija)';
        }

        return $options;
    }
}
