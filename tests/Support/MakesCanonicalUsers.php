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

    protected function validPib(int $seed): string
    {
        $base = str_pad((string) abs($seed % 10000000), 7, '0', STR_PAD_LEFT);
        $product = 10;
        for ($i = 0; $i < 7; $i++) {
            $product = ($product + (int) $base[$i]) % 10;
            if ($product === 0) {
                $product = 10;
            }
            $product = ($product * 2) % 11;
        }

        return $base.((11 - $product) % 10);
    }

    protected function validCrps(int $mark, int $serial = 1): string
    {
        return $mark.str_pad((string) $serial, 7, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function registrationHttpPayload(string $email, array $overrides = []): array
    {
        return array_merge([
            'user_type' => UserType::PHYSICAL_PERSON,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'email_confirmation' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone_calling_code' => '+382',
            'phone_national' => '67000001',
            'address' => 'Njegoševa 12',
            'city' => 'Podgorica',
            'residential_status' => 'resident',
            'jmb' => $this->validJmb(10),
        ], $overrides);
    }
}
