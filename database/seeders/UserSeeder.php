<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder; // Uvozimo User model
use Illuminate\Support\Facades\Hash; // Za hešovanje lozinke

class UserSeeder extends Seeder
{
    /**
     * Popunjava tabelu 'users' sa test korisnicima.
     *
     * Superadmin se ne kreira ovdje — v. SuperAdminSeeder.
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
            'role_id' => 3, // prijavitelj / korisnik
        ]);
    }
}
