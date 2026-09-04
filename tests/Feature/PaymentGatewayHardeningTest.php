<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionEvent;
use App\Models\User;
use App\Services\Payments\FakePaymentGateway;
use App\Services\Payments\GatewayInquiryException;
use App\Services\Payments\PaymentGatewayCapabilities;
use App\Services\Payments\PaymentGatewayInquiryOutcome;
use App\Services\Payments\PaymentGatewayInquiryResult;
use App\Services\Payments\PaymentGatewayNotConfiguredException;
use App\Services\Payments\PaymentGatewayResolver;
use App\Services\Payments\PaymentGatewayVerifiedResult;
use App\Services\Payments\PaymentResultProcessor;
use App\Services\Payments\PaymentStatusInquiryService;
use App\Services\Payments\PaymentTransactionEventType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\Support\StartsSyntheticPayment;
use Tests\Support\SyntheticInquiryGateway;
use Tests\Support\ThrowingStartFakeGateway;
use Tests\TestCase;
use TypeError;

class PaymentGatewayHardeningTest extends TestCase
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

    public function test_fake_capabilities_do_not_include_inquiry(): void
    {
        $gateway = app(FakePaymentGateway::class);
        $capabilities = $gateway->capabilities();

        $this->assertInstanceOf(PaymentGatewayCapabilities::class, $capabilities);
        $this->assertTrue($capabilities->start);
        $this->assertTrue($capabilities->resultVerification);
        $this->assertTrue($capabilities->hostedRedirect);
        $this->assertFalse($capabilities->statusInquiry);
    }

    public function test_unknown_provider_fails_closed_without_transaction(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f6a-unk', 'SYN-F6A-UNK-00000000001');
        $this->reachPreview($this->payer, $type, '8.00');

        config(['payments.gateway' => 'unknown-provider']);

        $this->actingAs($this->payer)
            ->post(route('payments.launch'))
            ->assertRedirect(route('payments.index'))
            ->assertSessionHas('error', 'Plaćanje trenutno nije dostupno. Pokušajte kasnije.');

        $this->assertSame(0, PaymentTransaction::query()->count());
    }

    public function test_missing_provider_config_fails_closed(): void
    {
        config(['payments.gateway' => null]);

        $this->expectException(PaymentGatewayNotConfiguredException::class);
        app(PaymentGatewayResolver::class)->resolve();
    }

    public function test_raw_array_result_cannot_reach_processor(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f6a-raw', 'SYN-F6A-RAW-00000000001');
        $transaction = $this->launchProcessing($this->payer, $type);

        $this->expectException(TypeError::class);
        app(PaymentResultProcessor::class)->apply($transaction, ['status' => 'successful']);
    }

    public function test_start_exception_after_durable_write_stays_processing(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f6a-stex', 'SYN-F6A-STEX-0000000001');
        $this->reachPreview($this->payer, $type, '6.00');

        $this->app->bind(FakePaymentGateway::class, ThrowingStartFakeGateway::class);

        $response = $this->actingAs($this->payer)->post(route('payments.launch'));
        $transaction = PaymentTransaction::query()->firstOrFail();

        $response->assertRedirect(route('payments.result', $transaction));
        $this->assertSame(PaymentStatus::Processing, $transaction->fresh()->status);
        $this->assertTrue(
            PaymentTransactionEvent::query()
                ->where('payment_transaction_id', $transaction->id)
                ->where('event_type', PaymentTransactionEventType::GATEWAY_START_FAILED)
                ->exists()
        );

        $this->actingAs($this->payer)
            ->get(route('payments.result', $transaction))
            ->assertOk()
            ->assertSee('Plaćanje je u obradi')
            ->assertDontSee('Plaćanje nije uspjelo.')
            ->assertDontSee('GatewayStartException');
    }

    public function test_fake_inquiry_is_unsupported(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f6a-nouq', 'SYN-F6A-NOUQ-000000001');
        $transaction = $this->launchProcessing($this->payer, $type);

        try {
            app(PaymentStatusInquiryService::class)->checkStatus($transaction);
            $this->fail('Fake inquiry must be unsupported.');
        } catch (GatewayInquiryException) {
            $this->assertSame(PaymentStatus::Processing, $transaction->fresh()->status);
        }
    }

    public function test_inquiry_successful_uses_same_processor(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f6a-inqs', 'SYN-F6A-INQS-000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '12.50');

        $gateway = new SyntheticInquiryGateway(new PaymentGatewayInquiryResult(
            outcome: PaymentGatewayInquiryOutcome::Successful,
            amount: '12.50',
            currency: 'EUR',
            providerReference: 'SYN-INQ-'.$transaction->uuid,
            eventId: 'SYN-INQ-EVT-'.$transaction->uuid,
            merchantTransactionId: (string) $transaction->merchant_transaction_id,
            provider: 'synthetic-inquiry',
        ));

        $updated = app(PaymentStatusInquiryService::class)->checkStatus($transaction, $gateway);

        $this->assertSame(PaymentStatus::Successful, $updated->status);
        $this->assertTrue(
            PaymentTransactionEvent::query()
                ->where('payment_transaction_id', $transaction->id)
                ->where('event_type', PaymentTransactionEventType::SUCCESSFUL)
                ->exists()
        );
    }

    public function test_inquiry_unknown_leaves_processing(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f6a-inqu', 'SYN-F6A-INQU-000000001');
        $transaction = $this->launchProcessing($this->payer, $type);

        $gateway = new SyntheticInquiryGateway(new PaymentGatewayInquiryResult(
            outcome: PaymentGatewayInquiryOutcome::Unknown,
            merchantTransactionId: (string) $transaction->merchant_transaction_id,
            provider: 'synthetic-inquiry',
        ));

        $updated = app(PaymentStatusInquiryService::class)->checkStatus($transaction, $gateway);

        $this->assertSame(PaymentStatus::Processing, $updated->status);
    }

    public function test_inquiry_conflict_on_terminal_is_ignored(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f6a-inqc', 'SYN-F6A-INQC-000000001');
        $transaction = $this->launchProcessing($this->payer, $type);
        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Successful->value);

        $gateway = new SyntheticInquiryGateway(new PaymentGatewayInquiryResult(
            outcome: PaymentGatewayInquiryOutcome::Failed,
            amount: (string) $transaction->amount,
            currency: 'EUR',
            eventId: 'SYN-INQ-CONFLICT-'.$transaction->uuid,
            merchantTransactionId: (string) $transaction->merchant_transaction_id,
            provider: 'synthetic-inquiry',
        ));

        $updated = app(PaymentStatusInquiryService::class)->checkStatus($transaction->fresh(), $gateway);

        $this->assertSame(PaymentStatus::Successful, $updated->status);
        $this->assertTrue(
            PaymentTransactionEvent::query()
                ->where('payment_transaction_id', $transaction->id)
                ->where('event_type', PaymentTransactionEventType::GATEWAY_CONTRADICTORY_RESULT)
                ->exists()
        );
    }

    public function test_inquiry_technical_error_does_not_change_status(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f6a-inqe', 'SYN-F6A-INQE-000000001');
        $transaction = $this->launchProcessing($this->payer, $type);

        $gateway = new SyntheticInquiryGateway(
            new PaymentGatewayInquiryResult(outcome: PaymentGatewayInquiryOutcome::Unknown),
            throwTechnical: true,
        );

        try {
            app(PaymentStatusInquiryService::class)->checkStatus($transaction, $gateway);
            $this->fail('Technical inquiry error must be raised.');
        } catch (GatewayInquiryException) {
            $this->assertSame(PaymentStatus::Processing, $transaction->fresh()->status);
        }
    }

    public function test_mismatch_records_verification_failed_without_status_change(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f6a-mm', 'SYN-F6A-MM-0000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '12.50');

        try {
            app(PaymentResultProcessor::class)->apply($transaction, new PaymentGatewayVerifiedResult(
                status: PaymentStatus::Successful,
                amount: '99.00',
                currency: 'EUR',
                provider: 'fake',
            ));
            $this->fail('Mismatch must be rejected.');
        } catch (\App\Services\Payments\GatewayVerificationException) {
            $this->assertSame(PaymentStatus::Processing, $transaction->fresh()->status);
            $this->assertTrue(
                PaymentTransactionEvent::query()
                    ->where('payment_transaction_id', $transaction->id)
                    ->where('event_type', PaymentTransactionEventType::GATEWAY_VERIFICATION_FAILED)
                    ->exists()
            );
        }
    }
}
