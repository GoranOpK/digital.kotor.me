<?php

namespace App\Models;

use App\Services\CulturalCalendar\CulturalPublicCardOccurrenceCriteria;
use App\Services\CulturalCalendar\CulturalPublicHistoricalOccurrenceCriteria;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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
     * Dozvoljeni izvorni statusi za archived_from_status (6A-09 / PO-6A09-02).
     *
     * @var list<string>
     */
    public const ARCHIVED_FROM_STATUSES = [
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
        'archived_from_status',
        'organizer_id',
        'manifestation_id',
        'organizer_manual_name',
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
        'manifestation_id' => 'integer',
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

    /**
     * UI label za urednički portal (PATCH-063).
     * Direct-flow draft (bez registrovanog Org) = „U pripremi“; Moderator draft = „Nacrt“.
     */
    public function editorialStatusLabel(): string
    {
        if ($this->status === self::STATUS_DRAFT && $this->organizer_id === null) {
            return 'U pripremi';
        }

        return $this->statusLabel();
    }

    /**
     * PATCH-063 — Urednik može trajno obrisati samo direct-flow draft.
     */
    public function isEditorialPreparationDeletable(): bool
    {
        return $this->isDraft() && $this->organizer_id === null;
    }

    /**
     * PATCH-063 §4.13 — Urednik može direktno uređivati ordinary content Objavljenog direct-flow Događaja.
     */
    public function isDirectFlowPublishedContentEditable(): bool
    {
        return $this->isPublished() && $this->organizer_id === null;
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

    public function manifestation(): BelongsTo
    {
        return $this->belongsTo(CulturalManifestation::class, 'manifestation_id');
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

        return \App\Support\CulturalCalendarDefaultImages::urlForCategory($this->category?->naziv);
    }

    /**
     * Javni status badge Događaja (PO-6A11-01 / TS-009 §7.1.6 / BM-PK-34 / BR-285).
     *
     * Otkazan = apsolutni prioritet. Za published: agregat Planiranih OCC
     * (U toku → Predstoji → Završen); postponed-only / 0 OCC → null.
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

        if ($this->status === self::STATUS_ARCHIVED) {
            if ($this->archived_from_status === self::STATUS_CANCELLED) {
                return [
                    'key' => 'cancelled',
                    'label' => 'Otkazan',
                    'class' => 'kk-status-cancelled',
                ];
            }

            if ($this->archived_from_status === self::STATUS_PUBLISHED) {
                return [
                    'key' => 'finished',
                    'label' => 'Završen',
                    'class' => 'kk-status-finished',
                ];
            }

            return null;
        }

        if ($this->status !== self::STATUS_PUBLISHED) {
            return null;
        }

        $now = ($now ?? \Carbon\Carbon::now(config('app.timezone')))->copy();

        $occurrences = $this->relationLoaded('occurrences')
            ? $this->occurrences
            : $this->occurrences()->get();

        if ($occurrences->isEmpty()) {
            return null;
        }

        $onlyPostponed = $occurrences->every(
            fn (CulturalOccurrence $occ): bool => $occ->status === CulturalOccurrence::STATUS_POSTPONED
        );
        if ($onlyPostponed) {
            return null;
        }

        $hasOngoing = false;
        $hasUpcoming = false;

        foreach ($occurrences as $occurrence) {
            if (! $occurrence->contributesToEntryPublicTimeStatus()) {
                continue;
            }

            if ($occurrence->isInProgressAt($now)) {
                $hasOngoing = true;
                break;
            }

            if ($occurrence->isUpcomingAt($now)) {
                $hasUpcoming = true;
            }
        }

        if ($hasOngoing) {
            return [
                'key' => 'ongoing',
                'label' => 'U toku',
                'class' => 'kk-status-ongoing',
            ];
        }

        if ($hasUpcoming) {
            return [
                'key' => 'upcoming',
                'label' => 'Predstoji',
                'class' => 'kk-status-upcoming',
            ];
        }

        return [
            'key' => 'finished',
            'label' => 'Završen',
            'class' => 'kk-status-finished',
        ];
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
     * Javni prikaz Organizatora (PATCH-063 / TS-009 §7.3.6).
     * Prioritet: registered Org → manual name → null (bez prazne sekcije).
     */
    public function publicOrganizerDisplayName(): ?string
    {
        if ($this->organizer_id !== null) {
            $naziv = $this->organizer?->naziv;
            if ($naziv !== null) {
                $trimmed = trim((string) $naziv);
                if ($trimmed !== '') {
                    return $trimmed;
                }
            }
        }

        $manual = $this->organizer_manual_name;
        if ($manual === null) {
            return null;
        }

        $trimmed = trim((string) $manual);

        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * Opciona javna napomena pri Entry otkazivanju (PATCH-063 / BR-295).
     */
    public function publicCancellationNotice(): ?string
    {
        if ($this->cancellation_reason === null) {
            return null;
        }

        $trimmed = trim((string) $this->cancellation_reason);

        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * Održavanje na datom kalendarskom datumu (naslovna lista za izabrani dan).
     * Preferira Planirano nad Odgođenim/Otkazanim (PATCH-063 Phase 6).
     */
    public function occurrenceOnDate(string $dateYmd): ?CulturalOccurrence
    {
        if ($this->relationLoaded('occurrences')) {
            $onDate = $this->occurrences
                ->filter(function (CulturalOccurrence $occurrence) use ($dateYmd): bool {
                    $datum = $occurrence->datum;

                    $key = $datum instanceof CarbonInterface
                        ? $datum->format('Y-m-d')
                        : \Carbon\Carbon::parse((string) $datum)->format('Y-m-d');

                    return $key === $dateYmd;
                })
                ->sort(function (CulturalOccurrence $a, CulturalOccurrence $b): int {
                    $rank = static fn (CulturalOccurrence $o): int => match ($o->status) {
                        CulturalOccurrence::STATUS_PLANNED => 0,
                        CulturalOccurrence::STATUS_FINISHED => 1,
                        CulturalOccurrence::STATUS_CANCELLED => 2,
                        default => 3,
                    };
                    $rankCmp = $rank($a) <=> $rank($b);
                    if ($rankCmp !== 0) {
                        return $rankCmp;
                    }

                    $timeA = trim((string) ($a->vrijeme_od ?? '')) ?: '00:00:00';
                    $timeB = trim((string) ($b->vrijeme_od ?? '')) ?: '00:00:00';
                    $cmp = strcmp($timeA, $timeB);

                    return $cmp !== 0 ? $cmp : ($a->id <=> $b->id);
                })
                ->values();

            return $onDate->first();
        }

        return $this->occurrences()
            ->whereDate('datum', $dateYmd)
            ->orderByRaw("CASE status
                WHEN '".CulturalOccurrence::STATUS_PLANNED."' THEN 0
                WHEN '".CulturalOccurrence::STATUS_FINISHED."' THEN 1
                WHEN '".CulturalOccurrence::STATUS_CANCELLED."' THEN 2
                ELSE 3 END")
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
     * Posljednje istorijsko Održavanje za Javnu Arhivu (6A-09 / PO-6A09-05).
     * Kandidati: finished | cancelled. Ne koristi nextRelevantOccurrence().
     */
    public function lastHistoricalOccurrence(): ?CulturalOccurrence
    {
        if ($this->relationLoaded('occurrences')) {
            return CulturalPublicHistoricalOccurrenceCriteria::filterAndSortCollection(
                $this->occurrences
            )->first();
        }

        return CulturalPublicHistoricalOccurrenceCriteria::orderForLast(
            CulturalPublicHistoricalOccurrenceCriteria::constrain(
                $this->occurrences()->getQuery()
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

    /**
     * Sva Održavanja za javni detalj (6A-08 / TS-009 §7.3.3).
     * Nije cardRelevant — uključuje Planiran, Odgođen, Otkazan, Završen.
     * Redoslijed: datum ASC, vrijeme_od ASC, id ASC.
     *
     * @return Collection<int, CulturalOccurrence>
     */
    public function publicDetailOccurrences(): Collection
    {
        if ($this->relationLoaded('occurrences')) {
            return $this->occurrences
                ->sort(function (CulturalOccurrence $a, CulturalOccurrence $b): int {
                    $dateA = $a->datum instanceof CarbonInterface
                        ? $a->datum->format('Y-m-d')
                        : Carbon::parse((string) $a->datum)->format('Y-m-d');
                    $dateB = $b->datum instanceof CarbonInterface
                        ? $b->datum->format('Y-m-d')
                        : Carbon::parse((string) $b->datum)->format('Y-m-d');

                    $cmp = strcmp($dateA, $dateB);
                    if ($cmp !== 0) {
                        return $cmp;
                    }

                    $timeA = trim((string) ($a->vrijeme_od ?? '')) ?: '00:00:00';
                    $timeB = trim((string) ($b->vrijeme_od ?? '')) ?: '00:00:00';
                    $cmp = strcmp($timeA, $timeB);

                    return $cmp !== 0 ? $cmp : ($a->id <=> $b->id);
                })
                ->values();
        }

        return $this->occurrences()
            ->orderBy('datum')
            ->orderByRaw("COALESCE(NULLIF(TRIM(vrijeme_od), ''), '00:00:00')")
            ->orderBy('id')
            ->get();
    }
}
