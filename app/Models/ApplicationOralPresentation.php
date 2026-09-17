<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationOralPresentation extends Model
{
    public const APPLICATION_UNIQUE_INDEX = 'application_oral_presentations_application_id_unique';

    protected $fillable = [
        'commission_session_id',
        'application_id',
        'scheduled_at',
        'held_at',
        'applicant_attended',
        'notes',
        'completed_at',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'held_at' => 'datetime',
        'completed_at' => 'datetime',
        'applicant_attended' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CommissionSession::class, 'commission_session_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function isDraft(): bool
    {
        return $this->completed_at === null;
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isNoShow(): bool
    {
        return $this->isCompleted() && $this->applicant_attended === false;
    }

    public function applicantAttended(): bool
    {
        return $this->applicant_attended === true;
    }
}
