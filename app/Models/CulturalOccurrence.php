<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kanonsko Održavanje (TS-004 Korak 1 / PO-EV-01).
 * Status nezavisan od statusa Događaja (BR-134).
 */
class CulturalOccurrence extends Model
{
    use HasFactory;

    public const STATUS_PLANNED = 'planned';

    public const STATUS_POSTPONED = 'postponed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FINISHED = 'finished';

    public const STATUSES = [
        self::STATUS_PLANNED,
        self::STATUS_POSTPONED,
        self::STATUS_CANCELLED,
        self::STATUS_FINISHED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PLANNED => 'Planiran',
        self::STATUS_POSTPONED => 'Odgođen',
        self::STATUS_CANCELLED => 'Otkazan',
        self::STATUS_FINISHED => 'Završen',
    ];

    /**
     * Dozvoljeni prelazi (TS-004 §4).
     * Završen iz Planiran — Sistem; ručno Završen nije V1.
     *
     * @var array<string, list<string>>
     */
    public const ALLOWED_TRANSITIONS = [
        self::STATUS_PLANNED => [
            self::STATUS_POSTPONED,
            self::STATUS_CANCELLED,
            self::STATUS_FINISHED,
        ],
        self::STATUS_POSTPONED => [
            self::STATUS_PLANNED, // zahtijeva novi termin (servis)
            self::STATUS_CANCELLED,
        ],
        self::STATUS_CANCELLED => [],
        self::STATUS_FINISHED => [],
    ];

    protected $fillable = [
        'event_entry_id',
        'datum',
        'vrijeme_od',
        'vrijeme_do',
        'cjelodnevno',
        'status',
        'location_id',
        'location_manual_name',
        'postponement_reason',
        'cancellation_reason',
    ];

    protected $casts = [
        'datum' => 'date',
        'cjelodnevno' => 'boolean',
        'event_entry_id' => 'integer',
        'location_id' => 'integer',
    ];

    public function isPlanned(): bool
    {
        return $this->status === self::STATUS_PLANNED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isFinished(): bool
    {
        return $this->status === self::STATUS_FINISHED;
    }

    public function isPostponed(): bool
    {
        return $this->status === self::STATUS_POSTPONED;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Javna oznaka statusa Održavanja na detalju (TS-009 §7.3.3–7.3.4).
     * Planiran nema posebnu oznaku.
     */
    public function publicDetailStatusLabel(): ?string
    {
        return match ($this->status) {
            self::STATUS_POSTPONED => 'Odgođeno',
            self::STATUS_CANCELLED => 'Otkazano',
            self::STATUS_FINISHED => 'Završeno',
            default => null,
        };
    }

    /**
     * Opciona javna napomena za Odgođeno / Otkazano Održavanje (PATCH-063).
     */
    public function publicDetailNotice(): ?string
    {
        $raw = match ($this->status) {
            self::STATUS_POSTPONED => $this->postponement_reason,
            self::STATUS_CANCELLED => $this->cancellation_reason,
            default => null,
        };

        if ($raw === null) {
            return null;
        }

        $trimmed = trim((string) $raw);

        return $trimmed !== '' ? $trimmed : null;
    }

    public function canTransitionTo(string $target): bool
    {
        $allowed = self::ALLOWED_TRANSITIONS[$this->status] ?? [];

        return in_array($target, $allowed, true);
    }

    public function eventEntry(): BelongsTo
    {
        return $this->belongsTo(CulturalEventEntry::class, 'event_entry_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(CulturalLocation::class, 'location_id');
    }

    public function hasCatalogLocation(): bool
    {
        return $this->location_id !== null;
    }

    public function hasManualLocation(): bool
    {
        return $this->location_manual_name !== null
            && trim((string) $this->location_manual_name) !== '';
    }

    public function hasNoLocation(): bool
    {
        return ! $this->hasCatalogLocation() && ! $this->hasManualLocation();
    }

    /**
     * Javni display naziv lokacije Održavanja (6A-04 / TS-009 §3.3.4).
     *
     * Domenski XOR (OccurrenceWriter): kataloška ILI ručna, nikad oboje.
     * Deaktivirana kataloška lokacija i dalje daje naziv (istorijska referenca).
     */
    public function publicLocationDisplayName(): ?string
    {
        if ($this->hasCatalogLocation()) {
            $naziv = $this->location?->naziv;
            if ($naziv === null) {
                return null;
            }

            $trimmed = trim((string) $naziv);

            return $trimmed !== '' ? $trimmed : null;
        }

        if ($this->hasManualLocation()) {
            return trim((string) $this->location_manual_name);
        }

        return null;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_PLANNED,
            self::STATUS_POSTPONED,
        ]);
    }

    /**
     * Kartično relevantna Održavanja (6A-03 / TS-009 §7.3.1): Planiran + nije istekao.
     *
     * @param  Builder<CulturalOccurrence>  $query
     * @return Builder<CulturalOccurrence>
     */
    public function scopeCardRelevantForPublic(Builder $query, ?CarbonInterface $now = null): Builder
    {
        return \App\Services\CulturalCalendar\CulturalPublicCardOccurrenceCriteria::constrain($query, $now);
    }

    /**
     * Trenutak isteka prema PO-AUTO-02 (aplikaciona vremenska zona).
     * Sa `vrijeme_do`: datum + vrijeme_do. Bez njega: kraj kalendarskog dana `datum`.
     */
    public function expiresAt(): CarbonInterface
    {
        $tz = (string) config('app.timezone');
        $date = $this->datum instanceof CarbonInterface
            ? $this->datum->format('Y-m-d')
            : Carbon::parse((string) $this->datum)->format('Y-m-d');

        $vrijemeDo = $this->normalizedTimeString($this->vrijeme_do);
        if ($vrijemeDo !== null) {
            return Carbon::parse($date.' '.$vrijemeDo, $tz);
        }

        return Carbon::parse($date, $tz)->endOfDay();
    }

    /**
     * Deterministički trenutak za sort istorijskih Održavanja (6A-09 / PO-6A09-05).
     * finished → expiresAt(); cancelled → stvarni datum/vrijeme_od (all-day = početak dana).
     */
    public function historicalSortAt(): ?CarbonInterface
    {
        if ($this->status === self::STATUS_FINISHED) {
            return $this->expiresAt();
        }

        if ($this->status === self::STATUS_CANCELLED) {
            return $this->startsAt();
        }

        return null;
    }

    /**
     * Da li je termin istekao u datom trenutku (strogo nakon expiresAt).
     */
    public function isExpiredAt(CarbonInterface $now): bool
    {
        return $now->greaterThan($this->expiresAt());
    }

    /**
     * Početak važećeg intervala Održavanja (PO-6A11-01 / TS-009 §7.1.6).
     * Sa `vrijeme_od`: datum + vrijeme_od. Bez njega / cjelodnevno: početak kalendarskog dana.
     * Neispravan `vrijeme_od` → null (PO-CR4A-05 stil).
     */
    public function startsAt(): ?CarbonInterface
    {
        $tz = (string) config('app.timezone');
        $date = $this->datum instanceof CarbonInterface
            ? $this->datum->format('Y-m-d')
            : Carbon::parse((string) $this->datum)->format('Y-m-d');

        if ($this->hasRawTimeValue($this->vrijeme_od)) {
            $vrijemeOd = $this->normalizedTimeString($this->vrijeme_od);
            if ($vrijemeOd === null) {
                return null;
            }

            return Carbon::parse($date.' '.$vrijemeOd, $tz);
        }

        return Carbon::parse($date, $tz)->startOfDay();
    }

    /**
     * Da li Održavanje traje u datom trenutku (PO-6A11-01).
     * Granica završetka = !isExpiredAt (strogo nakon expiresAt → isteklo).
     */
    public function isInProgressAt(CarbonInterface $now): bool
    {
        $start = $this->startsAt();
        if ($start === null) {
            return false;
        }

        if ($this->hasRawTimeValue($this->vrijeme_do)
            && ! $this->hasRawTimeValue($this->vrijeme_od)
            && ! $this->cjelodnevno) {
            return false;
        }

        if ($this->expiresAt()->lte($start)) {
            return false;
        }

        if ($now->lt($start)) {
            return false;
        }

        return ! $this->isExpiredAt($now);
    }

    /**
     * Da li Planirano Održavanje pouzdano doprinosi javnom vremenskom statusu Entry-ja.
     */
    public function contributesToEntryPublicTimeStatus(): bool
    {
        if ($this->status !== self::STATUS_PLANNED) {
            return false;
        }

        $start = $this->startsAt();
        if ($start === null) {
            return false;
        }

        if ($this->hasRawTimeValue($this->vrijeme_do)
            && ! $this->hasRawTimeValue($this->vrijeme_od)
            && ! $this->cjelodnevno) {
            return false;
        }

        return $this->expiresAt()->gt($start);
    }

    /**
     * Da li je Planirano Održavanje još buduće u datom trenutku.
     */
    public function isUpcomingAt(CarbonInterface $now): bool
    {
        if (! $this->contributesToEntryPublicTimeStatus()) {
            return false;
        }

        $start = $this->startsAt();

        return $start !== null && $now->lt($start);
    }

    private function hasRawTimeValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        return trim((string) $value) !== '';
    }

    private function normalizedTimeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $raw) === 1) {
            $parts = explode(':', $raw);

            return sprintf('%02d:%02d:%02d', (int) $parts[0], (int) $parts[1], (int) ($parts[2] ?? 0));
        }

        try {
            return Carbon::parse($raw, (string) config('app.timezone'))->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
