<?php

namespace Tests\Unit;

use App\Enums\PaymentAvailabilityOutcome;
use Tests\TestCase;

class PaymentAvailabilityOutcomeTest extends TestCase
{
    public function test_engine_outcomes_are_not_transaction_statuses(): void
    {
        $this->assertSame(
            ['available', 'not_available', 'residential_declaration_required'],
            array_map(fn (PaymentAvailabilityOutcome $case) => $case->value, PaymentAvailabilityOutcome::cases())
        );
        $this->assertTrue(PaymentAvailabilityOutcome::Available->isUsable());
        $this->assertFalse(PaymentAvailabilityOutcome::NotAvailable->isUsable());
        $this->assertFalse(PaymentAvailabilityOutcome::ResidentialDeclarationRequired->isUsable());
    }
}
