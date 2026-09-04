<?php

namespace Tests\Feature;

use App\Enums\PaymentConfirmationDeliveryStatus;
use App\Enums\PaymentStatus;
use App\Mail\PaymentSuccessfulConfirmationMail;
use App\Models\PaymentConfirmationDelivery;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Payments\PaymentConfirmationAssembler;
use App\Services\Payments\PaymentConfirmationDeliveryService;
use App\Services\Payments\PaymentGatewayInquiryOutcome;
use App\Services\Payments\PaymentGatewayInquiryResult;
use App\Services\Payments\PaymentGatewayVerifiedResult;
use App\Services\Payments\PaymentResultProcessor;
use App\Services\Payments\PaymentStatusInquiryService;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PDOException;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\Support\StartsSyntheticPayment;
use Tests\Support\SyntheticInquiryGateway;
use Tests\TestCase;

class PaymentConfirmationTest extends TestCase
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
        $this->payer = $this->makeKorisnik(['email' => 'payer-f7@example.com']);
        if ($this->name() !== 'test_email_failure_does_not_change_successful_status') {
            Mail::fake();
        }
    }

    public function test_successful_transition_sends_one_email_with_snapshot_and_pdf(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f7-ok', 'SYN-F7-OK-00000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '21.40');

        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Successful->value)
            ->assertRedirect(route('payments.result', $transaction));

        Mail::assertSent(PaymentSuccessfulConfirmationMail::class, 1);
        Mail::assertSent(PaymentSuccessfulConfirmationMail::class, function (PaymentSuccessfulConfirmationMail $mail) use ($transaction): bool {
            $html = $mail->render();

            return $mail->hasTo('payer-f7@example.com')
                && $mail->envelope()->subject === 'Potvrda o uspješnoj transakciji — Digital Kotor'
                && str_contains($html, '21.40 EUR')
                && str_contains($html, 'Synthetic user-flow type')
                && str_contains($html, (string) $transaction->merchant_transaction_id)
                && str_contains($html, 'Ne predstavlja fiskalni račun')
                && ! str_contains($html, 'Obaveza je izmirena')
                && ! str_contains(strtolower($html), 'invoice')
                && ! str_contains(strtolower($html), 'faktura')
                && is_string($mail->pdfBinary)
                && str_starts_with($mail->pdfBinary, '%PDF');
        });

        $this->assertDatabaseHas('payment_confirmation_deliveries', [
            'payment_transaction_id' => $transaction->id,
            'channel' => PaymentConfirmationDelivery::CHANNEL_EMAIL,
            'status' => PaymentConfirmationDeliveryStatus::Sent->value,
            'recipient_email' => 'payer-f7@example.com',
        ]);
    }

    public function test_failed_and_cancelled_send_no_email_and_have_no_pdf_cta(): void
    {
        [$failedType] = $this->syntheticUsablePair($this->payer, 'syn-f7-fail', 'SYN-F7-FAIL-000000000001');
        $failed = $this->launchProcessing($this->payer, $failedType, '9.00');
        $this->simulateOutcome($this->payer, $failed, PaymentStatus::Failed->value);

        [$cancelledType] = $this->syntheticUsablePair($this->payer, 'syn-f7-can', 'SYN-F7-CAN-0000000000001');
        $cancelled = $this->launchProcessing($this->payer, $cancelledType, '8.00');
        $this->simulateOutcome($this->payer, $cancelled, PaymentStatus::Cancelled->value);

        Mail::assertNothingSent();

        $this->actingAs($this->payer)->get(route('payments.result', $failed))
            ->assertOk()
            ->assertDontSee('Preuzmi potvrdu (PDF)');
        $this->actingAs($this->payer)->get(route('payments.result', $cancelled))
            ->assertOk()
            ->assertDontSee('Preuzmi potvrdu (PDF)');

        $this->actingAs($this->payer)->get(route('payments.confirmation.pdf', $failed))->assertNotFound();
        $this->actingAs($this->payer)->get(route('payments.confirmation.pdf', $cancelled))->assertNotFound();
    }

    public function test_processing_has_no_confirmation(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f7-proc', 'SYN-F7-PROC-00000000001');
        $transaction = $this->launchProcessing($this->payer, $type);

        Mail::assertNothingSent();
        $this->actingAs($this->payer)->get(route('payments.result', $transaction))
            ->assertOk()
            ->assertDontSee('Preuzmi potvrdu (PDF)');
        $this->actingAs($this->payer)->get(route('payments.confirmation.pdf', $transaction))->assertNotFound();
    }

    public function test_owner_can_download_pdf_guest_and_other_user_cannot(): void
    {
        $transaction = $this->successfulTransaction('syn-f7-own', 'SYN-F7-OWN-0000000000001', '15.00');

        auth()->logout();
        $this->app['auth']->forgetGuards();
        $this->get(route('payments.confirmation.pdf', $transaction))->assertRedirect();

        $other = $this->makeKorisnik(['email' => 'other-f7@example.com', 'jmb' => $this->validJmb(22)]);
        $this->actingAs($other)->get(route('payments.confirmation.pdf', $transaction))->assertNotFound();

        $first = $this->actingAs($this->payer)->get(route('payments.confirmation.pdf', $transaction));
        $first->assertOk();
        $first->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $first->getContent());
        $this->assertStringContainsString('attachment; filename="potvrda-', (string) $first->headers->get('content-disposition'));

        $updatedAt = $transaction->fresh()->updated_at;
        $second = $this->actingAs($this->payer)->get(route('payments.confirmation.pdf', $transaction));
        $third = $this->actingAs($this->payer)->get(route('payments.confirmation.pdf', $transaction));
        $second->assertOk();
        $third->assertOk();
        $this->assertSame($updatedAt?->timestamp, $transaction->fresh()->updated_at?->timestamp);
        $this->assertSame(1, PaymentConfirmationDelivery::query()->where('payment_transaction_id', $transaction->id)->count());
        Mail::assertSent(PaymentSuccessfulConfirmationMail::class, 1);
    }

    public function test_malformed_confirmation_uuid_fails_closed(): void
    {
        $this->actingAs($this->payer)
            ->get('/payments/transakcije/not-a-uuid/potvrda')
            ->assertNotFound();
    }

    public function test_result_page_shows_pdf_cta_and_email_sent_only_for_success(): void
    {
        $transaction = $this->successfulTransaction('syn-f7-cta', 'SYN-F7-CTA-0000000000001', '11.00');

        $this->actingAs($this->payer)->get(route('payments.result', $transaction))
            ->assertOk()
            ->assertSee('Preuzmi potvrdu (PDF)')
            ->assertSee('Potvrda je poslata na email')
            ->assertSee('Uspješna');
    }

    public function test_snapshot_survives_catalog_rename_for_pdf_and_email(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f7-snap', 'SYN-F7-SNAP-00000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '18.25');
        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Successful->value);

        $type->update(['name' => 'RENAMED LIVE TYPE']);
        $transaction->paymentAccount->update(['name' => 'RENAMED LIVE ACCOUNT']);

        $confirmation = app(PaymentConfirmationAssembler::class)
            ->fromSuccessfulTransaction($transaction->fresh());

        $this->assertSame('Synthetic user-flow type', $confirmation->paymentTypeName);
        $this->assertSame('Synthetic flow account', $confirmation->accountName);
        $this->assertSame('SYN-F7-SNAP-00000000001', $confirmation->accountNumber);
        $this->assertSame('18.25', $confirmation->amount);

        $html = view('payments.confirmation-pdf', ['confirmation' => $confirmation])->render();
        $this->assertStringContainsString('Potvrda o uspješnoj transakciji', $html);
        $this->assertStringContainsString('Synthetic user-flow type', $html);
        $this->assertStringContainsString('SYN-F7-SNAP-00000000001', $html);
        $this->assertStringContainsString('Ana Anić', $html);
        $this->assertStringNotContainsString('RENAMED LIVE TYPE', $html);
        $this->assertStringNotContainsString('JMB', $html);
        $this->assertStringNotContainsString('0202990123456', $html);

        Mail::assertSent(PaymentSuccessfulConfirmationMail::class, function (PaymentSuccessfulConfirmationMail $mail): bool {
            $body = $mail->render();

            return str_contains($body, 'Synthetic user-flow type')
                && ! str_contains($body, 'RENAMED LIVE TYPE');
        });
    }

    public function test_callback_replay_result_refresh_and_pdf_download_do_not_resend_email(): void
    {
        $transaction = $this->successfulTransaction('syn-f7-rep', 'SYN-F7-REP-0000000000001', '13.00');
        Mail::assertSent(PaymentSuccessfulConfirmationMail::class, 1);

        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Successful->value);
        $this->actingAs($this->payer)->get(route('payments.result', $transaction))->assertOk();
        $this->actingAs($this->payer)->get(route('payments.confirmation.pdf', $transaction))->assertOk();
        $this->actingAs($this->payer)->get(route('payments.confirmation.pdf', $transaction))->assertOk();
        $this->actingAs($this->payer)->get(route('payments.confirmation.pdf', $transaction))->assertOk();

        Mail::assertSent(PaymentSuccessfulConfirmationMail::class, 1);
        $this->assertSame(1, PaymentConfirmationDelivery::query()->where('payment_transaction_id', $transaction->id)->count());
    }

    public function test_conflicting_callback_does_not_send_another_email(): void
    {
        $transaction = $this->successfulTransaction('syn-f7-conf', 'SYN-F7-CONF-00000000001', '14.00');
        Mail::assertSent(PaymentSuccessfulConfirmationMail::class, 1);

        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Failed->value);

        $this->assertSame(PaymentStatus::Successful, $transaction->fresh()->status);
        Mail::assertSent(PaymentSuccessfulConfirmationMail::class, 1);
    }

    public function test_inquiry_success_uses_same_confirmation_path(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f7-inq', 'SYN-F7-INQ-0000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '16.50');

        $gateway = new SyntheticInquiryGateway(new PaymentGatewayInquiryResult(
            outcome: PaymentGatewayInquiryOutcome::Successful,
            amount: '16.50',
            currency: 'EUR',
            providerReference: 'SYN-INQ-'.$transaction->uuid,
            eventId: 'SYN-INQ-EVT-'.$transaction->uuid,
            merchantTransactionId: (string) $transaction->merchant_transaction_id,
            provider: 'synthetic-inquiry',
        ));

        $updated = app(PaymentStatusInquiryService::class)->checkStatus($transaction, $gateway);

        $this->assertSame(PaymentStatus::Successful, $updated->status);
        Mail::assertSent(PaymentSuccessfulConfirmationMail::class, 1);
        $this->assertDatabaseHas('payment_confirmation_deliveries', [
            'payment_transaction_id' => $transaction->id,
            'status' => PaymentConfirmationDeliveryStatus::Sent->value,
        ]);
    }

    public function test_email_failure_does_not_change_successful_status(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('SMTP failure'));

        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f7-mailfail', 'SYN-F7-MF-0000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '7.00');

        app(PaymentResultProcessor::class)->apply(
            $transaction,
            new PaymentGatewayVerifiedResult(
                status: PaymentStatus::Successful,
                amount: '7.00',
                currency: 'EUR',
                providerReference: 'SYN-GW-FAILMAIL',
                eventId: 'SYN-EVT-FAILMAIL',
                merchantTransactionId: (string) $transaction->merchant_transaction_id,
                provider: 'fake',
            )
        );

        $this->assertSame(PaymentStatus::Successful, $transaction->fresh()->status);
        $this->assertDatabaseHas('payment_confirmation_deliveries', [
            'payment_transaction_id' => $transaction->id,
            'status' => PaymentConfirmationDeliveryStatus::Failed->value,
            'error_class' => \RuntimeException::class,
        ]);
    }

    public function test_existing_successful_transaction_is_not_backfilled_on_download(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f7-old', 'SYN-F7-OLD-0000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '5.00');
        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Successful->value);
        PaymentConfirmationDelivery::query()->where('payment_transaction_id', $transaction->id)->delete();
        Mail::fake();

        $this->actingAs($this->payer)->get(route('payments.confirmation.pdf', $transaction))->assertOk();

        Mail::assertNothingSent();
        $this->assertSame(0, PaymentConfirmationDelivery::query()->where('payment_transaction_id', $transaction->id)->count());
    }

    public function test_duplicate_delivery_unique_violation_does_not_resend_or_change_status(): void
    {
        Log::spy();
        $transaction = $this->successfulTransaction('syn-f7-dup', 'SYN-F7-DUP-0000000000001', '6.00');
        Mail::assertSent(PaymentSuccessfulConfirmationMail::class, 1);

        app(PaymentConfirmationDeliveryService::class)->sendAfterNewSuccessfulTransition($transaction->fresh());

        Mail::assertSent(PaymentSuccessfulConfirmationMail::class, 1);
        $this->assertSame(PaymentStatus::Successful, $transaction->fresh()->status);
        $this->assertSame(1, PaymentConfirmationDelivery::query()->where('payment_transaction_id', $transaction->id)->count());
        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'ep.payment.confirmation_email_skipped'
                    && ($context['reason'] ?? null) === 'duplicate_delivery';
            })
            ->once();
    }

    public function test_unexpected_query_exception_is_logged_and_does_not_change_status(): void
    {
        Log::spy();
        PaymentConfirmationDelivery::creating(function (): void {
            throw $this->queryException('42S02', 1146, 'Base table or view not found');
        });

        try {
            [$type] = $this->syntheticUsablePair($this->payer, 'syn-f7-qex', 'SYN-F7-QEX-0000000000001');
            $transaction = $this->launchProcessing($this->payer, $type, '4.00');

            app(PaymentResultProcessor::class)->apply(
                $transaction,
                new PaymentGatewayVerifiedResult(
                    status: PaymentStatus::Successful,
                    amount: '4.00',
                    currency: 'EUR',
                    providerReference: 'SYN-GW-QEX',
                    eventId: 'SYN-EVT-QEX',
                    merchantTransactionId: (string) $transaction->merchant_transaction_id,
                    provider: 'fake',
                )
            );

            $this->assertSame(PaymentStatus::Successful, $transaction->fresh()->status);
            Mail::assertNothingSent();
            $this->assertSame(0, PaymentConfirmationDelivery::query()->where('payment_transaction_id', $transaction->id)->count());
            Log::shouldHaveReceived('info')
                ->withArgs(function (string $message, array $context): bool {
                    return $message === 'ep.payment.confirmation_delivery_unavailable'
                        && ($context['sql_state'] ?? null) === '42S02'
                        && ($context['exception_class'] ?? null) === QueryException::class;
                })
                ->once();
        } finally {
            PaymentConfirmationDelivery::flushEventListeners();
        }
    }

    public function test_result_page_stays_ok_when_delivery_lookup_fails_and_does_not_claim_email_sent(): void
    {
        $transaction = $this->successfulTransaction('syn-f7-lookup', 'SYN-F7-LOOKUP-000000001', '3.00');
        Log::spy();

        PaymentConfirmationDelivery::addGlobalScope('f7-lookup-fail', new class implements Scope
        {
            public function apply(Builder $builder, Model $model): void
            {
                $previous = new PDOException('SQLSTATE[42S02]: Base table or view not found');
                $previous->errorInfo = ['42S02', 1146, 'Base table or view not found'];

                throw new QueryException('mysql', 'select 1', [], $previous);
            }
        });

        try {
            $this->actingAs($this->payer)->get(route('payments.result', $transaction))
                ->assertOk()
                ->assertSee('Preuzmi potvrdu (PDF)')
                ->assertDontSee('Potvrda je poslata na email');

            Log::shouldHaveReceived('info')
                ->withArgs(function (string $message, array $context): bool {
                    return $message === 'ep.payment.confirmation_delivery_lookup_failed'
                        && ($context['sql_state'] ?? null) === '42S02';
                })
                ->once();
        } finally {
            $clear = \Closure::bind(function (): void {
                unset(static::$globalScopes[self::class]['f7-lookup-fail']);
            }, null, PaymentConfirmationDelivery::class);
            $clear();
        }
    }

    private function successfulTransaction(string $code, string $account, string $amount): PaymentTransaction
    {
        [$type] = $this->syntheticUsablePair($this->payer, $code, $account);
        $transaction = $this->launchProcessing($this->payer, $type, $amount);
        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Successful->value);

        return $transaction->fresh();
    }

    private function queryException(string $sqlState, int $driverCode, string $message): QueryException
    {
        $previous = new PDOException('SQLSTATE['.$sqlState.']: '.$message);
        $previous->errorInfo = [$sqlState, $driverCode, $message];

        return new QueryException('mysql', 'insert into payment_confirmation_deliveries', [], $previous);
    }
}
