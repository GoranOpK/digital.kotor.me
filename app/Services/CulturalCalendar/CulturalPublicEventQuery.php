<?php

namespace App\Services\CulturalCalendar;

use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalLocation;
use App\Models\CulturalOccurrence;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Kanonski SSOT ulaz za javne query-je Faze 6A (PO-TS9-08J / TS-009 §11–12).
 *
 * 6A-02: statusna javna vidljivost (fail-closed).
 * 6A-03: sort po prvom narednom kartično relevantnom Održavanju.
 * 6A-04: kategorije + lokacijski filter adapter (bez controller cutover-a).
 * 6A-05: q / date / week / month filteri Pretrage (bez controller cutover-a).
 * 6A-07: index helperi (featured / upcoming / day counts).
 * 6A-08: findPublicEntryForShow (detalj + eager load).
 */
final class CulturalPublicEventQuery
{
    /**
     * Bazni kanonski public query — uvijek sa statusnom vidljivošću.
     *
     * @return Builder<CulturalEventEntry>
     */
    public function base(): Builder
    {
        return CulturalEventEntry::query()->publiclyVisible();
    }

    /**
     * Javni detalj Entry-ja (6A-08): fail-closed visibility + eager load za show.
     */
    public function findPublicEntryForShow(int|string $id): CulturalEventEntry
    {
        return $this->base()
            ->whereKey($id)
            ->with([
                'category',
                'coverMedia',
                'occurrences' => function ($query): void {
                    $query->orderBy('datum')
                        ->orderByRaw("COALESCE(NULLIF(TRIM(vrijeme_od), ''), '00:00:00')")
                        ->orderBy('id');
                },
                'occurrences.location',
            ])
            ->firstOrFail();
    }

    /**
     * @return Builder<CulturalEventEntry>
     */
    public function entries(): Builder
    {
        return $this->base();
    }

    /**
     * Aktivne kanonske kategorije za javni dropdown (TS-009 §3.3.3 / PO-TS9-08E).
     *
     * @return Collection<int, CulturalCategory>
     */
    public function categoryOptions(): Collection
    {
        return CulturalCategory::query()
            ->active()
            ->orderedByName()
            ->get();
    }

    /**
     * Filter po tačnom kanonskom nazivu aktivne kategorije.
     * Nevalidan / neaktivan / prazan naziv → ignoriše se (TS-009 §3.3.3).
     *
     * @param  Builder<CulturalEventEntry>|null  $query
     * @return Builder<CulturalEventEntry>
     */
    public function filterByCategoryName(?string $canonicalName, ?Builder $query = null): Builder
    {
        $query ??= $this->base();

        $name = is_string($canonicalName) ? trim($canonicalName) : '';
        if ($name === '') {
            return $query;
        }

        $categoryId = CulturalCategory::query()
            ->active()
            ->where('naziv', $name)
            ->value('id');

        if ($categoryId === null) {
            return $query;
        }

        return $query->where('category_id', $categoryId);
    }

    /**
     * Jedinstveni display nazivi lokacija iz Objavljenih Događaja (PO-CR3-04).
     * Bez vremenske arhivske semantike (6A-09).
     *
     * @return list<string>
     */
    public function locationDisplayOptions(): array
    {
        $entryTable = (new CulturalEventEntry)->getTable();
        $occTable = (new CulturalOccurrence)->getTable();
        $locTable = (new CulturalLocation)->getTable();

        $catalog = DB::table("{$occTable} as o")
            ->join("{$entryTable} as e", 'e.id', '=', 'o.event_entry_id')
            ->join("{$locTable} as l", 'l.id', '=', 'o.location_id')
            ->where('e.status', CulturalEventEntry::STATUS_PUBLISHED)
            ->whereNotNull('o.location_id')
            ->whereNotNull('l.naziv')
            ->whereRaw("TRIM(l.naziv) <> ''")
            ->distinct()
            ->orderBy('l.naziv')
            ->pluck('l.naziv');

        $manual = DB::table("{$occTable} as o")
            ->join("{$entryTable} as e", 'e.id', '=', 'o.event_entry_id')
            ->where('e.status', CulturalEventEntry::STATUS_PUBLISHED)
            ->whereNotNull('o.location_manual_name')
            ->whereRaw("TRIM(o.location_manual_name) <> ''")
            ->distinct()
            ->orderBy('o.location_manual_name')
            ->pluck('o.location_manual_name');

        return $catalog
            ->merge($manual)
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn (string $value) => $value !== '')
            ->unique()
            ->sort(SORT_STRING)
            ->values()
            ->all();
    }

    /**
     * Filter po tačnom display nazivu lokacije (katalog ili manual).
     * Nevalidan / nepostojeći / prazan → ignoriše se (TS-009 §3.3.4).
     *
     * @param  Builder<CulturalEventEntry>|null  $query
     * @return Builder<CulturalEventEntry>
     */
    public function filterByLocationDisplayName(?string $displayName, ?Builder $query = null): Builder
    {
        $query ??= $this->base();

        $name = is_string($displayName) ? trim($displayName) : '';
        if ($name === '') {
            return $query;
        }

        if (! in_array($name, $this->locationDisplayOptions(), true)) {
            return $query;
        }

        return $query->whereHas('occurrences', function (Builder $occurrenceQuery) use ($name): void {
            $occurrenceQuery->where(function (Builder $inner) use ($name): void {
                $inner->whereHas('location', function (Builder $locationQuery) use ($name): void {
                    $locationQuery->where('naziv', $name);
                })->orWhereRaw('TRIM(location_manual_name) = ?', [$name]);
            });
        });
    }

    /**
     * Sort: next relevant OCC ASC (NULL last), zatim Entry.id ASC.
     * Opcioni $query omogućava chain nakon filtera (6A-05); default ostaje base().
     *
     * @param  Builder<CulturalEventEntry>|null  $query
     * @return Builder<CulturalEventEntry>
     */
    public function orderedByNextRelevantOccurrence(?CarbonInterface $now = null, ?Builder $query = null): Builder
    {
        $now = CulturalPublicCardOccurrenceCriteria::now($now);
        $nowStr = Carbon::parse($now)
            ->timezone((string) config('app.timezone'))
            ->format('Y-m-d H:i:s');

        $datumSub = $this->nextRelevantOccurrenceColumnSubquery('datum', $nowStr);
        $timeSub = $this->nextRelevantOccurrenceColumnSubquery(
            "COALESCE(NULLIF(TRIM(vrijeme_od), ''), '00:00:00')",
            $nowStr,
            rawColumn: true
        );

        $query ??= $this->base();

        return $query
            ->orderByRaw('('.$datumSub->toSql().') IS NULL')
            ->addBinding($datumSub->getBindings(), 'order')
            ->orderBy($datumSub)
            ->orderBy($timeSub)
            ->orderBy('cultural_event_entries.id');
    }

    /**
     * Tekstualna pretraga q (TS-009 §3.3.2 / PO-CR3-02).
     * Obuhvat: naslov, opis, javni display lokacije OCC (katalog naziv | TRIM manual).
     * Prazan / whitespace → ignore.
     *
     * @param  Builder<CulturalEventEntry>|null  $query
     * @return Builder<CulturalEventEntry>
     */
    public function filterByQ(?string $term, ?Builder $query = null): Builder
    {
        $query ??= $this->base();

        $term = is_string($term) ? trim($term) : '';
        if ($term === '') {
            return $query;
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(function (Builder $outer) use ($like): void {
            $outer->where('naslov', 'like', $like)
                ->orWhere('opis', 'like', $like)
                ->orWhereHas('occurrences', function (Builder $occurrenceQuery) use ($like): void {
                    $occurrenceQuery->where(function (Builder $inner) use ($like): void {
                        $inner->whereHas('location', function (Builder $locationQuery) use ($like): void {
                            $locationQuery->where('naziv', 'like', $like);
                        })->orWhereRaw('TRIM(location_manual_name) LIKE ?', [$like]);
                    });
                });
        });
    }

    /**
     * Filter po kalendarskom datumu OCC (TS-009 §3.2): occurrences.datum = Y-m-d.
     * Nevalidan / prazan → ignore. Sva OCC statusa (nije cardRelevant).
     *
     * @param  Builder<CulturalEventEntry>|null  $query
     * @return Builder<CulturalEventEntry>
     */
    public function filterByDate(?string $dateYmd, ?Builder $query = null): Builder
    {
        $query ??= $this->base();
        $date = $this->parseDateYmd($dateYmd);
        if ($date === null) {
            return $query;
        }

        return $query->whereHas('occurrences', function (Builder $occurrenceQuery) use ($date): void {
            $occurrenceQuery->whereDate('datum', $date);
        });
    }

    /**
     * Filter po sedmici: OCC.datum u [week_start, week_end] uključivo (TS-009 §3.2).
     * Oba parametra moraju biti validna; inače ignore. Ako start > end, zamijeni (legacy).
     *
     * @param  Builder<CulturalEventEntry>|null  $query
     * @return Builder<CulturalEventEntry>
     */
    public function filterByWeek(?string $weekStartYmd, ?string $weekEndYmd, ?Builder $query = null): Builder
    {
        $query ??= $this->base();
        $start = $this->parseDateYmd($weekStartYmd);
        $end = $this->parseDateYmd($weekEndYmd);
        if ($start === null || $end === null) {
            return $query;
        }

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        return $query->whereHas('occurrences', function (Builder $occurrenceQuery) use ($start, $end): void {
            $occurrenceQuery
                ->whereDate('datum', '>=', $start)
                ->whereDate('datum', '<=', $end);
        });
    }

    /**
     * Filter po mjesecu YYYY-MM (TS-009 §3.2 / CR-002). Nevalidan → ignore.
     * Bez ograničenja „samo od danas“.
     *
     * @param  Builder<CulturalEventEntry>|null  $query
     * @return Builder<CulturalEventEntry>
     */
    public function filterByMonth(?string $monthYm, ?Builder $query = null): Builder
    {
        $query ??= $this->base();
        $bounds = $this->parseMonthYm($monthYm);
        if ($bounds === null) {
            return $query;
        }

        [$start, $end] = $bounds;

        return $query->whereHas('occurrences', function (Builder $occurrenceQuery) use ($start, $end): void {
            $occurrenceQuery
                ->whereDate('datum', '>=', $start)
                ->whereDate('datum', '<=', $end);
        });
    }

    /**
     * Entry sa makar jednim kartično relevantnim OCC (Planiran + nije istekao).
     *
     * @param  Builder<CulturalEventEntry>|null  $query
     * @return Builder<CulturalEventEntry>
     */
    public function withCardRelevantOccurrence(?Builder $query = null, ?CarbonInterface $now = null): Builder
    {
        $query ??= $this->base();

        return $query->whereHas('occurrences', function (Builder $occurrenceQuery) use ($now): void {
            CulturalPublicCardOccurrenceCriteria::constrain($occurrenceQuery, $now);
        });
    }

    /**
     * Istaknuti za naslovnu: published + featured + aktuelan (next relevant OCC).
     *
     * @return Builder<CulturalEventEntry>
     */
    public function featuredForPublicIndex(?CarbonInterface $now = null): Builder
    {
        $query = CulturalEventEntry::query()
            ->where('status', CulturalEventEntry::STATUS_PUBLISHED)
            ->where('featured', true);

        $query = $this->withCardRelevantOccurrence($query, $now);

        return $this->orderedByNextRelevantOccurrence($now, $query);
    }

    /**
     * Naredni događaji: javno vidljivi sa next relevant OCC, sortirani.
     *
     * @return Builder<CulturalEventEntry>
     */
    public function upcomingForPublicIndex(?CarbonInterface $now = null): Builder
    {
        $query = $this->withCardRelevantOccurrence($this->base(), $now);

        return $this->orderedByNextRelevantOccurrence($now, $query);
    }

    /**
     * Broj jedinstvenih javno vidljivih Entry-ja po OCC.datum (kalendar mreža).
     *
     * @return array<string, int>  Y-m-d => count
     */
    public function distinctPublicEntryCountsByOccurrenceDate(string $fromYmd, string $toYmd): array
    {
        $entryTable = (new CulturalEventEntry)->getTable();
        $occTable = (new CulturalOccurrence)->getTable();

        $rows = DB::table("{$occTable} as o")
            ->join("{$entryTable} as e", 'e.id', '=', 'o.event_entry_id')
            ->whereIn('e.status', CulturalEventEntry::PUBLICLY_VISIBLE_STATUSES)
            ->whereDate('o.datum', '>=', $fromYmd)
            ->whereDate('o.datum', '<=', $toYmd)
            ->groupByRaw('DATE(o.datum)')
            ->orderByRaw('DATE(o.datum)')
            ->selectRaw('DATE(o.datum) as day_key, COUNT(DISTINCT o.event_entry_id) as entry_count')
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row->day_key] = (int) $row->entry_count;
        }

        return $counts;
    }

    private function parseDateYmd(?string $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
            if ($parsed->format('Y-m-d') !== $value) {
                return null;
            }

            return $value;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{0: string, 1: string}|null  [startYmd, endYmd]
     */
    private function parseMonthYm(?string $value): ?array
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) !== 1) {
            return null;
        }

        try {
            $start = Carbon::createFromFormat('!Y-m', $value)->startOfMonth();
            if ($start->format('Y-m') !== $value) {
                return null;
            }

            return [
                $start->toDateString(),
                $start->copy()->endOfMonth()->toDateString(),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function nextRelevantOccurrenceColumnSubquery(
        string $column,
        string $nowStr,
        bool $rawColumn = false
    ): QueryBuilder {
        $occTable = (new CulturalOccurrence)->getTable();
        $expiresSql = CulturalPublicCardOccurrenceCriteria::expiresAtSql($occTable);

        $query = CulturalOccurrence::query()
            ->whereColumn("{$occTable}.event_entry_id", 'cultural_event_entries.id')
            ->where("{$occTable}.status", CulturalOccurrence::STATUS_PLANNED)
            ->whereRaw("{$expiresSql} >= ?", [$nowStr])
            ->orderBy("{$occTable}.datum")
            ->orderByRaw("COALESCE(NULLIF(TRIM({$occTable}.vrijeme_od), ''), '00:00:00')")
            ->orderBy("{$occTable}.id")
            ->limit(1);

        if ($rawColumn) {
            $query->selectRaw($column);
        } else {
            $query->select("{$occTable}.{$column}");
        }

        return $query->getQuery();
    }
}
