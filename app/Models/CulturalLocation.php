<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Kataloška Lokacija (TS-006 Korak 1).
 * Bez veze ka Održavanjima / CulturalEvent u ovom koraku.
 */
class CulturalLocation extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DEACTIVATED = 'deactivated';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_DEACTIVATED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'Aktivna',
        self::STATUS_DEACTIVATED => 'Deaktivirana',
    ];

    protected $fillable = [
        'naziv',
        'opis',
        'status',
    ];

    /**
     * Normalizacija naziva za poređenje duplikata (trim + case insensitive).
     */
    public static function normalizeName(string $naziv): string
    {
        return mb_strtolower(trim($naziv), 'UTF-8');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isDeactivated(): bool
    {
        return $this->status === self::STATUS_DEACTIVATED;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Da li postoji druga AKTIVNA lokacija sa istim normalizovanim nazivom.
     */
    public static function activeDuplicateExists(string $naziv, ?int $exceptId = null): bool
    {
        $normalized = self::normalizeName($naziv);

        if ($normalized === '') {
            return false;
        }

        $query = static::query()
            ->where('status', self::STATUS_ACTIVE)
            ->whereRaw('LOWER(TRIM(naziv)) = ?', [$normalized]);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOrderedByName(Builder $query): Builder
    {
        return $query->orderBy('naziv')->orderBy('id');
    }
}
