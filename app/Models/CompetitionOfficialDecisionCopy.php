<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionOfficialDecisionCopy extends Model
{
    protected $fillable = [
        'competition_id',
        'storage_path',
        'uploaded_by',
    ];

    protected $casts = [
        'competition_id' => 'integer',
        'uploaded_by' => 'integer',
    ];

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
