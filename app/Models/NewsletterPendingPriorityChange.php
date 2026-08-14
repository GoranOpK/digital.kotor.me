<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Promjena na čekanju (TS-011 §14). Capture only — not delivery evidence.
 */
class NewsletterPendingPriorityChange extends Model
{
    public const KIND_EVENT_CANCELLED = 'event_cancelled';

    public const KIND_OCCURRENCE_CANCELLED = 'occurrence_cancelled';

    public const KIND_POSTPONED = 'postponed';

    public const KIND_DATETIME_CHANGED = 'datetime_changed';

    public const KIND_LOCATION_CHANGED = 'location_changed';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_SUPERSEDED = 'superseded';

    protected $table = 'newsletter_pending_priority_changes';

    protected $fillable = [
        'cultural_event_entry_id',
        'cultural_occurrence_id',
        'change_kind',
        'change_control_key',
        'effective_state',
        'detected_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'effective_state' => 'array',
            'detected_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(CulturalEventEntry::class, 'cultural_event_entry_id');
    }

    public function occurrence(): BelongsTo
    {
        return $this->belongsTo(CulturalOccurrence::class, 'cultural_occurrence_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
