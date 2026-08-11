<?php

namespace App\Services\CulturalCalendar;

use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * PHASE 6B-04 — Pretraga + Tip sadržaja orchestrator.
 *
 * Reuse:
 * - CulturalPublicEventQuery (6A Event subset)
 * - CulturalPublicManifestationQuery (6B-03 MF subset)
 *
 * PO-6B-10: Tip=Sve global ordering by temporal_key (not grouped by type).
 */
final class CulturalPublicSearchQuery
{
    public const TIP_SVE = 'sve';

    public const TIP_DOGADJAJI = 'dogadjaji';

    public const TIP_MANIFESTACIJE = 'manifestacije';

    public const TYPE_EVENT = 'event';

    public const TYPE_MANIFESTATION = 'manifestation';

    public function __construct(
        private readonly CulturalPublicEventQuery $eventQuery = new CulturalPublicEventQuery,
        private readonly CulturalPublicManifestationQuery $manifestationQuery = new CulturalPublicManifestationQuery,
    ) {}

    /**
     * PO-6B-01: bez tip / invalid → Sve.
     */
    public function normalizeTip(mixed $tip): string
    {
        if (! is_string($tip) || $tip === '') {
            return self::TIP_SVE;
        }

        $normalized = strtolower(trim($tip));

        return match ($normalized) {
            self::TIP_DOGADJAJI => self::TIP_DOGADJAJI,
            self::TIP_MANIFESTACIJE => self::TIP_MANIFESTACIJE,
            default => self::TIP_SVE,
        };
    }

    public function eventFiltersApplicable(string $tip): bool
    {
        return $tip === self::TIP_DOGADJAJI;
    }

    /**
     * Tip=Manifestacije — MF-only list with existing 6B-03 ordering + PO-6B-05 q.
     *
     * @return LengthAwarePaginator<int, CulturalManifestation>
     */
    public function paginateManifestations(?string $q, int $perPage = 12, ?CarbonInterface $now = null): LengthAwarePaginator
    {
        $query = $this->manifestationQuery->filterByQ($q, $this->manifestationQuery->base($now));

        return $this->manifestationQuery
            ->orderedForActiveList($query)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Tip=Sve — combined Event+MF feed (PO-6B-04 / PO-6B-10).
     * Event-specific filters are intentionally not applied here.
     *
     * @return LengthAwarePaginator<int, CulturalPublicSearchHit>
     */
    public function paginateCombined(
        ?string $q,
        int $perPage = 12,
        ?int $page = null,
        ?CarbonInterface $now = null,
        array $queryParams = [],
    ): LengthAwarePaginator {
        $page = max(1, $page ?? (int) request()->input('page', 1));
        $perPage = max(1, $perPage);

        $eventBuilder = $this->eventQuery->filterByQ($q, $this->eventQuery->base());
        $mfBuilder = $this->manifestationQuery->filterByQ($q, $this->manifestationQuery->base($now));

        $hits = collect()
            ->merge(
                $this->eventQuery->combinedSortProjections($eventBuilder, $now)
                    ->map(fn (object $row): CulturalPublicSearchHit => new CulturalPublicSearchHit(
                        type: self::TYPE_EVENT,
                        id: (int) $row->id,
                        title: (string) $row->title,
                        temporalKey: $row->temporal_key,
                    ))
            )
            ->merge(
                $this->manifestationQuery->combinedSortProjections($mfBuilder)
                    ->map(fn (object $row): CulturalPublicSearchHit => new CulturalPublicSearchHit(
                        type: self::TYPE_MANIFESTATION,
                        id: (int) $row->id,
                        title: (string) $row->title,
                        temporalKey: $row->temporal_key,
                    ))
            );

        $sorted = $this->sortHits($hits);
        $total = $sorted->count();
        $slice = $sorted->slice(($page - 1) * $perPage, $perPage)->values();
        $hydrated = $this->hydrateHits($slice);

        return new LengthAwarePaginator(
            $hydrated,
            $total,
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $queryParams,
                'pageName' => 'page',
            ]
        );
    }

    /**
     * PO-6B-10 sort:
     * 1) temporal_key ASC (null last)
     * 2) naziv ASC
     * 3) tip tie-breaker (event before manifestation — stable, not business priority)
     * 4) id ASC
     *
     * @param  Collection<int, CulturalPublicSearchHit>  $hits
     * @return Collection<int, CulturalPublicSearchHit>
     */
    public function sortHits(Collection $hits): Collection
    {
        return $hits
            ->sort(function (CulturalPublicSearchHit $a, CulturalPublicSearchHit $b): int {
                $aNull = $a->temporalKey === null ? 1 : 0;
                $bNull = $b->temporalKey === null ? 1 : 0;
                if ($aNull !== $bNull) {
                    return $aNull <=> $bNull;
                }

                if ($a->temporalKey !== null && $b->temporalKey !== null) {
                    $dateCmp = strcmp($a->temporalKey, $b->temporalKey);
                    if ($dateCmp !== 0) {
                        return $dateCmp;
                    }
                }

                $nameCmp = strcmp(mb_strtolower($a->title), mb_strtolower($b->title));
                if ($nameCmp !== 0) {
                    return $nameCmp;
                }

                $typeCmp = strcmp($a->type, $b->type);
                if ($typeCmp !== 0) {
                    return $typeCmp;
                }

                return $a->id <=> $b->id;
            })
            ->values();
    }

    /**
     * @param  Collection<int, CulturalPublicSearchHit>  $hits
     * @return Collection<int, CulturalPublicSearchHit>
     */
    private function hydrateHits(Collection $hits): Collection
    {
        if ($hits->isEmpty()) {
            return $hits;
        }

        $eventIds = $hits
            ->filter(fn (CulturalPublicSearchHit $hit): bool => $hit->isEvent())
            ->map(fn (CulturalPublicSearchHit $hit): int => $hit->id)
            ->all();
        $mfIds = $hits
            ->filter(fn (CulturalPublicSearchHit $hit): bool => $hit->isManifestation())
            ->map(fn (CulturalPublicSearchHit $hit): int => $hit->id)
            ->all();

        $events = $eventIds === []
            ? collect()
            : CulturalEventEntry::query()
                ->whereIn('id', $eventIds)
                ->with(['category', 'coverMedia', 'occurrences.location'])
                ->get()
                ->keyBy('id');

        $manifestations = $mfIds === []
            ? collect()
            : $this->manifestationQuery
                ->withDerivedPeriodSelect(
                    CulturalManifestation::query()->whereIn('id', $mfIds)
                )
                ->withCount([
                    'events as published_events_count' => function ($events): void {
                        $events->where('status', CulturalEventEntry::STATUS_PUBLISHED);
                    },
                ])
                ->with(['coverMedia', 'organizer'])
                ->get()
                ->keyBy('id');

        return $hits->map(function (CulturalPublicSearchHit $hit) use ($events, $manifestations): CulturalPublicSearchHit {
            $model = $hit->type === self::TYPE_EVENT
                ? $events->get($hit->id)
                : $manifestations->get($hit->id);

            return $hit->withModel($model);
        })->filter(fn (CulturalPublicSearchHit $hit): bool => $hit->model !== null)->values();
    }
}
