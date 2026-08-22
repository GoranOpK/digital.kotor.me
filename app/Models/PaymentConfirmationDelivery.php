<?php

namespace App\Models;

use App\Enums\PaymentConfirmationDeliveryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentConfirmationDelivery extends Model
{
    public const CHANNEL_EMAIL = 'email';

    protected $fillable = [
        'payment_transaction_id',
        'channel',
        'status',
        'recipient_email',
        'error_class',
        'sent_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentConfirmationDeliveryStatus::class,
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $delivery): void {
            foreach (['payment_transaction_id', 'channel', 'recipient_email'] as $field) {
                if ($delivery->isDirty($field)) {
                    $delivery->{$field} = $delivery->getOriginal($field);
                }
            }
        });

        static::deleting(function (): void {
            throw new \LogicException('Payment confirmation deliveries must not be deleted.');
        });
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    public function isSent(): bool
    {
        return $this->status === PaymentConfirmationDeliveryStatus::Sent;
    }
}
