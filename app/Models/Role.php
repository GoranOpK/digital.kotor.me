<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    // Veza: jedna rola ima više korisnika (users)
    // Ova funkcija omogućava da iz modela Role dođeš do svih korisnika koji imaju tu rolu
    public function users()
    {
        // Svaka rola može imati više korisnika (hasMany)
        return $this->hasMany(User::class);
    }
}