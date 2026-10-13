<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Canonical Commission conflict evidence (e.g. represented-company PIB collision).
 */
class CommissionGovernanceConflict extends Model
{
    public const TYPE_REPRESENTED_COMPANY_PIB = 'represented_company_pib';

    protected $fillable = [
        'commission_id',
        'user_id',
        'commission_member_id',
        'competition_id',
        'application_id',
        'conflict_type',
        'normalized_value',
        'reason',
        'detected_at',
        'detected_by_user_id',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
    ];

    public function commission(): BelongsTo
    {
        return $this->belongsTo(Commission::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CommissionMember::class, 'commission_member_id');
    }
}
