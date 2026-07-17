<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Napomena: SuperAdminSeeder se namjerno NE poziva ovdje.
     * Na produkciji pokrenuti isključivo:
     * php artisan db:seed --class=SuperAdminSeeder
     */
    public function run(): void
    {
        // Prvo seeduj roles, pa users
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            KkAdministratorSeeder::class,
        ]);
    }
}
