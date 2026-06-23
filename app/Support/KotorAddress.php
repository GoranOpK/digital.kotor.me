<?php

namespace App\Support;

final class KotorAddress
{
    /**
     * Naselja na teritoriji Opštine Kotor (normalizovana latinica, bez dijakritika).
     *
     * @var list<string>
     */
    private const LOCALITIES = [
        'kotor',
        'dobrota',
        'prcanj',
        'skaljari',
        'risan',
        'perast',
        'muo',
        'orahovac',
        'stoliv',
        'ljuta',
        'mirac',
        'kostanjica',
        'lastva',
        'mrcajevici',
        'puce',
        'grbalj',
    ];

    public static function validationMessage(): string
    {
        return 'Adresa mora biti na teritoriji Opštine Kotor (npr. Kotor, Dobrota, Prčanj, Risan, Perast ili poštanski broj 85310/85330).';
    }

    public static function isInKotorMunicipality(?string $address): bool
    {
        if ($address === null || trim($address) === '') {
            return false;
        }

        $normalized = self::normalize($address);

        if (preg_match('/\b85310\b|\b85330\b/', $normalized)) {
            return true;
        }

        if (str_contains($normalized, 'opstina kotor') || str_contains($normalized, 'opstine kotor')) {
            return true;
        }

        foreach (self::LOCALITIES as $locality) {
            if (self::containsLocality($normalized, $locality)) {
                return true;
            }
        }

        return false;
    }

    private static function containsLocality(string $normalizedAddress, string $locality): bool
    {
        return (bool) preg_match('/\b' . preg_quote($locality, '/') . '\b/u', $normalizedAddress);
    }

    private static function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');

        return strtr($value, [
            'č' => 'c',
            'ć' => 'c',
            'ž' => 'z',
            'š' => 's',
            'đ' => 'dj',
            'dž' => 'dz',
        ]);
    }
}
