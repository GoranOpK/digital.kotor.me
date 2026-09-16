<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionSessionAttendance extends Model
{
    protected $fillable = [
        'commission_session_id',
        'commission_member_id',
        'present',
    ];

    protected $casts = [
        'present' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CommissionSession::class, 'commission_session_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CommissionMember::class, 'commission_member_id');
    }
}
