<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalEntityAuthorizedPerson extends Model
{
    protected $table = 'legal_entity_authorized_persons';

    public const DOCUMENT_JMB = 'jmb';

    public const DOCUMENT_PASSPORT = 'passport';

    protected $fillable = [
        'legal_entity_identity_id',
        'first_name',
        'last_name',
        'id_document_type',
        'jmb',
        'passport_number',
        'passport_issuing_country_code',
    ];

    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntityIdentity::class, 'legal_entity_identity_id');
    }
}
