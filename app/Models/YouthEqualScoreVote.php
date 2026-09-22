<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YouthEqualScoreVote extends Model
{
    public const FOR = 'for';

    public const AGAINST = 'against';

    protected $fillable = [
        'round_id',
        'canonical_seat_no',
        'commission_member_id',
        'user_id',
        'member_name',
        'vote_value',
        'recorded_by_user_id',
        'voted_at',
    ];

    protected $casts = [
        'canonical_seat_no' => 'integer',
        'voted_at' => 'datetime',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(YouthEqualScoreRound::class, 'round_id');
    }

    public function commissionMember(): BelongsTo
    {
        return $this->belongsTo(CommissionMember::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
