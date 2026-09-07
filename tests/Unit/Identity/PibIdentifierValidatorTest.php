<?php

namespace Tests\Unit\Identity;

use App\Identity\Validation\PibIdentifierValidator;
use App\Rules\ValidPib;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PibIdentifierValidatorTest extends TestCase
{
    public function test_valid_iso_7064_mod_11_10_is_accepted(): void
    {
        $validator = new PibIdentifierValidator;

        $this->assertTrue($validator->isValid('12345672'));
        $this->assertTrue($validator->isValid('00000007'));
    }

    public function test_invalid_check_digit_is_rejected(): void
    {
        $this->assertFalse((new PibIdentifierValidator)->isValid('12345670'));
    }

    public function test_wrong_length_or_non_digits_are_rejected(): void
    {
        $validator = new PibIdentifierValidator;

        $this->assertFalse($validator->isValid('1234567'));
        $this->assertFalse($validator->isValid('123456789'));
        $this->assertFalse($validator->isValid('1234567A'));
    }

    public function test_laravel_rule_adapter_delegates_to_pure_validator(): void
    {
        $pass = Validator::make(['pib' => '12345672'], ['pib' => [new ValidPib]]);
        $fail = Validator::make(['pib' => '12345670'], ['pib' => [new ValidPib]]);

        $this->assertFalse($pass->fails());
        $this->assertTrue($fail->fails());
    }
}
