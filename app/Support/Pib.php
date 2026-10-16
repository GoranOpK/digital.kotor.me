<?php

namespace App\Support;

class Pib
{
    public const LENGTH = 8;

    public const REGEX = '/^[0-9]{8}$/';

    public const VALIDATION_MESSAGE = 'PIB mora imati tačno 8 cifara.';

    /**
     * Comparison form: separators/whitespace removed; null unless exactly 8 digits remain.
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/[\s.\-\/]+/u', '', $value) ?? '';

        return preg_match(self::REGEX, $digits) === 1 ? $digits : null;
    }
}
