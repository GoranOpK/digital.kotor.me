<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Moderatorsko ovlašćenje User ↔ Organizator (TS-001 / PO-ORG-02 / PO-ORG-04).
 */
class CulturalModeratorAuthorization extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REMOVED = 'removed';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_REMOVED,
    ];

    public const SOURCE_INITIAL = 'initial';

    public const SOURCE_SUBSEQUENT = 'subsequent';

    public const SOURCES = [
        self::SOURCE_INITIAL,
        self::SOURCE_SUBSEQUENT,
    ];

    protected $fillable = [
        'user_id',
        'organizer_id',
        'status',
        'source',
        'activated_at',
        'removed_at',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(CulturalOrganizer::class, 'organizer_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
