<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Models\Role;
use App\Models\User;
use App\Services\Payments\FakePaymentGateway;
use App\Services\Payments\PaymentGatewayNotConfiguredException;
use App\Services\Payments\PaymentGatewayResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\Support\StartsSyntheticPayment;
use Tests\TestCase;

class PaymentProviderIdentityTest extends TestCase
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
        $this->admin = $this->userWithRole('admin', 'Platform Admin', 'ep-prov-admin@example.com');
        $this->payer = $this->makeKorisnik(['email' => 'payer-prov@example.com', 'jmb' => $this->validJmb(61)]);
    }

    public function test_new_transaction_persists_gateway_name_not_request_value(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-prov-new', 'SYN-PROV-NEW-00000000001');
        $this->reachPreview($this->payer, $type, '8.00');

        $this->actingAs($this->payer)->post(route('payments.launch'), [
            'provider' => 'client-forged',
        ])->assertRedirect();

        $transaction = PaymentTransaction::query()->where('user_id', $this->payer->id)->latest('id')->firstOrFail();
        $this->assertSame(app(FakePaymentGateway::class)->name(), $transaction->provider);
        $this->assertArrayNotHasKey('provider', $transaction->snapshot ?? []);
    }

    public function test_provider_is_immutable_and_survives_global_config_change(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-prov-imm', 'SYN-PROV-IMM-00000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '6.00');
        $original = $transaction->provider;

        $transaction->provider = 'other-provider';
        $transaction->save();
        $this->assertSame($original, $transaction->fresh()->provider);

        config(['payments.gateway' => 'unknown-provider']);
        $this->assertSame($original, $transaction->fresh()->provider);
        $this->assertSame(
            app(FakePaymentGateway::class)->name(),
            app(PaymentGatewayResolver::class)->forTransaction($transaction->fresh())->name()
        );

        $this->actingAs($this->admin)->get(route('admin.e-payments.transactions.show', $transaction))
            ->assertOk()
            ->assertSee('Provajder')
            ->assertSee('fake')
            ->assertDontSee('Trenutno konfigurisani provajder')
            ->assertDontSee('Provjeri status');
    }

    public function test_null_legacy_provider_inquiry_fails_closed(): void
    {
        $transaction = PaymentTransaction::factory()->create([
            'user_id' => $this->payer->id,
            'status' => PaymentStatus::Processing,
            'provider' => null,
        ]);

        try {
            app(PaymentGatewayResolver::class)->forTransaction($transaction);
            $this->fail('Null historical provider must fail closed.');
        } catch (PaymentGatewayNotConfiguredException) {
        }

        $this->actingAs($this->admin)->get(route('admin.e-payments.transactions.show', $transaction))
            ->assertOk()
            ->assertSee('Nepoznato')
            ->assertDontSee('Provjeri status');
        $this->actingAs($this->admin)
            ->post(route('admin.e-payments.transactions.check-status', $transaction))
            ->assertSessionHas('error', 'Provajder ne podržava provjeru statusa.');
        $this->assertSame(PaymentStatus::Processing, $transaction->fresh()->status);
    }

    public function test_unknown_persisted_provider_fails_closed(): void
    {
        $transaction = PaymentTransaction::factory()->create([
            'user_id' => $this->payer->id,
            'status' => PaymentStatus::Processing,
            'provider' => 'not-a-real-provider',
        ]);

        $this->expectException(PaymentGatewayNotConfiguredException::class);
        app(PaymentGatewayResolver::class)->forTransaction($transaction);
    }

    public function test_fake_persisted_provider_resolves_fake_without_inquiry_cta(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-prov-fake', 'SYN-PROV-FAKE-0000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '3.00');

        $gateway = app(PaymentGatewayResolver::class)->forTransaction($transaction);
        $this->assertSame(app(FakePaymentGateway::class)->name(), $gateway->name());
        $this->assertFalse($gateway->capabilities()->statusInquiry);

        $this->actingAs($this->admin)->get(route('admin.e-payments.transactions.show', $transaction))
            ->assertOk()
            ->assertDontSee('Provjeri status');
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
