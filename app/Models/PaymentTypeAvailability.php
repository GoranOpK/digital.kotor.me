<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTypeAvailability extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentTypeAvailabilityFactory> */
    use HasFactory;

    protected $fillable = [
        'payment_type_id',
        'user_type',
        'residential_status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    protected static function booted(): void
    {
        static::updating(function (self $rule): void {
            foreach (['payment_type_id', 'user_type', 'residential_status'] as $field) {
                if ($rule->isDirty($field)) {
                    $rule->{$field} = $rule->getOriginal($field);
                }
            }
        });
    }
}
