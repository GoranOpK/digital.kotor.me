<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ApplicationPrigovor extends Model
{
    public const STATUS_PODNESEN = 'podnesen';

    public const STATUS_PRIHVACEN = 'prihvacen';

    public const STATUS_ODBIJEN = 'odbijen';

    public const OUTCOME_OTKLONJEN = 'otklonjen';

    public const OUTCOME_OSTAJE = 'ostaje';

    public const KOMISIJA_DECISION_DAYS = 7;

    protected $fillable = [
        'application_id',
        'obrazlozenje',
        'status',
        'submitted_at',
        'submitted_by_user_id',
        'decided_at',
        'decided_by_commission_member_id',
        'decided_by_user_id',
        'decided_by_name',
        'decision_note',
        'eliminatory_reason_remaining',
        'criterion_1_remaining',
        'criterion_2_remaining',
        'criterion_3_remaining',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
            'eliminatory_reason_remaining' => 'boolean',
            // criterion_*_remaining stay uncast so NULL (original Da) is not coerced to false.
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function decidedByCommissionMember(): BelongsTo
    {
        return $this->belongsTo(CommissionMember::class, 'decided_by_commission_member_id');
    }

    public function decidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    public function isPodnesen(): bool
    {
        return $this->status === self::STATUS_PODNESEN;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_PRIHVACEN;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_ODBIJEN;
    }

    public function isFinished(): bool
    {
        return $this->isAccepted() || $this->isRejected();
    }

    public function komisijaDeadlineAt(): Carbon
    {
        return $this->submitted_at->copy()->addDays(self::KOMISIJA_DECISION_DAYS);
    }

    public function liftsEliminatoryBar(): bool
    {
        return $this->isAccepted() && $this->eliminatory_reason_remaining === false;
    }

    /**
     * @return list<string>
     */
    public function remainingReasonLabels(): array
    {
        $labels = [];
        foreach (ApplicationEliminatoryCheck::CRITERION_LABELS as $number => $label) {
            if ($this->criterionRemainingIsTrue($number)) {
                $labels[] = $label;
            }
        }

        return $labels;
    }

    public function criterionOutcomeLabel(int $number): ?string
    {
        $remaining = $this->{"criterion_{$number}_remaining"};
        if ($remaining === null || $remaining === '') {
            return null;
        }
        if ($this->criterionRemainingIsTrue($number)) {
            return 'Ostaje';
        }

        return 'Otklonjen';
    }

    public function criterionRemainingIsTrue(int $number): bool
    {
        $value = $this->{"criterion_{$number}_remaining"};

        return $value === true || $value === 1 || $value === '1';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PODNESEN => 'Podnesen',
            self::STATUS_PRIHVACEN => 'Prihvaćen',
            self::STATUS_ODBIJEN => 'Odbijen',
            default => $this->status,
        };
    }
}
