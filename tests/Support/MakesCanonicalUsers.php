<?php

namespace Tests\Support;

use App\Models\Role;
use App\Models\User;
use App\Support\UserType;

trait MakesCanonicalUsers
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function makeKorisnik(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'first_name' => 'Ana',
            'last_name' => 'Anić',
            'name' => 'Ana Anić',
            'phone' => '+38267000001',
            'address' => 'Njegoševa 12',
            'city' => 'Kotor',
            'user_type' => UserType::PHYSICAL_PERSON,
            'residential_status' => 'resident',
            'jmb' => '0202990123456',
            'email_verified_at' => now(),
        ], $overrides));
    }

    protected function validJmb(int $serial): string
    {
        $prefix = sprintf('010199000%03d', $serial);
        $weights = [7, 6, 5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $prefix[$i] * $weights[$i];
        }
        $m = $sum % 11;
        if ($m === 1) {
            return $this->validJmb($serial + 1);
        }

        $k = $m === 0 ? 0 : 11 - $m;

        return $prefix.$k;
    }
}
