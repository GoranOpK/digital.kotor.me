<?php

namespace Tests\Feature;

use App\Models\MmspTestAccessGrant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUsersSuperadminProtectionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Role $superadminRole;

    private Role $kkAdminRole;

    private Role $korisnikRole;

    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->superadminRole = Role::where('name', 'superadmin')->firstOrFail();
        $this->kkAdminRole = Role::where('name', 'kk_admin')->firstOrFail();
        $this->korisnikRole = Role::where('name', 'korisnik')->firstOrFail();
        $this->adminRole = Role::where('name', 'admin')->firstOrFail();

        $this->admin = $this->userWithRole('admin', 'Platform Admin', 'adm-c1-admin@example.com');
    }

    public function test_admin_cannot_assign_superadmin_to_self(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $this->admin), $this->updatePayload($this->admin, [
                'role_id' => $this->superadminRole->id,
            ]))
            ->assertForbidden();

        $this->assertSame($this->adminRole->id, (int) $this->admin->fresh()->role_id);
        $this->assertSame('active', $this->admin->fresh()->activation_status);
    }

    public function test_admin_cannot_assign_superadmin_to_another_user(): void
    {
        $target = $this->userWithRole('korisnik', 'Other User', 'adm-c1-other@example.com');

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $target), $this->updatePayload($target, [
                'role_id' => $this->superadminRole->id,
            ]))
            ->assertForbidden();

        $this->assertSame($this->korisnikRole->id, (int) $target->fresh()->role_id);
    }

    public function test_admin_cannot_remove_superadmin_role(): void
    {
        $superadmin = $this->userWithRole('superadmin', 'Super Admin', 'adm-c1-super@example.com');

        foreach (['admin', 'kk_admin'] as $roleName) {
            $roleId = Role::where('name', $roleName)->firstOrFail()->id;

            $this->actingAs($this->admin)
                ->put(route('admin.users.update', $superadmin), $this->updatePayload($superadmin, [
                    'role_id' => $roleId,
                ]))
                ->assertForbidden();

            $this->assertSame($this->superadminRole->id, (int) $superadmin->fresh()->role_id);
        }
    }

    public function test_admin_cannot_deactivate_superadmin(): void
    {
        $superadmin = $this->userWithRole('superadmin', 'Super Admin', 'adm-c1-super-deact@example.com');

        $this->actingAs($this->admin)
            ->post(route('admin.users.deactivate', $superadmin))
            ->assertForbidden();

        $this->assertSame('active', $superadmin->fresh()->activation_status);
        $this->assertSame($this->superadminRole->id, (int) $superadmin->fresh()->role_id);
    }

    public function test_admin_cannot_activate_superadmin_via_users_ui(): void
    {
        $superadmin = $this->userWithRole('superadmin', 'Super Admin', 'adm-c1-super-inact@example.com', 'deactivated');

        $this->actingAs($this->admin)
            ->post(route('admin.users.activate', $superadmin))
            ->assertForbidden();

        $this->assertSame('deactivated', $superadmin->fresh()->activation_status);
    }

    public function test_admin_cannot_change_superadmin_activation_via_update(): void
    {
        $superadmin = $this->userWithRole('superadmin', 'Super Admin', 'adm-c1-super-upd@example.com');

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $superadmin), $this->updatePayload($superadmin, [
                'activation_status' => 'deactivated',
            ]))
            ->assertForbidden();

        $this->assertSame('active', $superadmin->fresh()->activation_status);
        $this->assertSame($this->superadminRole->id, (int) $superadmin->fresh()->role_id);
    }

    public function test_superadmin_is_not_offered_as_assignable_role(): void
    {
        $target = $this->userWithRole('korisnik', 'Role Option User', 'adm-c1-options@example.com');

        $html = $this->actingAs($this->admin)
            ->get(route('admin.users.edit', $target))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('value="'.$this->superadminRole->id.'"', $html);
        $this->assertStringNotContainsString('Super administrator', $html);
        $this->assertStringContainsString('value="'.$this->kkAdminRole->id.'"', $html);
    }

    public function test_admin_can_assign_kk_admin_to_ordinary_user(): void
    {
        $target = $this->userWithRole('korisnik', 'Promote Me', 'adm-c1-promote@example.com');

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $target), $this->updatePayload($target, [
                'role_id' => $this->kkAdminRole->id,
            ]))
            ->assertRedirect(route('admin.users.show', $target));

        $this->assertSame($this->kkAdminRole->id, (int) $target->fresh()->role_id);
        $this->assertSame('active', $target->fresh()->activation_status);
    }

    public function test_ordinary_user_cannot_use_users_endpoints(): void
    {
        $ordinary = $this->userWithRole('korisnik', 'Plain', 'adm-c1-plain@example.com');
        $target = $this->userWithRole('korisnik', 'Target', 'adm-c1-target@example.com');

        $this->actingAs($ordinary)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($ordinary)
            ->put(route('admin.users.update', $target), $this->updatePayload($target, [
                'role_id' => $this->kkAdminRole->id,
            ]))
            ->assertForbidden();

        $this->assertSame($this->korisnikRole->id, (int) $target->fresh()->role_id);
    }

    public function test_kk_admin_does_not_get_platform_users_access(): void
    {
        $editor = $this->userWithRole('kk_admin', 'Editor', 'adm-c1-editor@example.com');
        $target = $this->userWithRole('korisnik', 'Stay Korisnik', 'adm-c1-stay@example.com');

        $this->actingAs($editor)
            ->get(route('admin.users.index'))
            ->assertRedirect(route('cultural-calendar.index'));

        $this->actingAs($editor)
            ->put(route('admin.users.update', $target), $this->updatePayload($target, [
                'role_id' => $this->kkAdminRole->id,
            ]))
            ->assertRedirect(route('cultural-calendar.index'));

        $this->assertSame($this->korisnikRole->id, (int) $target->fresh()->role_id);
    }

    public function test_optional_password_stays_unchanged_until_a_confirmed_replacement(): void
    {
        $omitted = $this->userWithRole('korisnik', 'Bez Lozinke', 'adm-pass-omitted@example.com');
        $omittedHash = $omitted->password;
        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $omitted), $this->updatePayload($omitted, [
                'first_name' => 'Izmjena',
            ]))
            ->assertRedirect(route('admin.users.show', $omitted));
        $this->assertSame($omittedHash, $omitted->fresh()->password);
        $this->assertSame('Izmjena', $omitted->fresh()->first_name);

        $blank = $this->userWithRole('korisnik', 'Prazna Lozinka', 'adm-pass-blank@example.com');
        $blankHash = $blank->password;
        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $blank), $this->updatePayload($blank, [
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertRedirect(route('admin.users.show', $blank));
        $this->assertSame($blankHash, $blank->fresh()->password);

        $granted = $this->userWithRole('korisnik', 'Mmsp Lozinka', 'adm-pass-mmsp@example.com');
        $grantedHash = $granted->password;
        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $granted), $this->updatePayload($granted, [
                'mmsp_test_access' => '1',
            ]))
            ->assertRedirect(route('admin.users.show', $granted));
        $this->assertSame($grantedHash, $granted->fresh()->password);
        $this->assertSame(
            MmspTestAccessGrant::ACTION_GRANTED,
            MmspTestAccessGrant::query()->where('user_id', $granted->id)->orderByDesc('id')->value('action')
        );

        $missingConfirmation = $this->userWithRole('korisnik', 'Bez Potvrde', 'adm-pass-missing@example.com');
        $missingHash = $missingConfirmation->password;
        $this->actingAs($this->admin)
            ->from(route('admin.users.edit', $missingConfirmation))
            ->put(route('admin.users.update', $missingConfirmation), $this->updatePayload($missingConfirmation, [
                'password' => 'new-password-1',
            ]))
            ->assertSessionHasErrors(['password' => 'validation.confirmed']);
        $this->assertSame($missingHash, $missingConfirmation->fresh()->password);

        $mismatch = $this->userWithRole('korisnik', 'Razlicita Lozinka', 'adm-pass-mismatch@example.com');
        $mismatchHash = $mismatch->password;
        $this->actingAs($this->admin)
            ->from(route('admin.users.edit', $mismatch))
            ->put(route('admin.users.update', $mismatch), $this->updatePayload($mismatch, [
                'password' => 'new-password-1',
                'password_confirmation' => 'new-password-2',
            ]))
            ->assertSessionHasErrors(['password' => 'validation.confirmed']);
        $this->assertSame($mismatchHash, $mismatch->fresh()->password);

        $tooShort = $this->userWithRole('korisnik', 'Kratka Lozinka', 'adm-pass-short@example.com');
        $shortHash = $tooShort->password;
        $this->actingAs($this->admin)
            ->from(route('admin.users.edit', $tooShort))
            ->put(route('admin.users.update', $tooShort), $this->updatePayload($tooShort, [
                'password' => 'short',
                'password_confirmation' => 'short',
            ]))
            ->assertSessionHasErrors('password');
        $this->assertSame($shortHash, $tooShort->fresh()->password);

        $replaced = $this->userWithRole('korisnik', 'Nova Lozinka', 'adm-pass-new@example.com');
        $replacedHash = $replaced->password;
        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $replaced), $this->updatePayload($replaced, [
                'password' => 'new-password-1',
                'password_confirmation' => 'new-password-1',
            ]))
            ->assertRedirect(route('admin.users.show', $replaced));
        $this->assertNotSame($replacedHash, $replaced->fresh()->password);
        $this->assertTrue(Hash::check('new-password-1', $replaced->fresh()->password));
    }

    public function test_edit_form_marks_both_password_fields_as_new_password(): void
    {
        $this->withoutVite();
        $target = $this->userWithRole('korisnik', 'Html Lozinka', 'adm-pass-html@example.com');

        $html = $this->actingAs($this->admin)
            ->get(route('admin.users.edit', $target))
            ->assertOk()
            ->getContent();

        $this->assertSame(2, substr_count($html, 'autocomplete="new-password"'));
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('name="password_confirmation"', $html);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function updatePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'first_name' => $user->first_name ?: 'Ime',
            'last_name' => $user->last_name ?: 'Prezime',
            'email' => $user->email,
            'phone' => $user->phone,
            'role_id' => $user->role_id,
            'activation_status' => $user->activation_status ?: 'active',
        ], $overrides);
    }

    private function userWithRole(string $role, string $name, string $email, string $activation = 'active'): User
    {
        $parts = explode(' ', $name, 2);

        return User::factory()->create([
            'name' => $name,
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? 'User',
            'email' => $email,
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => $activation,
        ]);
    }
}
