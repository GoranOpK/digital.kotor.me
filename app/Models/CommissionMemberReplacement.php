<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Canonical vacancy/replacement evidence (15 calendar days, warning only).
 */
class CommissionMemberReplacement extends Model
{
    protected $fillable = [
        'commission_id',
        'seat_no',
        'canonical_seat',
        'vacated_member_id',
        'replacement_member_id',
        'vacancy_reason',
        'vacancy_triggered_at',
        'due_at',
        'replaced_at',
        'late',
        'compliance_warning',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'seat_no' => 'integer',
        'vacancy_triggered_at' => 'datetime',
        'due_at' => 'datetime',
        'replaced_at' => 'datetime',
        'late' => 'boolean',
    ];

    public function commission(): BelongsTo
    {
        return $this->belongsTo(Commission::class);
    }

    public function vacatedMember(): BelongsTo
    {
        return $this->belongsTo(CommissionMember::class, 'vacated_member_id');
    }

    public function replacementMember(): BelongsTo
    {
        return $this->belongsTo(CommissionMember::class, 'replacement_member_id');
    }
}
