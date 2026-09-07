<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LegalEntityIdentity extends Model
{
    protected $table = 'legal_entity_identities';

    public const FORM_OD = 'od';

    public const FORM_KD = 'kd';

    public const FORM_AD = 'ad';

    public const FORM_DOO = 'doo';

    public const FORM_NVO_ASSOCIATION = 'nvo_association';

    public const FORM_NVO_FOUNDATION = 'nvo_foundation';

    public const FORM_SPORTS_ORGANIZATION = 'sports_organization';

    protected $fillable = [
        'platform_identity_id',
        'legal_form',
        'legal_name',
        'pib',
        'crps_number',
        'street_and_number',
        'city',
    ];

    public function platformIdentity(): BelongsTo
    {
        return $this->belongsTo(PlatformIdentity::class);
    }

    public function authorizedPerson(): HasOne
    {
        return $this->hasOne(LegalEntityAuthorizedPerson::class);
    }
}
