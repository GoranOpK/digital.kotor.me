<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * KN-SYNC-008 — one Commission seat's signature of the canonical final ranking. Immutable.
 */
class CanonicalFinalRankingSignature extends Model
{
    protected $fillable = [
        'final_ranking_id',
        'commission_member_id',
        'user_id',
        'canonical_seat_no',
        'canonical_seat',
        'signer_name',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException(CanonicalFinalRanking::IMMUTABLE_MESSAGE));
        static::deleting(fn () => throw new LogicException(CanonicalFinalRanking::IMMUTABLE_MESSAGE));
    }
}
