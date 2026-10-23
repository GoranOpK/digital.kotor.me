<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * KN-SYNC-008 — atomically confirmed canonical final ranking (outcome set + amounts).
 * Final only after 3/3 Commission signatures (finalized_at). Only finalized_at may be set later.
 */
class CanonicalFinalRanking extends Model
{
    public const IMMUTABLE_MESSAGE = 'Konačna rang lista je potvrđena i ne može se mijenjati.';

    protected $fillable = [
        'competition_id',
        'commission_id',
        'final_decision_session_id',
        'confirmed_by_member_id',
        'budget',
        'total_approved',
        'confirmed_at',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'total_approved' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $ranking) {
            $onlyFinalizing = array_diff(array_keys($ranking->getDirty()), ['finalized_at', 'updated_at']) === [];
            if (! $onlyFinalizing || $ranking->getOriginal('finalized_at') !== null) {
                throw new LogicException(self::IMMUTABLE_MESSAGE);
            }
        });

        static::deleting(function () {
            throw new LogicException(self::IMMUTABLE_MESSAGE);
        });
    }

    public function isFinal(): bool
    {
        return $this->finalized_at !== null;
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(CanonicalFinalRankingSignature::class, 'final_ranking_id');
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }
}
