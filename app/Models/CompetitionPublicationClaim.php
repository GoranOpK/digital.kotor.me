<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitionPublicationClaim extends Model
{
    protected $fillable = [
        'competition_id',
        'calendar_year',
        'competition_type',
        'publication_slot',
        'claimed_at',
    ];

    protected $casts = [
        'calendar_year' => 'integer',
        'claimed_at' => 'datetime',
    ];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }
}
