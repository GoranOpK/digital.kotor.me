<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\PaymentInitiation;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        $initiation = PaymentInitiation::factory()->create();

        return [
            'uuid' => (string) Str::uuid(),
            'payment_initiation_id' => $initiation->id,
            'user_id' => $initiation->user_id,
            'payment_type_id' => $initiation->payment_type_id,
            'payment_account_id' => $initiation->payment_account_id,
            'status' => PaymentStatus::Processing,
            'amount' => $initiation->amount,
            'currency' => $initiation->currency,
            'merchant_transaction_id' => null,
            'gateway_reference' => null,
            'provider' => null,
            'snapshot' => [
                'amount' => (string) $initiation->amount,
                'currency' => 'EUR',
                'payment_type_name' => $initiation->paymentType->name,
                'account_number' => $initiation->paymentAccount->account_number,
            ],
        ];
    }
}
