<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ApplicationEliminatoryNotice extends Model
{
    public const APPEAL_WINDOW_DAYS = 3;

    protected $fillable = [
        'application_id',
        'eliminatory_check_id',
        'sent_at',
        'portal_recorded_at',
        'mail_sent_at',
        'mail_failed_at',
        'reasons_snapshot',
        'note_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'portal_recorded_at' => 'datetime',
            'mail_sent_at' => 'datetime',
            'mail_failed_at' => 'datetime',
            'reasons_snapshot' => 'array',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function eliminatoryCheck(): BelongsTo
    {
        return $this->belongsTo(ApplicationEliminatoryCheck::class, 'eliminatory_check_id');
    }

    public function prigovorDeadlineAt(): Carbon
    {
        return $this->sent_at->copy()->addDays(self::APPEAL_WINDOW_DAYS);
    }

    public function prigovorWindowIsOpen(?Carbon $at = null): bool
    {
        $at ??= now();

        return $at->lte($this->prigovorDeadlineAt());
    }
}
