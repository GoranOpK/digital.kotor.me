<?php

namespace App\Support;

use App\Services\CulturalCategory\CanonicalCulturalCategoryCatalog;

/**
 * Javni fallback vizueli Kalendara kulture (MED-I4A).
 *
 * Event: statička fotografija kanonske kategorije (ako postoji Git fajl) → globalni Event placeholder.
 * Manifestacija: zaseban MF fallback ugovor (MED-I4B asset još nije dodat; compatibility path).
 *
 * Nije CulturalMedia. Nije category_default. Nije CulturalEvent.slika.
 */
final class CulturalCalendarDefaultImages
{
    /**
     * Dedicated Git category fallbacks by canonical name.
     * Sajmovi has no dedicated file yet and falls back to the global Event placeholder.
     * Legacy-only category names are not keys here.
     *
     * @var array<string, string>
     */
    public const DEDICATED_CATEGORY_ASSETS = [
        'Koncerti' => 'koncerti.jpg',
        'Predstave' => 'performansi.jpg',
        'Sportski događaji' => 'sportski-dogadjaji.jpg',
        'Izložbe' => 'izlozbe.jpg',
        'Književni programi' => 'knjizevne-veceri.jpg',
        'Filmske projekcije' => 'filmske-projekcije.jpg',
        'Dječiji programi' => 'predstave.jpg',
        'Konferencije' => 'prezentacije.jpg',
        'Radionice' => 'radionice.jpg',
        'Publikacije' => 'promocije-publikacija.jpg',
        'Performansi' => 'performansi.jpg',
        'Prezentacije i predavanja' => 'prezentacije.jpg',
        'Paneli i tribine' => 'paneli-o-kulturi.jpg',
    ];

    /**
     * Alias of {@see DEDICATED_CATEGORY_ASSETS} so legacy CulturalEvent constant wiring stays valid.
     * Runtime SSOT for dedicated files is DEDICATED_CATEGORY_ASSETS (canonical READY only).
     *
     * @var array<string, string>
     */
    public const CATEGORY_DEFAULT_IMAGES = self::DEDICATED_CATEGORY_ASSETS;

    public const FALLBACK_DEFAULT_IMAGE = 'img/kalendar-kulture-default-event.png';

    /**
     * Manifestation placeholder path contract (MED-09).
     *
     * Temporary compatibility: same file as the Event global PNG until MED-I4B adds a dedicated asset.
     * Callers must use manifestationFallbackUrl(), not Event category fallback.
     */
    public const FALLBACK_MANIFESTATION_IMAGE = 'img/kalendar-kulture-default-event.png';

    public static function isCanonicalCategory(?string $category): bool
    {
        if ($category === null || $category === '') {
            return false;
        }

        return in_array($category, CanonicalCulturalCategoryCatalog::NAMES, true);
    }

    public static function hasDedicatedCategoryAssetMapping(?string $category): bool
    {
        if ($category === null || $category === '') {
            return false;
        }

        return array_key_exists($category, self::DEDICATED_CATEGORY_ASSETS);
    }

    public static function dedicatedCategoryAssetAvailable(?string $category): bool
    {
        $relative = self::dedicatedCategoryRelativePath($category);

        return $relative !== null && self::publicFileExists($relative);
    }

    public static function urlForCategory(?string $category): string
    {
        return asset(static::pathForCategory($category));
    }

    /**
     * Relative path under public/ for Event category fallback or Event global placeholder.
     */
    public static function pathForCategory(?string $category): string
    {
        $relative = self::dedicatedCategoryRelativePath($category);

        if ($relative !== null) {
            return self::existingPublicRelativeOr($relative, self::FALLBACK_DEFAULT_IMAGE);
        }

        return self::FALLBACK_DEFAULT_IMAGE;
    }

    public static function fallbackUrl(): string
    {
        return asset(self::FALLBACK_DEFAULT_IMAGE);
    }

    public static function manifestationFallbackPath(): string
    {
        return self::existingPublicRelativeOr(
            self::FALLBACK_MANIFESTATION_IMAGE,
            self::FALLBACK_DEFAULT_IMAGE
        );
    }

    public static function manifestationFallbackUrl(): string
    {
        return asset(static::manifestationFallbackPath());
    }

    /**
     * @internal Public for tests: mapped/static path must not become a broken URL.
     */
    public static function existingPublicRelativeOr(string $relative, string $fallback): string
    {
        if ($relative !== '' && self::publicFileExists($relative)) {
            return $relative;
        }

        return $fallback;
    }

    /**
     * @return string|null Relative path under public/, or null when the canonical name has no dedicated mapping.
     */
    private static function dedicatedCategoryRelativePath(?string $category): ?string
    {
        $filename = self::DEDICATED_CATEGORY_ASSETS[$category] ?? null;

        if ($filename === null || $filename === '') {
            return null;
        }

        return 'img/kalendar-kulture/categories/'.$filename;
    }

    private static function publicFileExists(string $relative): bool
    {
        return is_file(public_path($relative));
    }
}
