<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ForeignBranchIdentity extends Model
{
    protected $table = 'foreign_branch_identities';

    protected $fillable = [
        'platform_identity_id',
        'foreign_company_name',
        'branch_name_in_montenegro',
        'pib',
        'crps_number',
        'street_and_number',
        'city',
    ];

    public function platformIdentity(): BelongsTo
    {
        return $this->belongsTo(PlatformIdentity::class);
    }

    public function representative(): HasOne
    {
        return $this->hasOne(ForeignBranchRepresentative::class);
    }
}
