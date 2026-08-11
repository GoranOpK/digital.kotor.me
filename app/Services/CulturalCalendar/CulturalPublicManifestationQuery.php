<?php

namespace App\Services\CulturalCalendar;

use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\CulturalOccurrence;
use App\Services\CulturalManifestationDomain\ManifestationPeriodCalculator;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Javni read SSOT za Manifestacije (PHASE 6B-03 / PO-6B-08 / PO-6B-09).
 *
 * Odvojeno od CulturalPublicEventQuery — bez combined search (6B-04).
 */
final class CulturalPublicManifestationQuery
{
    public function __construct(
        private readonly ManifestationPeriodCalculator $periodCalculator = new ManifestationPeriodCalculator,
    ) {}

    /**
     * Aktivna javna lista: Objavljene + Otkazane dok period nije istekao.
     * Arhivirane / draft / pending / returned — van liste.
     */
    public function base(?CarbonInterface $now = null): Builder
    {
        $expiredCancelledIds = $this->expiredCancelledManifestationIds($now);

        return CulturalManifestation::query()
            ->where(function (Builder $query) use ($expiredCancelledIds): void {
                $query->where('status', CulturalManifestation::STATUS_PUBLISHED)
                    ->orWhere(function (Builder $cancelled) use ($expiredCancelledIds): void {
                        $cancelled->where('status', CulturalManifestation::STATUS_CANCELLED);
                        if ($expiredCancelledIds !== []) {
                            $cancelled->whereNotIn('id', $expiredCancelledIds);
                        }
                    });
            });
    }

    /**
     * Canonical detail: published | cancelled | archived.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findPublicForShow(int|string $id): CulturalManifestation
    {
        return CulturalManifestation::query()
            ->whereKey($id)
            ->whereIn('status', [
                CulturalManifestation::STATUS_PUBLISHED,
                CulturalManifestation::STATUS_CANCELLED,
                CulturalManifestation::STATUS_ARCHIVED,
            ])
            ->with($this->publicShowEagerLoad())
            ->firstOrFail();
    }

    /**
     * Aktivna lista sort: (1) datum početka, (2) naziv, (3) id.
     *
     * derived_period_start / derived_period_end: read-only MIN/MAX(datum) sa
     * istim OCC filterima kao ManifestationPeriodCalculator (za ordering + card label).
     */
    public function orderedForActiveList(?Builder $query = null): Builder
    {
        $query ??= $this->base();
        $mfTable = (new CulturalManifestation)->getTable();

        [$periodStartSql, $periodStartBindings] = $this->derivedPeriodAggregateSql('MIN');
        [$periodEndSql, $periodEndBindings] = $this->derivedPeriodAggregateSql('MAX');

        return $query
            ->select("{$mfTable}.*")
            ->selectRaw("{$periodStartSql} as derived_period_start", $periodStartBindings)
            ->selectRaw("{$periodEndSql} as derived_period_end", $periodEndBindings)
            ->withCount([
                'events as published_events_count' => function (Builder $events): void {
                    $events->where('status', CulturalEventEntry::STATUS_PUBLISHED);
                },
            ])
            ->with(['coverMedia', 'organizer'])
            ->orderByRaw('CASE WHEN derived_period_start IS NULL THEN 1 ELSE 0 END')
            ->orderBy('derived_period_start')
            ->orderBy("{$mfTable}.naziv")
            ->orderBy("{$mfTable}.id");
    }

    /**
     * PO-6B-09: MF se smije prikazati na Event detailu.
     */
    public function isPubliclyLinkable(?CulturalManifestation $manifestation): bool
    {
        if ($manifestation === null) {
            return false;
        }

        return in_array($manifestation->status, [
            CulturalManifestation::STATUS_PUBLISHED,
            CulturalManifestation::STATUS_CANCELLED,
            CulturalManifestation::STATUS_ARCHIVED,
        ], true);
    }

    /**
     * Izvedeni period za javni prikaz (reuse calculator).
     *
     * @return array{start: CarbonInterface, end: CarbonInterface}|null
     */
    public function period(CulturalManifestation $manifestation): ?array
    {
        return $this->periodCalculator->calculate($manifestation);
    }

    public function formatPeriodLabel(?array $period): ?string
    {
        if ($period === null) {
            return null;
        }

        $start = Carbon::parse($period['start'])->format('d.m.Y');
        $end = Carbon::parse($period['end'])->format('d.m.Y');

        return $start === $end ? $start : "{$start} – {$end}";
    }

    /**
     * Card label from list-query derived date aggregates (no per-row period query).
     */
    public function formatDerivedPeriodLabel(mixed $startDate, mixed $endDate): ?string
    {
        if ($startDate === null || $endDate === null || $startDate === '' || $endDate === '') {
            return null;
        }

        $start = Carbon::parse((string) $startDate)->format('d.m.Y');
        $end = Carbon::parse((string) $endDate)->format('d.m.Y');

        return $start === $end ? $start : "{$start} – {$end}";
    }

    /**
     * Program: javno vidljivi Eventi × Održavanja, sort datum → vrijeme → naziv → occ.id.
     *
     * @return Collection<string, Collection<int, array{
     *     occurrence: CulturalOccurrence,
     *     event: CulturalEventEntry,
     *     date_key: string,
     *     time_key: string,
     *     event_name: string
     * }>>
     */
    public function programGroupedByDate(CulturalManifestation $manifestation): Collection
    {
        $rows = collect();

        foreach ($manifestation->events as $event) {
            if (! $event->isPubliclyVisible()) {
                continue;
            }

            foreach ($event->occurrences as $occurrence) {
                $dateKey = $occurrence->datum instanceof CarbonInterface
                    ? $occurrence->datum->format('Y-m-d')
                    : Carbon::parse((string) $occurrence->datum)->format('Y-m-d');

                $timeRaw = trim((string) ($occurrence->vrijeme_od ?? ''));
                $timeKey = $timeRaw !== '' ? $timeRaw : '99:99:99';

                $rows->push([
                    'occurrence' => $occurrence,
                    'event' => $event,
                    'date_key' => $dateKey,
                    'time_key' => $timeKey,
                    'event_name' => (string) $event->naslov,
                ]);
            }
        }

        $sorted = $rows->sort(function (array $a, array $b): int {
            $dateCmp = strcmp($a['date_key'], $b['date_key']);
            if ($dateCmp !== 0) {
                return $dateCmp;
            }

            $timeCmp = strcmp($a['time_key'], $b['time_key']);
            if ($timeCmp !== 0) {
                return $timeCmp;
            }

            $nameCmp = strcmp(mb_strtolower($a['event_name']), mb_strtolower($b['event_name']));
            if ($nameCmp !== 0) {
                return $nameCmp;
            }

            return $a['occurrence']->id <=> $b['occurrence']->id;
        })->values();

        return $sorted->groupBy('date_key');
    }

    /**
     * Cancelled expiry via one batched OCC load + calculator SSOT (no per-MF period query).
     *
     * @return list<int>
     */
    private function expiredCancelledManifestationIds(?CarbonInterface $now = null): array
    {
        $cancelledIds = CulturalManifestation::query()
            ->where('status', CulturalManifestation::STATUS_CANCELLED)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($cancelledIds === []) {
            return [];
        }

        $grouped = $this->periodCalculator->relevantOccurrencesGroupedByManifestation($cancelledIds);

        $expired = [];
        foreach ($cancelledIds as $id) {
            $period = $this->periodCalculator->periodFromOccurrences(
                $grouped->get($id, collect())
            );

            if ($this->periodCalculator->hasExpiredPeriod($period, $now)) {
                $expired[] = $id;
            }
        }

        return $expired;
    }

    /**
     * @return array{0: string, 1: list<string>}
     */
    private function derivedPeriodAggregateSql(string $aggregate): array
    {
        $mfTable = (new CulturalManifestation)->getTable();
        $entryTable = (new CulturalEventEntry)->getTable();
        $occTable = (new CulturalOccurrence)->getTable();
        $fn = strtoupper($aggregate) === 'MAX' ? 'MAX' : 'MIN';

        $sql = "(
            SELECT {$fn}({$occTable}.datum)
            FROM {$occTable}
            INNER JOIN {$entryTable} ON {$entryTable}.id = {$occTable}.event_entry_id
            WHERE {$entryTable}.manifestation_id = {$mfTable}.id
              AND {$entryTable}.status = ?
              AND {$occTable}.status NOT IN (?, ?)
        )";

        return [
            $sql,
            [
                CulturalEventEntry::STATUS_PUBLISHED,
                CulturalOccurrence::STATUS_CANCELLED,
                CulturalOccurrence::STATUS_POSTPONED,
            ],
        ];
    }

    /**
     * @return array<int, string|array<string, mixed>>
     */
    private function publicShowEagerLoad(): array
    {
        return [
            'organizer',
            'coverMedia',
            'events' => function ($query): void {
                $query->whereIn('status', CulturalEventEntry::PUBLICLY_VISIBLE_STATUSES)
                    ->orderBy('naslov')
                    ->orderBy('id');
            },
            'events.occurrences' => function ($query): void {
                $query->orderBy('datum')
                    ->orderByRaw("COALESCE(NULLIF(TRIM(vrijeme_od), ''), '99:99:99')")
                    ->orderBy('id');
            },
            'events.occurrences.location',
        ];
    }
}
