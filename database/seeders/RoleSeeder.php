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
        // Kreiraj ili ažuriraj role u bazi (može se pokrenuti više puta bez greške)
        $roles = [
            [
                'id' => 1,
                'name' => 'admin',
                'display_name' => 'Administrator'
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
            [
                'id' => 5,
                'name' => 'konkurs_admin',
                'display_name' => 'Administrator konkursa'
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['id' => $role['id']],
                ['name' => $role['name'], 'display_name' => $role['display_name']]
            );
        }
    }
}