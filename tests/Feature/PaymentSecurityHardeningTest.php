<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Mail\PaymentSuccessfulConfirmationMail;
use App\Models\PaymentAccount;
use App\Models\PaymentConfirmationDelivery;
use App\Models\PaymentInitiation;
use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionEvent;
use App\Models\PaymentType;
use App\Models\Role;
use App\Models\User;
use App\Services\Payments\PaymentConfirmationAssembler;
use App\Services\Payments\PaymentTransactionEventType;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use LogicException;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\Support\StartsSyntheticPayment;
use Tests\TestCase;

class PaymentSecurityHardeningTest extends TestCase
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
        $this->admin = $this->userWithRole('admin', 'Platform Admin', 'ep-f10-admin@example.com');
        $this->payer = $this->makeKorisnik(['email' => 'payer-f10@example.com']);
        Mail::fake();
        RateLimiter::clear('ep-admin-inquiry:'.$this->admin->id);
    }

    public function test_guest_and_unverified_cannot_open_user_or_admin_ep_routes(): void
    {
        $this->get(route('payments.index'))->assertRedirect(route('login'));
        $this->get(route('payments.history'))->assertRedirect(route('login'));
        $this->get(route('admin.e-payments.transactions.index'))->assertRedirect(route('login'));

        $unverified = $this->makeKorisnik([
            'email' => 'unverified-f10@example.com',
            'email_verified_at' => null,
            'jmb' => $this->validJmb(50),
        ]);

        $this->actingAs($unverified)->get(route('payments.index'))->assertRedirect('/verify-email');
        $this->actingAs($unverified)->get(route('admin.e-payments.transactions.index'))->assertRedirect('/verify-email');
    }

    public function test_non_admin_roles_cannot_open_admin_transactions_or_catalog(): void
    {
        $this->actingAs($this->payer)->get(route('admin.e-payments.transactions.index'))->assertForbidden();
        $this->actingAs($this->payer)->get(route('admin.e-payments.payment-types.index'))->assertForbidden();
        $this->actingAs($this->payer)->get(route('admin.e-payments.settings.edit'))->assertForbidden();

        foreach (['komisija', 'konkurs_admin', 'kk_admin'] as $role) {
            $user = $this->userWithRole($role, ucfirst($role).' User', 'ep-f10-'.$role.'@example.com');
            $this->actingAs($user)->get(route('admin.e-payments.transactions.index'))->assertRedirect();
            $this->actingAs($user)->get(route('admin.e-payments.payment-types.index'))->assertRedirect();
        }
    }

    public function test_idor_unknown_uuid_and_foreign_transaction_fail_closed(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f10-idor', 'SYN-F10-IDOR-0000000001');
        $own = $this->launchProcessing($this->payer, $type, '10.00');
        $this->simulateOutcome($this->payer, $own, PaymentStatus::Successful->value);

        $other = $this->makeKorisnik(['email' => 'other-f10@example.com', 'jmb' => $this->validJmb(42)]);
        [$otherType] = $this->syntheticUsablePair($other, 'syn-f10-idor-b', 'SYN-F10-IDOR-B-00000001');
        $foreign = $this->launchProcessing($other, $otherType, '11.00');
        $this->simulateOutcome($other, $foreign, PaymentStatus::Successful->value);

        $missing = '00000000-0000-4000-8000-000000000000';

        $this->actingAs($this->payer)
            ->get(route('payments.result', $missing))
            ->assertNotFound();
        $this->actingAs($this->payer)
            ->get(route('payments.confirmation.pdf', $missing))
            ->assertNotFound();
        $this->actingAs($this->payer)
            ->get(route('payments.result', $foreign))
            ->assertNotFound();
        $this->actingAs($this->payer)
            ->get(route('payments.confirmation.pdf', $foreign))
            ->assertNotFound();
        $this->actingAs($this->payer)
            ->get(route('admin.e-payments.transactions.show', $own))
            ->assertForbidden();
        $this->actingAs($this->admin)
            ->get(route('admin.e-payments.transactions.show', $missing))
            ->assertNotFound();
    }

    public function test_launch_ignores_client_controlled_amount_user_and_status(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f10-bypass', 'SYN-F10-BYPASS-00000001');
        $this->reachPreview($this->payer, $type, '12.50');

        $this->actingAs($this->payer)->post(route('payments.launch'), [
            'amount' => '999.99',
            'user_id' => $this->admin->id,
            'status' => PaymentStatus::Successful->value,
            'merchant_transaction_id' => 'CLIENT-FORGED',
        ])->assertRedirect();

        $transaction = PaymentTransaction::query()->where('user_id', $this->payer->id)->latest('id')->firstOrFail();

        $this->assertSame('12.50', (string) $transaction->amount);
        $this->assertSame($this->payer->id, (int) $transaction->user_id);
        $this->assertSame(PaymentStatus::Processing, $transaction->status);
        $this->assertNotSame('CLIENT-FORGED', $transaction->merchant_transaction_id);
        $this->assertStringStartsWith('EPLOCAL-', (string) $transaction->merchant_transaction_id);
    }

    public function test_catalog_mutations_reject_get_and_legal_entity_cannot_set_residential(): void
    {
        $type = PaymentType::factory()->create(['is_active' => false, 'code' => 'syn-f10-get']);

        $this->actingAs($this->admin)
            ->get(route('admin.e-payments.payment-types.activate', $type))
            ->assertMethodNotAllowed();
        $this->actingAs($this->admin)
            ->get('/admin/e-placanje/transakcije/00000000-0000-4000-8000-000000000001/provjeri-status')
            ->assertMethodNotAllowed();

        $legal = $this->makeKorisnik([
            'email' => 'legal-f10@example.com',
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'residential_status' => null,
            'company_name' => 'Syn DOO',
            'pib' => '12345678',
            'jmb' => null,
        ]);

        $this->actingAs($legal)
            ->post(route('payments.declaration.store'), [
                'residential_status' => 'resident',
                'user_id' => $this->payer->id,
            ])
            ->assertRedirect();

        $this->assertNull($legal->fresh()->residential_status);
    }

    public function test_transaction_identity_and_status_cannot_be_mutated_outside_processor(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f10-immut', 'SYN-F10-IMMUT-000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '15.00');
        $originalTypeId = (int) $transaction->payment_type_id;
        $originalSnapshot = $transaction->snapshot;
        $otherType = PaymentType::factory()->create(['code' => 'syn-f10-other']);

        $transaction->status = PaymentStatus::Successful;
        $transaction->amount = '99.00';
        $transaction->user_id = $this->admin->id;
        $transaction->payment_type_id = $otherType->id;
        $transaction->snapshot = array_merge($originalSnapshot, ['payer_label' => 'HACKED']);
        $transaction->merchant_transaction_id = 'HACKED-ID';
        $transaction->provider = 'other-provider';
        $transaction->save();
        $transaction->refresh();

        $this->assertSame(PaymentStatus::Processing, $transaction->status);
        $this->assertSame('15.00', (string) $transaction->amount);
        $this->assertSame($this->payer->id, (int) $transaction->user_id);
        $this->assertSame($originalTypeId, (int) $transaction->payment_type_id);
        $this->assertSame($originalSnapshot['payer_label'] ?? null, $transaction->snapshot['payer_label'] ?? null);
        $this->assertNotSame('HACKED-ID', $transaction->merchant_transaction_id);
        $this->assertSame(app(\App\Services\Payments\FakePaymentGateway::class)->name(), $transaction->provider);

        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Successful->value);
        $transaction->refresh();
        $this->assertSame(PaymentStatus::Successful, $transaction->status);

        $transaction->status = PaymentStatus::Failed;
        $transaction->gateway_reference = 'overwrite-ref';
        $transaction->save();
        $transaction->refresh();
        $this->assertSame(PaymentStatus::Successful, $transaction->status);
        $this->assertNotSame('overwrite-ref', $transaction->gateway_reference);

        try {
            $transaction->delete();
            $this->fail('Payment transactions must not be deleted.');
        } catch (LogicException $e) {
            $this->assertSame('Payment transactions must not be deleted.', $e->getMessage());
        }
    }

    public function test_events_reject_unknown_type_and_strip_sensitive_payload(): void
    {
        $transaction = PaymentTransaction::factory()->create();

        $event = PaymentTransactionEvent::factory()->create([
            'payment_transaction_id' => $transaction->id,
            'event_type' => PaymentTransactionEventType::GATEWAY_INQUIRY,
            'payload' => [
                'provider' => 'fake',
                'inquiry_outcome' => 'unknown',
                'email' => 'secret@example.com',
                'secret' => 'hmac-value',
                'raw' => ['body' => 'callback'],
            ],
        ]);

        $this->assertSame([
            'provider' => 'fake',
            'inquiry_outcome' => 'unknown',
        ], $event->fresh()->payload);

        $this->expectException(LogicException::class);
        PaymentTransactionEvent::factory()->create([
            'payment_transaction_id' => $transaction->id,
            'event_type' => 'raw.callback',
        ]);
    }

    public function test_delivery_identity_is_locked_and_delete_is_rejected(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f10-deliv', 'SYN-F10-DELIV-000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '9.00');
        $this->simulateOutcome($this->payer, $transaction, PaymentStatus::Successful->value);

        $delivery = PaymentConfirmationDelivery::query()
            ->where('payment_transaction_id', $transaction->id)
            ->firstOrFail();
        $originalRecipient = $delivery->recipient_email;

        $delivery->recipient_email = 'attacker@example.com';
        $delivery->channel = 'sms';
        $delivery->save();
        $delivery->refresh();

        $this->assertSame($originalRecipient, $delivery->recipient_email);
        $this->assertSame(PaymentConfirmationDelivery::CHANNEL_EMAIL, $delivery->channel);

        $this->expectException(LogicException::class);
        $delivery->delete();
    }

    public function test_snapshot_strings_are_escaped_in_history_result_admin_pdf_and_email(): void
    {
        $xss = '<script>alert(1)</script>';
        $payer = $this->makeKorisnik([
            'email' => 'xss-f10@example.com',
            'name' => $xss,
            'first_name' => $xss,
            'last_name' => 'X',
            'jmb' => $this->validJmb(51),
        ]);
        $type = PaymentType::factory()->create([
            'code' => 'syn-f10-xss',
            'name' => $xss,
            'is_active' => true,
        ]);
        $account = PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => 'SYN-F10-XSS-000000000001',
            'name' => $xss,
            'is_active' => true,
        ]);
        $this->grantAvailability($type, $account, (string) $payer->user_type, $payer->residential_status);

        $transaction = $this->launchProcessing($payer, $type, '13.10');
        $this->simulateOutcome($payer, $transaction, PaymentStatus::Successful->value);

        $this->actingAs($payer)->get(route('payments.history'))
            ->assertOk()
            ->assertSee($xss)
            ->assertDontSee($xss, false);
        $this->actingAs($payer)->get(route('payments.result', $transaction))
            ->assertOk()
            ->assertSee($xss)
            ->assertDontSee($xss, false);
        $this->actingAs($this->admin)->get(route('admin.e-payments.transactions.show', $transaction))
            ->assertOk()
            ->assertSee($xss)
            ->assertDontSee($xss, false);
        $this->actingAs($this->admin)->get(route('admin.e-payments.payment-types.index'))
            ->assertOk()
            ->assertSee($xss)
            ->assertDontSee($xss, false);

        $confirmation = app(PaymentConfirmationAssembler::class)->fromSuccessfulTransaction($transaction->fresh());
        $pdfHtml = view('payments.confirmation-pdf', ['confirmation' => $confirmation])->render();
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $pdfHtml);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $pdfHtml);

        Mail::assertSent(PaymentSuccessfulConfirmationMail::class, function (PaymentSuccessfulConfirmationMail $mail) use ($xss): bool {
            $html = $mail->render();

            return str_contains($html, e($xss))
                && ! str_contains($html, '<script>alert(1)</script>');
        });
    }

    public function test_admin_inquiry_is_throttled_without_changing_status(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f10-rl', 'SYN-F10-RL-0000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '6.00');

        $this->actingAs($this->admin);
        $last = null;
        for ($i = 0; $i < 21; $i++) {
            $last = $this->post(route('admin.e-payments.transactions.check-status', $transaction));
        }

        $last->assertStatus(429);
        $this->assertSame(PaymentStatus::Processing, $transaction->fresh()->status);
    }

    public function test_unknown_provider_fails_closed_and_does_not_expose_exception_class(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f10-unk', 'SYN-F10-UNK-000000000001');
        $this->reachPreview($this->payer, $type, '8.00');

        config(['payments.gateway' => 'not-a-real-provider']);

        $this->actingAs($this->payer)
            ->post(route('payments.launch'))
            ->assertRedirect(route('payments.index'))
            ->assertSessionHas('error', 'Plaćanje trenutno nije dostupno. Pokušajte kasnije.');

        $this->assertSame(0, PaymentTransaction::query()->count());
        $this->actingAs($this->payer)->get(route('payments.index'))
            ->assertOk()
            ->assertDontSee('PaymentGatewayNotConfiguredException')
            ->assertDontSee('not-a-real-provider');
    }

    public function test_tampered_fake_signed_url_is_rejected(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-f10-sig', 'SYN-F10-SIG-000000000001');
        $transaction = $this->launchProcessing($this->payer, $type, '4.00');

        $signed = URL::signedRoute('payments.fake.simulate', [
            'payment_transaction' => $transaction->uuid,
            'outcome' => PaymentStatus::Successful->value,
        ]);
        $tampered = str_replace('successful', 'failed', $signed);

        $this->actingAs($this->payer)->post($tampered)->assertForbidden();
        $this->actingAs($this->payer)
            ->get(route('payments.fake.show', $transaction))
            ->assertForbidden();
        $this->assertSame(PaymentStatus::Processing, $transaction->fresh()->status);
    }

    public function test_initiation_identity_cannot_be_rewritten(): void
    {
        $initiation = PaymentInitiation::factory()->create([
            'user_id' => $this->payer->id,
            'amount' => '3.00',
        ]);
        $originalAmount = (string) $initiation->amount;

        $initiation->user_id = $this->admin->id;
        $initiation->amount = '88.00';
        $initiation->save();
        $initiation->refresh();

        $this->assertSame($this->payer->id, (int) $initiation->user_id);
        $this->assertSame($originalAmount, (string) $initiation->amount);
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
