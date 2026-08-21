<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

/**
 * Business e-Payment transaction.
 * Created in a later phase only after a gateway attempt is accepted/started.
 */
class PaymentTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentTransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'payment_initiation_id',
        'user_id',
        'payment_type_id',
        'payment_account_id',
        'status',
        'amount',
        'currency',
        'merchant_transaction_id',
        'gateway_reference',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'snapshot' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $transaction): void {
            if ($transaction->uuid === null || $transaction->uuid === '') {
                $transaction->uuid = (string) Str::uuid();
            }

            if ($transaction->currency === null || $transaction->currency === '') {
                $transaction->currency = 'EUR';
            }
        });

        static::updating(function (self $transaction): void {
            foreach (['amount', 'currency', 'merchant_transaction_id', 'payment_initiation_id', 'user_id'] as $field) {
                if ($transaction->isDirty($field)) {
                    $transaction->{$field} = $transaction->getOriginal($field);
                }
            }

            if ($transaction->isDirty('status')) {
                $original = $transaction->getOriginal('status');
                $from = $original instanceof PaymentStatus
                    ? $original
                    : PaymentStatus::from((string) $original);

                if ($from->isTerminal()) {
                    $transaction->status = $from;
                }
            }
        });

        static::deleting(function (): void {
            throw new LogicException('Payment transactions must not be deleted.');
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function initiation(): BelongsTo
    {
        return $this->belongsTo(PaymentInitiation::class, 'payment_initiation_id');
    }

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PaymentTransactionEvent::class);
    }
}
