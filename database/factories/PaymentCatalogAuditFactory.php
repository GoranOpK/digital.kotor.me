<?php

namespace Database\Factories;

use App\Models\PaymentCatalogAudit;
use App\Models\User;
use App\Services\Payments\PaymentCatalogAuditAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentCatalogAudit>
 */
class PaymentCatalogAuditFactory extends Factory
{
    protected $model = PaymentCatalogAudit::class;

    public function definition(): array
    {
        return [
            'actor_user_id' => User::factory(),
            'action' => PaymentCatalogAuditAction::TYPE_CREATED,
            'entity_type' => PaymentCatalogAudit::ENTITY_PAYMENT_TYPE,
            'entity_id' => 1,
            'changes' => ['name' => ['to' => 'synthetic']],
            'created_at' => now(),
        ];
    }
}
