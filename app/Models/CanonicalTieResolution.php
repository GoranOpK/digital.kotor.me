<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * KN-SYNC-008 — Commission majority decision (2–1 or 3–0, all 3 seats voting) selecting which
 * applications of a shared-rank group at the funding boundary are supported. Ranks are unchanged.
 */
class CanonicalTieResolution extends Model
{
    public const PROPOSED = 'proposed';

    public const ADOPTED = 'adopted';

    public const REJECTED = 'rejected';

    public const IMMUTABLE_MESSAGE = 'Odluka Komisije o jednakom rangu je donesena i ne može se mijenjati.';

    protected $fillable = [
        'competition_id',
        'shared_rank',
        'tied_application_ids',
        'selected_application_ids',
        'status',
        'proposed_by_member_id',
    ];

    protected $casts = [
        'tied_application_ids' => 'array',
        'selected_application_ids' => 'array',
        'decided_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $resolution) {
            $dirty = array_values(array_diff(array_keys($resolution->getDirty()), ['updated_at']));
            if ($resolution->getOriginal('locked_at') !== null) {
                throw new LogicException(self::IMMUTABLE_MESSAGE);
            }
            if ($dirty === ['locked_at']) {
                return;
            }
            if ($resolution->getOriginal('status') !== self::PROPOSED || array_diff($dirty, ['status', 'decided_at']) !== []) {
                throw new LogicException(self::IMMUTABLE_MESSAGE);
            }
        });

        static::deleting(fn () => throw new LogicException(self::IMMUTABLE_MESSAGE));
    }

    /**
     * @return list<int>
     */
    public function tiedIds(): array
    {
        return self::normalizeIds($this->tied_application_ids);
    }

    /**
     * @return list<int>
     */
    public function selectedIds(): array
    {
        return self::normalizeIds($this->selected_application_ids);
    }

    /**
     * @return list<int>
     */
    public static function normalizeIds(mixed $ids): array
    {
        $ids = array_map('intval', is_array($ids) ? $ids : []);
        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }

    public function votes(): HasMany
    {
        return $this->hasMany(CanonicalTieVote::class, 'tie_resolution_id');
    }
}
