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

    public function hasBeenPublished(): bool
    {
        return Notice::query()
            ->where('source_type', 'competition_decision')
            ->where('source_id', $this->competition_id)
            ->where('source_object_id', $this->id)
            ->where('content_delivery', 'competition_decision_signed_copy')
            ->exists();
    }

    public static function competitionHasPublishedSignedCopy(int $competitionId): bool
    {
        return Notice::query()
            ->where('source_type', 'competition_decision')
            ->where('source_id', $competitionId)
            ->where('content_delivery', 'competition_decision_signed_copy')
            ->exists();
    }
}
