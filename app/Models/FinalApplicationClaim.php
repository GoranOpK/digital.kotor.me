<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OTD-004 / KN-SYNC-008A — successful canonical FINAL claim.
 *
 * One claim per (user, competition, claim_scope) where claim_scope = subsidy type, or '*'
 * for untyped historical claims (still one per user + competition). Per beneficiary PIB:
 * one claim per subsidy type and at most two slots per competition — čl.19.
 * Claim is authoritative via DB unique constraints and survives later statuses.
 */
class FinalApplicationClaim extends Model
{
    protected $fillable = [
        'user_id',
        'competition_id',
        'application_id',
        'subsidy_type',
        'beneficiary_pib',
        'beneficiary_slot',
        'claimed_at',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
