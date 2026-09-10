<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhysicalPersonIdentity extends Model
{
    use SynchronizesJmbEncryption;

    protected $table = 'physical_person_identities';

    public const RESIDENTIAL_RESIDENT = 'resident';

    public const RESIDENTIAL_NON_RESIDENT = 'non_resident';

    public const DOCUMENT_JMB = 'jmb';

    public const DOCUMENT_PASSPORT = 'passport';

    protected $fillable = [
        'platform_identity_id',
        'first_name',
        'last_name',
        'residential_status',
        'id_document_type',
        'jmb',
        'passport_number',
        'residence_country_code',
        'is_entrepreneur',
        'entrepreneur_business_name',
        'pib',
        'crps_number',
        'street_and_number',
        'city',
    ];

    protected function casts(): array
    {
        return [
            'is_entrepreneur' => 'boolean',
        ];
    }

    public function platformIdentity(): BelongsTo
    {
        return $this->belongsTo(PlatformIdentity::class);
    }
}
