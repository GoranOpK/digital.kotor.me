<?php

namespace Tests\Unit\Identity;

use App\Identity\Validation\CrpsIdentifierValidator;
use App\Rules\ValidCrps;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CrpsIdentifierValidatorTest extends TestCase
{
    public function test_valid_marks_and_serials_are_accepted(): void
    {
        $validator = new CrpsIdentifierValidator;

        $this->assertTrue($validator->isValid('10000001'));
        $this->assertTrue($validator->isValid('19999999'));
        $this->assertTrue($validator->isValid('20000001'));
        $this->assertTrue($validator->isValid('50000001'));
        $this->assertTrue($validator->isValid('60000001'));
        $this->assertTrue($validator->isValid('50764790'));
    }

    public function test_zero_serial_and_invalid_mark_are_rejected(): void
    {
        $validator = new CrpsIdentifierValidator;

        $this->assertFalse($validator->isValid('10000000'));
        $this->assertFalse($validator->isValid('70000001'));
        $this->assertFalse($validator->isValid('00000001'));
        $this->assertFalse($validator->isValid('1234567'));
    }

    public function test_expected_mark_mismatch_is_rejected(): void
    {
        $validator = new CrpsIdentifierValidator;

        $this->assertTrue($validator->isValid('10000001', CrpsIdentifierValidator::MARK_ENTREPRENEUR));
        $this->assertFalse($validator->isValid('20000001', CrpsIdentifierValidator::MARK_ENTREPRENEUR));
        $this->assertTrue($validator->isValid('60000001', CrpsIdentifierValidator::MARK_FOREIGN_BRANCH));
    }

    public function test_laravel_rule_adapter_delegates_to_pure_validator(): void
    {
        $pass = Validator::make(['crps' => '10000001'], ['crps' => [new ValidCrps(1)]]);
        $fail = Validator::make(['crps' => '20000001'], ['crps' => [new ValidCrps(1)]]);

        $this->assertFalse($pass->fails());
        $this->assertTrue($fail->fails());
    }
}
