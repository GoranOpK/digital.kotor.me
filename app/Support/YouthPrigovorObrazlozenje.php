<?php

namespace App\Support;

/**
 * Strukturirano obrazloženje omladinskog prigovora u postojećoj koloni `obrazlozenje`.
 * Ženski plain-text ostaje netaknut: parse na njemu vraća prazna polja i ne baca.
 */
final class YouthPrigovorObrazlozenje
{
    public const PREFIX = "OM-PRIGOVOR-v1\n";

    public const SHARED_PREFIX = "OM-PRIGOVOR-SHARED-v1\n";

    /**
     * @param  array<int|string, string|null>  $explanationsByNumber
     */
    public static function compose(array $explanationsByNumber): string
    {
        $payload = [];
        foreach ([1, 2, 3] as $number) {
            $payload[(string) $number] = trim((string) ($explanationsByNumber[$number] ?? $explanationsByNumber[(string) $number] ?? ''));
        }

        return self::PREFIX.json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    public static function composeShared(string $text): string
    {
        return self::SHARED_PREFIX.json_encode(
            ['text' => trim($text)],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * @return array{1: string, 2: string, 3: string}
     */
    public static function parse(?string $obrazlozenje): array
    {
        $empty = [1 => '', 2 => '', 3 => ''];

        if (! is_string($obrazlozenje) || ! str_starts_with($obrazlozenje, self::PREFIX)) {
            return $empty;
        }

        $decoded = json_decode(substr($obrazlozenje, strlen(self::PREFIX)), true);
        if (! is_array($decoded)) {
            return $empty;
        }

        return [
            1 => trim((string) ($decoded['1'] ?? $decoded[1] ?? '')),
            2 => trim((string) ($decoded['2'] ?? $decoded[2] ?? '')),
            3 => trim((string) ($decoded['3'] ?? $decoded[3] ?? '')),
        ];
    }

    public static function isStructured(?string $obrazlozenje): bool
    {
        return is_string($obrazlozenje) && str_starts_with($obrazlozenje, self::PREFIX);
    }

    public static function isShared(?string $obrazlozenje): bool
    {
        return is_string($obrazlozenje) && str_starts_with($obrazlozenje, self::SHARED_PREFIX);
    }

    public static function sharedText(?string $obrazlozenje): ?string
    {
        if (! self::isShared($obrazlozenje)) {
            return null;
        }

        $decoded = json_decode(substr($obrazlozenje, strlen(self::SHARED_PREFIX)), true);
        if (! is_array($decoded)) {
            return null;
        }

        return trim((string) ($decoded['text'] ?? ''));
    }

    public static function explanation(?string $obrazlozenje, int $number): string
    {
        return self::parse($obrazlozenje)[$number] ?? '';
    }
}
