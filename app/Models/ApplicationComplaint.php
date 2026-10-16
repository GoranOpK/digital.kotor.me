<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Canonical complaint against an incomplete completeness notice.
 * Separate from Women/Youth ApplicationPrigovor (Obrazac 3).
 */
class ApplicationComplaint extends Model
{
    public const ACCEPTED = 'accepted';

    public const REJECTED = 'rejected';

    protected $fillable = [
        'application_id',
        'competition_id',
        'completeness_assessment_id',
        'submitted_by_user_id',
        'body',
        'submitted_at',
        'decision_due_at',
        'decision',
        'decided_at',
        'decided_by_user_id',
        'decision_notes',
        'decision_late',
        'compliance_warning',
    ];

    protected $hidden = [
        'body',
        'decision_notes',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'decision_due_at' => 'datetime',
        'decided_at' => 'datetime',
        'decision_late' => 'boolean',
        'mail_sent_at' => 'datetime',
        'mail_failed_at' => 'datetime',
    ];

    public const IMMUTABLE_MESSAGE = 'Prigovor i odluka o prigovoru su nepromjenjivi.';

    /** The only permitted write after submission: recording the single decision. */
    private const DECISION_FIELDS = ['decision', 'decided_at', 'decided_by_user_id', 'decision_notes', 'decision_late', 'compliance_warning', 'updated_at'];

    /** Decision-mail delivery bookkeeping; never part of the legal record. */
    public const DELIVERY_FIELDS = ['mail_sent_at', 'mail_failed_at', 'updated_at'];

    protected static function booted(): void
    {
        static::updating(function (self $complaint) {
            $dirty = array_keys($complaint->getDirty());
            $recordingDecision = $complaint->getOriginal('decision') === null
                && array_diff($dirty, self::DECISION_FIELDS) === [];
            $recordingDelivery = $complaint->getOriginal('decision') !== null
                && $complaint->getOriginal('mail_sent_at') === null
                && array_intersect($dirty, ['mail_sent_at', 'mail_failed_at']) !== []
                && array_diff($dirty, self::DELIVERY_FIELDS) === [];
            if (! $recordingDecision && ! $recordingDelivery) {
                throw new \LogicException(self::IMMUTABLE_MESSAGE);
            }
        });
        static::deleting(fn () => throw new \LogicException(self::IMMUTABLE_MESSAGE));
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ApplicationCompletenessAssessment::class, 'completeness_assessment_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }
}
