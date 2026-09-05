<?php

namespace App\Identity\Validation;

final class JmbIdentifierValidator
{
    /**
     * @var list<int>
     */
    private const WEIGHTS = [7, 6, 5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

    public function isValid(mixed $value): bool
    {
        if (! is_string($value) && ! is_int($value)) {
            return false;
        }

        $jmb = (string) $value;

        if (! preg_match('/^[0-9]{13}$/', $jmb)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $jmb[$i] * self::WEIGHTS[$i];
        }

        $remainder = $sum % 11;

        if ($remainder === 1) {
            return false;
        }

        $checkDigit = $remainder === 0 ? 0 : 11 - $remainder;

        return $checkDigit === (int) $jmb[12];
    }
}
