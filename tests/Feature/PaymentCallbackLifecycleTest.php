<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionEvent;
use App\Models\User;
use App\Services\Payments\EpModuleSettings;
use App\Services\Payments\FakePaymentGateway;
use App\Services\Payments\PaymentGatewayVerifiedResult;
use App\Services\Payments\PaymentResultProcessor;
use App\Services\Payments\PaymentResultRejectedException;
use App\Services\Payments\PaymentTransactionEventType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\Support\StartsSyntheticPayment;
use Tests\TestCase;

class PaymentCallbackLifecycleTest extends TestCase
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

    public function test_success_flow_and_result_refresh_is_read_only(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-p5-ok', 'SYN-P5-OK-00000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '12.50');

        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Successful->value)
            ->assertRedirect(route('payments.result', $transaction));

        $transaction->refresh();
        $this->assertSame(PaymentStatus::Successful, $transaction->status);
        $this->assertNotNull($transaction->gateway_reference);
        $this->assertStringStartsWith(FakePaymentGateway::SYNTHETIC_REFERENCE_PREFIX, $transaction->gateway_reference);

        $events = PaymentTransactionEvent::query()
            ->where('payment_transaction_id', $transaction->id)
            ->where('event_type', PaymentTransactionEventType::SUCCESSFUL)
            ->count();
        $this->assertSame(1, $events);

        $this->actingAs($this->payer)
            ->get(route('payments.result', $transaction))
            ->assertOk()
            ->assertSee('Uspješno plaćanje')
            ->assertSee('12.50')
            ->assertSee('EUR')
            ->assertSee('Synthetic user-flow type')
            ->assertSee($transaction->merchant_transaction_id);

        $this->actingAs($this->payer)
            ->get(route('payments.result', $transaction))
            ->assertOk();

        $this->assertSame(1, PaymentTransaction::query()->count());
        $this->assertSame(1, PaymentTransactionEvent::query()
            ->where('payment_transaction_id', $transaction->id)
            ->where('event_type', PaymentTransactionEventType::SUCCESSFUL)
            ->count());
        $this->assertSame(PaymentStatus::Successful, $transaction->fresh()->status);
    }

    public function test_failed_flow_is_terminal_and_does_not_claim_charge(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-p5-fail', 'SYN-P5-FAIL-000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '9.25');

        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Failed->value)
            ->assertRedirect(route('payments.result', $transaction));

        $this->assertSame(PaymentStatus::Failed, $transaction->fresh()->status);

        $this->actingAs($this->payer)
            ->get(route('payments.result', $transaction))
            ->assertOk()
            ->assertSee('Plaćanje nije uspjelo.')
            ->assertSee('Novac nije naplaćen');
    }

    public function test_callback_replay_is_idempotent(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-p5-rep', 'SYN-P5-REP-0000000000001');
        $transaction = $this->launchProcessing($this->payer, $type);

        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Successful->value);
        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Successful->value)
            ->assertRedirect(route('payments.result', $transaction));

        $this->assertSame(1, PaymentTransaction::query()->count());
        $this->assertSame(PaymentStatus::Successful, $transaction->fresh()->status);
        $this->assertSame(1, PaymentTransactionEvent::query()
            ->where('payment_transaction_id', $transaction->id)
            ->where('event_type', PaymentTransactionEventType::SUCCESSFUL)
            ->count());
    }

    public function test_conflicting_terminal_callback_is_ignored(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-p5-conf', 'SYN-P5-CONF-00000000001');
        $transaction = $this->launchProcessing($this->payer, $type);

        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Successful->value);
        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Failed->value);

        $this->assertSame(PaymentStatus::Successful, $transaction->fresh()->status);
        $this->assertSame(0, PaymentTransactionEvent::query()
            ->where('payment_transaction_id', $transaction->id)
            ->where('event_type', PaymentTransactionEventType::FAILED)
            ->count());
        $this->assertTrue(
            PaymentTransactionEvent::query()
                ->where('payment_transaction_id', $transaction->id)
                ->where('event_type', PaymentTransactionEventType::GATEWAY_CONTRADICTORY_RESULT)
                ->exists()
        );
    }

    public function test_modified_outcome_signature_is_rejected(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-p5-tamp', 'SYN-P5-TAMP-00000000001');
        $transaction = $this->launchProcessing($this->payer, $type);

        $signed = \Illuminate\Support\Facades\URL::signedRoute('payments.fake.simulate', [
            'payment_transaction' => $transaction->uuid,
            'outcome' => PaymentStatus::Successful->value,
        ]);
        $tampered = str_replace('successful', 'failed', $signed);

        $this->actingAs($this->payer)->post($tampered)->assertForbidden();
        $this->assertSame(PaymentStatus::Processing, $transaction->fresh()->status);
    }

    public function test_amount_and_currency_mismatch_fail_closed(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-p5-mm', 'SYN-P5-MM-00000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '12.50');
        $processor = app(PaymentResultProcessor::class);

        try {
            $processor->apply($transaction, new PaymentGatewayVerifiedResult(
                PaymentStatus::Successful,
                '99.00',
                'EUR',
                FakePaymentGateway::SYNTHETIC_REFERENCE_PREFIX.'mismatch-amount'
            ));
            $this->fail('Amount mismatch must be rejected.');
        } catch (PaymentResultRejectedException) {
            $this->assertSame(PaymentStatus::Processing, $transaction->fresh()->status);
            $this->assertSame('12.50', (string) $transaction->fresh()->amount);
        }

        try {
            $processor->apply($transaction->fresh(), new PaymentGatewayVerifiedResult(
                PaymentStatus::Successful,
                '12.50',
                'USD',
                FakePaymentGateway::SYNTHETIC_REFERENCE_PREFIX.'mismatch-currency'
            ));
            $this->fail('Currency mismatch must be rejected.');
        } catch (PaymentResultRejectedException) {
            $this->assertSame(PaymentStatus::Processing, $transaction->fresh()->status);
            $this->assertSame('EUR', $transaction->fresh()->currency);
        }
    }

    public function test_module_off_during_processing_still_allows_verified_completion(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-p5-moff', 'SYN-P5-MOFF-00000000001');
        $transaction = $this->launchProcessing($this->payer, $type);

        app(EpModuleSettings::class)->setNewPaymentsEnabled(false);

        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Successful->value)
            ->assertRedirect(route('payments.result', $transaction));

        $this->assertSame(PaymentStatus::Successful, $transaction->fresh()->status);

        $this->actingAs($this->payer)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Nova e-Plaćanja su privremeno nedostupna.');
    }

    public function test_availability_change_during_processing_does_not_block_completion(): void
    {
        [$type, $account] = $this->syntheticUsablePair($this->payer, 'syn-p5-av', 'SYN-P5-AV-00000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '15.00');
        $originalName = $type->name;

        $type->update(['name' => 'Renamed after start', 'is_active' => false]);
        $account->update(['is_active' => false, 'name' => 'Renamed account']);

        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Successful->value)
            ->assertRedirect(route('payments.result', $transaction));

        $transaction->refresh();
        $this->assertSame(PaymentStatus::Successful, $transaction->status);
        $this->assertSame($originalName, $transaction->snapshot['payment_type_name']);

        $this->actingAs($this->payer)
            ->get(route('payments.result', $transaction))
            ->assertOk()
            ->assertSee($originalName)
            ->assertDontSee('Renamed after start');

        $this->actingAs($this->payer)
            ->get(route('payments.start', $type->fresh()))
            ->assertRedirect(route('payments.index'));
    }
}
