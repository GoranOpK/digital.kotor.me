<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * KN-SYNC-015 — čl.14 metadata of one canonical upload: the declared alternative
 * (PDV / IOPPD / godišnji računi) and the issue date for the 30-day documents.
 *
 * Frozen at canonical FINAL; a frozen row cannot be updated or deleted.
 */
class CanonicalDocumentEvidence extends Model
{
    public const FROZEN_MESSAGE = 'Prilog je zamrznut konačnom predajom i ne može se mijenjati.';

    protected $table = 'canonical_document_evidence';

    protected $fillable = [
        'application_document_id',
        'application_id',
        'document_type',
        'variant',
        'issued_on',
    ];

    protected $casts = [
        'issued_on' => 'date',
        'frozen_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $evidence) {
            if ($evidence->getOriginal('frozen_at') !== null) {
                throw new LogicException(self::FROZEN_MESSAGE);
            }
        });

        static::deleting(function (self $evidence) {
            if ($evidence->getOriginal('frozen_at') !== null) {
                throw new LogicException(self::FROZEN_MESSAGE);
            }
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ApplicationDocument::class, 'application_document_id');
    }
}
