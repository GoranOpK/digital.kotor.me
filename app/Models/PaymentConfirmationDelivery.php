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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    public function isSent(): bool
    {
        return $this->status === PaymentConfirmationDeliveryStatus::Sent;
    }
}
