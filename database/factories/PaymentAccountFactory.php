<?php

namespace Database\Factories;

use App\Models\PaymentAccount;
use App\Models\PaymentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentAccount>
 */
class PaymentAccountFactory extends Factory
{
    protected $model = PaymentAccount::class;

    public function definition(): array
    {
        return [
            'payment_type_id' => PaymentType::factory(),
            'account_number' => 'SYN-'.fake()->unique()->numerify('####################'),
            'name' => 'Synthetic municipal account',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
