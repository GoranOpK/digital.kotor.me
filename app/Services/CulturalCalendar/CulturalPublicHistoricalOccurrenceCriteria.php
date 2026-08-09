<?php

namespace App\Services\CulturalCalendar;

use App\Models\CulturalOccurrence;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * SSOT: posljednje istorijsko Održavanje za Javnu Arhivu (6A-09 / PO-6A09-05).
 *
 * Kandidati: finished | cancelled. postponed i planned nisu kandidati.
 */
final class CulturalPublicHistoricalOccurrenceCriteria
{
    public static function now(?CarbonInterface $now = null): CarbonInterface
    {
        return $now ?? Carbon::now((string) config('app.timezone'));
    }

    /**
     * @return list<string>
     */
    public static function candidateStatuses(): array
    {
        return [
            CulturalOccurrence::STATUS_FINISHED,
            CulturalOccurrence::STATUS_CANCELLED,
        ];
    }

    public static function isHistoricalCandidate(CulturalOccurrence $occurrence): bool
    {
        return in_array($occurrence->status, self::candidateStatuses(), true);
    }

    /**
     * SQL izraz za historicalSortAt() (MySQL DATETIME, aplikaciona TZ pretpostavka).
     */
    public static function historicalSortAtSql(string $table = 'cultural_occurrences'): string
    {
        $finished = CulturalOccurrence::STATUS_FINISHED;
        $cancelled = CulturalOccurrence::STATUS_CANCELLED;

        return "CASE
            WHEN {$table}.status = '{$finished}' THEN
                CASE
                    WHEN {$table}.vrijeme_do IS NOT NULL AND TRIM({$table}.vrijeme_do) <> ''
                        THEN TIMESTAMP(CONCAT(DATE({$table}.datum), ' ', {$table}.vrijeme_do))
                    ELSE TIMESTAMP(CONCAT(DATE({$table}.datum), ' 23:59:59'))
                END
            WHEN {$table}.status = '{$cancelled}' THEN
                CASE
                    WHEN {$table}.vrijeme_od IS NOT NULL AND TRIM({$table}.vrijeme_od) <> ''
                        THEN TIMESTAMP(CONCAT(DATE({$table}.datum), ' ', {$table}.vrijeme_od))
                    ELSE TIMESTAMP(CONCAT(DATE({$table}.datum), ' 00:00:00'))
                END
            ELSE NULL
        END";
    }

    /**
     * @param  Builder<CulturalOccurrence>  $query
     * @return Builder<CulturalOccurrence>
     */
    public static function constrain(Builder $query): Builder
    {
        return $query->whereIn('status', self::candidateStatuses());
    }

    /**
     * @param  Builder<CulturalOccurrence>  $query
     * @return Builder<CulturalOccurrence>
     */
    public static function orderForLast(Builder $query): Builder
    {
        $table = $query->getModel()->getTable();
        $sortSql = self::historicalSortAtSql($table);

        return $query
            ->orderByRaw("{$sortSql} DESC")
            ->orderByDesc("{$table}.id");
    }

    /**
     * @param  Collection<int, CulturalOccurrence>  $occurrences
     * @return Collection<int, CulturalOccurrence>
     */
    public static function filterAndSortCollection(Collection $occurrences): Collection
    {
        return $occurrences
            ->filter(fn (CulturalOccurrence $occ): bool => self::isHistoricalCandidate($occ))
            ->sort(function (CulturalOccurrence $a, CulturalOccurrence $b): int {
                $aAt = $a->historicalSortAt();
                $bAt = $b->historicalSortAt();
                if ($aAt === null && $bAt === null) {
                    return $b->id <=> $a->id;
                }
                if ($aAt === null) {
                    return 1;
                }
                if ($bAt === null) {
                    return -1;
                }
                $cmp = $bAt->getTimestamp() <=> $aAt->getTimestamp();
                if ($cmp !== 0) {
                    return $cmp;
                }

                return $b->id <=> $a->id;
            })
            ->values();
    }
}
