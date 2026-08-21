<?php

namespace Database\Factories;

use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentTransactionEvent>
 */
class PaymentTransactionEventFactory extends Factory
{
    protected $model = PaymentTransactionEvent::class;

    public function definition(): array
    {
        return [
            'payment_transaction_id' => PaymentTransaction::factory(),
            'event_type' => 'synthetic_recorded',
            'provider_event_id' => null,
            'payload' => ['source' => 'factory'],
            'occurred_at' => now(),
            'received_at' => now(),
        ];
    }
}
