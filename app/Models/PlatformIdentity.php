<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlatformIdentity extends Model
{
    protected $table = 'platform_identities';

    public const SUBJECT_PHYSICAL_PERSON = 'physical_person';

    public const SUBJECT_LEGAL_ENTITY = 'legal_entity';

    public const SUBJECT_FOREIGN_BRANCH = 'foreign_branch';

    protected $fillable = [
        'user_id',
        'subject_type',
        'mobile_phone',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function physicalPerson(): HasOne
    {
        return $this->hasOne(PhysicalPersonIdentity::class);
    }

    public function legalEntity(): HasOne
    {
        return $this->hasOne(LegalEntityIdentity::class);
    }

    public function foreignBranch(): HasOne
    {
        return $this->hasOne(ForeignBranchIdentity::class);
    }
}
