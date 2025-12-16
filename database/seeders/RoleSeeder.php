<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role; // Uvozimo model Role

class RoleSeeder extends Seeder
{
    /**
     * Popunjava tabelu 'roles' sa početnim podacima
     */
    public function run(): void
    {
        // Insertujemo tri osnovne uloge u bazu:
        // 1 - admin, 2 - komisija, 3 - korisnik
        Role::insert([
            [
                'id' => 1,
                'name' => 'admin',           // Sistematsko ime uloge
                'display_name' => 'Administrator' // Ime za prikaz
            ],
            [
                'id' => 2,
                'name' => 'komisija',
                'display_name' => 'Komisija'
            ],
            [
                'id' => 3,
                'name' => 'korisnik',
                'display_name' => 'Korisnik'
            ],
            [
                'id' => 4,
                'name' => 'superadmin',
                'display_name' => 'Super administrator'
            ],
        ]);
    }
}