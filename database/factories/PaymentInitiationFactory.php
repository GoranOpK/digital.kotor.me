<?php

namespace Database\Factories;

use App\Models\PaymentAccount;
use App\Models\PaymentInitiation;
use App\Models\PaymentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentInitiation>
 */
class PaymentInitiationFactory extends Factory
{
    protected $model = PaymentInitiation::class;

    public function definition(): array
    {
        $type = PaymentType::factory()->create();
        $account = PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
        ]);

        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'payment_type_id' => $type->id,
            'payment_account_id' => $account->id,
            'amount' => '12.50',
            'currency' => 'EUR',
        ];
    }
}
