<?php

namespace App\Models;

use App\Models\Concerns\HasNormalizedCatalogName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Kataloška Kategorija (TS-007 Korak 1).
 * Bez veze ka CulturalEvent u ovom koraku.
 */
class CulturalCategory extends Model
{
    use HasFactory;
    use HasNormalizedCatalogName;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    public const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'Aktivna',
        self::STATUS_INACTIVE => 'Neaktivna',
    ];

    /** Zabranjena vrijednost u novom katalogu (TS7-PO-05). */
    public const FORBIDDEN_NAME = 'Nešto drugo';

    protected $fillable = [
        'naziv',
        'opis',
        'status',
    ];

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public static function isForbiddenName(string $naziv): bool
    {
        return self::normalizeName($naziv) === self::normalizeName(self::FORBIDDEN_NAME);
    }
}
