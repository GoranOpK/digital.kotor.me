<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KkAdministratorSeeder extends Seeder
{
    /**
     * Kreira ili ažurira KK administrator nalog.
     */
    public function run(): void
    {
        $role = Role::where('name', 'kk_admin')->first();

        if (!$role) {
            $this->command->error('Rola "kk_admin" ne postoji. Prvo pokrenite RoleSeeder.');
            return;
        }

        User::updateOrCreate(
            ['email' => 'manifestacije@kotor.me'],
            [
                'name' => 'KKAdministrator',
                'first_name' => 'KKAdministrator',
                'last_name' => '',
                'email' => 'manifestacije@kotor.me',
                'password' => Hash::make('Kotor123'),
                'role_id' => $role->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
                'user_type' => null,
                'residential_status' => null,
            ]
        );

        $this->command->info('KKAdministrator je uspješno kreiran/ažuriran.');
        $this->command->info('Email: manifestacije@kotor.me');
        $this->command->info('Privremena lozinka: Kotor123');
    }
}
