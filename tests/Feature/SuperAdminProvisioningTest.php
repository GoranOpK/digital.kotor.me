<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class SuperAdminProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'superadmin-test@example.com';

    private const PASSWORD = 'test-password-123';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('provisioning.superadmin.email', self::EMAIL);
        config()->set('provisioning.superadmin.password', self::PASSWORD);
    }

    public function test_seeder_creates_an_active_verified_superadmin_that_can_log_in(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $superAdmin = User::where('email', self::EMAIL)->firstOrFail();

        $this->assertSame('superadmin', $superAdmin->role->name);
        $this->assertSame('active', $superAdmin->activation_status);
        $this->assertTrue($superAdmin->hasVerifiedEmail());
        $this->assertTrue(Hash::check(self::PASSWORD, $superAdmin->password));

        $response = $this->post('/login', [
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ]);

        $this->assertAuthenticatedAs($superAdmin);
        $response->assertRedirect(route('home'));
    }

    public function test_missing_configuration_fails(): void
    {
        $this->seed(RoleSeeder::class);

        config()->set('provisioning.superadmin.email', '');
        config()->set('provisioning.superadmin.password', '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('postavite SUPERADMIN_EMAIL i SUPERADMIN_PASSWORD');

        $this->seed(SuperAdminSeeder::class);
    }

    public function test_invalid_email_fails(): void
    {
        $this->seed(RoleSeeder::class);

        config()->set('provisioning.superadmin.email', 'not-an-email');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SUPERADMIN_EMAIL nije validan');

        $this->seed(SuperAdminSeeder::class);
    }

    public function test_short_password_fails(): void
    {
        $this->seed(RoleSeeder::class);

        config()->set('provisioning.superadmin.password', 'short-pass');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('lozinka mora imati najmanje 12 karaktera');

        $this->seed(SuperAdminSeeder::class);
    }

    public function test_email_belonging_to_other_role_fails(): void
    {
        $this->seed(RoleSeeder::class);

        $adminRole = Role::where('name', 'admin')->firstOrFail();

        User::forceCreate([
            'name' => 'Existing Admin',
            'email' => self::EMAIL,
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('već ima drugu rolu');

        $this->seed(SuperAdminSeeder::class);
    }

    public function test_deactivated_superadmin_is_not_reactivated(): void
    {
        $this->seed(RoleSeeder::class);

        $superAdminRole = Role::where('name', 'superadmin')->firstOrFail();

        User::forceCreate([
            'name' => 'Deactivated Super Admin',
            'email' => self::EMAIL,
            'password' => Hash::make('old-password'),
            'role_id' => $superAdminRole->id,
            'activation_status' => 'deactivated',
            'email_verified_at' => now(),
        ]);

        try {
            $this->seed(SuperAdminSeeder::class);
            $this->fail('Očekivana RuntimeException nije bačena.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('nije aktivan', $exception->getMessage());
        }

        $user = User::where('email', self::EMAIL)->firstOrFail();

        $this->assertSame('deactivated', $user->activation_status);
        $this->assertTrue(Hash::check('old-password', $user->password));
    }

    public function test_existing_superadmin_with_different_email_blocks_creation(): void
    {
        $this->seed(RoleSeeder::class);

        $superAdminRole = Role::where('name', 'superadmin')->firstOrFail();

        User::forceCreate([
            'name' => 'Other Super Admin',
            'email' => 'other-superadmin@example.com',
            'password' => Hash::make('other-password'),
            'role_id' => $superAdminRole->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        try {
            $this->seed(SuperAdminSeeder::class);
            $this->fail('Očekivana RuntimeException nije bačena.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('već postoji sa drugim emailom', $exception->getMessage());
        }

        $this->assertNull(User::where('email', self::EMAIL)->first());
        $this->assertSame(1, User::where('role_id', $superAdminRole->id)->count());
    }

    public function test_rerunning_seeder_does_not_change_password_or_activation_status(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $original = User::where('email', self::EMAIL)->firstOrFail();
        $originalPasswordHash = $original->password;
        $originalActivationStatus = $original->activation_status;

        config()->set('provisioning.superadmin.password', 'different-password-456');
        $this->seed(SuperAdminSeeder::class);

        $superAdmin = User::where('email', self::EMAIL)->firstOrFail();

        $this->assertSame(1, User::where('email', self::EMAIL)->count());
        $this->assertSame($originalPasswordHash, $superAdmin->password);
        $this->assertSame($originalActivationStatus, $superAdmin->activation_status);
        $this->assertSame('active', $superAdmin->activation_status);
        $this->assertTrue(Hash::check(self::PASSWORD, $superAdmin->password));
    }

    public function test_password_is_not_printed_to_console_output(): void
    {
        $this->seed(RoleSeeder::class);

        Artisan::call('db:seed', ['--class' => SuperAdminSeeder::class]);

        $output = Artisan::output();

        $this->assertStringNotContainsString(self::PASSWORD, $output);
        $this->assertStringNotContainsString('Lozinka', $output);
        $this->assertStringNotContainsString(self::EMAIL, $output);
        $this->assertStringContainsString('Super admin korisnik je kreiran.', $output);
    }
}
