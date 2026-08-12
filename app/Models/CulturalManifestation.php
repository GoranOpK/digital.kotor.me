<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CulturalManifestation extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_RETURNED_FOR_REVISION = 'returned_for_revision';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_APPROVAL,
        self::STATUS_RETURNED_FOR_REVISION,
        self::STATUS_PUBLISHED,
        self::STATUS_CANCELLED,
        self::STATUS_ARCHIVED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Nacrt',
        self::STATUS_PENDING_APPROVAL => 'Na odobrenju',
        self::STATUS_RETURNED_FOR_REVISION => 'Vraćena na doradu',
        self::STATUS_PUBLISHED => 'Objavljena',
        self::STATUS_CANCELLED => 'Otkazana',
        self::STATUS_ARCHIVED => 'Arhivirana',
    ];

    protected $fillable = [
        'naziv',
        'opis',
        'status',
        'organizer_id',
        'cover_media_id',
        'web_stranica',
        'created_by',
        'last_modified_by',
        'first_submitted_at',
        'published_at',
        'cancelled_at',
        'archived_at',
    ];

    protected $casts = [
        'organizer_id' => 'integer',
        'cover_media_id' => 'integer',
        'created_by' => 'integer',
        'last_modified_by' => 'integer',
        'first_submitted_at' => 'datetime',
        'published_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function isReturnedForRevision(): bool
    {
        return $this->status === self::STATUS_RETURNED_FOR_REVISION;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * PO-MF-WF-03 — porijeklo toka = akter kreiranja (`created_by` → uloga), ne `organizer_id`.
     */
    public function isEditorCreated(): bool
    {
        $this->loadMissing('creator.role');

        return $this->creator
            && $this->creator->role
            && $this->creator->role->name === 'kk_admin';
    }

    public function isModeratorCreated(): bool
    {
        return ! $this->isEditorCreated();
    }

    /**
     * PO-MF-WF-01 / PO-MF-WF-02 — draft→published samo Urednik-kreirana; draft→pending samo Moderator-kreirana.
     */
    public function canTransitionTo(string $target): bool
    {
        if ($this->status === self::STATUS_DRAFT) {
            if ($target === self::STATUS_PUBLISHED) {
                return $this->isEditorCreated();
            }
            if ($target === self::STATUS_PENDING_APPROVAL) {
                return $this->isModeratorCreated();
            }

            return false;
        }

        $allowed = [
            self::STATUS_PENDING_APPROVAL => [
                self::STATUS_PUBLISHED,
                self::STATUS_RETURNED_FOR_REVISION,
            ],
            self::STATUS_RETURNED_FOR_REVISION => [
                self::STATUS_PENDING_APPROVAL,
            ],
            self::STATUS_PUBLISHED => [
                self::STATUS_CANCELLED,
                self::STATUS_ARCHIVED,
            ],
            self::STATUS_CANCELLED => [
                self::STATUS_ARCHIVED,
            ],
            self::STATUS_ARCHIVED => [],
        ][$this->status] ?? [];

        if ($this->status === self::STATUS_PENDING_APPROVAL
            && $target === self::STATUS_RETURNED_FOR_REVISION
            && $this->isEditorCreated()) {
            return false;
        }

        return in_array($target, $allowed, true);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CulturalEventEntry::class, 'manifestation_id');
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(CulturalOrganizer::class, 'organizer_id');
    }

    public function coverMedia(): BelongsTo
    {
        return $this->belongsTo(CulturalMedia::class, 'cover_media_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastModifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    /**
     * Javni cover URL — cover media ili generički placeholder.
     * Ne fallback-uje na medije povezanih Događaja (BM-MF-17).
     */
    public function imageUrl(): string
    {
        if ($this->coverMedia && filled($this->coverMedia->storage_path)) {
            return $this->coverMedia->publicUrl();
        }

        return asset(CulturalEvent::FALLBACK_DEFAULT_IMAGE);
    }
}

