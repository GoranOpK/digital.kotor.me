<?php

namespace Database\Factories;

use App\Models\PaymentAccount;
use App\Models\PaymentAccountAvailability;
use App\Support\UserType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentAccountAvailability>
 */
class PaymentAccountAvailabilityFactory extends Factory
{
    protected $model = PaymentAccountAvailability::class;

    public function definition(): array
    {
        return [
            'payment_account_id' => PaymentAccount::factory(),
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'residential_status' => null,
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
