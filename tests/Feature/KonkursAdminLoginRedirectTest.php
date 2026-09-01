<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KonkursAdminLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_konkurs_admin_normal_login_lands_on_zensko_active_competitions(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'konkurs_admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.competitions.index', [
            'type' => 'zensko',
            'tab' => 'active',
        ]));
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('zensko', $query['type'] ?? null);
        $this->assertSame('active', $query['tab'] ?? null);
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

    public function test_kk_admin_login_keeps_cultural_calendar_fallback(): void
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

    public function test_konkurs_admin_ignores_safe_intended_url(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'konkurs_admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $intended = route('admin.dashboard', absolute: false);

        $response = $this->withSession(['url.intended' => url($intended)])
            ->post('/login', [
                'email' => $admin->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($admin);
        $location = (string) $response->headers->get('Location');
        $this->assertStringNotContainsString('/admin/dashboard', $location);
        $response->assertRedirect(route('admin.competitions.index', [
            'type' => 'zensko',
            'tab' => 'active',
        ]));
        $response->assertSessionMissing('url.intended');
    }

    public function test_kk_admin_intended_internal_route_is_preserved(): void
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
        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'konkurs_admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $this->from('/login')->post('/login', [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ])->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_external_intended_url_is_not_honored(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'konkurs_admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $response = $this->withSession(['url.intended' => 'https://evil.example/phish'])
            ->post('/login', [
                'email' => $admin->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.competitions.index', [
            'type' => 'zensko',
            'tab' => 'active',
        ]));
    }
}
