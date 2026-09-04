<?php

namespace Tests\Feature;

use App\Models\PaymentAccount;
use App\Models\PaymentType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PaymentCatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $this->admin = $this->userWithRole('admin', 'Platform Admin', 'ep-admin@example.com');
    }

    public function test_guest_cannot_access_catalog_admin(): void
    {
        $this->get(route('admin.e-payments.payment-types.index'))
            ->assertRedirect(route('login'));
    }

    public function test_ordinary_user_cannot_access_catalog_admin(): void
    {
        $user = $this->userWithRole('korisnik', 'Ordinary User', 'ep-user@example.com');

        $this->actingAs($user)
            ->get(route('admin.e-payments.payment-types.index'))
            ->assertForbidden();
    }

    public function test_kk_admin_and_competition_staff_cannot_use_catalog_admin(): void
    {
        $kkAdmin = $this->userWithRole('kk_admin', 'KK Admin', 'ep-kk@example.com');
        $this->actingAs($kkAdmin)
            ->get(route('admin.e-payments.payment-types.index'))
            ->assertRedirect(route('cultural-calendar.index'));

        $konkursAdmin = $this->userWithRole('konkurs_admin', 'Konkurs Admin', 'ep-konkurs@example.com');
        $this->actingAs($konkursAdmin)
            ->get(route('admin.e-payments.payment-types.index'))
            ->assertRedirect(route('admin.dashboard'));

        $komisija = $this->userWithRole('komisija', 'Commission', 'ep-komisija@example.com');
        $this->actingAs($komisija)
            ->get(route('admin.e-payments.payment-types.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_administrator_and_superadmin_can_access_catalog_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.e-payments.payment-types.index'))
            ->assertOk()
            ->assertSee('Katalog e-Plaćanja');

        $superadmin = $this->userWithRole('superadmin', 'Super Admin', 'ep-super@example.com');
        $this->actingAs($superadmin)
            ->get(route('admin.e-payments.payment-types.index'))
            ->assertOk();
    }

    public function test_admin_can_create_type_inactive_and_validation_rejects_invalid_payload(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.store'), [])
            ->assertSessionHasErrors(['code', 'name']);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.store'), [
                'code' => 'syn-communal',
                'name' => 'Synthetic communal type',
                'description' => 'Test only',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payment_types', [
            'code' => 'syn-communal',
            'name' => 'Synthetic communal type',
            'is_active' => 0,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.store'), [
                'code' => 'syn-communal',
                'name' => 'Duplicate code type',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_admin_can_edit_name_but_not_code(): void
    {
        $type = PaymentType::factory()->create([
            'code' => 'syn-locked',
            'name' => 'Original',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.e-payments.payment-types.update', $type), [
                'name' => 'Updated synthetic type',
                'description' => 'Changed',
                'code' => 'hacked-code',
            ])
            ->assertRedirect(route('admin.e-payments.payment-types.index'));

        $type->refresh();
        $this->assertSame('Updated synthetic type', $type->name);
        $this->assertSame('syn-locked', $type->code);
    }

    public function test_cannot_activate_type_without_active_account_then_can_after_account_activation(): void
    {
        $type = PaymentType::factory()->create([
            'code' => 'syn-activate',
            'name' => 'Synthetic activate type',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.activate', $type))
            ->assertRedirect(route('admin.e-payments.payment-types.index'))
            ->assertSessionHas('error');

        $this->assertFalse($type->fresh()->is_active);

        $account = PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => 'SYN-22222222222222222222',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.accounts.activate', [$type, $account]))
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.activate', $type))
            ->assertRedirect(route('admin.e-payments.payment-types.index'))
            ->assertSessionHas('success');

        $this->assertTrue($type->fresh()->is_active);
    }

    public function test_admin_can_deactivate_and_reactivate_valid_type(): void
    {
        $type = PaymentType::factory()->create([
            'code' => 'syn-cycle',
            'name' => 'Synthetic cycle',
            'is_active' => true,
        ]);
        PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => 'SYN-33333333333333333333',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.deactivate', $type))
            ->assertRedirect();

        $this->assertFalse($type->fresh()->is_active);
        $this->assertDatabaseHas('payment_accounts', ['account_number' => 'SYN-33333333333333333333']);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.activate', $type))
            ->assertSessionHas('success');

        $this->assertTrue($type->fresh()->is_active);
    }

    public function test_no_hard_delete_route_for_types_or_accounts(): void
    {
        $this->assertFalse(Route::has('admin.e-payments.payment-types.destroy'));
        $this->assertFalse(Route::has('admin.e-payments.payment-types.accounts.destroy'));

        $type = PaymentType::factory()->create(['code' => 'syn-nodelete', 'is_active' => false]);

        $this->actingAs($this->admin)
            ->delete('/admin/e-placanje/payment-types/'.$type->id)
            ->assertStatus(405);

        $this->assertDatabaseHas('payment_types', ['id' => $type->id]);
    }

    public function test_admin_can_create_account_and_account_number_is_string_and_unique(): void
    {
        $type = PaymentType::factory()->create(['code' => 'syn-acc', 'is_active' => false]);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.accounts.store', $type), [])
            ->assertSessionHasErrors('account_number');

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.accounts.store', $type), [
                'account_number' => 'SYN-44444444444444444444',
                'name' => 'Synthetic label',
            ])
            ->assertRedirect();

        $account = PaymentAccount::query()->where('account_number', 'SYN-44444444444444444444')->first();
        $this->assertNotNull($account);
        $this->assertIsString($account->account_number);
        $this->assertFalse($account->is_active);

        $otherType = PaymentType::factory()->create(['code' => 'syn-acc-2', 'is_active' => false]);
        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.accounts.store', $otherType), [
                'account_number' => 'SYN-44444444444444444444',
            ])
            ->assertSessionHasErrors('account_number');
    }

    public function test_account_number_cannot_be_overwritten_and_label_can_be_edited(): void
    {
        $type = PaymentType::factory()->create(['code' => 'syn-immut', 'is_active' => false]);
        $account = PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => 'SYN-55555555555555555555',
            'name' => 'Old label',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.e-payments.payment-types.accounts.update', [$type, $account]), [
                'name' => 'New synthetic label',
                'account_number' => 'SYN-HACKED-NUMBER',
            ])
            ->assertRedirect();

        $account->refresh();
        $this->assertSame('SYN-55555555555555555555', $account->account_number);
        $this->assertSame('New synthetic label', $account->name);
    }

    public function test_account_deactivate_and_reactivate_do_not_delete_rows(): void
    {
        $type = PaymentType::factory()->create(['code' => 'syn-acc-cycle', 'is_active' => true]);
        $account = PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => 'SYN-66666666666666666666',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.accounts.deactivate', [$type, $account]))
            ->assertRedirect();

        $this->assertFalse($account->fresh()->is_active);
        $this->assertFalse($type->fresh()->is_active);
        $this->assertDatabaseHas('payment_types', ['id' => $type->id]);
        $this->assertDatabaseHas('payment_accounts', ['id' => $account->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.accounts.activate', [$type, $account]))
            ->assertRedirect();

        $this->assertTrue($account->fresh()->is_active);
    }

    public function test_cannot_manipulate_account_under_another_type(): void
    {
        $typeA = PaymentType::factory()->create(['code' => 'syn-a', 'is_active' => false]);
        $typeB = PaymentType::factory()->create(['code' => 'syn-b', 'is_active' => false]);
        $account = PaymentAccount::factory()->create([
            'payment_type_id' => $typeA->id,
            'account_number' => 'SYN-77777777777777777777',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.e-payments.payment-types.accounts.edit', [$typeB, $account]))
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->put(route('admin.e-payments.payment-types.accounts.update', [$typeB, $account]), [
                'name' => 'Tampered',
            ])
            ->assertNotFound();
    }

    public function test_user_payments_stub_is_unchanged(): void
    {
        $user = $this->userWithRole('korisnik', 'Payer', 'ep-payer@example.com');

        $this->actingAs($user)
            ->get(route('payments.index'))
            ->assertOk();
    }

    private function userWithRole(string $role, string $name, string $email): User
    {
        $parts = explode(' ',$name, 2);

        return User::factory()->create([
            'name' => $name,
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? 'User',
            'email' => $email,
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
    }
}
