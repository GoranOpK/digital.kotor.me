<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentType extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(PaymentAccount::class);
    }

    public function activeAccounts(): HasMany
    {
        return $this->accounts()->where('is_active', true);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(PaymentTypeAvailability::class);
    }

    public function activeAvailabilities(): HasMany
    {
        return $this->availabilities()->where('is_active', true);
    }

    public function activationBlockReason(): ?string
    {
        if (trim((string) $this->name) === '') {
            return 'Naziv je obavezan.';
        }

        if (trim((string) $this->code) === '') {
            return 'Interni kod je obavezan.';
        }

        if (! $this->accounts()->where('is_active', true)->exists()) {
            return 'Vrsta mora imati najmanje jedan aktivan račun.';
        }

        return null;
    }

    protected static function booted(): void
    {
        static::updating(function (self $type): void {
            if ($type->isDirty('code')) {
                $type->code = $type->getOriginal('code');
            }
        });
    }
}
