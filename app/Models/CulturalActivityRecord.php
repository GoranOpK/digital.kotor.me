<?php

namespace App\Models;

use App\Exceptions\CulturalActivityRecordException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Centralni TS-012 audit zapis. Kreira se isključivo kroz CulturalActivityStore.
 * Nije Newsletter delivery ledger.
 */
class CulturalActivityRecord extends Model
{
    public const ACTOR_USER = 'user';

    public const ACTOR_SYSTEM = 'system';

    protected $table = 'cultural_activity_records';

    public const UPDATED_AT = null;

    /**
     * Nema mass-assignment API-ja. Writer koristi forceFill unutar store-a.
     *
     * @var list<string>
     */
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
            'context' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new CulturalActivityRecordException('Audit zapis je nepromjenjiv.');
        });

        static::deleting(function (): void {
            throw new CulturalActivityRecordException('Audit zapis je nepromjenjiv.');
        });
    }

    public function newEloquentBuilder($query): CulturalActivityRecordBuilder
    {
        return new CulturalActivityRecordBuilder($query);
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function isSystemActor(): bool
    {
        return $this->actor_type === self::ACTOR_SYSTEM;
    }
}
