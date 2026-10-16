<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * KN-SYNC-008 — one Commission seat's vote on a canonical tie resolution. Immutable.
 */
class CanonicalTieVote extends Model
{
    protected $fillable = [
        'tie_resolution_id',
        'commission_member_id',
        'user_id',
        'canonical_seat_no',
        'in_favour',
        'voted_at',
    ];

    protected $casts = [
        'in_favour' => 'boolean',
        'voted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException(CanonicalTieResolution::IMMUTABLE_MESSAGE));
        static::deleting(fn () => throw new LogicException(CanonicalTieResolution::IMMUTABLE_MESSAGE));
    }
}
