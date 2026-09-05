<?php

namespace Tests\Unit\Identity;

use App\Identity\CountryCatalog;
use Tests\TestCase;

class CountryCatalogTest extends TestCase
{
    /**
     * Officially assigned ISO 3166-1 alpha-2 codes (independent expected set).
     *
     * @var list<string>
     */
    private const EXPECTED_ORDINARY_ISO_ALPHA2 = [
        'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ',
        'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS', 'BT', 'BV', 'BW', 'BY', 'BZ',
        'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN', 'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ',
        'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ',
        'EC', 'EE', 'EG', 'EH', 'ER', 'ES', 'ET',
        'FI', 'FJ', 'FK', 'FM', 'FO', 'FR',
        'GA', 'GB', 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY',
        'HK', 'HM', 'HN', 'HR', 'HT', 'HU',
        'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT',
        'JE', 'JM', 'JO', 'JP',
        'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ',
        'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY',
        'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ',
        'NA', 'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ',
        'OM',
        'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY',
        'QA',
        'RE', 'RO', 'RS', 'RU', 'RW',
        'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY', 'SZ',
        'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW', 'TZ',
        'UA', 'UG', 'UM', 'US', 'UY', 'UZ',
        'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU',
        'WF', 'WS',
        'YE', 'YT',
        'ZA', 'ZM', 'ZW',
    ];

    public function test_catalog_contains_the_complete_official_iso_alpha2_set(): void
    {
        $ordinary = CountryCatalog::ordinaryIso31661Alpha2Codes();
        sort($ordinary);
        $expected = self::EXPECTED_ORDINARY_ISO_ALPHA2;
        sort($expected);

        $this->assertCount(249, $expected);
        $this->assertCount(249, $ordinary);
        $this->assertSame($expected, $ordinary);
        $this->assertNotContains('XK', $ordinary);
    }

    public function test_montenegro_and_other_iso_codes_are_valid(): void
    {
        $this->assertTrue(CountryCatalog::isValidCode('ME'));
        $this->assertTrue(CountryCatalog::isValidCode('me'));
        $this->assertTrue(CountryCatalog::isValidCode('RS'));
        $this->assertTrue(CountryCatalog::isValidCode('IT'));
        $this->assertTrue(CountryCatalog::isValidCode('DE'));
        $this->assertTrue(CountryCatalog::isValidCode('HR'));
        $this->assertNotSame('ME', CountryCatalog::label('ME'));
        $this->assertNotNull(CountryCatalog::label('ME'));
        $this->assertTrue(CountryCatalog::isClaimedIso31661Alpha2('ME'));
        $this->assertFalse(CountryCatalog::isDocumentedException('ME'));
    }

    public function test_xk_is_documented_exception_not_claimed_iso(): void
    {
        $this->assertSame(['XK'], CountryCatalog::documentedExceptionCodes());
        $this->assertTrue(CountryCatalog::isValidCode('XK'));
        $this->assertTrue(CountryCatalog::isDocumentedException('XK'));
        $this->assertFalse(CountryCatalog::isClaimedIso31661Alpha2('XK'));
        $this->assertNotNull(CountryCatalog::label('XK'));
        $this->assertFalse(CountryCatalog::entries()['XK']['iso_3166_1_alpha_2']);
    }

    public function test_unknown_and_non_assigned_codes_are_rejected(): void
    {
        $this->assertFalse(CountryCatalog::isValidCode('ZZ'));
        $this->assertNull(CountryCatalog::label('ZZ'));
        $this->assertFalse(CountryCatalog::isValidCode('XX'));
        $this->assertFalse(CountryCatalog::isValidCode('UK'));
        $this->assertFalse(CountryCatalog::isValidCode('EU'));
    }

    public function test_codes_are_unique_and_ordinary_codes_are_two_letter_alpha(): void
    {
        $ordinary = CountryCatalog::ordinaryIso31661Alpha2Codes();
        $exceptions = CountryCatalog::documentedExceptionCodes();
        $all = CountryCatalog::codes();

        $this->assertSame($ordinary, array_values(array_unique($ordinary)));
        $this->assertSame($exceptions, array_values(array_unique($exceptions)));
        $this->assertSame($all, array_values(array_unique($all)));
        $this->assertSame([], array_values(array_intersect($ordinary, $exceptions)));

        foreach ($ordinary as $code) {
            $this->assertMatchesRegularExpression('/^[A-Z]{2}$/', $code);
        }

        foreach ($exceptions as $code) {
            $this->assertMatchesRegularExpression('/^[A-Z]{2}$/', $code);
        }
    }

    public function test_storage_keys_and_display_labels_are_separate(): void
    {
        $entry = CountryCatalog::entries()['ME'];

        $this->assertSame('ME', $entry['code']);
        $this->assertNotSame('ME', $entry['label']);
        $this->assertNotSame('', $entry['label']);
        $this->assertArrayHasKey('iso_3166_1_alpha_2', $entry);
        $this->assertArrayNotHasKey('nationality', $entry);
        $this->assertArrayNotHasKey('citizenship', $entry);
        $this->assertTrue($entry['iso_3166_1_alpha_2']);
    }
}
