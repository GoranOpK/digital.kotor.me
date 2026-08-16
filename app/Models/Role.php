<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public const NAME_SUPERADMIN = 'superadmin';

    public function isSuperadmin(): bool
    {
        return $this->name === self::NAME_SUPERADMIN;
    }

    /**
     * Role koje Users administracija smije dodijeliti (superadmin je provisioning-only).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Role>
     */
    public static function assignableForUsersAdministration()
    {
        return static::query()
            ->where('name', '!=', self::NAME_SUPERADMIN)
            ->orderBy('id')
            ->get();
    }

    // Veza: jedna rola ima više korisnika (users)
    // Ova funkcija omogućava da iz modela Role dođeš do svih korisnika koji imaju tu rolu
    public function users()
    {
        // Svaka rola može imati više korisnika (hasMany)
        return $this->hasMany(User::class);
    }
}