<?php

namespace Tests\Feature;

use App\Models\PaymentAccount;
use App\Models\PaymentAccountAvailability;
use App\Models\PaymentType;
use App\Models\PaymentTypeAvailability;
use App\Models\Role;
use App\Models\User;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PaymentAvailabilityAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $this->admin = $this->userWithRole('admin', 'Platform Admin', 'ep-av-admin@example.com');
    }

    public function test_guest_cannot_access_availability_admin(): void
    {
        $type = PaymentType::factory()->create(['code' => 'syn-av-guest']);

        $this->get(route('admin.e-payments.payment-types.availabilities.index', $type))
            ->assertRedirect(route('login'));
    }

    public function test_ordinary_user_cannot_access_availability_admin(): void
    {
        $type = PaymentType::factory()->create(['code' => 'syn-av-user']);
        $user = $this->userWithRole('korisnik', 'Ordinary User', 'ep-av-user@example.com');

        $this->actingAs($user)
            ->get(route('admin.e-payments.payment-types.availabilities.index', $type))
            ->assertForbidden();
    }

    public function test_staff_roles_cannot_use_availability_admin(): void
    {
        $type = PaymentType::factory()->create(['code' => 'syn-av-staff']);

        $this->actingAs($this->userWithRole('kk_admin', 'KK Admin', 'ep-av-kk@example.com'))
            ->get(route('admin.e-payments.payment-types.availabilities.index', $type))
            ->assertRedirect(route('cultural-calendar.index'));

        $this->actingAs($this->userWithRole('konkurs_admin', 'Konkurs Admin', 'ep-av-konkurs@example.com'))
            ->get(route('admin.e-payments.payment-types.availabilities.index', $type))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($this->userWithRole('komisija', 'Commission', 'ep-av-komisija@example.com'))
            ->get(route('admin.e-payments.payment-types.availabilities.index', $type))
            ->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_create_type_availability_and_ui_lists_canonical_eight(): void
    {
        $type = PaymentType::factory()->create(['code' => 'syn-av-type', 'is_active' => false]);

        $create = $this->actingAs($this->admin)
            ->get(route('admin.e-payments.payment-types.availabilities.create', $type))
            ->assertOk();

        foreach (UserType::canonicalStorageValues() as $value) {
            $create->assertSee($value, false);
        }

        $create->assertDontSee('Poljoprivrednik', false);
        $create->assertDontSee('Dio stranog društva', false);
        $create->assertDontSee('Individualni sportista', false);
        $create->assertDontSee('Udruženje (nvo, fondacije, sportske organizacije)', false);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.availabilities.store', $type), [
                'user_type' => UserType::PHYSICAL_PERSON,
                'residential_status' => 'resident',
            ])
            ->assertRedirect(route('admin.e-payments.payment-types.availabilities.index', $type));

        $this->assertDatabaseHas('payment_type_availabilities', [
            'payment_type_id' => $type->id,
            'user_type' => UserType::PHYSICAL_PERSON,
            'residential_status' => 'resident',
            'is_active' => 1,
        ]);
    }

    public function test_admin_can_create_account_availability(): void
    {
        [$type, $account] = $this->syntheticAccount();

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.accounts.availabilities.store', [$type, $account]), [
                'user_type' => UserType::ENTREPRENEUR,
                'residential_status' => 'non-resident',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payment_account_availabilities', [
            'payment_account_id' => $account->id,
            'user_type' => UserType::ENTREPRENEUR,
            'residential_status' => 'non-resident',
            'is_active' => 1,
        ]);
    }

    public function test_legacy_and_invalid_user_types_are_rejected(): void
    {
        $type = PaymentType::factory()->create(['code' => 'syn-av-legacy']);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.availabilities.store', $type), [
                'user_type' => UserType::LEGACY_FOREIGN_BRANCH,
            ])
            ->assertSessionHasErrors('user_type');

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.availabilities.store', $type), [
                'user_type' => 'DOO',
            ])
            ->assertSessionHasErrors('user_type');

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.availabilities.store', $type), [
                'user_type' => 'Poljoprivrednik',
            ])
            ->assertSessionHasErrors('user_type');
    }

    public function test_legal_entity_plus_residential_is_rejected(): void
    {
        $type = PaymentType::factory()->create(['code' => 'syn-av-legal-res']);

        foreach (UserType::canonicalLegalEntityStorageValues() as $legalType) {
            $this->actingAs($this->admin)
                ->post(route('admin.e-payments.payment-types.availabilities.store', $type), [
                    'user_type' => $legalType,
                    'residential_status' => 'resident',
                ])
                ->assertSessionHasErrors('residential_status');
        }
    }

    public function test_natural_person_requires_valid_residential(): void
    {
        $type = PaymentType::factory()->create(['code' => 'syn-av-nat-res']);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.availabilities.store', $type), [
                'user_type' => UserType::PHYSICAL_PERSON,
            ])
            ->assertSessionHasErrors('residential_status');

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.availabilities.store', $type), [
                'user_type' => UserType::ENTREPRENEUR,
                'residential_status' => 'ex-non-resident',
            ])
            ->assertSessionHasErrors('residential_status');

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.availabilities.store', $type), [
                'user_type' => UserType::PHYSICAL_PERSON,
                'residential_status' => 'resident',
            ])
            ->assertSessionDoesntHaveErrors();
    }

    public function test_duplicate_combination_is_rejected_for_type_and_account(): void
    {
        [$type, $account] = $this->syntheticAccount();

        $payload = [
            'user_type' => UserType::SPORTS_ORGANIZATION,
            'residential_status' => null,
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.availabilities.store', $type), $payload)
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.availabilities.store', $type), $payload)
            ->assertSessionHasErrors('user_type');

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.accounts.availabilities.store', [$type, $account]), $payload)
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.accounts.availabilities.store', [$type, $account]), $payload)
            ->assertSessionHasErrors('user_type');

        $this->expectException(QueryException::class);
        PaymentTypeAvailability::factory()->create([
            'payment_type_id' => $type->id,
            'user_type' => UserType::SPORTS_ORGANIZATION,
            'residential_status' => null,
        ]);
    }

    public function test_activate_deactivate_rule_without_hard_delete(): void
    {
        $type = PaymentType::factory()->create(['code' => 'syn-av-toggle']);
        $rule = PaymentTypeAvailability::factory()->create([
            'payment_type_id' => $type->id,
            'user_type' => UserType::NGO_ASSOCIATION,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.availabilities.deactivate', [$type, $rule]))
            ->assertRedirect();

        $this->assertFalse($rule->fresh()->is_active);
        $this->assertDatabaseHas('payment_type_availabilities', ['id' => $rule->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.availabilities.activate', [$type, $rule]))
            ->assertRedirect();

        $this->assertTrue($rule->fresh()->is_active);

        $this->assertFalse(Route::has('admin.e-payments.payment-types.availabilities.destroy'));
        $this->assertFalse(Route::has('admin.e-payments.payment-types.accounts.availabilities.destroy'));

        $this->actingAs($this->admin)
            ->delete('/admin/e-placanje/payment-types/'.$type->id.'/availabilities/'.$rule->id.'/deactivate')
            ->assertStatus(405);

        $this->assertDatabaseHas('payment_type_availabilities', ['id' => $rule->id]);
    }

    public function test_route_tampering_is_not_found(): void
    {
        $typeA = PaymentType::factory()->create(['code' => 'syn-av-a']);
        $typeB = PaymentType::factory()->create(['code' => 'syn-av-b']);
        $rule = PaymentTypeAvailability::factory()->create([
            'payment_type_id' => $typeA->id,
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.availabilities.deactivate', [$typeB, $rule]))
            ->assertNotFound();

        $accountA = PaymentAccount::factory()->create([
            'payment_type_id' => $typeA->id,
            'account_number' => 'SYN-AV-AAA-000000000001',
        ]);
        $accountB = PaymentAccount::factory()->create([
            'payment_type_id' => $typeB->id,
            'account_number' => 'SYN-AV-BBB-000000000001',
        ]);
        $accountRule = PaymentAccountAvailability::factory()->create([
            'payment_account_id' => $accountA->id,
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.e-payments.payment-types.accounts.availabilities.index', [$typeB, $accountA]))
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.accounts.availabilities.deactivate', [$typeA, $accountB, $accountRule]))
            ->assertNotFound();
    }

    public function test_catalog_activation_does_not_require_availability_rules(): void
    {
        $type = PaymentType::factory()->create([
            'code' => 'syn-av-keep-activation',
            'name' => 'Synthetic keep activation',
            'is_active' => false,
        ]);
        PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => 'SYN-AV-KEEP-00000000001',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.activate', $type))
            ->assertSessionHas('success');

        $this->assertTrue($type->fresh()->is_active);
    }

    public function test_user_payments_stub_is_unchanged(): void
    {
        $user = $this->userWithRole('korisnik', 'Payer', 'ep-av-payer@example.com');

        $this->actingAs($user)
            ->get(route('payments.index'))
            ->assertOk();
    }

    /**
     * @return array{0: PaymentType, 1: PaymentAccount}
     */
    private function syntheticAccount(): array
    {
        $type = PaymentType::factory()->create(['code' => 'syn-av-acc-parent', 'is_active' => false]);
        $account = PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => 'SYN-AV-ACC-000000000001',
            'is_active' => false,
        ]);

        return [$type, $account];
    }

    private function userWithRole(string $role, string $name, string $email): User
    {
        $parts = explode(' ', $name, 2);

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
