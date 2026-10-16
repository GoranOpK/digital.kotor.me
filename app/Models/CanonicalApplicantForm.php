<?php

namespace App\Models;

use App\Support\CanonicalLegalForm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * KN-SYNC-015 — canonical Obrazac P1a (preduzetnik) / P1b (društvo), čl.16. One per canonical application.
 *
 * person_name: P1a ime i prezime preduzetnika / P1b ime i prezime odgovornog lica.
 * address: P1a adresa / P1b sjedište. registration_date: P1a datum registracije u CRPS / P1b datum osnivanja.
 * phone, email: P1b contacts of the odgovorno lice. PIB lives in applications.pib.
 *
 * Frozen at canonical FINAL; a frozen row cannot be updated or deleted.
 */
class CanonicalApplicantForm extends Model
{
    public const FROZEN_MESSAGE = 'Obrazac P1 je zamrznut konačnom predajom i ne može se mijenjati.';

    protected $fillable = [
        'application_id',
        'legal_form',
        'subsidy_type',
        'person_name',
        'company_name',
        'address',
        'activity_code',
        'employee_count',
        'bank_account',
        'phone',
        'email',
        'website',
        'registration_date',
        'accuracy_confirmed_at',
    ];

    protected $casts = [
        'employee_count' => 'integer',
        'registration_date' => 'date',
        'accuracy_confirmed_at' => 'datetime',
        'frozen_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $form) {
            if ($form->getOriginal('frozen_at') !== null) {
                throw new LogicException(self::FROZEN_MESSAGE);
            }
            if ($form->isDirty('legal_form')) {
                throw new LogicException('Pravna forma kanonske prijave se ne mijenja nakon izbora.');
            }
        });

        static::deleting(function (self $form) {
            if ($form->getOriginal('frozen_at') !== null) {
                throw new LogicException(self::FROZEN_MESSAGE);
            }
        });
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function isFrozen(): bool
    {
        return $this->frozen_at !== null;
    }

    public function isEntrepreneur(): bool
    {
        return $this->legal_form === CanonicalLegalForm::PREDUZETNIK;
    }

    public function formCode(): string
    {
        return CanonicalLegalForm::formCode($this->legal_form);
    }

    /**
     * Review basis facts (no JMB, no live User fields).
     *
     * @return array<string, mixed>
     */
    public function basis(): array
    {
        return [
            'legal_form' => $this->legal_form,
            'form' => $this->formCode(),
            'subsidy_type' => $this->subsidy_type,
            'person_name' => $this->person_name,
            'company_name' => $this->company_name,
            'address' => $this->address,
            'activity_code' => $this->activity_code,
            'employee_count' => $this->employee_count,
            'bank_account' => $this->bank_account,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'registration_date' => $this->registration_date?->toDateString(),
            'accuracy_confirmed_at' => $this->accuracy_confirmed_at?->toIso8601String(),
            'frozen_at' => $this->frozen_at?->toIso8601String(),
        ];
    }
}
