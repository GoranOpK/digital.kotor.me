<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForeignBranchRepresentative extends Model
{
    use SynchronizesJmbEncryption;

    protected $table = 'foreign_branch_representatives';

    public const DOCUMENT_JMB = 'jmb';

    public const DOCUMENT_PASSPORT = 'passport';

    protected $fillable = [
        'foreign_branch_identity_id',
        'first_name',
        'last_name',
        'id_document_type',
        'jmb',
        'passport_number',
        'passport_issuing_country_code',
    ];

    public function foreignBranch(): BelongsTo
    {
        return $this->belongsTo(ForeignBranchIdentity::class, 'foreign_branch_identity_id');
    }
}
