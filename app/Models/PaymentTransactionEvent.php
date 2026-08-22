<?php

namespace App\Models;

use App\Services\Payments\PaymentTransactionEventPayload;
use App\Services\Payments\PaymentTransactionEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Append-only gateway/status event foundation.
 * No callback processing in Phase 1.
 */
class PaymentTransactionEvent extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentTransactionEventFactory> */
    use HasFactory;

    protected $fillable = [
        'payment_transaction_id',
        'event_type',
        'provider_event_id',
        'payload',
        'occurred_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            $type = (string) $event->event_type;
            if (! PaymentTransactionEventType::isKnown($type)) {
                throw new LogicException('Unknown payment transaction event type.');
            }

            $event->payload = PaymentTransactionEventPayload::sanitize(
                is_array($event->payload) ? $event->payload : []
            );

            if ($event->occurred_at === null) {
                $event->occurred_at = now();
            }

            if ($event->received_at === null) {
                $event->received_at = now();
            }
        });

        static::updating(function (): void {
            throw new LogicException('Payment transaction events are append-only.');
        });

        static::deleting(function (): void {
            throw new LogicException('Payment transaction events are append-only.');
        });
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }
}
