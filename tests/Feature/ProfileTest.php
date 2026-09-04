<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_profile_page_is_displayed_without_alpine(): void
    {
        $user = $this->makeUser();

        $html = $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Izmjena profila', false)
            ->assertSee('Osnovni podaci', false)
            ->assertSee('Promjena lozinke', false)
            ->getContent();

        $this->assertStringNotContainsString('x-data', $html);
        $this->assertStringNotContainsString('x-show', $html);
        $this->assertStringNotContainsString('x-transition', $html);
        $this->assertStringNotContainsString('x-init', $html);
        $this->assertStringNotContainsString('$dispatch', $html);
        $this->assertStringNotContainsString('<x-modal', $html);
        $this->assertStringNotContainsString('confirm-user-deletion', $html);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = $this->makeUser();

        $response = $this
            ->actingAs($user)
            ->put(route('profile.update'), [
                'first_name' => 'Petar',
                'last_name' => 'Petrović',
                'email' => 'petar.profile@example.com',
                'phone' => '+38267111222',
                'address' => 'Njegoševa 12',
                'city' => 'Kotor',
                'user_type' => 'Fizičko lice',
                'residential_status' => 'resident',
                'jmb' => '0101990123456',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertSame('Petar', $user->first_name);
        $this->assertSame('Petrović', $user->last_name);
        $this->assertSame('Petar Petrović', $user->name);
        $this->assertSame('petar.profile@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertSame('resident', $user->residential_status);
    }

    public function test_profile_information_can_be_updated_to_non_resident(): void
    {
        $user = $this->makeUser();

        $response = $this
            ->actingAs($user)
            ->put(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'user_type' => 'Fizičko lice',
                'residential_status' => 'non-resident',
                'jmb' => $user->jmb,
                'passport_number' => 'AB123456',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertSame('non-resident', $user->refresh()->residential_status);
        $this->assertSame('AB123456', $user->passport_number);
    }

    public function test_profile_update_rejects_legacy_ex_non_resident_status(): void
    {
        $user = $this->makeUser();

        $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'user_type' => 'Fizičko lice',
                'residential_status' => 'ex-non-resident',
                'jmb' => $user->jmb,
            ])
            ->assertSessionHasErrors('residential_status')
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('resident', $user->refresh()->residential_status);
    }

    public function test_profile_form_does_not_offer_legacy_ex_non_resident_option(): void
    {
        $html = $this->actingAs($this->makeUser())
            ->get(route('profile.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('ex-non-resident', $html);
        $this->assertStringNotContainsString('Bivši nerezident', $html);
        $this->assertStringContainsString('value="resident"', $html);
        $this->assertStringContainsString('value="non-resident"', $html);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = $this->makeUser([
            'email' => 'same@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => 'same@example.com',
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'user_type' => $user->user_type,
                'residential_status' => $user->residential_status,
                'jmb' => $user->jmb,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_password_can_be_updated_and_status_is_server_rendered(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->put(route('profile.password.update'), [
                'current_password' => 'password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $html = $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Lozinka je uspješno promijenjena.', false)
            ->getContent();

        $this->assertStringNotContainsString('x-data', $html);
        $this->assertStringNotContainsString('x-show', $html);
        $this->assertStringNotContainsString('x-transition', $html);
    }

    public function test_account_deletion_is_not_exposed_on_active_profile_page(): void
    {
        $user = $this->makeUser();

        $this->assertFalse(
            Route::has('profile.destroy'),
            'Active Profile UX has no account-deletion route; Breeze delete flow is not product surface.'
        );

        $html = $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('data-kk-profile-delete-open', $html);
        $this->assertStringNotContainsString('Delete Account', $html);
        $this->assertStringNotContainsString('$dispatch', $html);
        $this->assertStringNotContainsString('open-modal', $html);
    }

    public function test_breeze_profile_partials_no_longer_depend_on_alpine(): void
    {
        $files = [
            resource_path('views/profile/partials/update-profile-information-form.blade.php'),
            resource_path('views/profile/partials/update-password-form.blade.php'),
            resource_path('views/profile/partials/delete-user-form.blade.php'),
        ];

        foreach ($files as $path) {
            $source = file_get_contents($path);
            $this->assertNotFalse($source);
            $this->assertStringNotContainsString('x-data', $source);
            $this->assertStringNotContainsString('x-show', $source);
            $this->assertStringNotContainsString('x-transition', $source);
            $this->assertStringNotContainsString('x-init', $source);
            $this->assertStringNotContainsString('x-on:', $source);
            $this->assertStringNotContainsString('$dispatch', $source);
            $this->assertStringNotContainsString('<x-modal', $source);
        }

        $deleteSource = file_get_contents($files[2]);
        $this->assertStringContainsString('data-kk-profile-delete-open', $deleteSource);
        $this->assertStringContainsString('data-kk-profile-delete-dialog', $deleteSource);
        $this->assertStringContainsString("Route::has('profile.destroy')", $deleteSource);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'first_name' => 'Ana',
            'last_name' => 'Anić',
            'name' => 'Ana Anić',
            'phone' => '+38267000001',
            'address' => 'Stari grad 1',
            'city' => 'Kotor',
            'user_type' => 'Fizičko lice',
            'residential_status' => 'resident',
            'jmb' => '0202990123456',
            'email_verified_at' => now(),
        ], $overrides));
    }
}
