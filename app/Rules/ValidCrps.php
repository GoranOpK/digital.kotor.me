<?php

namespace App\Rules;

use App\Identity\Validation\CrpsIdentifierValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCrps implements ValidationRule
{
    public function __construct(private readonly ?int $expectedMark = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! (new CrpsIdentifierValidator)->isValid($value, $this->expectedMark)) {
            $fail('The :attribute is invalid.');
        }
    }
}
