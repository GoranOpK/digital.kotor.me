<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Organizator — poslovni entitet (TS-001 / PO-ORG-01).
 */
class CulturalOrganizer extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DEACTIVATED = 'deactivated';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_DEACTIVATED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'Aktivan',
        self::STATUS_DEACTIVATED => 'Deaktiviran',
    ];

    protected $fillable = [
        'naziv',
        'opis',
        'contact_email',
        'contact_phone',
        'website',
        'status',
        'approved_creation_request_id',
    ];

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isDeactivated(): bool
    {
        return $this->status === self::STATUS_DEACTIVATED;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function approvedCreationRequest(): BelongsTo
    {
        return $this->belongsTo(CulturalOrganizerCreationRequest::class, 'approved_creation_request_id');
    }

    public function authorizations(): HasMany
    {
        return $this->hasMany(CulturalModeratorAuthorization::class, 'organizer_id');
    }

    public function activeAuthorizations(): HasMany
    {
        return $this->authorizations()->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE);
    }

    public function moderatorRequests(): HasMany
    {
        return $this->hasMany(CulturalModeratorRequest::class, 'organizer_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOrderedByName(Builder $query): Builder
    {
        return $query->orderBy('naziv')->orderBy('id');
    }
}
