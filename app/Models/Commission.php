<?php

namespace App\Models;

use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CommissionProfileConfig;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'year',
        'start_date',
        'end_date',
        'status',
        'profile_type',
        'appointment_reference',
        'appointed_at',
        'mandate_starts_at',
        'mandate_ends_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'year' => 'integer',
        'appointed_at' => 'datetime',
        'mandate_starts_at' => 'datetime',
        'mandate_ends_at' => 'datetime',
    ];

    /**
     * Veza sa članovima komisije
     */
    public function members(): HasMany
    {
        return $this->hasMany(CommissionMember::class);
    }

    /**
     * Aktivni članovi komisije
     */
    public function activeMembers(): HasMany
    {
        return $this->hasMany(CommissionMember::class)->where('status', 'active');
    }

    /**
     * Proverava da li komisija ima kvorum (većina članova)
     */
    public function hasQuorum(): bool
    {
        $totalMembers = $this->activeMembers()->count();
        return $totalMembers >= 3; // Većina od 5 članova je 3
    }

    /**
     * Veza: komisija ima više konkursa
     */
    public function competitions()
    {
        return $this->hasMany(Competition::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CommissionSession::class);
    }

    /**
     * Trajni profil Komisije iz `profile_type`. Ne nagađa iz Poziva ni sastava.
     */
    public function assignedProfileType(): ?string
    {
        if (in_array($this->profile_type, CommissionProfileConfig::COMMISSION_PROFILE_TYPES, true)) {
            return $this->profile_type;
        }

        return null;
    }

    public function profileConfig(): CommissionProfileConfig
    {
        return CommissionProfileConfig::for($this->assignedProfileType());
    }

    /**
     * Canonical Commissions are marked at establishment and stay canonical regardless of assignment.
     */
    public function isCanonicalProfile(): bool
    {
        return $this->profile_type === CompetitionProfileResolver::TYPE_CANONICAL;
    }

    public function replacements(): HasMany
    {
        return $this->hasMany(CommissionMemberReplacement::class);
    }

    public function governanceConflicts(): HasMany
    {
        return $this->hasMany(CommissionGovernanceConflict::class);
    }

    /**
     * Da li komisija ima aktivnog zamjenskog člana.
     */
    public function hasActiveSubstitute(): bool
    {
        return $this->members()
            ->where('is_substitute', true)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Da li postoji aktivan zamjenski član za dati slot (1–5).
     */
    public function hasActiveSubstituteForSlot(int $slot): bool
    {
        return $this->members()
            ->where('is_substitute', true)
            ->where('status', 'active')
            ->where('replaces_member_number', $slot)
            ->exists();
    }

    /**
     * Ocjenjivanje je u toku na barem jednom dodijeljenom konkursu.
     */
    public function isEvaluationInProgressOnAnyCompetition(): bool
    {
        $this->loadMissing('competitions');

        return $this->competitions
            ->contains(function (Competition $competition) {
                if (in_array($competition->status, ['completed'])) {
                    return false;
                }

                return $competition->isApplicationDeadlinePassed()
                    && !$competition->isRankingFormed();
            });
    }

    /**
     * Donošenje odluka (rang lista formirana, predsjednik još nije završio odluke).
     */
    public function isDecisionMakingInProgressOnAnyCompetition(): bool
    {
        $this->loadMissing('competitions');

        return $this->competitions
            ->contains(function (Competition $competition) {
                return $competition->isRankingFormed()
                    && !$competition->hasChairmanCompletedDecisions();
            });
    }
}

