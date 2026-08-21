<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * User confirmation / future idempotency boundary.
 * Not a business PaymentTransaction and not a fifth payment status.
 */
class PaymentInitiation extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentInitiationFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'payment_type_id',
        'payment_account_id',
        'amount',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $initiation): void {
            if ($initiation->uuid === null || $initiation->uuid === '') {
                $initiation->uuid = (string) Str::uuid();
            }

            if ($initiation->currency === null || $initiation->currency === '') {
                $initiation->currency = 'EUR';
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class);
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(PaymentTransaction::class);
    }
}
