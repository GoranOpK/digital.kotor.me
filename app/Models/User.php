<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\VerifyEmailNotification;
use App\Support\UserType;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }

    /**
     * Atributi koji mogu biti masovno dodijeljeni (mass assignable).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'activation_status',
        'user_type',
        'residential_status',
        'first_name',
        'last_name',
        'company_name',
        'jmb',
        'pib',
        'passport_number',
        'email',
        'phone',
        'address',
        'city',
        'password',
        'role_id',
        'used_storage_bytes',
    ];

    /**
     * Atributi koji će biti sakriveni prilikom serijalizacije (npr. za API).
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Definiše kako će se određeni atributi kastovati (pretvarati) u tipove.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Accessor za name - automatski kombinuje first_name i last_name.
     * Podržava srpske dijakritičke znakove (š, đ, ž, č, ć) zahvaljujući utf8mb4 charset-u.
     *
     * @return string
     */
    public function getNameAttribute($value)
    {
        // Ako postoji name u bazi, vrati ga, inače kombinuj first_name + last_name
        if ($value) {
            return $value;
        }
        
        $firstName = $this->attributes['first_name'] ?? '';
        $lastName = $this->attributes['last_name'] ?? '';
        
        return trim($firstName . ' ' . $lastName);
    }

    /**
     * Mutator koji automatski ažurira name kada se postavi first_name ili last_name.
     * Poziva se pre čuvanja u bazu.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatski popuni name kada se postave first_name ili last_name
        static::saving(function ($user) {
            if ($user->first_name || $user->last_name) {
                $user->attributes['name'] = trim(
                    ($user->first_name ?? '') . ' ' . ($user->last_name ?? '')
                );
            }
        });
    }

    /**
     * Veza sa modelom Role – korisnik pripada jednoj roli.
     * Koristi se za dohvatanje role korisnika ($user->role).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role()
    {
        // Ovdje kažemo da svaki korisnik (User) pripada jednoj ulozi (Role)
        return $this->belongsTo(Role::class);
    }

    public function isSuperadmin(): bool
    {
        $role = $this->relationLoaded('role') ? $this->role : $this->role()->first();

        return $role !== null && $role->isSuperadmin();
    }

    public function isStaffAccount(): bool
    {
        $role = $this->relationLoaded('role') ? $this->role : $this->role()->first();
        $name = $role?->name;

        return in_array($name, [
            'admin',
            'komisija',
            'superadmin',
            'konkurs_admin',
            'kk_admin',
        ], true);
    }

    public function collectsBusinessIdentity(): bool
    {
        if (UserType::isNaturalPerson($this->user_type) || UserType::isLegalEntity($this->user_type)) {
            return true;
        }

        $role = $this->relationLoaded('role') ? $this->role : $this->role()->first();

        return $role !== null && $role->name === 'korisnik';
    }

    public function isNaturalPerson(): bool
    {
        return UserType::isNaturalPerson($this->user_type);
    }

    public function isEntrepreneur(): bool
    {
        return UserType::isEntrepreneur($this->user_type);
    }

    public function isLegalEntity(): bool
    {
        return UserType::isLegalEntity($this->user_type);
    }

    /**
     * Puna adresa iz profila (ulica i broj + grad) za obrasce prijave.
     */
    public function formattedAddress(): string
    {
        return \App\Support\KotorAddress::formatStreetAndCity($this->address, $this->city);
    }

    /**
     * Veza sa dokumentima korisnika.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function documents()
    {
        return $this->hasMany(UserDocument::class);
    }

    /**
     * TS-001 — moderatorska ovlašćenja (nije platformska uloga).
     */
    public function culturalModeratorAuthorizations()
    {
        return $this->hasMany(CulturalModeratorAuthorization::class);
    }

    /**
     * NL-01 — kanonska Newsletter pretplata (najviše jedna; nije automatska).
     */
    public function newsletterSubscription()
    {
        return $this->hasOne(NewsletterSubscription::class);
    }

    public function paymentInitiations()
    {
        return $this->hasMany(PaymentInitiation::class);
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}