<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'commission_id',
        'user_id',
        'name',
        'position',
        'member_type',
        'organization',
        'is_substitute',
        'replaces_member_number',
        'confidentiality_declaration',
        'conflict_of_interest_declaration',
        'declarations_signed_at',
        'status',
    ];

    protected $casts = [
        'declarations_signed_at' => 'datetime',
        'is_substitute' => 'boolean',
        'replaces_member_number' => 'integer',
    ];

    /**
     * Veza sa komisijom
     */
    public function commission(): BelongsTo
    {
        return $this->belongsTo(Commission::class);
    }

    /**
     * Veza sa korisnikom (ako je član iz sistema)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Veza sa ocjenama (evaluacije)
     */
    public function evaluationScores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class);
    }

    /**
     * Proverava da li su izjave potpisane
     */
    public function hasSignedDeclarations(): bool
    {
        return !is_null($this->declarations_signed_at) 
            && !empty($this->confidentiality_declaration)
            && !empty($this->conflict_of_interest_declaration);
    }

    /**
     * Aktivan član komisije za korisnika na konkretnoj komisiji.
     */
    public static function activeForCommission(int $userId, int $commissionId): ?self
    {
        return static::where('user_id', $userId)
            ->where('commission_id', $commissionId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Aktivno članstvo ulogovanog člana komisije (bez konkursa u kontekstu).
     *
     * Koristi se na dashboardu i listi ocjenjivanja. Za konkurs podrške ženskom
     * preduzetništvu postoji jedna komisija po mandatu; više konkursa dijeli isti commission_id.
     */
    public static function activeMembershipForUser(int $userId): ?self
    {
        return static::where('user_id', $userId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Oznaka uloge za zamjenu (slot 1–5).
     */
    public static function replacementSlotLabel(int $slot): string
    {
        return match ($slot) {
            1 => 'predsjednik komisije',
            2 => 'član 2',
            3 => 'član 3',
            4 => 'član 4',
            5 => 'član 5',
            default => 'član komisije',
        };
    }
}

