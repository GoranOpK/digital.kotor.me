<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class CompetitionAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pronađi rolu "konkurs_admin"
        $role = Role::where('name', 'konkurs_admin')->first();

        if (!$role) {
            $this->command->error('Rola "konkurs_admin" ne postoji! Prvo pokrenite RoleSeeder.');
            return;
        }

        // Kreiraj ili ažuriraj administratora konkursa
        $admin = User::updateOrCreate(
            ['email' => 'konkurs.admin@kotor.me'],
            [
                'name' => 'Administrator konkursa',
                'first_name' => 'Administrator',
                'last_name' => 'Konkursa',
                'email' => 'konkurs.admin@kotor.me',
                'password' => Hash::make('M4nuyN4AIfaHDPxfpWD7'),
                'role_id' => $role->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
                'user_type' => null,
                'residential_status' => null,
            ]
        );

        $this->command->info('Administrator konkursa je uspješno kreiran/ažuriran!');
        $this->command->info('Email: konkurs.admin@kotor.me');
        $this->command->info('Lozinka: M4nuyN4AIfaHDPxfpWD7');
    }
}
