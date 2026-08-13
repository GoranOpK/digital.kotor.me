<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Zahtjev za dodjelu / uklanjanje Moderatora (TS-001).
 */
class CulturalModeratorRequest extends Model
{
    use HasFactory;

    public const TYPE_ADD = 'add';

    public const TYPE_REMOVE = 'remove';

    public const TYPES = [
        self::TYPE_ADD,
        self::TYPE_REMOVE,
    ];

    public const STATUS_AWAITING_MODERATOR_ELIGIBILITY = 'awaiting_moderator_eligibility';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_AWAITING_MODERATOR_ELIGIBILITY,
        self::STATUS_SUBMITTED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_AWAITING_MODERATOR_ELIGIBILITY => 'Čeka registraciju Moderatora',
        self::STATUS_SUBMITTED => 'Podnesen',
        self::STATUS_APPROVED => 'Odobren',
        self::STATUS_REJECTED => 'Odbijen',
    ];

    public const TYPE_LABELS = [
        self::TYPE_ADD => 'Dodjela',
        self::TYPE_REMOVE => 'Uklanjanje',
    ];

    protected $fillable = [
        'organizer_id',
        'submitter_user_id',
        'target_user_id',
        'proposed_moderator_name',
        'proposed_moderator_email',
        'type',
        'status',
        'decision_user_id',
        'decision_at',
        'decision_note',
        'editor_dismissed_at',
        'editor_dismissed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'decision_at' => 'datetime',
            'editor_dismissed_at' => 'datetime',
        ];
    }

    public function isAwaitingModeratorEligibility(): bool
    {
        return $this->status === self::STATUS_AWAITING_MODERATOR_ELIGIBILITY;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isDismissedByEditor(): bool
    {
        return $this->editor_dismissed_at !== null;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(CulturalOrganizer::class, 'organizer_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitter_user_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function decisionUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_user_id');
    }

    public function editorDismissedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_dismissed_by_user_id');
    }

    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeVisibleInEditorWorkspace(Builder $query): Builder
    {
        return $query->whereNull('editor_dismissed_at');
    }
}
