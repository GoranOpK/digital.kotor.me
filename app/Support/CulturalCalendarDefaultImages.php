<?php

namespace App\Support;

/**
 * Shared default / category placeholder images for public Kalendar kulture surfaces.
 * Formerly on legacy CulturalEvent; relocated for canonical-only runtime (Phase B2).
 */
final class CulturalCalendarDefaultImages
{
    /**
     * Reserved default images under public/img/kalendar-kulture/categories/
     * when an event has no uploaded cover.
     */
    public const CATEGORY_DEFAULT_IMAGES = [
        'Koncerti' => 'koncerti.jpg',
        'Predstave' => 'predstave.jpg',
        'Izložbe' => 'izlozbe.jpg',
        'Sportski događaji' => 'sportski-dogadjaji.jpg',
        'Književne večeri' => 'knjizevne-veceri.jpg',
        'Filmske projekcije' => 'filmske-projekcije.jpg',
        'Radionice' => 'radionice.jpg',
        'Promocije publikacija' => 'promocije-publikacija.jpg',
        'Performansi' => 'performansi.jpg',
        'Filmski festivali' => 'filmski-festivali.jpg',
        'Likovne manifestacije' => 'likovne-manifestacije.jpg',
        'Prezentacije' => 'prezentacije.jpg',
        'Paneli o kulturi' => 'paneli-o-kulturi.jpg',
        'Manifestacije u organizaciji Mjesnih zajednica' => 'manifestacije-mjesne-zajednice.jpg',
        'Manifestacije u organizaciji NVU' => 'manifestacije-nvu.jpg',
    ];

    public const FALLBACK_DEFAULT_IMAGE = 'img/kalendar-kulture-default-event.png';

    public static function urlForCategory(?string $category): string
    {
        return asset(static::pathForCategory($category));
    }

    /**
     * Relative path under public/ for the reserved category image.
     */
    public static function pathForCategory(?string $category): string
    {
        $filename = static::CATEGORY_DEFAULT_IMAGES[$category] ?? null;

        if ($filename) {
            $relative = 'img/kalendar-kulture/categories/'.$filename;
            if (is_file(public_path($relative))) {
                return $relative;
            }
        }

        return static::FALLBACK_DEFAULT_IMAGE;
    }

    public static function fallbackUrl(): string
    {
        return asset(static::FALLBACK_DEFAULT_IMAGE);
    }
}
