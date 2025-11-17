<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Role (Uloga)
 * 
 * Predstavlja korisničke uloge u sistemu (admin, evaluator, user).
 * Omogućava dodjelu različitih nivoa pristupa i dozvola korisnicima.
 * 
 * @property int $id - Jedinstveni identifikator uloge
 * @property string $name - Naziv uloge (npr. 'admin', 'evaluator', 'user')
 * @property string $description - Opis uloge i njenih dozvola
 * @property \Illuminate\Support\Carbon $created_at - Vrijeme kreiranja
 * @property \Illuminate\Support\Carbon $updated_at - Vrijeme posljednje izmjene
 */
class Role extends Model
{
    /**
     * Veza: jedna rola ima više korisnika (One-to-Many)
     * 
     * Ova metoda definiše relaciju između Role i User modela.
     * Omogućava pristup svim korisnicima koji imaju određenu ulogu.
     * 
     * Primjer korištenja:
     * $adminRole = Role::where('name', 'admin')->first();
     * $admins = $adminRole->users; // Svi korisnici sa admin ulogom
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}