<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'user_type' => 'Fizičko lice',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'email_confirmation' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone_full' => '+38267000001',
            'address' => 'Njegoševa 12',
            'city' => 'Kotor',
            'residential_status' => 'resident',
            'jmb' => '0101990000000',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));
        $this->assertSame(3, User::query()->where('email', 'test@example.com')->value('role_id'));
        $this->assertSame('resident', User::query()->where('email', 'test@example.com')->value('residential_status'));
    }

    public function test_non_resident_physical_person_can_register(): void
    {
        $response = $this->post('/register', [
            'user_type' => 'Fizičko lice',
            'first_name' => 'Mara',
            'last_name' => 'Nerezident',
            'email' => 'mara.nerezident@example.com',
            'email_confirmation' => 'mara.nerezident@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone_full' => '+38267000002',
            'address' => 'Main Street 1',
            'city' => 'Berlin',
            'residential_status' => 'non-resident',
            'non_resident_id_type' => 'passport',
            'passport_number' => 'XY987654',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));
        $this->assertSame(
            'non-resident',
            User::query()->where('email', 'mara.nerezident@example.com')->value('residential_status')
        );
    }

    public function test_registration_rejects_legacy_ex_non_resident_status(): void
    {
        $this->post('/register', [
            'user_type' => 'Fizičko lice',
            'first_name' => 'Legacy',
            'last_name' => 'Status',
            'email' => 'legacy.status@example.com',
            'email_confirmation' => 'legacy.status@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone_full' => '+38267000003',
            'address' => 'Njegoševa 12',
            'city' => 'Kotor',
            'residential_status' => 'ex-non-resident',
            'jmb' => '0101990000000',
        ])->assertSessionHasErrors('residential_status');

        $this->assertGuest();
        $this->assertFalse(User::query()->where('email', 'legacy.status@example.com')->exists());
    }

    public function test_registration_form_does_not_offer_legacy_ex_non_resident_option(): void
    {
        $html = $this->get('/register')->assertOk()->getContent();

        $this->assertStringNotContainsString('ex-non-resident', $html);
        $this->assertStringNotContainsString('Bivši nerezident', $html);
        $this->assertStringContainsString('value="resident"', $html);
        $this->assertStringContainsString('value="non-resident"', $html);
    }
}
