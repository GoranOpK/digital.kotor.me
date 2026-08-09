<?php

namespace App\Models;

use App\Services\CulturalCalendar\CulturalPublicCardOccurrenceCriteria;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kanonski Događaj (TS-003 Korak 1 / PO-EV-01).
 * Paralelno sa legacy CulturalEvent — bez cutover-a u ovom koraku.
 */
class CulturalEventEntry extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_ARCHIVED = 'archived';

    /** Maksimum istaknutih objavljenih/aktuelnih događaja (BM-PK-15 / BR-117). */
    public const MAX_FEATURED = 3;

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_APPROVAL,
        self::STATUS_PUBLISHED,
        self::STATUS_CANCELLED,
        self::STATUS_ARCHIVED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Nacrt',
        self::STATUS_PENDING_APPROVAL => 'Na odobrenju',
        self::STATUS_PUBLISHED => 'Objavljen',
        self::STATUS_CANCELLED => 'Otkazan',
        self::STATUS_ARCHIVED => 'Arhiviran',
    ];

    /**
     * Javno dostupni statusi na portalu (TS-009 §12 / CR-004B).
     * Portalna Arhiva ≠ interni status archived — archived nije u ovom skupu.
     * Samo statusna vidljivost; vremenska aktuelnost je odvojena (6A-03+).
     *
     * @var list<string>
     */
    public const PUBLICLY_VISIBLE_STATUSES = [
        self::STATUS_PUBLISHED,
        self::STATUS_CANCELLED,
    ];

    /**
     * Dozvoljeni prelazi statusa (TS-003 §4).
     * Ključ = od; vrijednost = lista ciljeva.
     *
     * @var array<string, list<string>>
     */
    public const ALLOWED_TRANSITIONS = [
        self::STATUS_DRAFT => [
            self::STATUS_PENDING_APPROVAL,
            self::STATUS_PUBLISHED, // direktna objava — samo bez Organizatora (servis)
        ],
        self::STATUS_PENDING_APPROVAL => [
            self::STATUS_PUBLISHED,
            self::STATUS_DRAFT, // vraćanje na doradu
        ],
        self::STATUS_PUBLISHED => [
            self::STATUS_CANCELLED,
            self::STATUS_ARCHIVED, // Sistem
        ],
        self::STATUS_CANCELLED => [
            self::STATUS_ARCHIVED, // Sistem — nema republish
        ],
        self::STATUS_ARCHIVED => [],
    ];

    protected $fillable = [
        'naslov',
        'opis',
        'status',
        'organizer_id',
        'category_id',
        'cover_media_id',
        'featured',
        'cancellation_reason',
        'return_reason',
        'created_by',
        'last_modified_by',
        'first_submitted_at',
    ];

    protected $casts = [
        'featured' => 'boolean',
        'organizer_id' => 'integer',
        'category_id' => 'integer',
        'cover_media_id' => 'integer',
        'created_by' => 'integer',
        'last_modified_by' => 'integer',
        'first_submitted_at' => 'datetime',
    ];

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isContentLocked(): bool
    {
        return $this->isPendingApproval()
            || $this->isPublished()
            || $this->isCancelled()
            || $this->status === self::STATUS_ARCHIVED;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Aktuelan = ima barem jedno Održavanje Planiran/Odgođen sa datumom ≥ danas (BM-PK-15).
     */
    public function isAktuelan(?\DateTimeInterface $today = null): bool
    {
        $date = \Illuminate\Support\Carbon::parse($today ?? now())->toDateString();

        return $this->occurrences()
            ->whereIn('status', [
                CulturalOccurrence::STATUS_PLANNED,
                CulturalOccurrence::STATUS_POSTPONED,
            ])
            ->whereDate('datum', '>=', $date)
            ->exists();
    }

    /**
     * Broj trenutno istaknutih slotova: published + featured + aktuelan.
     */
    public static function currentFeaturedAktuelniCount(?int $exceptId = null, ?\DateTimeInterface $today = null): int
    {
        $date = \Illuminate\Support\Carbon::parse($today ?? now())->toDateString();

        $query = static::query()
            ->where('status', self::STATUS_PUBLISHED)
            ->where('featured', true)
            ->whereHas('occurrences', function ($q) use ($date) {
                $q->whereIn('status', [
                    CulturalOccurrence::STATUS_PLANNED,
                    CulturalOccurrence::STATUS_POSTPONED,
                ])->whereDate('datum', '>=', $date);
            });

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->count();
    }

    public function hasEnteredEditorialFlow(): bool
    {
        return $this->first_submitted_at !== null;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function canTransitionTo(string $target): bool
    {
        $allowed = self::ALLOWED_TRANSITIONS[$this->status] ?? [];

        return in_array($target, $allowed, true);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(CulturalOrganizer::class, 'organizer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CulturalCategory::class, 'category_id');
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

    public function occurrences(): HasMany
    {
        return $this->hasMany(CulturalOccurrence::class, 'event_entry_id');
    }

    public function changeProposals(): HasMany
    {
        return $this->hasMany(CulturalEventChangeProposal::class, 'event_entry_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            CulturalTag::class,
            'cultural_event_entry_tag',
            'cultural_event_entry_id',
            'cultural_tag_id'
        )->withTimestamps();
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }

    /**
     * Statusna javna vidljivost (TS-009 §12): published | cancelled.
     * Bez vremenske logike (aktivno/arhiva/next OCC).
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('status', self::PUBLICLY_VISIBLE_STATUSES);
    }

    public function isPubliclyVisible(): bool
    {
        return in_array($this->status, self::PUBLICLY_VISIBLE_STATUSES, true);
    }

    /**
     * Javni URL naslovne fotografije (6A-06): coverMedia ili category default / global fallback.
     */
    public function imageUrl(): string
    {
        if ($this->coverMedia && filled($this->coverMedia->storage_path)) {
            return $this->coverMedia->publicUrl();
        }

        return CulturalEvent::defaultImageUrlForCategory($this->category?->naziv);
    }

    /**
     * Minimalni javni badge za Pretragu (6A-06).
     * Pun Predstoji/U toku/Završen → 6A-11; ovdje samo Otkazan.
     *
     * @return array{key: string, label: string, class: string}|null
     */
    public function publicStatus(?CarbonInterface $now = null): ?array
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return [
                'key' => 'cancelled',
                'label' => 'Otkazan',
                'class' => 'kk-status-cancelled',
            ];
        }

        return null;
    }

    public function publicCategoryName(): ?string
    {
        $naziv = $this->category?->naziv;

        if ($naziv === null) {
            return null;
        }

        $trimmed = trim((string) $naziv);

        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * Održavanje na datom kalendarskom datumu (naslovna lista za izabrani dan).
     */
    public function occurrenceOnDate(string $dateYmd): ?CulturalOccurrence
    {
        if ($this->relationLoaded('occurrences')) {
            return $this->occurrences
                ->filter(function (CulturalOccurrence $occurrence) use ($dateYmd): bool {
                    $datum = $occurrence->datum;

                    $key = $datum instanceof CarbonInterface
                        ? $datum->format('Y-m-d')
                        : \Carbon\Carbon::parse((string) $datum)->format('Y-m-d');

                    return $key === $dateYmd;
                })
                ->sort(function (CulturalOccurrence $a, CulturalOccurrence $b): int {
                    $timeA = trim((string) ($a->vrijeme_od ?? '')) ?: '00:00:00';
                    $timeB = trim((string) ($b->vrijeme_od ?? '')) ?: '00:00:00';
                    $cmp = strcmp($timeA, $timeB);

                    return $cmp !== 0 ? $cmp : ($a->id <=> $b->id);
                })
                ->first();
        }

        return $this->occurrences()
            ->whereDate('datum', $dateYmd)
            ->orderByRaw("COALESCE(NULLIF(TRIM(vrijeme_od), ''), '00:00:00')")
            ->orderBy('id')
            ->first();
    }

    /**
     * Prvo naredno kartično relevantno Održavanje (6A-03 / TS-009 §7.3.2).
     * Koristi eager-load `occurrences` ako je učitano (bez N+1).
     */
    public function nextRelevantOccurrence(?CarbonInterface $now = null): ?CulturalOccurrence
    {
        if ($this->relationLoaded('occurrences')) {
            return CulturalPublicCardOccurrenceCriteria::filterAndSortCollection(
                $this->occurrences,
                $now
            )->first();
        }

        return CulturalPublicCardOccurrenceCriteria::orderForNext(
            CulturalPublicCardOccurrenceCriteria::constrain(
                $this->occurrences()->getQuery(),
                $now
            )
        )->first();
    }

    /**
     * Broj kartično relevantnih Održavanja (kandidati za next / +N).
     */
    public function cardRelevantOccurrencesCount(?CarbonInterface $now = null): int
    {
        if ($this->relationLoaded('occurrences')) {
            return CulturalPublicCardOccurrenceCriteria::filterAndSortCollection(
                $this->occurrences,
                $now
            )->count();
        }

        return CulturalPublicCardOccurrenceCriteria::constrain(
            $this->occurrences()->getQuery(),
            $now
        )->count();
    }

    /**
     * „+ još N termina“ = max(kandidati − 1, 0).
     */
    public function additionalRelevantOccurrencesCount(?CarbonInterface $now = null): int
    {
        return max($this->cardRelevantOccurrencesCount($now) - 1, 0);
    }
}
