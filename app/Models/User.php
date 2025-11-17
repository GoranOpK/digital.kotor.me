<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Atributi koji mogu biti masovno dodijeljeni (mass assignable).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
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