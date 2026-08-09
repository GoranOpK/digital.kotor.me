<?php

namespace App\Services\CulturalCalendar;

use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Kanonski SSOT ulaz za javne query-je Faze 6A (PO-TS9-08J / TS-009 §11–12).
 *
 * 6A-02: statusna javna vidljivost (fail-closed).
 * 6A-03: sort po prvom narednom kartično relevantnom Održavanju.
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
     * @return Builder<CulturalEventEntry>
     */
    public function entries(): Builder
    {
        return $this->base();
    }

    /**
     * Sort: next relevant OCC ASC (NULL last), zatim Entry.id ASC.
     *
     * @return Builder<CulturalEventEntry>
     */
    public function orderedByNextRelevantOccurrence(?CarbonInterface $now = null): Builder
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

        return $this->base()
            ->orderByRaw('('.$datumSub->toSql().') IS NULL')
            ->addBinding($datumSub->getBindings(), 'order')
            ->orderBy($datumSub)
            ->orderBy($timeSub)
            ->orderBy('cultural_event_entries.id');
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
