<?php

namespace Tests\Feature;

use App\Models\PaymentInitiation;
use App\Models\PaymentTransaction;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\Support\StartsSyntheticPayment;
use Tests\TestCase;

class PaymentIdempotencyTest extends TestCase
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

    public function test_double_start_with_same_draft_creates_one_transaction(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-p5-dbl', 'SYN-P5-DBL-0000000000001');
        $this->reachPreview($this->payer, $type, '11.00');

        $draft = session('ep_payment_draft');
        $this->assertIsArray($draft);
        $this->assertNotEmpty($draft['merchant_transaction_id'] ?? null);

        $this->actingAs($this->payer)->post(route('payments.launch'))->assertRedirect();
        $this->withSession(['ep_payment_draft' => $draft])
            ->actingAs($this->payer)
            ->post(route('payments.launch'))
            ->assertRedirect();

        $this->assertSame(1, PaymentInitiation::query()->count());
        $this->assertSame(1, PaymentTransaction::query()->count());
        $this->assertSame(1, PaymentTransaction::query()
            ->where('merchant_transaction_id', $draft['merchant_transaction_id'])
            ->count());
    }

    public function test_browser_back_repost_after_start_does_not_create_another_transaction(): void
    {
        [$type] = $this->syntheticUsablePair($this->payer, 'syn-p5-back', 'SYN-P5-BACK-00000000001');
        $this->launchProcessing($this->payer, $type, '6.00');

        $this->actingAs($this->payer)
            ->from(route('payments.preview'))
            ->post(route('payments.launch'))
            ->assertRedirect(route('payments.index'));

        $this->assertSame(1, PaymentInitiation::query()->count());
        $this->assertSame(1, PaymentTransaction::query()->count());
    }
}
