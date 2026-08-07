<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kanonsko Održavanje (TS-004 Korak 1 / PO-EV-01).
 * Status nezavisan od statusa Događaja (BR-134).
 */
class CulturalOccurrence extends Model
{
    use HasFactory;

    public const STATUS_PLANNED = 'planned';

    public const STATUS_POSTPONED = 'postponed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FINISHED = 'finished';

    public const STATUSES = [
        self::STATUS_PLANNED,
        self::STATUS_POSTPONED,
        self::STATUS_CANCELLED,
        self::STATUS_FINISHED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PLANNED => 'Planiran',
        self::STATUS_POSTPONED => 'Odgođen',
        self::STATUS_CANCELLED => 'Otkazan',
        self::STATUS_FINISHED => 'Završen',
    ];

    /**
     * Dozvoljeni prelazi (TS-004 §4).
     * Završen iz Planiran — Sistem; ručno Završen nije V1.
     *
     * @var array<string, list<string>>
     */
    public const ALLOWED_TRANSITIONS = [
        self::STATUS_PLANNED => [
            self::STATUS_POSTPONED,
            self::STATUS_CANCELLED,
            self::STATUS_FINISHED,
        ],
        self::STATUS_POSTPONED => [
            self::STATUS_PLANNED, // zahtijeva novi termin (servis)
            self::STATUS_CANCELLED,
        ],
        self::STATUS_CANCELLED => [],
        self::STATUS_FINISHED => [],
    ];

    protected $fillable = [
        'event_entry_id',
        'datum',
        'vrijeme_od',
        'vrijeme_do',
        'cjelodnevno',
        'status',
        'location_id',
        'location_manual_name',
    ];

    protected $casts = [
        'datum' => 'date',
        'cjelodnevno' => 'boolean',
        'event_entry_id' => 'integer',
        'location_id' => 'integer',
    ];

    public function isPlanned(): bool
    {
        return $this->status === self::STATUS_PLANNED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isFinished(): bool
    {
        return $this->status === self::STATUS_FINISHED;
    }

    public function isPostponed(): bool
    {
        return $this->status === self::STATUS_POSTPONED;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function canTransitionTo(string $target): bool
    {
        $allowed = self::ALLOWED_TRANSITIONS[$this->status] ?? [];

        return in_array($target, $allowed, true);
    }

    public function eventEntry(): BelongsTo
    {
        return $this->belongsTo(CulturalEventEntry::class, 'event_entry_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(CulturalLocation::class, 'location_id');
    }

    public function hasCatalogLocation(): bool
    {
        return $this->location_id !== null;
    }

    public function hasManualLocation(): bool
    {
        return $this->location_manual_name !== null
            && trim((string) $this->location_manual_name) !== '';
    }

    public function hasNoLocation(): bool
    {
        return ! $this->hasCatalogLocation() && ! $this->hasManualLocation();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_PLANNED,
            self::STATUS_POSTPONED,
        ]);
    }
}
