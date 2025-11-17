<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Prvo seeduj roles, pa users
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
        ]);
    }
}