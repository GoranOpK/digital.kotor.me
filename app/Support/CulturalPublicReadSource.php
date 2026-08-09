<?php

namespace App\Support;

/**
 * Privremeni XOR izbor izvora javnog čitanja Kalendara kulture (Faza 6A).
 * Ne izvršava query — samo određuje aktivni read source.
 */
final class CulturalPublicReadSource
{
    public const LEGACY = 'legacy';

    public const CANONICAL = 'canonical';

    /**
     * @return self::LEGACY|self::CANONICAL
     */
    public static function current(): string
    {
        $raw = config('cultural_calendar.public_read_source', self::LEGACY);
        $value = is_string($raw) ? strtolower(trim($raw)) : '';

        // Fail-safe: samo tačan "canonical" aktivira kanonski read.
        if ($value === self::CANONICAL) {
            return self::CANONICAL;
        }

        return self::LEGACY;
    }

    public static function usesLegacy(): bool
    {
        return self::current() === self::LEGACY;
    }

    public static function usesCanonical(): bool
    {
        return self::current() === self::CANONICAL;
    }
}
