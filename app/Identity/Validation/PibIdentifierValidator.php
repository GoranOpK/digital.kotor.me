<?php

namespace App\Identity\Validation;

/**
 * PIB is accepted on format alone (exactly 8 digits). The check digit is
 * intentionally not verified: official PIBs exist that fail ISO 7064 MOD 11,10.
 */
final class PibIdentifierValidator
{
    public function isValid(mixed $value): bool
    {
        if (! is_string($value) && ! is_int($value)) {
            return false;
        }

        return preg_match('/^[0-9]{8}$/', (string) $value) === 1;
    }
}
