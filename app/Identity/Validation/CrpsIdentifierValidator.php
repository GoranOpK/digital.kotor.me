<?php

namespace App\Identity\Validation;

final class CrpsIdentifierValidator
{
    public const MARK_ENTREPRENEUR = 1;

    public const MARK_OD = 2;

    public const MARK_KD = 3;

    public const MARK_AD = 4;

    public const MARK_DOO = 5;

    public const MARK_FOREIGN_BRANCH = 6;

    public function isValid(mixed $value, ?int $expectedMark = null): bool
    {
        if (! is_string($value) && ! is_int($value)) {
            return false;
        }

        $crps = (string) $value;

        if (! preg_match('/^[0-9]{8}$/', $crps)) {
            return false;
        }

        $mark = (int) $crps[0];
        if ($mark < 1 || $mark > 6) {
            return false;
        }

        if ($expectedMark !== null && $mark !== $expectedMark) {
            return false;
        }

        $serial = (int) substr($crps, 1, 7);

        return $serial >= 1 && $serial <= 9999999;
    }
}
