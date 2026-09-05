<?php

namespace App\Identity;

/**
 * Application-level canonical country catalog (DK-TS-002 D6).
 *
 * Stored identity value is the stable country code, not the display label.
 * Ordinary entries are the officially assigned ISO 3166-1 alpha-2 codes.
 * XK is a documented platform exception, not a claimed ISO 3166-1 alpha-2 assignment.
 *
 * D6 does not specify a complete translated label table. Labels below are the
 * ISO 3166 English short names as a non-normative display representation.
 * They are not nationality, citizenship, or political-status fields.
 */
final class CountryCatalog
{
    /**
     * Officially assigned ISO 3166-1 alpha-2 codes mapped to ISO English short names.
     *
     * @var array<string, string>
     */
    private const ISO_ALPHA2_LABELS = [
        'AD' => 'Andorra',
        'AE' => 'United Arab Emirates',
        'AF' => 'Afghanistan',
        'AG' => 'Antigua and Barbuda',
        'AI' => 'Anguilla',
        'AL' => 'Albania',
        'AM' => 'Armenia',
        'AO' => 'Angola',
        'AQ' => 'Antarctica',
        'AR' => 'Argentina',
        'AS' => 'American Samoa',
        'AT' => 'Austria',
        'AU' => 'Australia',
        'AW' => 'Aruba',
        'AX' => 'Åland Islands',
        'AZ' => 'Azerbaijan',
        'BA' => 'Bosnia and Herzegovina',
        'BB' => 'Barbados',
        'BD' => 'Bangladesh',
        'BE' => 'Belgium',
        'BF' => 'Burkina Faso',
        'BG' => 'Bulgaria',
        'BH' => 'Bahrain',
        'BI' => 'Burundi',
        'BJ' => 'Benin',
        'BL' => 'Saint Barthélemy',
        'BM' => 'Bermuda',
        'BN' => 'Brunei Darussalam',
        'BO' => 'Bolivia (Plurinational State of)',
        'BQ' => 'Bonaire, Sint Eustatius and Saba',
        'BR' => 'Brazil',
        'BS' => 'Bahamas',
        'BT' => 'Bhutan',
        'BV' => 'Bouvet Island',
        'BW' => 'Botswana',
        'BY' => 'Belarus',
        'BZ' => 'Belize',
        'CA' => 'Canada',
        'CC' => 'Cocos (Keeling) Islands',
        'CD' => 'Congo, Democratic Republic of the',
        'CF' => 'Central African Republic',
        'CG' => 'Congo',
        'CH' => 'Switzerland',
        'CI' => "Côte d'Ivoire",
        'CK' => 'Cook Islands',
        'CL' => 'Chile',
        'CM' => 'Cameroon',
        'CN' => 'China',
        'CO' => 'Colombia',
        'CR' => 'Costa Rica',
        'CU' => 'Cuba',
        'CV' => 'Cabo Verde',
        'CW' => 'Curaçao',
        'CX' => 'Christmas Island',
        'CY' => 'Cyprus',
        'CZ' => 'Czechia',
        'DE' => 'Germany',
        'DJ' => 'Djibouti',
        'DK' => 'Denmark',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'DZ' => 'Algeria',
        'EC' => 'Ecuador',
        'EE' => 'Estonia',
        'EG' => 'Egypt',
        'EH' => 'Western Sahara',
        'ER' => 'Eritrea',
        'ES' => 'Spain',
        'ET' => 'Ethiopia',
        'FI' => 'Finland',
        'FJ' => 'Fiji',
        'FK' => 'Falkland Islands (Malvinas)',
        'FM' => 'Micronesia (Federated States of)',
        'FO' => 'Faroe Islands',
        'FR' => 'France',
        'GA' => 'Gabon',
        'GB' => 'United Kingdom of Great Britain and Northern Ireland',
        'GD' => 'Grenada',
        'GE' => 'Georgia',
        'GF' => 'French Guiana',
        'GG' => 'Guernsey',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GL' => 'Greenland',
        'GM' => 'Gambia',
        'GN' => 'Guinea',
        'GP' => 'Guadeloupe',
        'GQ' => 'Equatorial Guinea',
        'GR' => 'Greece',
        'GS' => 'South Georgia and the South Sandwich Islands',
        'GT' => 'Guatemala',
        'GU' => 'Guam',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HK' => 'Hong Kong',
        'HM' => 'Heard Island and McDonald Islands',
        'HN' => 'Honduras',
        'HR' => 'Croatia',
        'HT' => 'Haiti',
        'HU' => 'Hungary',
        'ID' => 'Indonesia',
        'IE' => 'Ireland',
        'IL' => 'Israel',
        'IM' => 'Isle of Man',
        'IN' => 'India',
        'IO' => 'British Indian Ocean Territory',
        'IQ' => 'Iraq',
        'IR' => 'Iran (Islamic Republic of)',
        'IS' => 'Iceland',
        'IT' => 'Italy',
        'JE' => 'Jersey',
        'JM' => 'Jamaica',
        'JO' => 'Jordan',
        'JP' => 'Japan',
        'KE' => 'Kenya',
        'KG' => 'Kyrgyzstan',
        'KH' => 'Cambodia',
        'KI' => 'Kiribati',
        'KM' => 'Comoros',
        'KN' => 'Saint Kitts and Nevis',
        'KP' => "Korea (Democratic People's Republic of)",
        'KR' => 'Korea, Republic of',
        'KW' => 'Kuwait',
        'KY' => 'Cayman Islands',
        'KZ' => 'Kazakhstan',
        'LA' => "Lao People's Democratic Republic",
        'LB' => 'Lebanon',
        'LC' => 'Saint Lucia',
        'LI' => 'Liechtenstein',
        'LK' => 'Sri Lanka',
        'LR' => 'Liberia',
        'LS' => 'Lesotho',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'LV' => 'Latvia',
        'LY' => 'Libya',
        'MA' => 'Morocco',
        'MC' => 'Monaco',
        'MD' => 'Moldova, Republic of',
        'ME' => 'Montenegro',
        'MF' => 'Saint Martin (French part)',
        'MG' => 'Madagascar',
        'MH' => 'Marshall Islands',
        'MK' => 'North Macedonia',
        'ML' => 'Mali',
        'MM' => 'Myanmar',
        'MN' => 'Mongolia',
        'MO' => 'Macao',
        'MP' => 'Northern Mariana Islands',
        'MQ' => 'Martinique',
        'MR' => 'Mauritania',
        'MS' => 'Montserrat',
        'MT' => 'Malta',
        'MU' => 'Mauritius',
        'MV' => 'Maldives',
        'MW' => 'Malawi',
        'MX' => 'Mexico',
        'MY' => 'Malaysia',
        'MZ' => 'Mozambique',
        'NA' => 'Namibia',
        'NC' => 'New Caledonia',
        'NE' => 'Niger',
        'NF' => 'Norfolk Island',
        'NG' => 'Nigeria',
        'NI' => 'Nicaragua',
        'NL' => 'Netherlands, Kingdom of the',
        'NO' => 'Norway',
        'NP' => 'Nepal',
        'NR' => 'Nauru',
        'NU' => 'Niue',
        'NZ' => 'New Zealand',
        'OM' => 'Oman',
        'PA' => 'Panama',
        'PE' => 'Peru',
        'PF' => 'French Polynesia',
        'PG' => 'Papua New Guinea',
        'PH' => 'Philippines',
        'PK' => 'Pakistan',
        'PL' => 'Poland',
        'PM' => 'Saint Pierre and Miquelon',
        'PN' => 'Pitcairn',
        'PR' => 'Puerto Rico',
        'PS' => 'Palestine, State of',
        'PT' => 'Portugal',
        'PW' => 'Palau',
        'PY' => 'Paraguay',
        'QA' => 'Qatar',
        'RE' => 'Réunion',
        'RO' => 'Romania',
        'RS' => 'Serbia',
        'RU' => 'Russian Federation',
        'RW' => 'Rwanda',
        'SA' => 'Saudi Arabia',
        'SB' => 'Solomon Islands',
        'SC' => 'Seychelles',
        'SD' => 'Sudan',
        'SE' => 'Sweden',
        'SG' => 'Singapore',
        'SH' => 'Saint Helena, Ascension and Tristan da Cunha',
        'SI' => 'Slovenia',
        'SJ' => 'Svalbard and Jan Mayen',
        'SK' => 'Slovakia',
        'SL' => 'Sierra Leone',
        'SM' => 'San Marino',
        'SN' => 'Senegal',
        'SO' => 'Somalia',
        'SR' => 'Suriname',
        'SS' => 'South Sudan',
        'ST' => 'Sao Tome and Principe',
        'SV' => 'El Salvador',
        'SX' => 'Sint Maarten (Dutch part)',
        'SY' => 'Syrian Arab Republic',
        'SZ' => 'Eswatini',
        'TC' => 'Turks and Caicos Islands',
        'TD' => 'Chad',
        'TF' => 'French Southern Territories',
        'TG' => 'Togo',
        'TH' => 'Thailand',
        'TJ' => 'Tajikistan',
        'TK' => 'Tokelau',
        'TL' => 'Timor-Leste',
        'TM' => 'Turkmenistan',
        'TN' => 'Tunisia',
        'TO' => 'Tonga',
        'TR' => 'Türkiye',
        'TT' => 'Trinidad and Tobago',
        'TV' => 'Tuvalu',
        'TW' => 'Taiwan, Province of China',
        'TZ' => 'Tanzania, United Republic of',
        'UA' => 'Ukraine',
        'UG' => 'Uganda',
        'UM' => 'United States Minor Outlying Islands',
        'US' => 'United States of America',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan',
        'VA' => 'Holy See',
        'VC' => 'Saint Vincent and the Grenadines',
        'VE' => 'Venezuela (Bolivarian Republic of)',
        'VG' => 'Virgin Islands (British)',
        'VI' => 'Virgin Islands (U.S.)',
        'VN' => 'Viet Nam',
        'VU' => 'Vanuatu',
        'WF' => 'Wallis and Futuna',
        'WS' => 'Samoa',
        'YE' => 'Yemen',
        'YT' => 'Mayotte',
        'ZA' => 'South Africa',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe',
    ];

    /**
     * Documented platform exceptions. Not claimed ISO 3166-1 alpha-2 assignments.
     *
     * @var array<string, string>
     */
    private const DOCUMENTED_EXCEPTIONS = [
        'XK' => 'Kosovo',
    ];

    public static function isValidCode(string $code): bool
    {
        $normalized = strtoupper($code);

        return isset(self::ISO_ALPHA2_LABELS[$normalized])
            || isset(self::DOCUMENTED_EXCEPTIONS[$normalized]);
    }

    public static function isDocumentedException(string $code): bool
    {
        return isset(self::DOCUMENTED_EXCEPTIONS[strtoupper($code)]);
    }

    public static function isClaimedIso31661Alpha2(string $code): bool
    {
        return isset(self::ISO_ALPHA2_LABELS[strtoupper($code)]);
    }

    public static function label(string $code): ?string
    {
        $normalized = strtoupper($code);

        return self::ISO_ALPHA2_LABELS[$normalized]
            ?? self::DOCUMENTED_EXCEPTIONS[$normalized]
            ?? null;
    }

    /**
     * @return list<string>
     */
    public static function ordinaryIso31661Alpha2Codes(): array
    {
        return array_keys(self::ISO_ALPHA2_LABELS);
    }

    /**
     * @return list<string>
     */
    public static function documentedExceptionCodes(): array
    {
        return array_keys(self::DOCUMENTED_EXCEPTIONS);
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::entries());
    }

    /**
     * @return array<string, array{code: string, label: string, iso_3166_1_alpha_2: bool}>
     */
    public static function entries(): array
    {
        $entries = [];

        foreach (self::ISO_ALPHA2_LABELS as $code => $label) {
            $entries[$code] = [
                'code' => $code,
                'label' => $label,
                'iso_3166_1_alpha_2' => true,
            ];
        }

        foreach (self::DOCUMENTED_EXCEPTIONS as $code => $label) {
            $entries[$code] = [
                'code' => $code,
                'label' => $label,
                'iso_3166_1_alpha_2' => false,
            ];
        }

        ksort($entries);

        return $entries;
    }
}
