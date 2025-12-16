<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // Uvozimo User model
use Illuminate\Support\Facades\Hash; // Za hešovanje lozinke

class UserSeeder extends Seeder
{
    /**
     * Popunjava tabelu 'users' sa test korisnicima.
     */
    public function run(): void
    {
        // Administrator
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'), // Lozinka je 'password'
            'role_id' => 1, // admin
        ]);

        // Super administrator (podatke čitamo iz .env ako su postavljeni)
        $superAdminEmail = env('SUPERADMIN_EMAIL', 'superadmin@example.com');
        $superAdminPass = env('SUPERADMIN_PASSWORD', 'password');

        User::updateOrCreate(
            ['email' => $superAdminEmail],
            [
                'name' => 'Super Admin',
                'password' => Hash::make($superAdminPass),
                'role_id' => 4, // superadmin
            ]
        );

        // Član komisije
        User::create([
            'name' => 'Komisija',
            'email' => 'komisija@example.com',
            'password' => Hash::make('password'), // Lozinka je 'password'
            'role_id' => 2, // komisija
        ]);

        // Prijavitelj
        User::create([
            'name' => 'Prijavitelj',
            'email' => 'prijavitelj@example.com',
            'password' => Hash::make('password'), // Lozinka je 'password'
            'role_id' => 3, // prijavitelj
        ]);
    }
}