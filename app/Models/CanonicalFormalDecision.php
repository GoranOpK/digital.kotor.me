<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * KN-SYNC-009 — record of the formal Odluka adopted outside the platform. The platform records,
 * it does not decide. Immutable except the one-time signed copy attachment and the one-time
 * publication stamp; no replacement, correction or republication.
 */
class CanonicalFormalDecision extends Model
{
    public const SLOT = 'formal_decision';

    public const IMMUTABLE_MESSAGE = 'Formalna Odluka je evidentirana i ne može se mijenjati ni zamijeniti.';

    private const ATTACH_FIELDS = ['official_decision_copy_id', 'attached_by_user_id', 'attached_at', 'updated_at'];

    private const PUBLISH_FIELDS = ['published_notice_id', 'published_by_user_id', 'published_at', 'updated_at'];

    protected $fillable = [
        'competition_id',
        'publication_slot',
        'predlog_id',
        'handoff_id',
        'decision_number',
        'decision_date',
        'issuer',
        'notes',
        'recorded_by_user_id',
        'recorded_at',
    ];

    protected $casts = [
        'decision_date' => 'date',
        'recorded_at' => 'datetime',
        'attached_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (CanonicalFormalDecision $decision) {
            $dirty = array_keys($decision->getDirty());

            $attaching = $decision->getOriginal('official_decision_copy_id') === null
                && $decision->getOriginal('published_at') === null
                && $decision->official_decision_copy_id !== null
                && $decision->attached_at !== null
                && $decision->attached_by_user_id !== null
                && array_diff($dirty, self::ATTACH_FIELDS) === [];

            $publishing = $decision->getOriginal('published_at') === null
                && $decision->getOriginal('official_decision_copy_id') !== null
                && $decision->published_at !== null
                && $decision->published_notice_id !== null
                && $decision->published_by_user_id !== null
                && array_diff($dirty, self::PUBLISH_FIELDS) === [];

            if (! $attaching && ! $publishing) {
                throw new LogicException(self::IMMUTABLE_MESSAGE);
            }
        });
        static::deleting(fn () => throw new LogicException(self::IMMUTABLE_MESSAGE));
    }

    public function predlog()
    {
        return $this->belongsTo(CanonicalPredlog::class, 'predlog_id');
    }

    public function handoff()
    {
        return $this->belongsTo(CanonicalSecretariatHandoff::class, 'handoff_id');
    }

    public function officialDecisionCopy()
    {
        return $this->belongsTo(CompetitionOfficialDecisionCopy::class, 'official_decision_copy_id');
    }

    public function publishedNotice()
    {
        return $this->belongsTo(Notice::class, 'published_notice_id');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }
}
