<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model za upravljanje ulogama korisnika.
 * 
 * Predstavlja ulogu u sistemu (npr. admin, evaluator, applicant).
 * Koristi se za kontrolu pristupa i autorizaciju različitih akcija
 * u aplikaciji kroz middleware i policy.
 */
class Role extends Model
{
    /**
     * Veza jedan-na-više: jedna uloga ima više korisnika.
     * 
     * Omogućava dohvatanje svih korisnika koji imaju određenu ulogu u sistemu.
     * Na primjer, dohvatanje svih administratora ili svih evaluatora.
     * Koristi se: $role->users
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        // Svaka rola može imati više korisnika (hasMany)
        return $this->hasMany(User::class);
    }
}