<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationPrigovorMappingCorrection extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'application_id',
        'prigovor_id',
        'from_criterion',
        'to_criterion',
        'before_snapshot',
        'after_snapshot',
        'reason',
        'corrected_by_commission_member_id',
        'corrected_by_user_id',
        'corrected_by_name',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before_snapshot' => 'array',
            'after_snapshot' => 'array',
            'created_at' => 'datetime',
            'from_criterion' => 'integer',
            'to_criterion' => 'integer',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function prigovor(): BelongsTo
    {
        return $this->belongsTo(ApplicationPrigovor::class, 'prigovor_id');
    }

    public function correctedByCommissionMember(): BelongsTo
    {
        return $this->belongsTo(CommissionMember::class, 'corrected_by_commission_member_id');
    }

    public function correctedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by_user_id');
    }
}
