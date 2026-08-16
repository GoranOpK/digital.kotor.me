<?php

namespace Tests\Unit;

use App\Services\CulturalCategory\CanonicalCulturalCategoryCatalog;
use App\Support\CulturalCalendarDefaultImages;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CulturalCalendarDefaultImagesTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function readyCategories(): array
    {
        return [
            ['Koncerti', 'img/kalendar-kulture/categories/koncerti.jpg'],
            ['Predstave', 'img/kalendar-kulture/categories/predstave.jpg'],
            ['Sportski događaji', 'img/kalendar-kulture/categories/sportski-dogadjaji.jpg'],
            ['Izložbe', 'img/kalendar-kulture/categories/izlozbe.jpg'],
            ['Filmske projekcije', 'img/kalendar-kulture/categories/filmske-projekcije.jpg'],
            ['Radionice', 'img/kalendar-kulture/categories/radionice.jpg'],
            ['Performansi', 'img/kalendar-kulture/categories/performansi.jpg'],
        ];
    }

    /**
     * @return list<array{0: string}>
     */
    public static function missingCategories(): array
    {
        return [
            ['Dječiji programi'],
            ['Konferencije'],
            ['Sajmovi'],
        ];
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function ambiguousCategoriesWithLegacyCandidates(): array
    {
        return [
            ['Književni programi', 'img/kalendar-kulture/categories/knjizevne-veceri.jpg'],
            ['Publikacije', 'img/kalendar-kulture/categories/promocije-publikacija.jpg'],
            ['Prezentacije i predavanja', 'img/kalendar-kulture/categories/prezentacije.jpg'],
            ['Paneli i tribine', 'img/kalendar-kulture/categories/paneli-o-kulturi.jpg'],
        ];
    }

    /**
     * @return list<array{0: string}>
     */
    public static function legacyOnlyCategories(): array
    {
        return [
            ['Filmski festivali'],
            ['Likovne manifestacije'],
            ['Manifestacije u organizaciji Mjesnih zajednica'],
            ['Manifestacije u organizaciji NVU'],
            ['Književne večeri'],
            ['Promocije publikacija'],
            ['Prezentacije'],
            ['Paneli o kulturi'],
        ];
    }

    public function test_canonical_category_count_is_14(): void
    {
        $this->assertSame(14, CanonicalCulturalCategoryCatalog::count());
        $this->assertCount(14, CanonicalCulturalCategoryCatalog::NAMES);
    }

    public function test_resolver_recognizes_exactly_the_14_canonical_names(): void
    {
        foreach (CanonicalCulturalCategoryCatalog::NAMES as $name) {
            $this->assertTrue(
                CulturalCalendarDefaultImages::isCanonicalCategory($name),
                'Canonical name must be recognized: '.$name
            );
        }

        $this->assertFalse(CulturalCalendarDefaultImages::isCanonicalCategory(null));
        $this->assertFalse(CulturalCalendarDefaultImages::isCanonicalCategory(''));
    }

    public function test_dedicated_asset_map_keys_are_canonical_ready_subset_only(): void
    {
        $map = CulturalCalendarDefaultImages::DEDICATED_CATEGORY_ASSETS;
        $canonical = CanonicalCulturalCategoryCatalog::NAMES;

        foreach (array_keys($map) as $name) {
            $this->assertContains($name, $canonical);
        }

        $this->assertCount(7, $map);
        $this->assertSame(
            $map,
            CulturalCalendarDefaultImages::CATEGORY_DEFAULT_IMAGES
        );
    }

    #[DataProvider('readyCategories')]
    public function test_ready_category_resolves_to_existing_dedicated_asset(string $name, string $relative): void
    {
        $this->assertTrue(CulturalCalendarDefaultImages::isCanonicalCategory($name));
        $this->assertTrue(CulturalCalendarDefaultImages::hasDedicatedCategoryAssetMapping($name));
        $this->assertTrue(CulturalCalendarDefaultImages::dedicatedCategoryAssetAvailable($name));
        $this->assertTrue(is_file(public_path($relative)));
        $this->assertSame($relative, CulturalCalendarDefaultImages::pathForCategory($name));
        $this->assertSame(asset($relative), CulturalCalendarDefaultImages::urlForCategory($name));
    }

    #[DataProvider('missingCategories')]
    public function test_missing_category_is_canonical_but_uses_global_event_fallback(string $name): void
    {
        $this->assertTrue(CulturalCalendarDefaultImages::isCanonicalCategory($name));
        $this->assertFalse(CulturalCalendarDefaultImages::hasDedicatedCategoryAssetMapping($name));
        $this->assertFalse(CulturalCalendarDefaultImages::dedicatedCategoryAssetAvailable($name));
        $this->assertSame(
            CulturalCalendarDefaultImages::FALLBACK_DEFAULT_IMAGE,
            CulturalCalendarDefaultImages::pathForCategory($name)
        );
        $this->assertSame(
            CulturalCalendarDefaultImages::fallbackUrl(),
            CulturalCalendarDefaultImages::urlForCategory($name)
        );
    }

    #[DataProvider('ambiguousCategoriesWithLegacyCandidates')]
    public function test_ambiguous_category_does_not_use_legacy_rename_candidate(string $name, string $legacyRelative): void
    {
        $this->assertTrue(CulturalCalendarDefaultImages::isCanonicalCategory($name));
        $this->assertFalse(CulturalCalendarDefaultImages::hasDedicatedCategoryAssetMapping($name));
        $this->assertTrue(is_file(public_path($legacyRelative)), 'Legacy candidate still exists on disk.');
        $this->assertSame(
            CulturalCalendarDefaultImages::FALLBACK_DEFAULT_IMAGE,
            CulturalCalendarDefaultImages::pathForCategory($name)
        );
        $this->assertNotSame($legacyRelative, CulturalCalendarDefaultImages::pathForCategory($name));
        $this->assertStringNotContainsString(
            basename($legacyRelative),
            CulturalCalendarDefaultImages::urlForCategory($name)
        );
    }

    #[DataProvider('legacyOnlyCategories')]
    public function test_legacy_only_names_are_not_canonical_map_keys_and_fall_back_globally(string $name): void
    {
        $this->assertFalse(CulturalCalendarDefaultImages::isCanonicalCategory($name));
        $this->assertArrayNotHasKey($name, CulturalCalendarDefaultImages::DEDICATED_CATEGORY_ASSETS);
        $this->assertSame(
            CulturalCalendarDefaultImages::FALLBACK_DEFAULT_IMAGE,
            CulturalCalendarDefaultImages::pathForCategory($name)
        );
    }

    public function test_null_and_unknown_category_use_global_event_fallback(): void
    {
        $this->assertSame(
            CulturalCalendarDefaultImages::FALLBACK_DEFAULT_IMAGE,
            CulturalCalendarDefaultImages::pathForCategory(null)
        );
        $this->assertSame(
            CulturalCalendarDefaultImages::FALLBACK_DEFAULT_IMAGE,
            CulturalCalendarDefaultImages::pathForCategory('Nepostojeća kategorija')
        );
    }

    public function test_mapped_file_missing_falls_back_to_global_event_placeholder(): void
    {
        $this->assertSame(
            CulturalCalendarDefaultImages::FALLBACK_DEFAULT_IMAGE,
            CulturalCalendarDefaultImages::existingPublicRelativeOr(
                'img/kalendar-kulture/categories/nema-takvog-fajla.jpg',
                CulturalCalendarDefaultImages::FALLBACK_DEFAULT_IMAGE
            )
        );
        $this->assertSame(
            'img/kalendar-kulture/categories/koncerti.jpg',
            CulturalCalendarDefaultImages::existingPublicRelativeOr(
                'img/kalendar-kulture/categories/koncerti.jpg',
                CulturalCalendarDefaultImages::FALLBACK_DEFAULT_IMAGE
            )
        );
    }

    public function test_manifestation_fallback_is_a_separate_contract_with_temporary_compatibility_asset(): void
    {
        $this->assertSame(
            'img/kalendar-kulture-default-event.png',
            CulturalCalendarDefaultImages::FALLBACK_MANIFESTATION_IMAGE,
            'MED-I4A: temporary compatibility — not a dedicated MED-I4B MF file.'
        );
        $this->assertSame(
            CulturalCalendarDefaultImages::FALLBACK_DEFAULT_IMAGE,
            CulturalCalendarDefaultImages::FALLBACK_MANIFESTATION_IMAGE,
            'Compatibility only: MF path currently points at the Event global PNG file.'
        );
        $this->assertSame(
            CulturalCalendarDefaultImages::FALLBACK_MANIFESTATION_IMAGE,
            CulturalCalendarDefaultImages::manifestationFallbackPath()
        );
        $this->assertSame(
            asset(CulturalCalendarDefaultImages::FALLBACK_MANIFESTATION_IMAGE),
            CulturalCalendarDefaultImages::manifestationFallbackUrl()
        );
        $this->assertNotSame(
            CulturalCalendarDefaultImages::pathForCategory('Koncerti'),
            CulturalCalendarDefaultImages::manifestationFallbackPath()
        );
    }

    public function test_missing_mf_compatibility_file_would_not_return_a_broken_url(): void
    {
        $this->assertSame(
            CulturalCalendarDefaultImages::FALLBACK_DEFAULT_IMAGE,
            CulturalCalendarDefaultImages::existingPublicRelativeOr(
                'img/kalendar-kulture-dedicated-mf-placeholder-does-not-exist.png',
                CulturalCalendarDefaultImages::FALLBACK_DEFAULT_IMAGE
            )
        );
    }
}
