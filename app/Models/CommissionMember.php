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
        'confidentiality_declaration',
        'conflict_of_interest_declaration',
        'declarations_signed_at',
        'status',
    ];

    protected $casts = [
        'declarations_signed_at' => 'datetime',
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
}

