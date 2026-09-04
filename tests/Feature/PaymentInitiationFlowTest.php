<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\PaymentInitiation;
use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionEvent;
use App\Models\User;
use App\Services\Payments\FakePaymentGateway;
use App\Services\Payments\PaymentTransactionEventType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\Support\StartsSyntheticPayment;
use Tests\TestCase;

class PaymentInitiationFlowTest extends TestCase
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

    public function test_browse_account_amount_preview_create_no_records(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-p5-pre', 'SYN-P5-PRE-0000000000001');

        $this->assertSame(0, PaymentInitiation::query()->count());
        $this->assertSame(0, PaymentTransaction::query()->count());

        $this->actingAs($this->payer)->get(route('payments.index'))->assertOk();
        $this->actingAs($this->payer)->get(route('payments.start', $type))->assertRedirect();
        $this->actingAs($this->payer)->get(route('payments.amount.edit'))->assertOk();
        $this->actingAs($this->payer)->post(route('payments.amount.store'), ['amount' => '12.50'])->assertRedirect();
        $this->actingAs($this->payer)->get(route('payments.preview'))->assertOk()->assertSee('Pokreni plaćanje');

        $this->assertSame(0, PaymentInitiation::query()->count());
        $this->assertSame(0, PaymentTransaction::query()->count());
    }

    public function test_pre_start_abandon_creates_no_transaction(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-p5-abd', 'SYN-P5-ABD-0000000000001');
        $this->reachPreview($this->payer, $type, '8.00');

        $this->actingAs($this->payer)
            ->post(route('payments.abandon'))
            ->assertRedirect(route('payments.index'));

        $this->assertSame(0, PaymentInitiation::query()->count());
        $this->assertSame(0, PaymentTransaction::query()->count());
    }

    public function test_explicit_start_creates_initiation_and_processing_transaction(): void
    {
        [$type, $account] = $this->syntheticUsablePair($this->payer, 'syn-p5-start', 'SYN-P5-START-00000000001');

        $transaction = $this->launchProcessing($this->payer, $type, '12.50');

        $this->assertSame(1, PaymentInitiation::query()->count());
        $this->assertSame(1, PaymentTransaction::query()->count());
        $this->assertSame(PaymentStatus::Processing, $transaction->status);
        $this->assertSame('12.50', (string) $transaction->amount);
        $this->assertSame('EUR', $transaction->currency);
        $this->assertSame($this->payer->id, $transaction->user_id);
        $this->assertSame($account->id, $transaction->payment_account_id);
        $this->assertSame($account->account_number, $transaction->snapshot['account_number']);
        $this->assertNotNull($transaction->merchant_transaction_id);
        $this->assertStringStartsWith('EPLOCAL-', $transaction->merchant_transaction_id);
        $this->assertSame(app(FakePaymentGateway::class)->name(), $transaction->provider);
        $this->assertTrue(
            PaymentTransactionEvent::query()
                ->where('payment_transaction_id', $transaction->id)
                ->where('event_type', PaymentTransactionEventType::STARTED)
                ->exists()
        );
        $this->assertTrue(
            PaymentTransactionEvent::query()
                ->where('payment_transaction_id', $transaction->id)
                ->where('event_type', PaymentTransactionEventType::GATEWAY_REDIRECTED)
                ->exists()
        );
    }

    public function test_consumed_draft_cannot_start_a_second_payment(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-p5-cons', 'SYN-P5-CONS-00000000001');
        $this->launchProcessing($this->payer, $type, '4.00');

        $this->actingAs($this->payer)
            ->post(route('payments.launch'))
            ->assertRedirect(route('payments.index'));

        $this->assertSame(1, PaymentInitiation::query()->count());
        $this->assertSame(1, PaymentTransaction::query()->count());
    }
}
