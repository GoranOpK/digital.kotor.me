<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Zahtjev za kreiranje Organizatora (TS-001 / PO-ORG-03).
 */
class CulturalOrganizerCreationRequest extends Model
{
    use HasFactory;

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

    protected $fillable = [
        'submitter_user_id',
        'proposed_moderator_user_id',
        'proposed_moderator_name',
        'proposed_moderator_email',
        'proposed_moderator_is_submitter',
        'proposed_naziv',
        'proposed_opis',
        'proposed_contact_email',
        'proposed_contact_phone',
        'proposed_website',
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
            'proposed_moderator_is_submitter' => 'boolean',
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

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitter_user_id');
    }

    public function proposedModerator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_moderator_user_id');
    }

    public function decisionUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_user_id');
    }

    public function editorDismissedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_dismissed_by_user_id');
    }

    public function organizer(): HasOne
    {
        return $this->hasOne(CulturalOrganizer::class, 'approved_creation_request_id');
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
