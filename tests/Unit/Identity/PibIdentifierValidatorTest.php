<?php

namespace Tests\Unit\Identity;

use App\Identity\Validation\PibIdentifierValidator;
use App\Rules\ValidPib;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PibIdentifierValidatorTest extends TestCase
{
    public function test_any_eight_digit_pib_is_accepted(): void
    {
        $validator = new PibIdentifierValidator;

        $this->assertTrue($validator->isValid('12345672'));
        $this->assertTrue($validator->isValid('00000007'));
        $this->assertTrue($validator->isValid('00000000'));
        $this->assertTrue($validator->isValid('99999999'));
    }

    public function test_eight_digit_pib_failing_iso_7064_check_digit_is_accepted(): void
    {
        $this->assertTrue((new PibIdentifierValidator)->isValid('12345670'));
        $this->assertTrue((new PibIdentifierValidator)->isValid('11111111'));
    }

    public function test_wrong_length_or_non_digits_are_rejected(): void
    {
        $validator = new PibIdentifierValidator;

        $this->assertFalse($validator->isValid('1234567'));
        $this->assertFalse($validator->isValid('123456789'));
        $this->assertFalse($validator->isValid('1234567A'));
        $this->assertFalse($validator->isValid(' 12345670'));
        $this->assertFalse($validator->isValid('1234 5670'));
        $this->assertFalse($validator->isValid(''));
        $this->assertFalse($validator->isValid(null));
    }

    public function test_laravel_rule_adapter_delegates_to_pure_validator(): void
    {
        $pass = Validator::make(['pib' => '12345670'], ['pib' => [new ValidPib]]);
        $fail = Validator::make(['pib' => '1234567'], ['pib' => [new ValidPib]]);

        $this->assertFalse($pass->fails());
        $this->assertTrue($fail->fails());
        $this->assertSame(['PIB nije ispravan.'], $fail->errors()->get('pib'));
    }
}
