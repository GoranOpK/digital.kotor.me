<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YouthEqualScoreRound extends Model
{
    public const OUTCOME_ADOPTED = 'adopted';

    public const OUTCOME_REJECTED = 'rejected';

    protected $fillable = [
        'group_id',
        'round_no',
        'justification',
        'budget_before',
        'proposal_total',
        'locked_at',
        'outcome',
        'created_by_user_id',
        'created_by_member_id',
        'created_by_name',
        'locked_by_user_id',
        'locked_by_member_id',
        'locked_by_name',
    ];

    protected $casts = [
        'round_no' => 'integer',
        'budget_before' => 'decimal:2',
        'proposal_total' => 'decimal:2',
        'locked_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(YouthEqualScoreGroup::class, 'group_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(YouthEqualScoreRoundApplication::class, 'round_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(YouthEqualScoreVote::class, 'round_id');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }
}
