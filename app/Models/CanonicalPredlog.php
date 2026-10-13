<?php

namespace App\Models;

use App\Services\Competitions\Outcome\CanonicalPredlogService;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * KN-SYNC-008 — immutable, private Predlog dataset for the Secretariat handoff.
 * Not public, not a formal Odluka, not a contract or payment instruction.
 *
 * KN-SYNC-009 — the only permitted write is the one-time finalization stamp.
 */
class CanonicalPredlog extends Model
{
    public const IMMUTABLE_MESSAGE = 'Predlog je pripremljen i ne može se mijenjati.';

    private const FINALIZATION_FIELDS = ['finalized_at', 'finalized_by_user_id', 'updated_at'];

    protected $fillable = [
        'competition_id',
        'final_ranking_id',
        'payload',
        'fingerprint',
        'prepared_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'prepared_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (CanonicalPredlog $predlog) {
            $dirty = array_keys($predlog->getDirty());
            $finalizing = $predlog->getOriginal('finalized_at') === null
                && $predlog->finalized_at !== null
                && $predlog->finalized_by_user_id !== null
                && array_diff($dirty, self::FINALIZATION_FIELDS) === [];

            if (! $finalizing) {
                throw new LogicException(self::IMMUTABLE_MESSAGE);
            }
        });
        static::deleting(fn () => throw new LogicException(self::IMMUTABLE_MESSAGE));
    }

    public function isFinalized(): bool
    {
        return $this->finalized_at !== null;
    }

    public function integrityIntact(): bool
    {
        return is_array($this->payload)
            && hash_equals((string) $this->fingerprint, CanonicalPredlogService::fingerprint($this->payload));
    }
}
