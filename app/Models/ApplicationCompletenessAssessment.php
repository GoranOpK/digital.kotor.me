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

    /** Email delivery bookkeeping; never part of the legal finding. */
    public const DELIVERY_FIELDS = ['notice_sent_at', 'mail_sent_at', 'mail_failed_at', 'updated_at'];

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
     * Delivery bookkeeping until the mail succeeds.
     * notice_sent_at is written only together with a successful send, and only once.
     */
    private function isPendingDeliveryUpdate(): bool
    {
        $dirty = array_keys($this->getDirty());
        if ($dirty === [] || array_diff($dirty, self::DELIVERY_FIELDS) !== []) {
            return false;
        }

        if ($this->getOriginal('mail_sent_at') !== null) {
            return false;
        }

        $settingNotice = in_array('notice_sent_at', $dirty, true);
        $settingMailSent = in_array('mail_sent_at', $dirty, true);
        if ($settingNotice || $settingMailSent) {
            if (! $settingMailSent || $this->mail_sent_at === null) {
                return false;
            }

            if ($this->getOriginal('notice_sent_at') === null) {
                if (! $settingNotice || $this->notice_sent_at === null) {
                    return false;
                }
            } elseif ($settingNotice) {
                return false;
            }

            return true;
        }

        return in_array('mail_failed_at', $dirty, true)
            && $this->mail_failed_at !== null;
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
