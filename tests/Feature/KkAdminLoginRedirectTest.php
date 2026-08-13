<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KkAdminLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_kk_admin_normal_login_lands_on_cultural_calendar_index(): void
    {
        $kkAdmin = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => $kkAdmin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($kkAdmin);
        $response->assertRedirect(route('cultural-calendar.index'));
    }

    public function test_ordinary_user_login_keeps_home_fallback(): void
    {
        $user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home'));
    }

    public function test_admin_login_keeps_home_fallback(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('home'));
    }

    public function test_kk_admin_intended_internal_kk_editorial_route_is_preserved(): void
    {
        $kkAdmin = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $intended = route('cultural-event-entries.index', absolute: false);

        $response = $this->withSession(['url.intended' => url($intended)])
            ->post('/login', [
                'email' => $kkAdmin->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($kkAdmin);
        $response->assertRedirect($intended);
    }

    public function test_failed_login_unaffected(): void
    {
        $kkAdmin = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $this->from('/login')->post('/login', [
            'email' => $kkAdmin->email,
            'password' => 'wrong-password',
        ])->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_external_intended_url_is_not_honored(): void
    {
        $kkAdmin = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $response = $this->withSession(['url.intended' => 'https://evil.example/phish'])
            ->post('/login', [
                'email' => $kkAdmin->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($kkAdmin);
        $response->assertRedirect(route('cultural-calendar.index'));
    }
}
