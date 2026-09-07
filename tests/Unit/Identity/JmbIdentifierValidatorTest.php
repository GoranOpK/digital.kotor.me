<?php

namespace Tests\Unit\Identity;

use App\Identity\Validation\JmbIdentifierValidator;
use App\Rules\ValidJmb;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class JmbIdentifierValidatorTest extends TestCase
{
    public function test_valid_checksum_is_accepted(): void
    {
        $validator = new JmbIdentifierValidator;

        $this->assertTrue($validator->isValid('0000000000000'));
        $this->assertTrue($validator->isValid($this->jmbWithChecksum('010199000001')));
    }

    public function test_invalid_checksum_is_rejected(): void
    {
        $this->assertFalse((new JmbIdentifierValidator)->isValid('0000000000001'));
    }

    public function test_non_digit_or_wrong_length_is_rejected(): void
    {
        $validator = new JmbIdentifierValidator;

        $this->assertFalse($validator->isValid('123'));
        $this->assertFalse($validator->isValid('abcdefghijklm'));
        $this->assertFalse($validator->isValid('00000000000000'));
    }

    public function test_unusual_date_or_region_digits_are_not_independently_rejected(): void
    {
        $validator = new JmbIdentifierValidator;

        $this->assertTrue(
            $validator->isValid('0000000000000'),
            'Checksum-valid JMB with day/month/year 00 must not be rejected for semantic date/region.'
        );
        $this->assertTrue(
            $validator->isValid($this->jmbWithChecksum('321399000001')),
            'Checksum-valid JMB with impossible calendar digits must not be rejected for date/region.'
        );
    }

    public function test_laravel_rule_adapter_delegates_to_pure_validator(): void
    {
        $pass = Validator::make(['jmb' => '0000000000000'], ['jmb' => [new ValidJmb]]);
        $fail = Validator::make(['jmb' => '0000000000001'], ['jmb' => [new ValidJmb]]);

        $this->assertFalse($pass->fails());
        $this->assertTrue($fail->fails());
    }

    private function jmbWithChecksum(string $prefix12): string
    {
        $this->assertSame(12, strlen($prefix12));

        $weights = [7, 6, 5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $prefix12[$i] * $weights[$i];
        }
        $remainder = $sum % 11;
        $this->assertNotSame(1, $remainder);

        $check = $remainder === 0 ? 0 : 11 - $remainder;

        return $prefix12.$check;
    }
}
