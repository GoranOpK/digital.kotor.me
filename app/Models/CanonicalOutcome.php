<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * KN-SYNC-008 — canonical MMSP third-session outcome for one ranked application.
 * Draft until the complete outcome set is confirmed; confirmed rows are immutable.
 */
class CanonicalOutcome extends Model
{
    public const SUPPORT = 'support';

    public const REJECT = 'reject';

    public const REASON_BELOW_30 = 'below_30';

    public const REASON_COMMISSION_REJECTED = 'commission_rejected';

    public const REASON_INSUFFICIENT_FUNDS = 'insufficient_funds';

    public const REASON_DUPLICATE_BENEFICIARY_SUPPORT = 'duplicate_beneficiary_support';

    public const REASON_TIE_BOUNDARY_NOT_SELECTED = 'tie_boundary_not_selected';

    /** @var array<string, string> */
    public const REASON_LABELS = [
        self::REASON_BELOW_30 => 'Konačna ocjena ispod 30 bodova (čl.22)',
        self::REASON_COMMISSION_REJECTED => 'Odbijeno odlukom Komisije',
        self::REASON_INSUFFICIENT_FUNDS => 'Nedovoljno raspoloživih sredstava (čl.19)',
        self::REASON_DUPLICATE_BENEFICIARY_SUPPORT => 'Isti podnosilac podržan po drugoj prijavi (čl.19)',
        self::REASON_TIE_BOUNDARY_NOT_SELECTED => 'Nije izabrano pri jednakom rangu na granici sredstava',
    ];

    public const CONFIRMED_MESSAGE = 'Ishod je konačno potvrđen i ne može se mijenjati.';

    protected $fillable = [
        'competition_id',
        'application_id',
        'decision',
        'approved_amount',
        'rejection_reason',
        'justification',
        'remarks',
        'recorded_by_member_id',
    ];

    protected $casts = [
        'approved_amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $outcome) {
            if ($outcome->getOriginal('confirmed_at') !== null) {
                throw new LogicException(self::CONFIRMED_MESSAGE);
            }
        });

        static::deleting(function (self $outcome) {
            if ($outcome->getOriginal('confirmed_at') !== null) {
                throw new LogicException(self::CONFIRMED_MESSAGE);
            }
        });
    }

    /**
     * @return list<string>
     */
    public static function reasons(): array
    {
        return array_keys(self::REASON_LABELS);
    }

    public function isSupport(): bool
    {
        return $this->decision === self::SUPPORT;
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }
}
