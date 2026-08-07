<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kataloški Medij — Fotografija (TS-008 Korak 1).
 * Bez veza ka Događaju / Manifestaciji / Kategoriji u ovom koraku.
 */
class CulturalMedia extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    public const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'Aktivan',
        self::STATUS_INACTIVE => 'Neaktivan',
    ];

    /** Zatvoreni katalog namjena (TS8-02). */
    public const PURPOSE_EVENT_COVER = 'event_cover';

    public const PURPOSE_MANIFESTATION_COVER = 'manifestation_cover';

    public const PURPOSE_CATEGORY_DEFAULT = 'category_default';

    public const PURPOSES = [
        self::PURPOSE_EVENT_COVER,
        self::PURPOSE_MANIFESTATION_COVER,
        self::PURPOSE_CATEGORY_DEFAULT,
    ];

    public const PURPOSE_LABELS = [
        self::PURPOSE_EVENT_COVER => 'Naslovna fotografija događaja',
        self::PURPOSE_MANIFESTATION_COVER => 'Naslovna fotografija manifestacije',
        self::PURPOSE_CATEGORY_DEFAULT => 'Podrazumijevana fotografija kategorije',
    ];

    protected $table = 'cultural_media';

    protected $fillable = [
        'naziv',
        'namjena',
        'status',
        'alt_tekst',
        'opis',
        'autor',
        'izvor',
        'licenca',
        'tagovi',
        'originalni_naziv',
        'interni_naziv',
        'mime',
        'format',
        'sirina',
        'visina',
        'velicina',
        'storage_path',
        'creator_id',
    ];

    protected $casts = [
        'tagovi' => 'array',
        'sirina' => 'integer',
        'visina' => 'integer',
        'velicina' => 'integer',
        'creator_id' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function purposeLabel(): string
    {
        return self::PURPOSE_LABELS[$this->namjena] ?? $this->namjena;
    }

    public function publicUrl(): string
    {
        return asset('storage/'.$this->storage_path);
    }

    /**
     * Hook za buduće poslovne veze (Događaj / Manifestacija / Kategorija).
     * Korak 1: uvijek false — nema FK/pivot tabela.
     */
    public function hasBusinessLinks(): bool
    {
        return $this->businessLinkCount() > 0;
    }

    /**
     * Broj poslovnih veza. Proširiti kada se uvedu relacije — bez prepisivanja delete toka.
     */
    public function businessLinkCount(): int
    {
        return 0;
    }

    public function canBePermanentlyDeleted(): bool
    {
        return ! $this->hasBusinessLinks();
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
