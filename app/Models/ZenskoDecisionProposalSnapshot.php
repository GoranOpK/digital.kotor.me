<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Trajno sačuvan HTML Predlog odluke za žensko preduzetništvo.
 * Eloquent odbija izmjenu i brisanje. To ne zamjenjuje prava MySQL naloga.
 */
class ZenskoDecisionProposalSnapshot extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'zensko_decision_proposal_snapshots';

    protected $fillable = [
        'competition_id',
        'storage_path',
        'content_sha256',
        'closed_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'competition_id' => 'integer',
            'closed_by' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Snimak Predloga odluke ne može da se mijenja.');
        });

        static::deleting(function (): void {
            throw new LogicException('Snimak Predloga odluke ne može da se briše.');
        });
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
