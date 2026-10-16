<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Canonical completeness assessment. Append-only: a later outcome is a new row,
 * so the original decision stays auditable. Does not touch Women/Youth Obrazac 3.
 */
class ApplicationCompletenessAssessment extends Model
{
    public const COMPLETE = 'complete';

    public const INCOMPLETE = 'incomplete';

    protected $fillable = [
        'application_id',
        'competition_id',
        'outcome',
        'catalog_key',
        'recorded_by_user_id',
        'notes',
        'evidence_snapshot',
        'evidence_fingerprint',
        'recorded_at',
        'notice_sent_at',
    ];

    protected $hidden = [
        'evidence_snapshot',
    ];

    protected $casts = [
        'evidence_snapshot' => 'array',
        'recorded_at' => 'datetime',
        'notice_sent_at' => 'datetime',
        'mail_sent_at' => 'datetime',
        'mail_failed_at' => 'datetime',
    ];

    public const IMMUTABLE_MESSAGE = 'Ocjena potpunosti je nepromjenjiva. Nova ocjena se dodaje kao novi zapis.';

    /** Email delivery bookkeeping; never part of the legal record. */
    public const DELIVERY_FIELDS = ['mail_sent_at', 'mail_failed_at', 'updated_at'];

    protected static function booted(): void
    {
        static::updating(function (self $assessment) {
            if (! $assessment->isPendingDeliveryUpdate()) {
                throw new \LogicException(self::IMMUTABLE_MESSAGE);
            }
        });
        static::deleting(fn () => throw new \LogicException(self::IMMUTABLE_MESSAGE));
    }

    /**
     * Only the notice of a recorded finding carries delivery state, and only until the mail succeeded.
     */
    private function isPendingDeliveryUpdate(): bool
    {
        $dirty = array_keys($this->getDirty());

        return $this->getOriginal('notice_sent_at') !== null
            && $this->getOriginal('mail_sent_at') === null
            && array_intersect($dirty, ['mail_sent_at', 'mail_failed_at']) !== []
            && array_diff($dirty, self::DELIVERY_FIELDS) === [];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
