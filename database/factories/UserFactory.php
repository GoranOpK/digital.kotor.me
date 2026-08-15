<?php

namespace Database\Factories;

use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role_id' => $this->defaultRoleId(),
        ];
    }

    /**
     * Osigurava validan FK na roles (RefreshDatabase ostavlja tabelu praznu).
     * Koristi postojeći RoleSeeder da id=3 (korisnik) ostane usklađen sa DB default-om.
     */
    private function defaultRoleId(): int
    {
        $roleId = Role::query()->where('name', 'korisnik')->value('id');
        if ($roleId !== null) {
            return (int) $roleId;
        }

        (new RoleSeeder)->run();

        $roleId = Role::query()->where('name', 'korisnik')->value('id');
        if ($roleId === null) {
            throw new \RuntimeException('RoleSeeder nije kreirao rolu korisnik.');
        }

        return (int) $roleId;
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
