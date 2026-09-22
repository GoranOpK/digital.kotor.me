<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YouthEqualScoreGroup extends Model
{
    protected $fillable = [
        'competition_id',
        'group_key',
        'full_score',
        'ranking_position',
        'applied_rule',
    ];

    protected $casts = [
        'full_score' => 'decimal:10',
        'ranking_position' => 'integer',
    ];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(YouthEqualScoreRound::class, 'group_id');
    }
}
