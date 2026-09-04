<?php

namespace Tests\Feature;

use App\Models\PaymentAccount;
use App\Models\PaymentInitiation;
use App\Models\PaymentTransaction;
use App\Models\PaymentType;
use App\Models\Role;
use App\Models\User;
use App\Services\Payments\EpModuleSettings;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\TestCase;

class PaymentUserFlowTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesSyntheticPaymentCatalog;
    use RefreshDatabase;

    private User $payer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $this->payer = $this->makeKorisnik();
    }

    public function test_guest_cannot_browse_payments(): void
    {
        $this->get(route('payments.index'))->assertRedirect(route('login'));
    }

    public function test_available_type_is_visible_and_unavailable_hidden(): void
    {
        [$visible] = $this->syntheticUsablePair($this->payer, 'syn-visible', 'SYN-FLOW-VISIBLE-0000001');

        $hidden = PaymentType::factory()->create([
            'code' => 'syn-hidden',
            'name' => 'Hidden synthetic type',
            'is_active' => true,
        ]);
        $hiddenAccount = PaymentAccount::factory()->create([
            'payment_type_id' => $hidden->id,
            'account_number' => 'SYN-FLOW-HIDDEN-00000001',
            'is_active' => true,
        ]);
        $this->grantAvailability($hidden, $hiddenAccount, UserType::JOINT_STOCK_COMPANY, null);

        $this->actingAs($this->payer)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Synthetic user-flow type')
            ->assertDontSee('Hidden synthetic type');
    }

    public function test_active_type_without_available_account_is_hidden(): void
    {
        $type = PaymentType::factory()->create([
            'code' => 'syn-no-acc',
            'name' => 'Type without usable account',
            'is_active' => true,
        ]);
        PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => 'SYN-FLOW-NOACC-000000001',
            'is_active' => true,
        ]);
        \App\Models\PaymentTypeAvailability::factory()->create([
            'payment_type_id' => $type->id,
            'user_type' => UserType::PHYSICAL_PERSON,
            'residential_status' => 'resident',
            'is_active' => true,
        ]);

        $this->actingAs($this->payer)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertDontSee('Type without usable account');
    }

    public function test_inactive_type_is_hidden(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-inactive-ui', 'SYN-FLOW-INACTIVE-000001');
        $type->update(['is_active' => false]);

        $this->actingAs($this->payer)
            ->get(route('payments.index'))
            ->assertDontSee('Synthetic user-flow type');
    }

    public function test_legacy_user_sees_empty_state(): void
    {
        $legacy = $this->makeKorisnik([
            'user_type' => UserType::LEGACY_ASSOCIATION_BUNDLE,
            'residential_status' => null,
            'company_name' => 'Legacy org',
            'pib' => '12345678',
            'jmb' => null,
        ]);
        $this->syntheticUsablePair($this->payer, 'syn-legacy-cat', 'SYN-FLOW-LEGACY-00000001');

        $this->actingAs($legacy)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Trenutno nema dostupnih vrsta plaćanja za vaš korisnički profil.');
    }

    public function test_empty_catalog_shows_empty_state(): void
    {
        $this->actingAs($this->payer)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Trenutno nema dostupnih vrsta plaćanja za vaš korisnički profil.');
    }

    public function test_single_account_is_auto_selected(): void
    {
        [$type, $account] = $this->syntheticUsablePair($this->payer);

        $this->actingAs($this->payer)
            ->get(route('payments.start', $type))
            ->assertRedirect(route('payments.amount.edit'));

        $this->actingAs($this->payer)
            ->get(route('payments.amount.edit'))
            ->assertOk()
            ->assertSee($account->account_number)
            ->assertDontSee('Izbor računa');
    }

    public function test_user_cannot_tamper_with_another_account(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-tamper-a', 'SYN-FLOW-TAMPER-A-000001');
        $otherType = PaymentType::factory()->create(['code' => 'syn-tamper-b', 'is_active' => true]);
        $foreign = PaymentAccount::factory()->create([
            'payment_type_id' => $otherType->id,
            'account_number' => 'SYN-FLOW-FOREIGN-00000001',
            'is_active' => true,
        ]);

        $this->actingAs($this->payer)
            ->from(route('payments.start', $type))
            ->post(route('payments.account.store', $type), [
                'payment_account_id' => $foreign->id,
                'account_number' => 'SYN-HACKED-IBAN',
            ])
            ->assertRedirect();

        $this->actingAs($this->payer)
            ->get(route('payments.amount.edit'))
            ->assertRedirect(route('payments.index'));
    }

    public function test_inactive_and_unavailable_accounts_are_rejected(): void
    {
        [$type, $inactive] = $this->syntheticUsablePair($this->payer, 'syn-rej', 'SYN-FLOW-REJ-00000000001');
        $inactive->update(['is_active' => false]);

        $this->actingAs($this->payer)
            ->get(route('payments.start', $type))
            ->assertRedirect(route('payments.index'));
    }

    public function test_multiple_accounts_require_selection_and_hide_unavailable(): void
    {
        $type = PaymentType::factory()->create([
            'code' => 'syn-multi',
            'name' => 'Synthetic multi account type',
            'is_active' => true,
        ]);
        $first = PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => 'SYN-ACCOUNT-001',
            'is_active' => true,
        ]);
        $second = PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => 'SYN-ACCOUNT-002',
            'is_active' => true,
        ]);
        $hidden = PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => 'SYN-ACCOUNT-HIDDEN',
            'is_active' => true,
        ]);
        $this->grantTypeAvailability($type, UserType::PHYSICAL_PERSON, 'resident');
        $this->grantAccountAvailability($first, UserType::PHYSICAL_PERSON, 'resident');
        $this->grantAccountAvailability($second, UserType::PHYSICAL_PERSON, 'resident');
        $this->grantTypeAvailability($type, UserType::LIMITED_LIABILITY_COMPANY, null);
        $this->grantAccountAvailability($hidden, UserType::LIMITED_LIABILITY_COMPANY, null);

        $this->actingAs($this->payer)
            ->get(route('payments.start', $type))
            ->assertOk()
            ->assertSee('Izbor računa')
            ->assertSee('SYN-ACCOUNT-001')
            ->assertSee('SYN-ACCOUNT-002')
            ->assertDontSee('SYN-ACCOUNT-HIDDEN');

        $this->actingAs($this->payer)
            ->post(route('payments.account.store', $type), [
                'payment_account_id' => $hidden->id,
            ])
            ->assertRedirect();

        $this->actingAs($this->payer)
            ->post(route('payments.account.store', $type), [
                'payment_account_id' => $second->id,
            ])
            ->assertRedirect(route('payments.amount.edit'));

        $this->actingAs($this->payer)
            ->get(route('payments.amount.edit'))
            ->assertSee('SYN-ACCOUNT-002');
    }

    public function test_amount_validation(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer);
        $this->actingAs($this->payer)->get(route('payments.start', $type));

        foreach (['0', '-1', '1.001', 'abc', '1e2'] as $invalid) {
            $this->actingAs($this->payer)
                ->from(route('payments.amount.edit'))
                ->post(route('payments.amount.store'), ['amount' => $invalid])
                ->assertSessionHasErrors('amount');
        }

        $this->actingAs($this->payer)
            ->post(route('payments.amount.store'), [
                'amount' => '0.01',
                'currency' => 'USD',
                'user_type' => UserType::JOINT_STOCK_COMPANY,
            ])
            ->assertRedirect(route('payments.preview'));

        $this->actingAs($this->payer)
            ->get(route('payments.preview'))
            ->assertOk()
            ->assertSee('0.01')
            ->assertSee('EUR')
            ->assertDontSee('USD');
    }

    public function test_preview_is_server_derived_and_creates_no_transaction(): void
    {
        [$type, $account] = $this->syntheticUsablePair($this->payer);
        $txBefore = PaymentTransaction::query()->count();
        $initBefore = PaymentInitiation::query()->count();

        $this->actingAs($this->payer)->get(route('payments.start', $type));
        $this->actingAs($this->payer)->post(route('payments.amount.store'), ['amount' => '12.50']);

        $this->actingAs($this->payer)
            ->get(route('payments.preview'))
            ->assertOk()
            ->assertSee('Ana Anić')
            ->assertSee('Fizičko lice')
            ->assertSee('Synthetic user-flow type')
            ->assertSee($account->account_number)
            ->assertSee('12.50')
            ->assertSee('0,00 EUR')
            ->assertSee('Pregled nije potpuna uplatnica')
            ->assertSee('Pokreni plaćanje')
            ->assertDontSee('Pokretanje payment gateway-a biće uvedeno u sljedećoj fazi.');

        $this->actingAs($this->payer)
            ->get(route('payments.amount.edit'))
            ->assertOk()
            ->assertSee('12.50');

        $this->actingAs($this->payer)
            ->post(route('payments.abandon'))
            ->assertRedirect(route('payments.index'));

        $this->assertSame($txBefore, PaymentTransaction::query()->count());
        $this->assertSame($initBefore, PaymentInitiation::query()->count());
    }

    public function test_legacy_pay_post_creates_no_transaction(): void
    {
        $before = PaymentTransaction::query()->count();

        $this->actingAs($this->payer)
            ->post(route('payments.pay'), ['amount' => '99', 'payment_type' => 'komunalije'])
            ->assertRedirect(route('payments.index'));

        $this->assertSame($before, PaymentTransaction::query()->count());
        $this->assertSame(0, PaymentInitiation::query()->count());
    }

    public function test_stale_catalog_fails_closed_on_preview(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-stale', 'SYN-FLOW-STALE-000000001');
        $this->actingAs($this->payer)->get(route('payments.start', $type));
        $this->actingAs($this->payer)->post(route('payments.amount.store'), ['amount' => '5.00']);

        $type->update(['is_active' => false]);

        $this->actingAs($this->payer)
            ->get(route('payments.preview'))
            ->assertRedirect(route('payments.index'))
            ->assertSessionHas('error');
    }

    public function test_session_draft_from_another_user_is_rejected(): void
    {
        [$type, $account] = $this->syntheticUsablePair($this->payer, 'syn-own', 'SYN-FLOW-OWN-00000000001');
        $other = $this->makeKorisnik(['email' => 'other-flow@example.com', 'jmb' => $this->validJmb(2)]);

        $this->actingAs($other)
            ->withSession([
                'ep_payment_draft' => [
                    'user_id' => $this->payer->id,
                    'payment_type_id' => $type->id,
                    'payment_account_id' => $account->id,
                    'amount' => '9.00',
                    'currency' => 'EUR',
                ],
            ])
            ->get(route('payments.preview'))
            ->assertRedirect(route('payments.index'));
    }

    public function test_module_disabled_blocks_browse_and_preview(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-off', 'SYN-FLOW-OFF-00000000001');
        $this->actingAs($this->payer)->get(route('payments.start', $type));
        $this->actingAs($this->payer)->post(route('payments.amount.store'), ['amount' => '3.00']);

        app(EpModuleSettings::class)->setNewPaymentsEnabled(false);

        $this->actingAs($this->payer)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Nova e-Plaćanja su privremeno nedostupna.')
            ->assertDontSee('Synthetic user-flow type');

        $this->actingAs($this->payer)
            ->get(route('payments.preview'))
            ->assertRedirect(route('payments.index'));

        app(EpModuleSettings::class)->setNewPaymentsEnabled(true);

        $this->actingAs($this->payer)
            ->get(route('payments.index'))
            ->assertSee('Synthetic user-flow type');
    }

    public function test_ordinary_user_cannot_toggle_module(): void
    {
        $this->actingAs($this->payer)
            ->get(route('admin.e-payments.settings.edit'))
            ->assertForbidden();
    }

    public function test_admin_can_disable_and_enable_new_payments(): void
    {
        $admin = $this->makeKorisnik([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'email' => 'ep-flow-admin@example.com',
            'jmb' => $this->validJmb(3),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.e-payments.settings.update'), ['new_payments_enabled' => '0'])
            ->assertRedirect(route('admin.e-payments.settings.edit'));

        $this->assertFalse(app(EpModuleSettings::class)->newPaymentsEnabled());

        $this->actingAs($admin)
            ->post(route('admin.e-payments.settings.update'), ['new_payments_enabled' => '1'])
            ->assertRedirect();

        $this->assertTrue(app(EpModuleSettings::class)->newPaymentsEnabled());
    }
}
