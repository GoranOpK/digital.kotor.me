<?php

namespace Tests\Support;

use App\Models\PaymentTransaction;
use App\Models\PaymentType;
use App\Models\User;
use Illuminate\Testing\TestResponse;

trait StartsSyntheticPayment
{
    protected function reachPreview(User $user, PaymentType $type, string $amount = '12.50'): void
    {
        $this->actingAs($user)->get(route('payments.start', $type));
        $this->actingAs($user)->post(route('payments.amount.store'), ['amount' => $amount]);
        $this->actingAs($user)->get(route('payments.preview'))->assertOk();
    }

    protected function launchProcessing(User $user, PaymentType $type, string $amount = '12.50'): PaymentTransaction
    {
        $this->reachPreview($user, $type, $amount);

        $response = $this->actingAs($user)->post(route('payments.launch'));
        $response->assertRedirect();

        return PaymentTransaction::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();
    }

    protected function openSimulator(User $user, PaymentTransaction $transaction): TestResponse
    {
        return $this->actingAs($user)->get(\Illuminate\Support\Facades\URL::signedRoute('payments.fake.show', [
            'payment_transaction' => $transaction->uuid,
        ]));
    }

    protected function simulateOutcome(User $user, PaymentTransaction $transaction, string $outcome): TestResponse
    {
        return $this->actingAs($user)->post(\Illuminate\Support\Facades\URL::signedRoute('payments.fake.simulate', [
            'payment_transaction' => $transaction->uuid,
            'outcome' => $outcome,
        ]));
    }
}
