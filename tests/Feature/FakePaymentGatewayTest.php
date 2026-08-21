<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\PaymentInitiation;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Payments\FakePaymentGatewayUnavailableException;
use App\Services\Payments\PaymentGatewayNotConfiguredException;
use App\Services\Payments\PaymentGatewayResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\Support\StartsSyntheticPayment;
use Tests\TestCase;

class FakePaymentGatewayTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesSyntheticPaymentCatalog;
    use RefreshDatabase;
    use StartsSyntheticPayment;

    private User $payer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $this->payer = $this->makeKorisnik();
    }

    public function test_explicit_start_redirects_to_labelled_local_simulator(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-p5-sim', 'SYN-P5-SIM-0000000000001');
        $transaction = $this->launchProcessing($this->payer, $type);

        $this->openSimulator($this->payer, $transaction)
            ->assertOk()
            ->assertSee('LOKALNI SIMULATOR PLAĆANJA')
            ->assertSee('Ovo nije stvarni payment gateway.')
            ->assertSee('Simuliraj uspješno plaćanje')
            ->assertSee('Simuliraj neuspješno plaćanje')
            ->assertSee('Simuliraj otkazivanje');
    }

    public function test_unsigned_simulator_route_is_rejected(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-p5-uns', 'SYN-P5-UNS-0000000000001');
        $transaction = $this->launchProcessing($this->payer, $type);

        $this->actingAs($this->payer)
            ->get(route('payments.fake.show', $transaction))
            ->assertForbidden();
    }

    public function test_user_cannot_open_another_users_simulator(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-p5-idor', 'SYN-P5-IDOR-00000000001');
        $transaction = $this->launchProcessing($this->payer, $type);
        $other = $this->makeKorisnik(['email' => 'p5-other@example.com', 'jmb' => $this->validJmb(11)]);

        $this->openSimulator($other, $transaction)->assertNotFound();
        $this->simulateOutcome($other, $transaction, PaymentStatus::Successful->value)->assertNotFound();
        $this->actingAs($other)
            ->get(route('payments.result', $transaction))
            ->assertNotFound();
    }

    public function test_production_environment_refuses_fake_provider(): void
    {
        config([
            'app.env' => 'production',
            'payments.gateway' => 'fake',
            'payments.fake.enabled' => true,
        ]);

        $this->expectException(FakePaymentGatewayUnavailableException::class);
        app(PaymentGatewayResolver::class)->resolve();
    }

    public function test_unconfigured_provider_fails_closed(): void
    {
        config(['payments.gateway' => null]);

        $this->expectException(PaymentGatewayNotConfiguredException::class);
        app(PaymentGatewayResolver::class)->resolve();
    }

    public function test_fake_routes_are_unavailable_when_fake_is_disabled(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-p5-offgw', 'SYN-P5-OFFGW-0000000001');
        $transaction = $this->launchProcessing($this->payer, $type);

        config([
            'app.env' => 'production',
            'payments.fake.enabled' => false,
        ]);

        $this->actingAs($this->payer)
            ->get(URL::signedRoute('payments.fake.show', ['payment_transaction' => $transaction->uuid]))
            ->assertNotFound();
    }

    public function test_gateway_cancel_creates_cancelled_transaction_unlike_pre_start_abandon(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-p5-can', 'SYN-P5-CAN-0000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '7.00');

        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Cancelled->value)
            ->assertRedirect(route('payments.result', $transaction));

        $this->assertSame(PaymentStatus::Cancelled, $transaction->fresh()->status);
        $this->assertSame(1, PaymentTransaction::query()->count());
        $this->assertSame(1, PaymentInitiation::query()->count());
    }

    public function test_cancel_button_is_enabled_and_rendered_form_posts_cancelled(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-p5-cui', 'SYN-P5-CUI-0000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '5.00');

        $html = html_entity_decode(
            $this->openSimulator($this->payer, $transaction)->assertOk()->getContent(),
            ENT_QUOTES
        );

        $this->assertMatchesRegularExpression(
            '/<form method="POST" action="[^"]*\/cancelled\?[^"]*"/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<button type="submit" class="[^"]+">Simuliraj otkazivanje<\/button>/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]*\bdisabled\b[^>]*>Simuliraj otkazivanje<\/button>/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<button type="submit" class="[^"]*bg-gray-700[^"]*">Simuliraj otkazivanje<\/button>/',
            $html
        );

        preg_match(
            '/<form method="POST" action="([^"]+\/cancelled\?[^"]+)"[^>]*>\s*.*?Simuliraj otkazivanje/s',
            $html,
            $matches
        );
        $this->assertNotEmpty($matches[1] ?? null);
        $this->assertStringContainsString('/cancelled?', $matches[1]);

        $this->actingAs($this->payer)
            ->post($matches[1])
            ->assertRedirect(route('payments.result', $transaction));

        $this->assertSame(PaymentStatus::Cancelled, $transaction->fresh()->status);
    }
}
