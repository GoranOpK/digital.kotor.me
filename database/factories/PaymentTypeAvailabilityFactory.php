<?php

namespace Database\Factories;

use App\Models\PaymentType;
use App\Models\PaymentTypeAvailability;
use App\Support\UserType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentTypeAvailability>
 */
class PaymentTypeAvailabilityFactory extends Factory
{
    protected $model = PaymentTypeAvailability::class;

    public function definition(): array
    {
        return [
            'payment_type_id' => PaymentType::factory(),
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
