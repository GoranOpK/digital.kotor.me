<?php

namespace App\Services\CulturalCategory;

use App\Models\CulturalCategory;
use Illuminate\Support\Collection;

/**
 * Idempotentno uspostavljanje CAT-14 (aktivni kanonski katalog).
 */
final class CanonicalCulturalCategorySync
{
    /**
     * @return array{
     *     canonical_total: int,
     *     created: list<string>,
     *     skipped: list<string>,
     *     reactivated: list<string>,
     *     inactive_conflicts: list<string>,
     *     duplicate_active_conflicts: list<string>,
     *     coverage: int,
     *     complete: bool
     * }
     */
    public function sync(bool $dryRun = false, bool $reactivateInactive = false): array
    {
        $created = [];
        $skipped = [];
        $reactivated = [];
        $inactiveConflicts = [];
        $duplicateActiveConflicts = [];

        foreach (CanonicalCulturalCategoryCatalog::names() as $canonicalName) {
            $normalized = CulturalCategory::normalizeName($canonicalName);
            $matches = $this->findByNormalizedName($normalized);

            $actives = $matches->filter(fn (CulturalCategory $c) => $c->isActive())->values();
            $inactives = $matches->filter(fn (CulturalCategory $c) => $c->isInactive())->values();

            if ($actives->count() > 1) {
                $duplicateActiveConflicts[] = $canonicalName;
                continue;
            }

            if ($actives->count() === 1) {
                $skipped[] = $canonicalName;
                continue;
            }

            // Nema aktivne.
            if ($inactives->isNotEmpty()) {
                if (! $reactivateInactive) {
                    $inactiveConflicts[] = $canonicalName;
                    continue;
                }

                /** @var CulturalCategory $target */
                $target = $inactives->sortBy('id')->first();
                if (! $dryRun) {
                    $target->update([
                        'status' => CulturalCategory::STATUS_ACTIVE,
                    ]);
                }
                $reactivated[] = $canonicalName;
                continue;
            }

            // Ne postoji nijedan zapis.
            if (! $dryRun) {
                CulturalCategory::create([
                    'naziv' => $canonicalName,
                    'opis' => null,
                    'status' => CulturalCategory::STATUS_ACTIVE,
                ]);
            }
            $created[] = $canonicalName;
        }

        // Coverage = kanonski nazivi koji imaju ≥1 aktivni zapis nakon odluke.
        $coverage = count($skipped) + count($created) + count($reactivated) + count($duplicateActiveConflicts);
        $total = CanonicalCulturalCategoryCatalog::count();

        return [
            'canonical_total' => $total,
            'created' => $created,
            'skipped' => $skipped,
            'reactivated' => $reactivated,
            'inactive_conflicts' => $inactiveConflicts,
            'duplicate_active_conflicts' => $duplicateActiveConflicts,
            'coverage' => $coverage,
            'complete' => $coverage === $total
                && $inactiveConflicts === []
                && $duplicateActiveConflicts === [],
        ];
    }

    /**
     * @return Collection<int, CulturalCategory>
     */
    private function findByNormalizedName(string $normalized): Collection
    {
        if ($normalized === '') {
            return collect();
        }

        return CulturalCategory::query()
            ->whereRaw('LOWER(TRIM(naziv)) = ?', [$normalized])
            ->orderBy('id')
            ->get();
    }
}
