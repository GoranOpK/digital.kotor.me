<?php

namespace App\Identity;

/**
 * Application-level canonical country catalog (DK-TS-002 D6).
 *
 * Stored identity value is the stable country code, not the display label.
 * Ordinary entries are the officially assigned ISO 3166-1 alpha-2 codes.
 * XK is a documented platform exception, not a claimed ISO 3166-1 alpha-2 assignment.
 *
 * Display labels are Montenegrin (Ijekavian where applicable). They are not
 * nationality, citizenship, or political-status fields. Changing a label does
 * not change canonical country identity.
 */
final class CountryCatalog
{
    /**
     * Officially assigned ISO 3166-1 alpha-2 codes mapped to Montenegrin display names.
     *
     * @var array<string, string>
     */
    private const ISO_ALPHA2_LABELS = [
        'AD' => 'Andora',
        'AE' => 'Ujedinjeni Arapski Emirati',
        'AF' => 'Avganistan',
        'AG' => 'Antigva i Barbuda',
        'AI' => 'Angvila',
        'AL' => 'Albanija',
        'AM' => 'Jermenija',
        'AO' => 'Angola',
        'AQ' => 'Antarktik',
        'AR' => 'Argentina',
        'AS' => 'Američka Samoa',
        'AT' => 'Austrija',
        'AU' => 'Australija',
        'AW' => 'Aruba',
        'AX' => 'Olandska Ostrva',
        'AZ' => 'Azerbejdžan',
        'BA' => 'Bosna i Hercegovina',
        'BB' => 'Barbados',
        'BD' => 'Bangladeš',
        'BE' => 'Belgija',
        'BF' => 'Burkina Faso',
        'BG' => 'Bugarska',
        'BH' => 'Bahrein',
        'BI' => 'Burundi',
        'BJ' => 'Benin',
        'BL' => 'Sveti Bartolomej',
        'BM' => 'Bermudi',
        'BN' => 'Brunej',
        'BO' => 'Bolivija',
        'BQ' => 'Karipska Nizozemska',
        'BR' => 'Brazil',
        'BS' => 'Bahami',
        'BT' => 'Butan',
        'BV' => 'Ostrvo Buve',
        'BW' => 'Bocvana',
        'BY' => 'Bjelorusija',
        'BZ' => 'Belize',
        'CA' => 'Kanada',
        'CC' => 'Kokosova (Kilingova) Ostrva',
        'CD' => 'Demokratska Republika Kongo',
        'CF' => 'Centralnoafrička Republika',
        'CG' => 'Kongo',
        'CH' => 'Švajcarska',
        'CI' => 'Obala Slonovače',
        'CK' => 'Kukova Ostrva',
        'CL' => 'Čile',
        'CM' => 'Kamerun',
        'CN' => 'Kina',
        'CO' => 'Kolumbija',
        'CR' => 'Kostarika',
        'CU' => 'Kuba',
        'CV' => 'Zelenortska Ostrva',
        'CW' => 'Kurasao',
        'CX' => 'Božićno Ostrvo',
        'CY' => 'Kipar',
        'CZ' => 'Češka',
        'DE' => 'Njemačka',
        'DJ' => 'Džibuti',
        'DK' => 'Danska',
        'DM' => 'Dominika',
        'DO' => 'Dominikanska Republika',
        'DZ' => 'Alžir',
        'EC' => 'Ekvador',
        'EE' => 'Estonija',
        'EG' => 'Egipat',
        'EH' => 'Zapadna Sahara',
        'ER' => 'Eritreja',
        'ES' => 'Španija',
        'ET' => 'Etiopija',
        'FI' => 'Finska',
        'FJ' => 'Fidži',
        'FK' => 'Foklandska Ostrva',
        'FM' => 'Mikronezija',
        'FO' => 'Farska Ostrva',
        'FR' => 'Francuska',
        'GA' => 'Gabon',
        'GB' => 'Ujedinjeno Kraljevstvo',
        'GD' => 'Grenada',
        'GE' => 'Gruzija',
        'GF' => 'Francuska Gvajana',
        'GG' => 'Gernzi',
        'GH' => 'Gana',
        'GI' => 'Gibraltar',
        'GL' => 'Grenland',
        'GM' => 'Gambija',
        'GN' => 'Gvineja',
        'GP' => 'Gvadelup',
        'GQ' => 'Ekvatorijalna Gvineja',
        'GR' => 'Grčka',
        'GS' => 'Južna Džordžija i Južna Sendvička Ostrva',
        'GT' => 'Gvatemala',
        'GU' => 'Guam',
        'GW' => 'Gvineja-Bisao',
        'GY' => 'Gvajana',
        'HK' => 'Hongkong',
        'HM' => 'Ostrvo Herd i Mekdonaldova ostrva',
        'HN' => 'Honduras',
        'HR' => 'Hrvatska',
        'HT' => 'Haiti',
        'HU' => 'Mađarska',
        'ID' => 'Indonezija',
        'IE' => 'Irska',
        'IL' => 'Izrael',
        'IM' => 'Ostrvo Man',
        'IN' => 'Indija',
        'IO' => 'Britanska teritorija Indijskog okeana',
        'IQ' => 'Irak',
        'IR' => 'Iran',
        'IS' => 'Island',
        'IT' => 'Italija',
        'JE' => 'Džerzi',
        'JM' => 'Jamajka',
        'JO' => 'Jordan',
        'JP' => 'Japan',
        'KE' => 'Kenija',
        'KG' => 'Kirgistan',
        'KH' => 'Kambodža',
        'KI' => 'Kiribati',
        'KM' => 'Komori',
        'KN' => 'Sent Kits i Nevis',
        'KP' => 'Sjeverna Koreja',
        'KR' => 'Južna Koreja',
        'KW' => 'Kuvajt',
        'KY' => 'Kajmanska Ostrva',
        'KZ' => 'Kazahstan',
        'LA' => 'Laos',
        'LB' => 'Liban',
        'LC' => 'Sveta Lucija',
        'LI' => 'Lihtenštajn',
        'LK' => 'Šri Lanka',
        'LR' => 'Liberija',
        'LS' => 'Lesoto',
        'LT' => 'Litvanija',
        'LU' => 'Luksemburg',
        'LV' => 'Letonija',
        'LY' => 'Libija',
        'MA' => 'Maroko',
        'MC' => 'Monako',
        'MD' => 'Moldavija',
        'ME' => 'Crna Gora',
        'MF' => 'Sveti Martin (Francuska)',
        'MG' => 'Madagaskar',
        'MH' => 'Maršalska Ostrva',
        'MK' => 'Sjeverna Makedonija',
        'ML' => 'Mali',
        'MM' => 'Mijanmar',
        'MN' => 'Mongolija',
        'MO' => 'Makao',
        'MP' => 'Sjeverna Marijanska Ostrva',
        'MQ' => 'Martinik',
        'MR' => 'Mauritanija',
        'MS' => 'Monserat',
        'MT' => 'Malta',
        'MU' => 'Mauricijus',
        'MV' => 'Maldivi',
        'MW' => 'Malavi',
        'MX' => 'Meksiko',
        'MY' => 'Malezija',
        'MZ' => 'Mozambik',
        'NA' => 'Namibija',
        'NC' => 'Nova Kaledonija',
        'NE' => 'Niger',
        'NF' => 'Ostrvo Norfok',
        'NG' => 'Nigerija',
        'NI' => 'Nikaragva',
        'NL' => 'Nizozemska',
        'NO' => 'Norveška',
        'NP' => 'Nepal',
        'NR' => 'Nauru',
        'NU' => 'Niue',
        'NZ' => 'Novi Zeland',
        'OM' => 'Oman',
        'PA' => 'Panama',
        'PE' => 'Peru',
        'PF' => 'Francuska Polinezija',
        'PG' => 'Papua Nova Gvineja',
        'PH' => 'Filipini',
        'PK' => 'Pakistan',
        'PL' => 'Poljska',
        'PM' => 'Sen Pjer i Mikelon',
        'PN' => 'Pitkern',
        'PR' => 'Portoriko',
        'PS' => 'Palestina',
        'PT' => 'Portugalija',
        'PW' => 'Palau',
        'PY' => 'Paragvaj',
        'QA' => 'Katar',
        'RE' => 'Reunion',
        'RO' => 'Rumunija',
        'RS' => 'Srbija',
        'RU' => 'Ruska Federacija',
        'RW' => 'Ruanda',
        'SA' => 'Saudijska Arabija',
        'SB' => 'Solomonska Ostrva',
        'SC' => 'Sejšeli',
        'SD' => 'Sudan',
        'SE' => 'Švedska',
        'SG' => 'Singapur',
        'SH' => 'Sveta Jelena, Asension i Tristan da Kunja',
        'SI' => 'Slovenija',
        'SJ' => 'Svalbard i Jan Majen',
        'SK' => 'Slovačka',
        'SL' => 'Sijera Leone',
        'SM' => 'San Marino',
        'SN' => 'Senegal',
        'SO' => 'Somalija',
        'SR' => 'Surinam',
        'SS' => 'Južni Sudan',
        'ST' => 'Sao Tome i Principe',
        'SV' => 'El Salvador',
        'SX' => 'Sveti Martin (Nizozemska)',
        'SY' => 'Sirija',
        'SZ' => 'Esvatini',
        'TC' => 'Ostrva Turks i Kaikos',
        'TD' => 'Čad',
        'TF' => 'Francuske Južne Teritorije',
        'TG' => 'Togo',
        'TH' => 'Tajland',
        'TJ' => 'Tadžikistan',
        'TK' => 'Tokelau',
        'TL' => 'Timor-Leste',
        'TM' => 'Turkmenistan',
        'TN' => 'Tunis',
        'TO' => 'Tonga',
        'TR' => 'Turska',
        'TT' => 'Trinidad i Tobago',
        'TV' => 'Tuvalu',
        'TW' => 'Tajvan',
        'TZ' => 'Tanzanija',
        'UA' => 'Ukrajina',
        'UG' => 'Uganda',
        'UM' => 'Udaljena ostrva Sjedinjenih Američkih Država',
        'US' => 'Sjedinjene Američke Države',
        'UY' => 'Urugvaj',
        'UZ' => 'Uzbekistan',
        'VA' => 'Vatikan',
        'VC' => 'Sent Vinsent i Grenadini',
        'VE' => 'Venecuela',
        'VG' => 'Britanska Devičanska Ostrva',
        'VI' => 'Američka Devičanska Ostrva',
        'VN' => 'Vijetnam',
        'VU' => 'Vanuatu',
        'WF' => 'Valis i Futuna',
        'WS' => 'Samoa',
        'YE' => 'Jemen',
        'YT' => 'Majot',
        'ZA' => 'Južnoafrička Republika',
        'ZM' => 'Zambija',
        'ZW' => 'Zimbabve',
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
