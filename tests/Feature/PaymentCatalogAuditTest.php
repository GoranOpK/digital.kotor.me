<?php

namespace Tests\Feature;

use App\Models\PaymentAccount;
use App\Models\PaymentAccountAvailability;
use App\Models\PaymentCatalogAudit;
use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionEvent;
use App\Models\PaymentType;
use App\Models\PaymentTypeAvailability;
use App\Models\Role;
use App\Models\User;
use App\Services\Payments\EpModuleSettings;
use App\Services\Payments\PaymentCatalogAuditAction;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\Support\StartsSyntheticPayment;
use Tests\TestCase;

class PaymentCatalogAuditTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesSyntheticPaymentCatalog;
    use RefreshDatabase;
    use StartsSyntheticPayment;

    private User $admin;

    private User $payer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $this->admin = $this->userWithRole('admin', 'Platform Admin', 'ep-audit-admin@example.com');
        $this->payer = $this->makeKorisnik(['email' => 'payer-audit@example.com', 'jmb' => $this->validJmb(60)]);
    }

    public function test_type_create_update_activate_deactivate_write_one_audit_each(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.store'), [
                'code' => 'syn-aud-type',
                'name' => 'Audit type',
                'description' => 'Desc',
            ])
            ->assertRedirect();

        $type = PaymentType::query()->where('code', 'syn-aud-type')->firstOrFail();
        $this->assertAuditCount(1);
        $created = $this->latestAudit();
        $this->assertSame(PaymentCatalogAuditAction::TYPE_CREATED, $created->action);
        $this->assertSame($this->admin->id, (int) $created->actor_user_id);
        $this->assertSame($type->id, (int) $created->entity_id);
        $this->assertSame('syn-aud-type', $created->changes['code']['to']);
        $this->assertArrayNotHasKey('email', $created->changes);

        $this->actingAs($this->admin)
            ->put(route('admin.e-payments.payment-types.update', $type), [
                'name' => 'Renamed type',
                'description' => 'Desc',
            ])
            ->assertRedirect();

        $this->assertAuditCount(2);
        $updated = $this->latestAudit();
        $this->assertSame(PaymentCatalogAuditAction::TYPE_UPDATED, $updated->action);
        $this->assertSame('Audit type', $updated->changes['name']['from']);
        $this->assertSame('Renamed type', $updated->changes['name']['to']);
        $this->assertArrayNotHasKey('code', $updated->changes);

        PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => 'SYN-AUD-TYPE-000000000001',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.activate', $type))
            ->assertSessionHas('success');
        $this->assertSame(PaymentCatalogAuditAction::TYPE_ACTIVATED, $this->latestAudit()->action);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.deactivate', $type))
            ->assertRedirect();
        $this->assertSame(PaymentCatalogAuditAction::TYPE_DEACTIVATED, $this->latestAudit()->action);
        $this->assertAuditCount(4);
    }

    public function test_account_and_availability_and_module_are_audited(): void
    {
        $type = PaymentType::factory()->create(['code' => 'syn-aud-acc', 'is_active' => false]);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.accounts.store', $type), [
                'account_number' => 'SYN-AUD-ACC-0000000000001',
                'name' => 'Audit account',
            ])
            ->assertRedirect();

        $account = PaymentAccount::query()->where('account_number', 'SYN-AUD-ACC-0000000000001')->firstOrFail();
        $create = $this->latestAudit();
        $this->assertSame(PaymentCatalogAuditAction::ACCOUNT_CREATED, $create->action);
        $this->assertSame('SYN-AUD-ACC-0000000000001', $create->changes['account_number']['to']);

        $this->actingAs($this->admin)
            ->put(route('admin.e-payments.payment-types.accounts.update', [$type, $account]), [
                'name' => 'Renamed account',
            ])
            ->assertRedirect();
        $updated = $this->latestAudit();
        $this->assertSame(PaymentCatalogAuditAction::ACCOUNT_UPDATED, $updated->action);
        $this->assertSame('Renamed account', $updated->changes['name']['to']);
        $this->assertArrayNotHasKey('account_number', $updated->changes);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.accounts.activate', [$type, $account]))
            ->assertRedirect();
        $this->assertSame(PaymentCatalogAuditAction::ACCOUNT_ACTIVATED, $this->latestAudit()->action);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.accounts.deactivate', [$type, $account]))
            ->assertRedirect();
        $this->assertSame(PaymentCatalogAuditAction::ACCOUNT_DEACTIVATED, $this->latestAudit()->action);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.availabilities.store', $type), [
                'user_type' => UserType::PHYSICAL_PERSON,
                'residential_status' => 'resident',
            ])
            ->assertRedirect();
        $this->assertSame(PaymentCatalogAuditAction::TYPE_AVAILABILITY_ADDED, $this->latestAudit()->action);

        $rule = PaymentTypeAvailability::query()->where('payment_type_id', $type->id)->firstOrFail();
        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.availabilities.deactivate', [$type, $rule]))
            ->assertRedirect();
        $this->assertSame(PaymentCatalogAuditAction::TYPE_AVAILABILITY_DEACTIVATED, $this->latestAudit()->action);

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.accounts.availabilities.store', [$type, $account]), [
                'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            ])
            ->assertRedirect();
        $this->assertSame(PaymentCatalogAuditAction::ACCOUNT_AVAILABILITY_ADDED, $this->latestAudit()->action);

        $accountRule = PaymentAccountAvailability::query()->where('payment_account_id', $account->id)->firstOrFail();
        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.accounts.availabilities.deactivate', [$type, $account, $accountRule]))
            ->assertRedirect();
        $this->assertSame(PaymentCatalogAuditAction::ACCOUNT_AVAILABILITY_DEACTIVATED, $this->latestAudit()->action);

        app(EpModuleSettings::class)->setNewPaymentsEnabled(true);
        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.settings.update'), ['new_payments_enabled' => '0'])
            ->assertRedirect();
        $module = $this->latestAudit();
        $this->assertSame(PaymentCatalogAuditAction::MODULE_DISABLED, $module->action);
        $this->assertNull($module->entity_id);
        $this->assertFalse($module->changes['enabled']['to']);
    }

    public function test_failed_validation_unauthorized_and_blocked_activate_do_not_audit(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.store'), [])
            ->assertSessionHasErrors();
        $this->assertAuditCount(0);

        $this->actingAs($this->payer)
            ->post(route('admin.e-payments.payment-types.store'), [
                'code' => 'syn-aud-forbid',
                'name' => 'No',
            ])
            ->assertForbidden();
        $this->assertAuditCount(0);

        $type = PaymentType::factory()->create(['code' => 'syn-aud-block', 'is_active' => false]);
        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.activate', $type))
            ->assertSessionHas('error');
        $this->assertAuditCount(0);
    }

    public function test_reads_and_payment_lifecycle_do_not_mix_audit_streams(): void
    {
        $this->actingAs($this->admin)->get(route('admin.e-payments.payment-types.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.e-payments.settings.edit'))->assertOk();
        $this->assertAuditCount(0);

        [$type] = $this->syntheticUsablePair($this->payer, 'syn-aud-sep', 'SYN-AUD-SEP-000000000001');
        $this->launchProcessing($this->payer, $type, '5.00');

        $this->assertSame(0, PaymentCatalogAudit::query()->count());
        $this->assertGreaterThan(0, PaymentTransaction::query()->count());
        $this->assertGreaterThan(0, PaymentTransactionEvent::query()->count());

        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.payment-types.store'), [
                'code' => 'syn-aud-sep-2',
                'name' => 'Separate',
            ])
            ->assertRedirect();

        $this->assertSame(1, PaymentCatalogAudit::query()->count());
        $this->assertFalse(
            PaymentTransactionEvent::query()
                ->where('event_type', 'like', 'payment_type.%')
                ->exists()
        );
    }

    public function test_audit_rows_cannot_update_or_delete(): void
    {
        $row = PaymentCatalogAudit::factory()->create([
            'actor_user_id' => $this->admin->id,
        ]);

        try {
            $row->update(['action' => 'tampered']);
            $this->fail('Catalog audit update must be rejected.');
        } catch (LogicException $e) {
            $this->assertSame('EP catalog audits are append-only.', $e->getMessage());
        }

        $this->expectException(LogicException::class);
        $row->delete();
    }

    private function assertAuditCount(int $expected): void
    {
        $this->assertSame($expected, PaymentCatalogAudit::query()->count());
    }

    private function latestAudit(): PaymentCatalogAudit
    {
        return PaymentCatalogAudit::query()->orderByDesc('id')->firstOrFail();
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
