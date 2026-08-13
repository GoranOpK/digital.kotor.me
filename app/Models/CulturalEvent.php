<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CulturalEvent extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'Koncerti',
        'Predstave',
        'Izložbe',
        'Sportski događaji',
        'Književne večeri',
        'Filmske projekcije',
        'Radionice',
        'Promocije publikacija',
        'Performansi',
        'Filmski festivali',
        'Likovne manifestacije',
        'Prezentacije',
        'Paneli o kulturi',
        'Manifestacije u organizaciji Mjesnih zajednica',
        'Manifestacije u organizaciji NVU',
        'Nešto drugo',
    ];

    public const FALLBACK_DEFAULT_IMAGE = \App\Support\CulturalCalendarDefaultImages::FALLBACK_DEFAULT_IMAGE;

    public const CATEGORY_DEFAULT_IMAGES = \App\Support\CulturalCalendarDefaultImages::CATEGORY_DEFAULT_IMAGES;

    public const STATUSES = [
        'draft',
        'published',
        'archived',
        'cancelled',
    ];

    /**
     * Javno dostupni interni statusi na portalu (CR-004B / PO-CR4B-01).
     * Portalna Arhiva ≠ interni status archived — archived nije u ovom skupu.
     */
    public const PUBLICLY_VISIBLE_STATUSES = [
        'published',
        'cancelled',
    ];

    protected $fillable = [
        'naslov',
        'opis',
        'datum_od',
        'datum_do',
        'vrijeme',
        'vrijeme_do',
        'lokacija',
        'kategorija',
        'slika',
        'status',
        'featured',
        'created_by',
    ];

    protected $casts = [
        'datum_od' => 'date',
        'datum_do' => 'date',
        'featured' => 'boolean',
        'created_by' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: događaji javno dostupni na portalu (CR-004B).
     * published | cancelled — bez draft / archived.
     */
    public function scopePubliclyVisible($query)
    {
        return $query->whereIn('status', self::PUBLICLY_VISIBLE_STATUSES);
    }

    /**
     * Da li je ovaj zapis javno dostupan na portalu (CR-004B / show).
     */
    public function isPubliclyVisible(): bool
    {
        return in_array($this->status, self::PUBLICLY_VISIBLE_STATUSES, true);
    }

    /**
     * Public URL for the event image: uploaded file, else category default.
     */
    public function imageUrl(): string
    {
        if ($this->slika) {
            return asset('storage/'.$this->slika);
        }

        return \App\Support\CulturalCalendarDefaultImages::urlForCategory($this->kategorija);
    }

    public static function defaultImageUrlForCategory(?string $category): string
    {
        return \App\Support\CulturalCalendarDefaultImages::urlForCategory($category);
    }

    /**
     * Relative path under public/ for the reserved category image.
     */
    public static function defaultImagePathForCategory(?string $category): string
    {
        return \App\Support\CulturalCalendarDefaultImages::pathForCategory($category);
    }

    /**
     * Javni status događaja (CR-004A / PO-CR4A-01…05).
     *
     * Vraća key/label/class ili null kada se status ne može sigurno odrediti (PO-CR4A-05).
     * Predstoji / U toku / Završen su izračunata stanja — nisu statusi baze.
     *
     * @return array{key: string, label: string, class: string}|null
     */
    public function publicStatus(?Carbon $now = null): ?array
    {
        if ($this->status === 'cancelled') {
            return [
                'key' => 'cancelled',
                'label' => 'Otkazan',
                'class' => 'kk-status-cancelled',
            ];
        }

        $window = $this->resolvePublicStatusWindow();
        if ($window === null) {
            return null;
        }

        [$start, $end] = $window;
        $now = ($now ?? Carbon::now(config('app.timezone')))->copy();

        if ($now->lt($start)) {
            return [
                'key' => 'upcoming',
                'label' => 'Predstoji',
                'class' => 'kk-status-upcoming',
            ];
        }

        if ($now->lte($end)) {
            return [
                'key' => 'ongoing',
                'label' => 'U toku',
                'class' => 'kk-status-ongoing',
            ];
        }

        return [
            'key' => 'finished',
            'label' => 'Završen',
            'class' => 'kk-status-finished',
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function resolvePublicStatusWindow(): ?array
    {
        if ($this->datum_od === null) {
            return null;
        }

        $tz = config('app.timezone');

        try {
            $datumOd = $this->datum_od instanceof Carbon
                ? $this->datum_od->copy()->timezone($tz)->startOfDay()
                : Carbon::parse((string) $this->datum_od, $tz)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        $datumDo = null;
        if ($this->datum_do !== null) {
            try {
                $datumDo = $this->datum_do instanceof Carbon
                    ? $this->datum_do->copy()->timezone($tz)->startOfDay()
                    : Carbon::parse((string) $this->datum_do, $tz)->startOfDay();
            } catch (\Throwable) {
                return null;
            }

            if ($datumDo->lt($datumOd)) {
                return null;
            }
        }

        $startParts = $this->parsePublicStatusTime($this->vrijeme);
        $endParts = $this->parsePublicStatusTime($this->vrijeme_do);

        if ($this->hasPublicStatusTimeValue($this->vrijeme) && $startParts === null) {
            return null;
        }

        if ($this->hasPublicStatusTimeValue($this->vrijeme_do) && $endParts === null) {
            return null;
        }

        if ($endParts !== null && $startParts === null) {
            return null;
        }

        $isMultiDay = $datumDo !== null && $datumDo->gt($datumOd);

        if ($isMultiDay) {
            if ($startParts !== null && $endParts !== null) {
                $start = $datumOd->copy()->setTime($startParts[0], $startParts[1], $startParts[2]);
                $end = $datumDo->copy()->setTime($endParts[0], $endParts[1], $endParts[2]);

                if ($end->lt($start)) {
                    return null;
                }

                return [$start, $end];
            }

            return [$datumOd->copy()->startOfDay(), $datumDo->copy()->endOfDay()];
        }

        $day = $datumOd->copy();

        if ($startParts !== null && $endParts !== null) {
            $start = $day->copy()->setTime($startParts[0], $startParts[1], $startParts[2]);
            $end = $day->copy()->setTime($endParts[0], $endParts[1], $endParts[2]);

            if ($end->lte($start)) {
                return null;
            }

            return [$start, $end];
        }

        if ($startParts !== null) {
            $start = $day->copy()->setTime($startParts[0], $startParts[1], $startParts[2]);

            return [$start, $day->copy()->endOfDay()];
        }

        return [$day->copy()->startOfDay(), $day->copy()->endOfDay()];
    }

    private function hasPublicStatusTimeValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        return trim((string) $value) !== '';
    }

    /**
     * @return array{0: int, 1: int, 2: int}|null
     */
    private function parsePublicStatusTime(mixed $value): ?array
    {
        if (! $this->hasPublicStatusTimeValue($value)) {
            return null;
        }

        $raw = trim((string) $value);

        if (! preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $raw, $matches)) {
            return null;
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];
        $second = isset($matches[3]) ? (int) $matches[3] : 0;

        if ($hour > 23 || $minute > 59 || $second > 59) {
            return null;
        }

        return [$hour, $minute, $second];
    }
}
