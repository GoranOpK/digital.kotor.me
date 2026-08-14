<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Successful Newsletter delivery evidence (TS-011 §13).
 * A row exists only after a successful e-mail send.
 */
class NewsletterDeliveryLedger extends Model
{
    public const TYPE_FIRST_INCLUDE = 'first_include';

    public const TYPE_PRIORITY_CHANGE = 'priority_change';

    protected $table = 'newsletter_delivery_ledger';

    protected $fillable = [
        'newsletter_subscription_id',
        'cultural_event_entry_id',
        'cultural_occurrence_id',
        'entry_type',
        'change_control_key',
        'delivery_cycle_id',
        'payload_snapshot',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_snapshot' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(NewsletterSubscription::class, 'newsletter_subscription_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(CulturalEventEntry::class, 'cultural_event_entry_id');
    }

    public function occurrence(): BelongsTo
    {
        return $this->belongsTo(CulturalOccurrence::class, 'cultural_occurrence_id');
    }

    public function isFirstInclude(): bool
    {
        return $this->entry_type === self::TYPE_FIRST_INCLUDE;
    }
}
