<?php

namespace App\Rules;

use App\Support\KotorAddress;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class KotorMunicipalityAddress implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || trim((string) $value) === '') {
            return;
        }

        if (!KotorAddress::isInKotorMunicipality((string) $value)) {
            $fail(KotorAddress::validationMessage());
        }
    }
}
