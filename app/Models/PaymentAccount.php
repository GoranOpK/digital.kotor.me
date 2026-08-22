<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentAccount extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'payment_type_id',
        'account_number',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(PaymentAccountAvailability::class);
    }

    public function activeAvailabilities(): HasMany
    {
        return $this->availabilities()->where('is_active', true);
    }

    public function activationBlockReason(): ?string
    {
        if (trim((string) $this->account_number) === '') {
            return 'Broj računa je obavezan.';
        }

        if ($this->payment_type_id === null) {
            return 'Račun mora pripadati vrsti plaćanja.';
        }

        return null;
    }

    protected static function booted(): void
    {
        static::updating(function (self $account): void {
            foreach (['account_number', 'payment_type_id'] as $field) {
                if ($account->isDirty($field)) {
                    $account->{$field} = $account->getOriginal($field);
                }
            }
        });
    }
}
