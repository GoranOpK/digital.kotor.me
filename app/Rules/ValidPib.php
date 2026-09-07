<?php

namespace App\Rules;

use App\Identity\Validation\PibIdentifierValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPib implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! (new PibIdentifierValidator)->isValid($value)) {
            $fail('The :attribute is invalid.');
        }
    }
}
