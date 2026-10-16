<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * KN-SYNC-009 — explicit, append-only handoff of the finalized Predlog to the responsible
 * Secretariat. Not a publication, not an Odluka, not a contract or payment.
 */
class CanonicalSecretariatHandoff extends Model
{
    public const IMMUTABLE_MESSAGE = 'Predaja Predloga Sekretarijatu je evidentirana i ne može se mijenjati.';

    protected $fillable = [
        'competition_id',
        'predlog_id',
        'recipient',
        'reference',
        'note',
        'handed_off_by_user_id',
        'handed_off_at',
    ];

    protected $casts = [
        'handed_off_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException(self::IMMUTABLE_MESSAGE));
        static::deleting(fn () => throw new LogicException(self::IMMUTABLE_MESSAGE));
    }

    public function predlog()
    {
        return $this->belongsTo(CanonicalPredlog::class, 'predlog_id');
    }
}
