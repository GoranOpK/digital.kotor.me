<?php

namespace App\Identity\Validation;

final class PibIdentifierValidator
{
    public function isValid(mixed $value): bool
    {
        if (! is_string($value) && ! is_int($value)) {
            return false;
        }

        $pib = (string) $value;

        if (! preg_match('/^[0-9]{8}$/', $pib)) {
            return false;
        }

        $product = 10;
        for ($i = 0; $i < 7; $i++) {
            $product = ($product + (int) $pib[$i]) % 10;
            if ($product === 0) {
                $product = 10;
            }
            $product = ($product * 2) % 11;
        }

        $checkDigit = (11 - $product) % 10;

        return $checkDigit === (int) $pib[7];
    }
}
