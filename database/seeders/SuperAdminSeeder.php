<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Kreira super admin korisnika
     */
    public function run(): void
    {
        // Pronađi superadmin rolu
        $superAdminRole = Role::where('name', 'superadmin')->first();

        if (!$superAdminRole) {
            $this->command->error('Super admin rola ne postoji! Prvo pokrenite RoleSeeder.');
            return;
        }

        // Proveri da li već postoji super admin
        $existingSuperAdmin = User::where('role_id', $superAdminRole->id)->first();

        if ($existingSuperAdmin) {
            $this->command->info('Super admin korisnik već postoji: ' . $existingSuperAdmin->email);
            return;
        }

        // Kreiraj super admin korisnika
        $superAdmin = User::create([
            'name' => 'Super Administrator',
            'first_name' => 'Super',
            'last_name' => 'Administrator',
            'email' => 'informatika@kotor.me',
            'password' => Hash::make('3TpTjPrDhYIF0G'),
            'role_id' => $superAdminRole->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'user_type' => 'Fizičko lice',
            'residential_status' => 'resident',
        ]);

        $this->command->info('Super admin korisnik je kreiran!');
        $this->command->info('Email: informatika@kotor.me');
        $this->command->info('Lozinka: 3TpTjPrDhYIF0G');
    }
}

