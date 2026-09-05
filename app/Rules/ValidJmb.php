<?php

namespace App\Rules;

use App\Identity\Validation\JmbIdentifierValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidJmb implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! (new JmbIdentifierValidator)->isValid($value)) {
            $fail('The :attribute is invalid.');
        }
    }
}
