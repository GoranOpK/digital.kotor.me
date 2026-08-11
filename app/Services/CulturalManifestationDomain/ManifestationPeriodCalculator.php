<?php

namespace App\Services\CulturalManifestationDomain;

use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\CulturalOccurrence;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class ManifestationPeriodCalculator
{
    /**
     * @return array{start: CarbonInterface, end: CarbonInterface}|null
     */
    public function calculate(CulturalManifestation $manifestation): ?array
    {
        return $this->periodFromOccurrences($this->relevantOccurrences($manifestation));
    }

    /**
     * Read-side helper: period from already-loaded relevant OCC (no DB query).
     *
     * @param  Collection<int, CulturalOccurrence>  $occurrences
     * @return array{start: CarbonInterface, end: CarbonInterface}|null
     */
    public function periodFromOccurrences(Collection $occurrences): ?array
    {
        if ($occurrences->isEmpty()) {
            return null;
        }

        $sorted = $occurrences->sort(function (CulturalOccurrence $a, CulturalOccurrence $b): int {
            $dateCmp = strcmp($this->dateKey($a), $this->dateKey($b));
            if ($dateCmp !== 0) {
                return $dateCmp;
            }

            $timeA = trim((string) ($a->vrijeme_od ?? '')) ?: '00:00:00';
            $timeB = trim((string) ($b->vrijeme_od ?? '')) ?: '00:00:00';
            $timeCmp = strcmp($timeA, $timeB);
            if ($timeCmp !== 0) {
                return $timeCmp;
            }

            return $a->id <=> $b->id;
        })->values();

        $first = $sorted->first();
        $last = $sorted->last();
        if (! $first instanceof CulturalOccurrence || ! $last instanceof CulturalOccurrence) {
            return null;
        }

        return [
            'start' => $this->startAt($first),
            'end' => $this->endAt($last),
        ];
    }

    public function hasExpired(CulturalManifestation $manifestation, ?CarbonInterface $now = null): bool
    {
        return $this->hasExpiredPeriod($this->calculate($manifestation), $now);
    }

    /**
     * @param  array{start: CarbonInterface, end: CarbonInterface}|null  $period
     */
    public function hasExpiredPeriod(?array $period, ?CarbonInterface $now = null): bool
    {
        if ($period === null) {
            return false;
        }

        $now ??= now((string) config('app.timezone'));

        return Carbon::parse($now)->greaterThan($period['end']);
    }

    /**
     * Batch load relevant OCC for many MF IDs (same filters as relevantOccurrences).
     *
     * @param  list<int|string>  $manifestationIds
     * @return Collection<int, Collection<int, CulturalOccurrence>>
     */
    public function relevantOccurrencesGroupedByManifestation(array $manifestationIds): Collection
    {
        $ids = array_values(array_unique(array_map('intval', $manifestationIds)));
        if ($ids === []) {
            return collect();
        }

        $entryTable = (new CulturalEventEntry)->getTable();
        $occTable = (new CulturalOccurrence)->getTable();

        $rows = CulturalOccurrence::query()
            ->from($occTable)
            ->join($entryTable, "{$entryTable}.id", '=', "{$occTable}.event_entry_id")
            ->whereIn("{$entryTable}.manifestation_id", $ids)
            ->where("{$entryTable}.status", CulturalEventEntry::STATUS_PUBLISHED)
            ->whereNotIn("{$occTable}.status", [
                CulturalOccurrence::STATUS_CANCELLED,
                CulturalOccurrence::STATUS_POSTPONED,
            ])
            ->orderBy("{$occTable}.datum")
            ->orderByRaw("COALESCE(NULLIF(TRIM({$occTable}.vrijeme_od), ''), '00:00:00')")
            ->orderBy("{$occTable}.id")
            ->select([
                "{$occTable}.*",
                "{$entryTable}.manifestation_id as period_manifestation_id",
            ])
            ->get();

        return $rows
            ->groupBy(fn (CulturalOccurrence $occurrence): int => (int) $occurrence->getAttribute('period_manifestation_id'))
            ->map(fn (Collection $group): Collection => $group->values());
    }

    /**
     * @return Collection<int, CulturalOccurrence>
     */
    private function relevantOccurrences(CulturalManifestation $manifestation): Collection
    {
        return $this->relevantOccurrencesGroupedByManifestation([(int) $manifestation->id])
            ->get((int) $manifestation->id, collect());
    }

    private function dateKey(CulturalOccurrence $occurrence): string
    {
        $datum = $occurrence->datum;

        return $datum instanceof CarbonInterface
            ? $datum->format('Y-m-d')
            : Carbon::parse((string) $datum)->format('Y-m-d');
    }

    private function startAt(CulturalOccurrence $occurrence): CarbonInterface
    {
        return $this->atTimeOrDayBoundary($occurrence, endOfDay: false);
    }

    private function endAt(CulturalOccurrence $occurrence): CarbonInterface
    {
        if ($this->hasTime($occurrence->vrijeme_do)) {
            $date = $this->dateKey($occurrence);
            $time = $this->normalizeTime((string) $occurrence->vrijeme_do);

            return Carbon::parse($date.' '.$time, (string) config('app.timezone'));
        }

        return $this->atTimeOrDayBoundary($occurrence, endOfDay: true);
    }

    private function atTimeOrDayBoundary(CulturalOccurrence $occurrence, bool $endOfDay): CarbonInterface
    {
        $date = Carbon::parse($this->dateKey($occurrence), (string) config('app.timezone'));

        if ($this->hasTime($occurrence->vrijeme_od)) {
            $time = $this->normalizeTime((string) $occurrence->vrijeme_od);

            return Carbon::parse($date->format('Y-m-d').' '.$time, (string) config('app.timezone'));
        }

        return $endOfDay ? $date->copy()->endOfDay() : $date->copy()->startOfDay();
    }

    private function hasTime(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        return trim((string) $value) !== '';
    }

    private function normalizeTime(string $value): string
    {
        $parts = explode(':', trim($value));
        $h = (int) ($parts[0] ?? 0);
        $m = (int) ($parts[1] ?? 0);
        $s = (int) ($parts[2] ?? 0);

        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}
