<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Mail\PaymentSuccessfulConfirmationMail;
use App\Models\PaymentTransaction;
use App\Models\Role;
use App\Models\User;
use App\Services\Payments\EpModuleSettings;
use App\Services\Payments\PaymentAdminTransactionQuery;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentGatewayInquiryOutcome;
use App\Services\Payments\PaymentGatewayInquiryResult;
use App\Services\Payments\PaymentGatewayResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\Support\StartsSyntheticPayment;
use Tests\Support\SyntheticInquiryGateway;
use Tests\TestCase;

class PaymentAdminTransactionTest extends TestCase
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
        $this->admin = $this->userWithRole('admin', 'Platform Admin', 'ep-f9-admin@example.com');
        $this->payer = $this->makeKorisnik(['email' => 'payer-f9@example.com']);
        Mail::fake();
    }

    public function test_guest_and_non_admin_cannot_list_transactions(): void
    {
        $this->get(route('admin.e-payments.transactions.index'))->assertRedirect(route('login'));
        $this->actingAs($this->payer)->get(route('admin.e-payments.transactions.index'))->assertForbidden();

        $kkAdmin = $this->userWithRole('kk_admin', 'KK Admin', 'ep-f9-kk@example.com');
        $this->actingAs($kkAdmin)->get(route('admin.e-payments.transactions.index'))->assertRedirect();
    }

    public function test_admin_sees_all_transactions_latest_first_with_snapshot(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f9-list', 'SYN-F9-LIST-00000000001');
        $older = $this->launchProcessing($this->payer, $type, '10.00');
        $older->forceFill(['created_at' => now()->subHour()])->saveQuietly();
        $newer = $this->launchProcessing($this->payer, $type, '11.00');

        $other = $this->makeKorisnik(['email' => 'other-f9@example.com', 'jmb' => $this->validJmb(41)]);
        [$otherType] = $this->syntheticUsablePair($other, 'syn-f9-other', 'SYN-F9-OTHER-0000000001');
        $foreign = $this->launchProcessing($other, $otherType, '44.00');

        $html = $this->actingAs($this->admin)->get(route('admin.e-payments.transactions.index'))
            ->assertOk()
            ->assertSee('Transakcije')
            ->assertSee('11.00 EUR')
            ->assertSee('10.00 EUR')
            ->assertSee('44.00 EUR')
            ->assertSee('Synthetic user-flow type')
            ->assertSee((string) $foreign->merchant_transaction_id)
            ->getContent();

        $this->assertTrue(strpos($html, '11.00 EUR') < strpos($html, '10.00 EUR'));
        $this->actingAs($this->payer)->get(route('payments.history'))
            ->assertOk()
            ->assertDontSee((string) $foreign->merchant_transaction_id);
    }

    public function test_status_date_and_merchant_search_filters(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f9-filt', 'SYN-F9-FILT-00000000001');
        $successful = $this->launchProcessing($this->payer, $type, '21.00');
        $this->simulateOutcome($this->payer, $successful, PaymentStatus::Successful->value);
        $failed = $this->launchProcessing($this->payer, $type, '8.00');
        $this->simulateOutcome($this->payer, $failed, PaymentStatus::Failed->value);
        $failed->forceFill(['created_at' => now()->subDays(3)])->saveQuietly();

        $this->actingAs($this->admin)->get(route('admin.e-payments.transactions.index', [
            'status' => PaymentStatus::Successful->value,
        ]))->assertOk()->assertSee('21.00 EUR')->assertDontSee('8.00 EUR');

        $this->actingAs($this->admin)->get(route('admin.e-payments.transactions.index', [
            'q' => (string) $successful->merchant_transaction_id,
        ]))->assertOk()->assertSee((string) $successful->merchant_transaction_id)->assertDontSee((string) $failed->merchant_transaction_id);

        $this->actingAs($this->admin)->get(route('admin.e-payments.transactions.index', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->toDateString(),
        ]))->assertOk()->assertSee('21.00 EUR')->assertDontSee('8.00 EUR');
    }

    public function test_invalid_filter_is_rejected(): void
    {
        $this->actingAs($this->admin)->get(route('admin.e-payments.transactions.index', [
            'status' => 'not-a-status',
        ]))->assertSessionHasErrors('status');
    }

    public function test_pagination_does_not_load_all_rows_on_first_page(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f9-page', 'SYN-F9-PAGE-000000000001');
        for ($i = 1; $i <= PaymentAdminTransactionQuery::PER_PAGE + 1; $i++) {
            PaymentTransaction::factory()->create([
                'user_id' => $this->payer->id,
                'payment_type_id' => $type->id,
                'payment_account_id' => $type->accounts()->first()->id,
                'merchant_transaction_id' => 'EPLOCAL-F9PAGE-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'amount' => '1.00',
                'snapshot' => [
                    'payment_type_name' => 'Paged admin type',
                    'amount' => '1.00',
                    'currency' => 'EUR',
                    'account_number' => 'SYN-F9-PAGE-000000000001',
                ],
            ]);
        }

        $this->actingAs($this->admin)->get(route('admin.e-payments.transactions.index'))
            ->assertOk()
            ->assertSee('EPLOCAL-F9PAGE-0021')
            ->assertDontSee('EPLOCAL-F9PAGE-0001');
    }

    public function test_admin_detail_is_read_only_and_uses_snapshot(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f9-det', 'SYN-F9-DET-0000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '15.00');
        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Successful->value);
        $type->update(['name' => 'RENAMED LIVE TYPE']);

        $this->actingAs($this->admin)->get(route('admin.e-payments.transactions.show', $transaction))
            ->assertOk()
            ->assertSee('Uspješna')
            ->assertSee((string) $transaction->uuid)
            ->assertSee((string) $transaction->merchant_transaction_id)
            ->assertSee('Synthetic user-flow type')
            ->assertSee('Plaćanje pokrenuto')
            ->assertSee('email')
            ->assertDontSee('RENAMED LIVE TYPE')
            ->assertDontSee('Provjeri status')
            ->assertDontSee('Preuzmi potvrdu (PDF)')
            ->assertDontSee('Pošalji ponovo')
            ->assertDontSee('mark-successful')
            ->assertDontSee('0202990123456')
            ->assertDontSee('Njegoševa 12');

        $this->actingAs($this->payer)->get(route('admin.e-payments.transactions.show', $transaction))->assertForbidden();
    }

    public function test_fake_processing_has_no_inquiry_cta_and_post_is_safe(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f9-fake', 'SYN-F9-FAKE-00000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '4.00');

        $this->actingAs($this->admin)->get(route('admin.e-payments.transactions.show', $transaction))
            ->assertOk()
            ->assertSee('U obradi')
            ->assertDontSee('Provjeri status');

        $this->actingAs($this->admin)->post(route('admin.e-payments.transactions.check-status', $transaction))
            ->assertRedirect(route('admin.e-payments.transactions.show', $transaction))
            ->assertSessionHas('error', 'Provajder ne podržava provjeru statusa.');
        $this->assertSame(PaymentStatus::Processing, $transaction->fresh()->status);
    }

    public function test_inquiry_capable_processing_shows_cta_and_applies_outcomes(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f9-cta', 'SYN-F9-CTA-0000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '12.50');
        $this->bindInquiryGateway($this->inquiryGateway(PaymentGatewayInquiryOutcome::Successful, $transaction));

        $this->actingAs($this->admin)->get(route('admin.e-payments.transactions.show', $transaction))
            ->assertOk()
            ->assertSee('Provjeri status');

        $this->actingAs($this->admin)->post(route('admin.e-payments.transactions.check-status', $transaction))
            ->assertRedirect(route('admin.e-payments.transactions.show', $transaction))
            ->assertSessionHas('success', 'Status je potvrđen kao Uspješna.');
        $this->assertSame(PaymentStatus::Successful, $transaction->fresh()->status);
        Mail::assertSent(PaymentSuccessfulConfirmationMail::class, 1);

        $this->actingAs($this->admin)->get(route('admin.e-payments.transactions.show', $transaction->fresh()))
            ->assertOk()
            ->assertDontSee('Provjeri status');
        $this->actingAs($this->admin)->post(route('admin.e-payments.transactions.check-status', $transaction))
            ->assertSessionHas('error', 'Transakcija već ima konačan status. Provjera se ne pokreće.');
        Mail::assertSent(PaymentSuccessfulConfirmationMail::class, 1);
    }

    public function test_inquiry_failed_sets_failed(): void
    {
        $this->assertInquiryOutcome(PaymentGatewayInquiryOutcome::Failed, PaymentStatus::Failed, 'success', 'Status je potvrđen kao Neuspješna.');
    }

    public function test_inquiry_cancelled_sets_cancelled(): void
    {
        $this->assertInquiryOutcome(PaymentGatewayInquiryOutcome::Cancelled, PaymentStatus::Cancelled, 'success', 'Status je potvrđen kao Otkazana.');
    }

    public function test_inquiry_processing_stays_processing(): void
    {
        $this->assertInquiryOutcome(PaymentGatewayInquiryOutcome::Processing, PaymentStatus::Processing, 'success', 'Provajder i dalje vodi transakciju kao U obradi.');
    }

    public function test_inquiry_unknown_stays_processing(): void
    {
        $this->assertInquiryOutcome(PaymentGatewayInquiryOutcome::Unknown, PaymentStatus::Processing, 'error', 'Provajder nije vratio konačan status.');
    }

    public function test_inquiry_not_found_stays_processing(): void
    {
        $this->assertInquiryOutcome(PaymentGatewayInquiryOutcome::NotFound, PaymentStatus::Processing, 'error', 'Transakcija nije pronađena kod provajdera.');
    }

    public function test_inquiry_technical_error_stays_processing(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f9-tech', 'SYN-F9-TECH-00000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '9.00');
        $this->bindInquiryGateway(new SyntheticInquiryGateway(
            new PaymentGatewayInquiryResult(outcome: PaymentGatewayInquiryOutcome::Unknown),
            throwTechnical: true,
        ));
        $this->actingAs($this->admin)->post(route('admin.e-payments.transactions.check-status', $transaction))
            ->assertSessionHas('error', 'Provjera statusa trenutno nije dostupna.');
        $this->assertSame(PaymentStatus::Processing, $transaction->fresh()->status);
    }

    private function assertInquiryOutcome(
        PaymentGatewayInquiryOutcome $outcome,
        PaymentStatus $status,
        string $flash,
        string $message
    ): void {
        $suffix = substr($outcome->value, 0, 6);
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f9-'.$suffix, 'SYN-F9-'.$suffix.'-0000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '9.00');
        $this->bindInquiryGateway($this->inquiryGateway($outcome, $transaction));
        $this->actingAs($this->admin)->post(route('admin.e-payments.transactions.check-status', $transaction))
            ->assertSessionHas($flash, $message);
        $this->assertSame($status, $transaction->fresh()->status);
    }

    public function test_inquiry_amount_mismatch_fails_closed(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f9-mm', 'SYN-F9-MM-00000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '12.50');
        $this->bindInquiryGateway(new SyntheticInquiryGateway(new PaymentGatewayInquiryResult(
            outcome: PaymentGatewayInquiryOutcome::Successful,
            amount: '99.00',
            currency: 'EUR',
            merchantTransactionId: (string) $transaction->merchant_transaction_id,
            provider: 'synthetic-inquiry',
        )));

        $this->actingAs($this->admin)->post(route('admin.e-payments.transactions.check-status', $transaction))
            ->assertSessionHas('error', 'Provjera statusa nije primijenjena zbog neusklađenosti iznosa ili valute.');
        $this->assertSame(PaymentStatus::Processing, $transaction->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_module_off_keeps_admin_access_and_inquiry(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f9-off', 'SYN-F9-OFF-0000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '7.00');
        app(EpModuleSettings::class)->setNewPaymentsEnabled(false);
        $this->bindInquiryGateway($this->inquiryGateway(PaymentGatewayInquiryOutcome::Successful, $transaction));

        $this->actingAs($this->admin)->get(route('admin.e-payments.transactions.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.e-payments.transactions.show', $transaction))->assertOk();
        $this->actingAs($this->admin)->post(route('admin.e-payments.transactions.check-status', $transaction))
            ->assertSessionHas('success');
        $this->assertSame(PaymentStatus::Successful, $transaction->fresh()->status);
    }

    public function test_no_manual_status_or_delete_routes(): void
    {
        $this->assertFalse(Route::has('admin.e-payments.transactions.destroy'));
        $this->assertFalse(Route::has('admin.e-payments.transactions.update'));
        $this->assertFalse(Route::has('admin.e-payments.transactions.resend'));
    }

    private function inquiryGateway(PaymentGatewayInquiryOutcome $outcome, PaymentTransaction $transaction): SyntheticInquiryGateway
    {
        return new SyntheticInquiryGateway(new PaymentGatewayInquiryResult(
            outcome: $outcome,
            amount: (string) $transaction->amount,
            currency: (string) $transaction->currency,
            merchantTransactionId: (string) $transaction->merchant_transaction_id,
            provider: 'synthetic-inquiry',
        ));
    }

    private function bindInquiryGateway(SyntheticInquiryGateway $gateway): void
    {
        $this->app->instance(
            PaymentGatewayResolver::class,
            new class($this->app, $gateway) extends PaymentGatewayResolver
            {
                public function __construct($app, private PaymentGateway $fixed)
                {
                    parent::__construct($app);
                }

                public function resolve(): PaymentGateway
                {
                    return $this->fixed;
                }
            }
        );
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
