<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalizuj broj telefona za CG format (+382XXXXXXXX).
     * Posebno uklanja slučaj dupliranog prefiksa (+382382...).
     */
    public static function normalize(?string $phone): ?string
    {
        if (!filled($phone)) {
            return $phone;
        }

        $value = preg_replace('/\s+/', '', (string) $phone);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^\d+]/', '', $value);

        if (str_starts_with($value, '00')) {
            $value = '+' . substr($value, 2);
        }

        if (str_starts_with($value, '+382382')) {
            return '+382' . substr($value, 7);
        }

        if (str_starts_with($value, '382382')) {
            return '+382' . substr($value, 6);
        }

        if (str_starts_with($value, '+382')) {
            return '+382' . substr($value, 4);
        }

        if (str_starts_with($value, '382')) {
            return '+382' . substr($value, 3);
        }

        if (str_starts_with($value, '0')) {
            return '+382' . ltrim($value, '0');
        }

        if (!str_starts_with($value, '+')) {
            return '+382' . $value;
        }

        return $value;
    }
}
