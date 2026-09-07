<?php

namespace App\Identity;

/**
 * Calling-code picker for identity completion. Calling code / E.164 is not country identity.
 * Display labels come from CountryCatalog. Stored phone is composed E.164, not an ISO code.
 */
final class PhoneCallingCodeCatalog
{
    /**
     * Registration picker pairs: ISO/XK display key => calling code.
     *
     * @var array<string, string>
     */
    private const COUNTRY_CALLING_CODES = [
        'ME' => '+382',
        'RS' => '+381',
        'BA' => '+387',
        'SI' => '+386',
        'HR' => '+385',
        'XK' => '+383',
        'MK' => '+389',
        'AL' => '+355',
        'BG' => '+359',
        'RO' => '+40',
        'HU' => '+36',
        'SK' => '+421',
        'CZ' => '+420',
        'PL' => '+48',
        'DE' => '+49',
        'AT' => '+43',
        'CH' => '+41',
        'IT' => '+39',
        'FR' => '+33',
        'BE' => '+32',
        'NL' => '+31',
        'GB' => '+44',
        'IE' => '+353',
        'DK' => '+45',
        'SE' => '+46',
        'NO' => '+47',
        'FI' => '+358',
        'IS' => '+354',
        'PT' => '+351',
        'ES' => '+34',
        'GR' => '+30',
        'CY' => '+357',
        'MT' => '+356',
        'LU' => '+352',
        'LI' => '+423',
        'MC' => '+377',
        'AD' => '+376',
        'SM' => '+378',
        'VA' => '+39',
        'RU' => '+7',
        'KZ' => '+7',
        'UA' => '+380',
        'BY' => '+375',
        'LT' => '+370',
        'LV' => '+371',
        'EE' => '+372',
        'MD' => '+373',
        'AM' => '+374',
        'GE' => '+995',
        'AZ' => '+994',
        'TR' => '+90',
        'US' => '+1',
        'CA' => '+1',
        'MX' => '+52',
        'AR' => '+54',
        'BR' => '+55',
        'CL' => '+56',
        'CO' => '+57',
        'PE' => '+51',
        'VE' => '+58',
        'BO' => '+591',
        'EC' => '+593',
        'PY' => '+595',
        'UY' => '+598',
        'GY' => '+592',
        'SR' => '+597',
        'GF' => '+594',
        'CN' => '+86',
        'JP' => '+81',
        'KR' => '+82',
        'VN' => '+84',
        'TH' => '+66',
        'SG' => '+65',
        'MY' => '+60',
        'ID' => '+62',
        'PH' => '+63',
        'NZ' => '+64',
        'AU' => '+61',
        'IN' => '+91',
        'PK' => '+92',
        'BD' => '+880',
        'LK' => '+94',
        'MM' => '+95',
        'KH' => '+855',
        'LA' => '+856',
        'BN' => '+673',
        'EG' => '+20',
        'MA' => '+212',
        'DZ' => '+213',
        'TN' => '+216',
        'LY' => '+218',
        'GM' => '+220',
        'SN' => '+221',
        'MR' => '+222',
        'ML' => '+223',
        'GN' => '+224',
        'CI' => '+225',
        'BF' => '+226',
        'NE' => '+227',
        'TG' => '+228',
        'BJ' => '+229',
        'MU' => '+230',
        'LR' => '+231',
        'SL' => '+232',
        'GH' => '+233',
        'NG' => '+234',
        'TD' => '+235',
        'CF' => '+236',
        'CM' => '+237',
        'CV' => '+238',
        'ST' => '+239',
        'GQ' => '+240',
        'GA' => '+241',
        'CG' => '+242',
        'CD' => '+243',
        'AO' => '+244',
        'GW' => '+245',
        'IO' => '+246',
        'SC' => '+248',
        'SD' => '+249',
        'RW' => '+250',
        'ET' => '+251',
        'SO' => '+252',
        'DJ' => '+253',
        'KE' => '+254',
        'TZ' => '+255',
        'UG' => '+256',
        'BI' => '+257',
        'MZ' => '+258',
        'ZM' => '+260',
        'MG' => '+261',
        'RE' => '+262',
        'ZW' => '+263',
        'NA' => '+264',
        'MW' => '+265',
        'LS' => '+266',
        'BW' => '+267',
        'SZ' => '+268',
        'KM' => '+269',
        'ZA' => '+27',
        'SH' => '+290',
        'ER' => '+291',
        'AW' => '+297',
        'FO' => '+298',
        'GL' => '+299',
        'GI' => '+350',
    ];

    public static function isValidCallingCode(string $code): bool
    {
        return in_array($code, self::COUNTRY_CALLING_CODES, true);
    }

    /**
     * @return list<array{country_code: string, calling_code: string, label: string}>
     */
    public static function pickerEntries(): array
    {
        $entries = [];
        foreach (self::COUNTRY_CALLING_CODES as $countryCode => $callingCode) {
            $entries[] = [
                'country_code' => $countryCode,
                'calling_code' => $callingCode,
                'label' => CountryCatalog::label($countryCode) ?? $countryCode,
            ];
        }

        return $entries;
    }

    /**
     * Parse leftover stored phone into a picker suggestion only when the prefix uniquely matches.
     * Does not default Montenegro. Does not rewrite the value.
     *
     * @return array{calling_code: string, national: string}|null
     */
    public static function suggestionFromStored(?string $phone): ?array
    {
        if (! is_string($phone)) {
            return null;
        }

        $trimmed = preg_replace('/\s+/', '', $phone) ?? '';
        if ($trimmed === '' || ! str_starts_with($trimmed, '+')) {
            return null;
        }

        $codes = array_values(array_unique(array_values(self::COUNTRY_CALLING_CODES)));
        usort($codes, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($codes as $code) {
            if (str_starts_with($trimmed, $code)) {
                $national = substr($trimmed, strlen($code));
                if ($national === '' || ! preg_match('/^[0-9]+$/', $national)) {
                    return null;
                }

                return [
                    'calling_code' => $code,
                    'national' => $national,
                ];
            }
        }

        return null;
    }

    public static function compose(string $callingCode, string $national): string
    {
        $digits = preg_replace('/\D+/', '', $national) ?? '';

        return $callingCode.$digits;
    }
}
