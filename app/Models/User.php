<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\VerifyEmailNotification;

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
        'user_type',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'password',
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
            'date_of_birth' => 'date',
            'password' => 'hashed',
        ];
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
}