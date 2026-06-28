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

    public static function cityValidationMessage(): string
    {
        return 'Grad mora biti naselje na teritoriji Opštine Kotor (npr. Kotor, Dobrota, Prčanj, Risan, Perast ili poštanski broj 85310/85330).';
    }

    public static function streetValidationMessage(): string
    {
        return 'Unesite naziv ulice i broj ili oznaku bb (bez broja), npr. Njegoševa 12 ili Maserikova bb. Grad unesite u posebno polje ispod.';
    }

    public static function streetLineValidationMessage(): string
    {
        return 'Unesite naziv ulice i broj ili oznaku bb (bez broja), npr. Njegoševa 12 ili Maserikova bb.';
    }

    /**
     * Ulica može imati kućni broj, bb/b.b./bez broja ili drugu oznaku – numerički broj nije obavezan.
     */
    public static function isValidStreetLine(?string $street): bool
    {
        $street = trim((string) $street);

        if ($street === '' || mb_strlen($street) < 2) {
            return false;
        }

        if (self::isOnlyLocality($street)) {
            return false;
        }

        return true;
    }

    /**
     * Priprema ulicu i grad za čuvanje: razdvaja staru punu adresu i uklanja dupli grad.
     *
     * @return array{0: string, 1: string}
     */
    public static function normalizeStreetAndCityInputs(?string $street, ?string $city): array
    {
        $street = trim((string) $street);
        $city = trim((string) $city);

        if ($street !== '' && $city === '') {
            return self::splitStreetAndCity($street);
        }

        if ($street !== '' && $city !== '') {
            $street = self::stripRedundantCityFromStreet($street, $city);
        }

        return [$street, $city];
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function splitStreetAndCity(string $fullAddress): array
    {
        $fullAddress = trim($fullAddress);
        if ($fullAddress === '') {
            return ['', ''];
        }

        $parts = preg_split('/,\s*/u', $fullAddress) ?: [];
        if (count($parts) < 2) {
            return [$fullAddress, ''];
        }

        $lastPart = trim((string) array_pop($parts));
        if ($lastPart === '') {
            return [$fullAddress, ''];
        }

        $normalizedLast = self::normalize($lastPart);
        $looksLikeCity = preg_match('/\b85310\b|\b85330\b/', $normalizedLast);

        if (!$looksLikeCity) {
            foreach (self::LOCALITIES as $locality) {
                if ($normalizedLast === $locality) {
                    $looksLikeCity = true;
                    break;
                }
            }
        }

        if (!$looksLikeCity) {
            return [$fullAddress, ''];
        }

        $street = trim(implode(', ', $parts));

        return [$street !== '' ? $street : $fullAddress, $lastPart];
    }

    public static function stripRedundantCityFromStreet(string $street, string $city): string
    {
        $street = trim($street);
        $city = trim($city);

        if ($street === '' || $city === '') {
            return $street;
        }

        $normalizedCity = self::normalize($city);
        $normalizedStreet = self::normalize($street);

        if (!preg_match('/,\s*' . preg_quote($normalizedCity, '/') . '\s*$/u', $normalizedStreet)) {
            return $street;
        }

        $lastComma = strrpos($street, ',');
        if ($lastComma === false) {
            return $street;
        }

        return trim(substr($street, 0, $lastComma));
    }

    public static function isOnlyLocality(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return false;
        }

        $normalized = self::normalize($value);

        if (preg_match('/^\s*85310\s*$/', $normalized) || preg_match('/^\s*85330\s*$/', $normalized)) {
            return true;
        }

        foreach (self::LOCALITIES as $locality) {
            if ($normalized === $locality) {
                return true;
            }
        }

        return false;
    }

    public static function formatStreetAndCity(?string $street, ?string $city): string
    {
        $street = trim((string) $street);
        $city = trim((string) $city);

        if ($street !== '' && $city !== '') {
            return $street . ', ' . $city;
        }

        return $street !== '' ? $street : $city;
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
