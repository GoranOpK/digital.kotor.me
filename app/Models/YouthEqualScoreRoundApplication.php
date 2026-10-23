<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YouthEqualScoreRoundApplication extends Model
{
    protected $fillable = [
        'round_id',
        'application_id',
        'business_stage',
        'requested_amount',
        'draft_amount',
        'applied_cap_percent',
        'selected',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'draft_amount' => 'decimal:2',
        'applied_cap_percent' => 'integer',
        'selected' => 'boolean',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(YouthEqualScoreRound::class, 'round_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
