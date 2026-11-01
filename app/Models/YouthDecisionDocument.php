<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;

/**
 * Trajni zapis generisane PM Word odluke.
 * Eloquent odbija izmjenu i brisanje. To ne zamjenjuje prava MySQL naloga.
 */
class YouthDecisionDocument extends Model
{
    public $timestamps = false;

    protected $table = 'youth_decision_documents';

    protected $fillable = [
        'competition_id',
        'generated_by',
        'generated_at',
        'storage_path',
        'content_sha256',
        'ranking_sha256',
    ];

    protected function casts(): array
    {
        return [
            'competition_id' => 'integer',
            'generated_by' => 'integer',
            'generated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $document): void {
            foreach (['content_sha256', 'ranking_sha256'] as $attribute) {
                $value = $document->getAttribute($attribute);
                if (! is_string($value) || ! preg_match('/^[0-9a-fA-F]{64}$/', $value)) {
                    throw new InvalidArgumentException(
                        'SHA-256 zapis PM odluke mora imati 64 heksadecimalna karaktera.'
                    );
                }
                $document->setAttribute($attribute, strtolower($value));
            }
        });

        static::updating(function (): void {
            throw new LogicException('Zapis PM odluke ne može da se mijenja.');
        });

        static::deleting(function (): void {
            throw new LogicException('Zapis PM odluke ne može da se briše.');
        });
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
