<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TS-010.3a/3b — Prijedlog izmjene objavljenog Događaja (+ Održavanja podaci).
 */
class CulturalEventChangeProposal extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_INOPERABLE = 'inoperable';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_REVIEW,
        self::STATUS_APPROVED,
        self::STATUS_INOPERABLE,
    ];

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Nacrt prijedloga',
        self::STATUS_PENDING_REVIEW => 'Na pregledu',
        self::STATUS_APPROVED => 'Odobren',
        self::STATUS_INOPERABLE => 'Neoperativan',
    ];

    /** Statusi koji drže BR-012 slot. */
    public const ACTIVE_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_REVIEW,
    ];

    public const INOPERABLE_REASON_EVENT_CANCELLED = 'event_cancelled';

    protected $fillable = [
        'event_entry_id',
        'organizer_id',
        'created_by',
        'last_modified_by',
        'status',
        'proposed_naslov',
        'proposed_opis',
        'proposed_category_id',
        'proposed_cover_media_id',
        'return_reason',
        'decision_user_id',
        'decision_at',
        'decision_note',
        'first_submitted_at',
        'last_submitted_at',
        'review_started_at',
        'review_started_by',
        'withdrawn_at',
        'inoperable_at',
        'inoperable_reason',
        'active_for_event_id',
    ];

    protected function casts(): array
    {
        return [
            'decision_at' => 'datetime',
            'first_submitted_at' => 'datetime',
            'last_submitted_at' => 'datetime',
            'review_started_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'inoperable_at' => 'datetime',
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPendingReview(): bool
    {
        return $this->status === self::STATUS_PENDING_REVIEW;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isInoperable(): bool
    {
        return $this->status === self::STATUS_INOPERABLE;
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function isUnderEditorialReview(): bool
    {
        return $this->isPendingReview() && $this->review_started_at !== null;
    }

    public function canBeWithdrawn(): bool
    {
        return $this->isPendingReview() && $this->review_started_at === null;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function eventEntry(): BelongsTo
    {
        return $this->belongsTo(CulturalEventEntry::class, 'event_entry_id');
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(CulturalOrganizer::class, 'organizer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastModifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    public function decisionUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_user_id');
    }

    public function reviewStartedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'review_started_by');
    }

    public function proposedCategory(): BelongsTo
    {
        return $this->belongsTo(CulturalCategory::class, 'proposed_category_id');
    }

    public function proposedCoverMedia(): BelongsTo
    {
        return $this->belongsTo(CulturalMedia::class, 'proposed_cover_media_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            CulturalTag::class,
            'cultural_event_change_proposal_tag',
            'proposal_id',
            'cultural_tag_id'
        )->withTimestamps();
    }

    public function occurrenceOps(): HasMany
    {
        return $this->hasMany(CulturalEventChangeProposalOccurrence::class, 'proposal_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING_REVIEW);
    }
}
