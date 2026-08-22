<?php

namespace Database\Factories;

use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionEvent;
use App\Services\Payments\PaymentTransactionEventType;
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
            'event_type' => PaymentTransactionEventType::STARTED,
            'provider_event_id' => null,
            'payload' => ['provider' => 'fake'],
            'occurred_at' => now(),
            'received_at' => now(),
        ];
    }
}
