<?php

namespace Tests\Unit;

use App\Support\CulturalPublicReadSource;
use Tests\TestCase;

/**
 * 6A-01 — CULTURAL_PUBLIC_READ_SOURCE / CulturalPublicReadSource.
 */
class CulturalPublicReadSourceTest extends TestCase
{
    public function test_default_without_explicit_config_is_legacy(): void
    {
        config(['cultural_calendar.public_read_source' => null]);

        $this->assertSame(CulturalPublicReadSource::LEGACY, CulturalPublicReadSource::current());
        $this->assertTrue(CulturalPublicReadSource::usesLegacy());
        $this->assertFalse(CulturalPublicReadSource::usesCanonical());
    }

    public function test_legacy_value_selects_legacy(): void
    {
        config(['cultural_calendar.public_read_source' => 'legacy']);

        $this->assertSame(CulturalPublicReadSource::LEGACY, CulturalPublicReadSource::current());
        $this->assertTrue(CulturalPublicReadSource::usesLegacy());
        $this->assertFalse(CulturalPublicReadSource::usesCanonical());
    }

    public function test_canonical_value_selects_canonical(): void
    {
        config(['cultural_calendar.public_read_source' => 'canonical']);

        $this->assertSame(CulturalPublicReadSource::CANONICAL, CulturalPublicReadSource::current());
        $this->assertTrue(CulturalPublicReadSource::usesCanonical());
        $this->assertFalse(CulturalPublicReadSource::usesLegacy());
    }

    public function test_case_insensitive_canonical_is_accepted(): void
    {
        config(['cultural_calendar.public_read_source' => 'Canonical']);

        $this->assertTrue(CulturalPublicReadSource::usesCanonical());
        $this->assertFalse(CulturalPublicReadSource::usesLegacy());
    }

    public function test_invalid_values_never_activate_canonical(): void
    {
        foreach (['', '  ', 'both', 'dual', 'unknown', 'legacyy', 'canonicall', '1', true, false, 0] as $invalid) {
            config(['cultural_calendar.public_read_source' => $invalid]);

            $this->assertSame(
                CulturalPublicReadSource::LEGACY,
                CulturalPublicReadSource::current(),
                'Invalid value must fail-safe to legacy: '.var_export($invalid, true)
            );
            $this->assertFalse(CulturalPublicReadSource::usesCanonical());
            $this->assertTrue(CulturalPublicReadSource::usesLegacy());
        }
    }

    public function test_xor_never_both_active(): void
    {
        foreach (['legacy', 'canonical', null, 'bogus'] as $value) {
            config(['cultural_calendar.public_read_source' => $value]);

            $legacy = CulturalPublicReadSource::usesLegacy();
            $canonical = CulturalPublicReadSource::usesCanonical();

            $this->assertTrue($legacy xor $canonical);
            $this->assertFalse($legacy && $canonical);
        }
    }

    public function test_config_file_default_key_resolves_to_legacy_when_unset_in_runtime(): void
    {
        // Simulira odsustvo env override-a: config ključ postoji sa default stringom.
        config(['cultural_calendar.public_read_source' => 'legacy']);

        $this->assertSame('legacy', config('cultural_calendar.public_read_source'));
        $this->assertTrue(CulturalPublicReadSource::usesLegacy());
    }
}
