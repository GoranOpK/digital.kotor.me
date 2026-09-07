<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationEliminatoryCheck extends Model
{
    public const CRITERION_LABELS = [
        1 => 'Dostavljena su sva potrebna dokumenta?',
        2 => 'Dostavljen je Izvještaj o realizaciji biznis plana sa Finansijskim izvještajem (Obrasci 4 i 4a) i pratećom dokumentacijom (fakture i izvodi sa banke) za biznis plan koji je u prethodnom periodu finansiran ili djelimično finansiran iz budžeta Opštine?',
        3 => 'Biznis plan je vezan za prioritetne oblasti navedene u članu 10 Odluke?',
    ];

    protected $fillable = [
        'application_id',
        'criterion_1',
        'criterion_2',
        'criterion_3',
        'note',
        'confirmed_at',
        'confirmed_by_commission_member_id',
        'confirmed_by_user_id',
        'confirmed_by_name',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function confirmedByCommissionMember(): BelongsTo
    {
        return $this->belongsTo(CommissionMember::class, 'confirmed_by_commission_member_id');
    }

    public function confirmedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function isConfirmedPass(): bool
    {
        return $this->isConfirmed()
            && $this->criterionIsTrue($this->criterion_1)
            && $this->criterionIsTrue($this->criterion_2)
            && $this->criterionIsTrue($this->criterion_3);
    }

    public function isConfirmedFail(): bool
    {
        return $this->isConfirmed() && $this->hasAnyFailCriterion();
    }

    public function hasAnyFailCriterion(): bool
    {
        return $this->criterionIsFalse($this->criterion_1)
            || $this->criterionIsFalse($this->criterion_2)
            || $this->criterionIsFalse($this->criterion_3);
    }

    /**
     * @return list<string>
     */
    public function failedCriterionLabels(): array
    {
        $failed = [];
        foreach (self::CRITERION_LABELS as $number => $label) {
            if ($this->criterionIsFalse($this->{"criterion_{$number}"})) {
                $failed[] = $label;
            }
        }

        return $failed;
    }

    /**
     * Null is not Da. New UI always writes explicit true/false.
     */
    public function criterionIsTrue(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1';
    }

    public function criterionIsFalse(mixed $value): bool
    {
        return $value === false || $value === 0 || $value === '0';
    }
}
