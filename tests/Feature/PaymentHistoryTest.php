<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Mail\PaymentSuccessfulConfirmationMail;
use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionEvent;
use App\Models\User;
use App\Services\Payments\EpModuleSettings;
use App\Services\Payments\PaymentHistoryService;
use App\Services\Payments\PaymentTransactionEventType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\Support\StartsSyntheticPayment;
use Tests\TestCase;

class PaymentHistoryTest extends TestCase
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
        $this->payer = $this->makeKorisnik(['email' => 'payer-f8@example.com']);
        Mail::fake();
    }

    public function test_guest_is_redirected_from_history(): void
    {
        $this->get(route('payments.history'))->assertRedirect(route('login'));
    }

    public function test_user_sees_only_own_transactions_latest_first(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f8-own', 'SYN-F8-OWN-0000000000001');
        $older = $this->launchProcessing($this->payer, $type, '10.00');
        $older->forceFill(['created_at' => now()->subHour()])->saveQuietly();

        $newer = $this->launchProcessing($this->payer, $type, '11.00');

        $other = $this->makeKorisnik(['email' => 'other-f8@example.com', 'jmb' => $this->validJmb(31)]);
        [$otherType] = $this->syntheticUsablePair($other, 'syn-f8-other', 'SYN-F8-OTHER-0000000001');
        $foreign = $this->launchProcessing($other, $otherType, '99.00');

        $html = $this->actingAs($this->payer)->get(route('payments.history'))
            ->assertOk()
            ->assertSee('Moja e-Plaćanja')
            ->assertSee('11.00 EUR')
            ->assertSee('10.00 EUR')
            ->assertSee('U obradi')
            ->assertDontSee('99.00 EUR')
            ->assertDontSee((string) $foreign->merchant_transaction_id)
            ->getContent();

        $this->assertTrue(strpos($html, '11.00 EUR') < strpos($html, '10.00 EUR'));
    }

    public function test_empty_state_and_status_filter(): void
    {
        $this->actingAs($this->payer)->get(route('payments.history'))
            ->assertOk()
            ->assertSee('Još nemate e-Plaćanja.')
            ->assertSee('Novo e-Plaćanje');

        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f8-filter', 'SYN-F8-FILTER-000000001');
        $successful = $this->launchProcessing($this->payer, $type, '21.40');
        $this->simulateOutcome($this->payer, $successful, PaymentStatus::Successful->value);
        $failed = $this->launchProcessing($this->payer, $type, '8.00');
        $this->simulateOutcome($this->payer, $failed, PaymentStatus::Failed->value);

        $this->actingAs($this->payer)->get(route('payments.history', ['status' => PaymentStatus::Successful->value]))
            ->assertOk()
            ->assertSee('21.40 EUR')
            ->assertSee('Uspješna')
            ->assertDontSee('8.00 EUR');
    }

    public function test_pagination_does_not_load_all_rows_on_first_page(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f8-page', 'SYN-F8-PAGE-000000000001');
        for ($i = 1; $i <= PaymentHistoryService::PER_PAGE + 1; $i++) {
            PaymentTransaction::factory()->create([
                'user_id' => $this->payer->id,
                'payment_type_id' => $type->id,
                'payment_account_id' => $type->accounts()->first()->id,
                'merchant_transaction_id' => 'EPLOCAL-F8PAGE-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'amount' => '1.00',
                'snapshot' => [
                    'payment_type_name' => 'Paged type',
                    'amount' => '1.00',
                    'currency' => 'EUR',
                    'account_number' => 'SYN-F8-PAGE-000000000001',
                ],
            ]);
        }

        $this->actingAs($this->payer)->get(route('payments.history'))
            ->assertOk()
            ->assertSee('EPLOCAL-F8PAGE-0016')
            ->assertDontSee('EPLOCAL-F8PAGE-0001');

        $this->actingAs($this->payer)->get(route('payments.history', ['page' => 2]))
            ->assertOk()
            ->assertSee('EPLOCAL-F8PAGE-0001');
    }

    public function test_owner_detail_and_other_user_cannot(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f8-det', 'SYN-F8-DET-0000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '15.00');
        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Successful->value);

        $this->actingAs($this->payer)->get(route('payments.result', $transaction))
            ->assertOk()
            ->assertSee('Uspješna')
            ->assertSee('15.00 EUR')
            ->assertSee('Synthetic user-flow type')
            ->assertSee((string) $transaction->merchant_transaction_id)
            ->assertSee('Preuzmi potvrdu (PDF)')
            ->assertSee('Plaćanje pokrenuto')
            ->assertSee('Preusmjereno na servis plaćanja')
            ->assertDontSee('gateway.inquiry')
            ->assertDontSee('gateway.verification_failed')
            ->assertDontSee('JMB')
            ->assertDontSee('0202990123456');

        $other = $this->makeKorisnik(['email' => 'other-f8-det@example.com', 'jmb' => $this->validJmb(32)]);
        $this->actingAs($other)->get(route('payments.result', $transaction))->assertNotFound();
        $this->actingAs($other)->get(route('payments.history'))
            ->assertOk()
            ->assertDontSee((string) $transaction->merchant_transaction_id);
    }

    public function test_non_success_details_have_no_pdf_cta(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f8-nopdf', 'SYN-F8-NOPDF-0000000001');
        $processing = $this->launchProcessing($this->payer, $type, '4.00');
        $failed = $this->launchProcessing($this->payer, $type, '5.00');
        $this->simulateOutcome($this->payer, $failed, PaymentStatus::Failed->value);
        $cancelled = $this->launchProcessing($this->payer, $type, '6.00');
        $this->simulateOutcome($this->payer, $cancelled, PaymentStatus::Cancelled->value);

        $this->actingAs($this->payer)->get(route('payments.result', $processing))
            ->assertOk()
            ->assertSee('U obradi')
            ->assertSee('Status plaćanja još nije konačno potvrđen.')
            ->assertDontSee('Preuzmi potvrdu (PDF)');
        $this->actingAs($this->payer)->get(route('payments.result', $failed))
            ->assertOk()
            ->assertSee('Neuspješna')
            ->assertDontSee('Preuzmi potvrdu (PDF)');
        $this->actingAs($this->payer)->get(route('payments.result', $cancelled))
            ->assertOk()
            ->assertSee('Otkazana')
            ->assertDontSee('Preuzmi potvrdu (PDF)');
    }

    public function test_snapshot_survives_catalog_rename_in_list_and_detail(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f8-snap', 'SYN-F8-SNAP-00000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '18.25');
        $type->update(['name' => 'RENAMED LIVE TYPE']);
        $transaction->paymentAccount->update(['name' => 'RENAMED LIVE ACCOUNT']);

        $this->actingAs($this->payer)->get(route('payments.history'))
            ->assertOk()
            ->assertSee('Synthetic user-flow type')
            ->assertDontSee('RENAMED LIVE TYPE');
        $this->actingAs($this->payer)->get(route('payments.result', $transaction))
            ->assertOk()
            ->assertSee('Synthetic user-flow type')
            ->assertSee('Synthetic flow account')
            ->assertDontSee('RENAMED LIVE TYPE');
    }

    public function test_technical_events_are_hidden_from_user_timeline(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f8-tl', 'SYN-F8-TL-00000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '7.00');
        PaymentTransactionEvent::factory()->create([
            'payment_transaction_id' => $transaction->id,
            'event_type' => PaymentTransactionEventType::GATEWAY_INQUIRY,
            'payload' => ['inquiry_outcome' => 'unknown', 'secret' => 'do-not-show'],
        ]);

        $this->actingAs($this->payer)->get(route('payments.result', $transaction))
            ->assertOk()
            ->assertSee('Plaćanje pokrenuto')
            ->assertDontSee('gateway.inquiry')
            ->assertDontSee('do-not-show')
            ->assertDontSee('inquiry_outcome');
    }

    public function test_module_off_keeps_history_detail_and_pdf(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f8-off', 'SYN-F8-OFF-0000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '12.50');
        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Successful->value);
        app(EpModuleSettings::class)->setNewPaymentsEnabled(false);

        $this->actingAs($this->payer)->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Nova e-Plaćanja su privremeno nedostupna.')
            ->assertSee('Moja e-Plaćanja');
        $this->actingAs($this->payer)->get(route('payments.history'))
            ->assertOk()
            ->assertSee((string) $transaction->merchant_transaction_id)
            ->assertDontSee('Novo e-Plaćanje');
        $this->actingAs($this->payer)->get(route('payments.result', $transaction))->assertOk();
        $this->actingAs($this->payer)->get(route('payments.confirmation.pdf', $transaction))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        Mail::assertSent(PaymentSuccessfulConfirmationMail::class, 1);
    }

    public function test_history_views_do_not_send_email(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f8-mail', 'SYN-F8-MAIL-00000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '9.00');
        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Successful->value);
        Mail::assertSent(PaymentSuccessfulConfirmationMail::class, 1);

        $this->actingAs($this->payer)->get(route('payments.history'))->assertOk();
        $this->actingAs($this->payer)->get(route('payments.result', $transaction))->assertOk();
        $this->actingAs($this->payer)->get(route('payments.confirmation.pdf', $transaction))->assertOk();
        Mail::assertSent(PaymentSuccessfulConfirmationMail::class, 1);
    }
}
