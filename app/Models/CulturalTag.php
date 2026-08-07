<?php

namespace App\Models;

use App\Models\Concerns\HasNormalizedCatalogName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Kataloška Oznaka (TS-007 Korak 1).
 * Bez pivot veze ka CulturalEvent u ovom koraku.
 */
class CulturalTag extends Model
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
}
