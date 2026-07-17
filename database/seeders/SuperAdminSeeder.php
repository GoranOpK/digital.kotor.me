<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    /**
     * Kreira tačno jedan aktivan i verifikovan superadmin nalog iz konfiguracije.
     *
     * Poslovno pravilo (trenutno): sistem podržava tačno jednog superadmina.
     * Dodatni superadmin se ne kreira automatski; uvođenje više naloga zahtijeva
     * eksplicitnu odluku i odvojeno odobrenje — ne tiho ponašanje seedera.
     */
    public function run(): void
    {
        $superAdminRole = Role::where('name', 'superadmin')->first();

        if (! $superAdminRole) {
            throw new RuntimeException('Super admin rola ne postoji! Prvo pokrenite RoleSeeder.');
        }

        $email = trim((string) config('provisioning.superadmin.email'));
        $password = (string) config('provisioning.superadmin.password');

        if ($email === '' || $password === '') {
            throw new RuntimeException(
                'Super admin nije kreiran: postavite SUPERADMIN_EMAIL i SUPERADMIN_PASSWORD.'
            );
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Super admin nije kreiran: SUPERADMIN_EMAIL nije validan.');
        }

        if (mb_strlen($password) < 12) {
            throw new RuntimeException(
                'Super admin nije kreiran: lozinka mora imati najmanje 12 karaktera.'
            );
        }

        $existingWithEmail = User::where('email', $email)->first();

        if ($existingWithEmail) {
            if ((int) $existingWithEmail->role_id !== (int) $superAdminRole->id) {
                throw new RuntimeException(
                    'Super admin nije kreiran: korisnik sa zadatim emailom već ima drugu rolu.'
                );
            }

            if ($existingWithEmail->activation_status !== 'active') {
                throw new RuntimeException(
                    'Super admin nije kreiran: postojeći nalog nije aktivan i neće biti automatski reaktiviran.'
                );
            }

            $this->command?->info('Super admin korisnik već postoji.');

            return;
        }

        $otherSuperAdmin = User::where('role_id', $superAdminRole->id)->first();

        if ($otherSuperAdmin) {
            throw new RuntimeException(
                'Super admin već postoji sa drugim emailom. '
                .'Kreiranje dodatnog superadmina nije dozvoljeno bez eksplicitne dozvole.'
            );
        }

        // forceCreate: email_verified_at nije u User::$fillable
        User::forceCreate([
            'name' => 'Super Administrator',
            'first_name' => 'Super',
            'last_name' => 'Administrator',
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $superAdminRole->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'user_type' => 'Fizičko lice',
            'residential_status' => 'resident',
        ]);

        $this->command?->info('Super admin korisnik je kreiran.');
    }
}
