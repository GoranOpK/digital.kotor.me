<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Zajednička normalizacija i provjera aktivnih duplikata za kataloge (TS-007 / TS-006).
 */
trait HasNormalizedCatalogName
{
    /**
     * Normalizacija naziva: trim + case-insensitive + UTF-8.
     */
    public static function normalizeName(string $naziv): string
    {
        return mb_strtolower(trim($naziv), 'UTF-8');
    }

    /**
     * Da li postoji druga AKTIVNA stavka sa istim normalizovanim nazivom.
     * Model mora imati STATUS_ACTIVE.
     */
    public static function activeDuplicateExists(string $naziv, ?int $exceptId = null): bool
    {
        $normalized = static::normalizeName($naziv);

        if ($normalized === '') {
            return false;
        }

        $query = static::query()
            ->where('status', static::STATUS_ACTIVE)
            ->whereRaw('LOWER(TRIM(naziv)) = ?', [$normalized]);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', static::STATUS_ACTIVE);
    }

    public function scopeOrderedByName(Builder $query): Builder
    {
        return $query->orderBy('naziv')->orderBy('id');
    }
}
