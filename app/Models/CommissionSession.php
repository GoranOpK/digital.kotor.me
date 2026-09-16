<?php

namespace App\Models;

use App\Support\CommissionProfileConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class CommissionSession extends Model
{
    public const TYPE_FIRST = 'first';

    public const TYPE_SECOND = 'second';

    public const TYPE_THIRD = 'third';

    protected $fillable = [
        'competition_id',
        'commission_id',
        'session_type',
        'held_at',
        'completed_at',
        'recorded_by_user_id',
        'notes',
    ];

    protected $casts = [
        'held_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function commission(): BelongsTo
    {
        return $this->belongsTo(Commission::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(CommissionSessionAttendance::class);
    }

    public function isDraft(): bool
    {
        return $this->completed_at === null;
    }

    public function isConfirmed(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * @return Collection<int, CommissionMember>
     */
    public function validPresentMembers(?Competition $competition = null): Collection
    {
        $competition ??= $this->competition;
        $config = CommissionProfileConfig::for($competition?->type);

        $this->loadMissing(['attendances.member', 'commission']);

        return $this->attendances
            ->filter(fn (CommissionSessionAttendance $row) => $row->present)
            ->map(fn (CommissionSessionAttendance $row) => $row->member)
            ->filter(function (?CommissionMember $member) use ($config) {
                if ($member === null) {
                    return false;
                }
                if ((int) $member->commission_id !== (int) $this->commission_id) {
                    return false;
                }
                if ($member->status !== 'active') {
                    return false;
                }

                $seat = $member->canonicalSeatNumber();
                if ($seat === null || ! $config->allowsSeat($seat)) {
                    return false;
                }

                return true;
            })
            ->unique('id')
            ->values();
    }

    public function validPresentCount(?Competition $competition = null): int
    {
        return $this->validPresentMembers($competition)->count();
    }

    public function meetsFirstSessionQuorum(?Competition $competition = null): bool
    {
        $competition ??= $this->competition;
        $quorum = CommissionProfileConfig::for($competition?->type)->firstSessionQuorum;

        if ($quorum === null) {
            return true;
        }

        if (! $this->isConfirmed()) {
            return false;
        }

        return $this->validPresentCount($competition) >= $quorum;
    }
}
