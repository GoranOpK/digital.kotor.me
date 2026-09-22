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

    public function oralPresentations(): HasMany
    {
        return $this->hasMany(ApplicationOralPresentation::class);
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
     * Aktuelna validacija nacrta i confirm POST-a: član mora biti trenutno active.
     *
     * @return Collection<int, CommissionMember>
     */
    public function validPresentMembers(?Competition $competition = null): Collection
    {
        return $this->filterPresentMembers($competition, requireActive: true);
    }

    public function validPresentCount(?Competition $competition = null): int
    {
        return $this->validPresentMembers($competition)->count();
    }

    /**
     * Istorijski potvrđeno prisustvo nakon completed_at.
     * Trenutni status=inactive ne poništava sačuvani commission_member red.
     *
     * @return Collection<int, CommissionMember>
     */
    public function historicalConfirmedPresentMembers(?Competition $competition = null): Collection
    {
        if (! $this->isConfirmed()) {
            return collect();
        }

        return $this->filterPresentMembers($competition, requireActive: false);
    }

    public function historicalConfirmedPresentCount(?Competition $competition = null): int
    {
        return $this->historicalConfirmedPresentMembers($competition)->count();
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

        return $this->historicalConfirmedPresentCount($competition) >= $quorum;
    }

    public function meetsSecondSessionAttendance(?Competition $competition = null): bool
    {
        $competition ??= $this->competition;
        $required = CommissionProfileConfig::for($competition?->type)->allMembersRequiredCount;
        if ($required < 1) {
            return false;
        }

        $present = $this->presentMembersForCurrentState($competition);
        if ($present->count() !== $required) {
            return false;
        }

        return $present->contains(
            fn (CommissionMember $member) => $member->position === 'predsjednik'
        );
    }

    public function chairmanIsPresent(?Competition $competition = null): bool
    {
        return $this->presentMembersForCurrentState($competition)->contains(
            fn (CommissionMember $member) => $member->position === 'predsjednik'
        );
    }

    /**
     * @return Collection<int, CommissionMember>
     */
    private function presentMembersForCurrentState(?Competition $competition = null): Collection
    {
        return $this->isConfirmed()
            ? $this->historicalConfirmedPresentMembers($competition)
            : $this->validPresentMembers($competition);
    }

    /**
     * @return Collection<int, CommissionMember>
     */
    private function filterPresentMembers(?Competition $competition, bool $requireActive): Collection
    {
        $competition ??= $this->competition;
        $config = CommissionProfileConfig::for($competition?->type);

        $this->loadMissing(['attendances.member', 'commission']);

        $seenSeats = [];

        return $this->attendances
            ->filter(fn (CommissionSessionAttendance $row) => $row->present)
            ->map(fn (CommissionSessionAttendance $row) => $row->member)
            ->filter(function (?CommissionMember $member) use ($config, $requireActive, &$seenSeats) {
                if ($member === null) {
                    return false;
                }
                if ((int) $member->commission_id !== (int) $this->commission_id) {
                    return false;
                }
                if ($requireActive && $member->status !== 'active') {
                    return false;
                }

                $seat = $member->canonicalSeatNumber();
                if ($seat === null || ! $config->allowsSeat($seat)) {
                    return false;
                }
                if (isset($seenSeats[$seat])) {
                    return false;
                }
                $seenSeats[$seat] = true;

                return true;
            })
            ->unique('id')
            ->values();
    }
}
