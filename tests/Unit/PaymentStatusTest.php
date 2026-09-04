<?php

namespace Tests\Unit;

use App\Enums\PaymentStatus;
use Tests\TestCase;

class PaymentStatusTest extends TestCase
{
    public function test_exactly_four_canonical_statuses_exist(): void
    {
        $this->assertCount(4, PaymentStatus::cases());
        $this->assertSame(PaymentStatus::cases(), PaymentStatus::casesInBusinessOrder());
    }

    public function test_storage_values_are_stable_technical_keys(): void
    {
        $this->assertSame('processing', PaymentStatus::Processing->value);
        $this->assertSame('successful', PaymentStatus::Successful->value);
        $this->assertSame('failed', PaymentStatus::Failed->value);
        $this->assertSame('cancelled', PaymentStatus::Cancelled->value);
    }

    public function test_labels_match_closed_business_model(): void
    {
        $this->assertSame('U obradi', PaymentStatus::Processing->label());
        $this->assertSame('Uspješna', PaymentStatus::Successful->label());
        $this->assertSame('Neuspješna', PaymentStatus::Failed->label());
        $this->assertSame('Otkazana', PaymentStatus::Cancelled->label());
    }

    public function test_only_processing_is_non_terminal(): void
    {
        $this->assertFalse(PaymentStatus::Processing->isTerminal());
        $this->assertTrue(PaymentStatus::Successful->isTerminal());
        $this->assertTrue(PaymentStatus::Failed->isTerminal());
        $this->assertTrue(PaymentStatus::Cancelled->isTerminal());
    }

    public function test_does_not_include_superseded_ep_statuses(): void
    {
        $values = array_map(fn (PaymentStatus $status) => $status->value, PaymentStatus::cases());
        $names = array_map(fn (PaymentStatus $status) => $status->name, PaymentStatus::cases());

        $this->assertNotContains('created', $values);
        $this->assertNotContains('pending', $values);
        $this->assertNotContains('error', $values);
        $this->assertNotContains('Created', $names);
        $this->assertNotContains('Pending', $names);
        $this->assertNotContains('Error', $names);
    }
}
