<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAccountAvailability extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentAccountAvailabilityFactory> */
    use HasFactory;

    protected $fillable = [
        'payment_account_id',
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

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class);
    }

    protected static function booted(): void
    {
        static::updating(function (self $rule): void {
            foreach (['payment_account_id', 'user_type', 'residential_status'] as $field) {
                if ($rule->isDirty($field)) {
                    $rule->{$field} = $rule->getOriginal($field);
                }
            }
        });
    }
}
