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
    }
}
