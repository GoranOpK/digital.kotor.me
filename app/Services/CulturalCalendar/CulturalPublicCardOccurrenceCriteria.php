<?php

namespace App\Services\CulturalCalendar;

use App\Models\CulturalOccurrence;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * SSOT: kartično „relevantno / važeće“ Održavanje (TS-009 §7.3.1–7.3.2 / 6A-03).
 *
 * Kandidat = Planiran + termin nije istekao (CulturalOccurrence::expiresAt / isExpiredAt).
 * Odgođen / Otkazan / Završen OCC nisu kandidati za next / +N / sort.
 */
final class CulturalPublicCardOccurrenceCriteria
{
    public static function now(?CarbonInterface $now = null): CarbonInterface
    {
        return $now ?? Carbon::now((string) config('app.timezone'));
    }

    public static function isCardRelevant(CulturalOccurrence $occurrence, ?CarbonInterface $now = null): bool
    {
        $now = self::now($now);

        return $occurrence->isPlanned() && ! $occurrence->isExpiredAt($now);
    }

    /**
     * SQL izraz za expiresAt() (PO-AUTO-02), u aplikacionoj TZ pretpostavci MySQL DATETIME.
     */
    public static function expiresAtSql(string $table = 'cultural_occurrences'): string
    {
        return "CASE
            WHEN {$table}.vrijeme_do IS NOT NULL AND TRIM({$table}.vrijeme_do) <> ''
                THEN TIMESTAMP(CONCAT(DATE({$table}.datum), ' ', {$table}.vrijeme_do))
            ELSE TIMESTAMP(CONCAT(DATE({$table}.datum), ' 23:59:59'))
        END";
    }

    /**
     * @param  Builder<CulturalOccurrence>  $query
     * @return Builder<CulturalOccurrence>
     */
    public static function constrain(Builder $query, ?CarbonInterface $now = null): Builder
    {
        $now = self::now($now);
        $nowStr = Carbon::parse($now)
            ->timezone((string) config('app.timezone'))
            ->format('Y-m-d H:i:s');

        return $query
            ->where('status', CulturalOccurrence::STATUS_PLANNED)
            ->whereRaw(self::expiresAtSql($query->getModel()->getTable()).' >= ?', [$nowStr]);
    }

    /**
     * @param  Builder<CulturalOccurrence>  $query
     * @return Builder<CulturalOccurrence>
     */
    public static function orderForNext(Builder $query): Builder
    {
        $table = $query->getModel()->getTable();

        return $query
            ->orderBy("{$table}.datum")
            ->orderByRaw("COALESCE(NULLIF(TRIM({$table}.vrijeme_od), ''), '00:00:00')")
            ->orderBy("{$table}.id");
    }

    /**
     * @param  Collection<int, CulturalOccurrence>  $occurrences
     * @return Collection<int, CulturalOccurrence>
     */
    public static function filterAndSortCollection(Collection $occurrences, ?CarbonInterface $now = null): Collection
    {
        $now = self::now($now);

        return $occurrences
            ->filter(fn (CulturalOccurrence $occurrence) => self::isCardRelevant($occurrence, $now))
            ->sort(function (CulturalOccurrence $a, CulturalOccurrence $b): int {
                $dateCmp = strcmp(
                    self::occurrenceDateKey($a),
                    self::occurrenceDateKey($b)
                );
                if ($dateCmp !== 0) {
                    return $dateCmp;
                }

                $timeCmp = strcmp(
                    self::occurrenceTimeKey($a),
                    self::occurrenceTimeKey($b)
                );
                if ($timeCmp !== 0) {
                    return $timeCmp;
                }

                return $a->id <=> $b->id;
            })
            ->values();
    }

    private static function occurrenceDateKey(CulturalOccurrence $occurrence): string
    {
        $datum = $occurrence->datum;

        return $datum instanceof CarbonInterface
            ? $datum->format('Y-m-d')
            : Carbon::parse((string) $datum)->format('Y-m-d');
    }

    private static function occurrenceTimeKey(CulturalOccurrence $occurrence): string
    {
        $raw = trim((string) ($occurrence->vrijeme_od ?? ''));

        return $raw !== '' ? $raw : '00:00:00';
    }
}
