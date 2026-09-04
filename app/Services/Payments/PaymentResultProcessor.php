<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentResultProcessor
{
    public function __construct(
        private readonly PaymentConfirmationDeliveryService $confirmations,
    ) {}

    public function apply(PaymentTransaction $transaction, PaymentGatewayVerifiedResult $result): PaymentTransaction
    {
        try {
            $this->assertAmountAndCurrency($transaction, $result);
        } catch (GatewayVerificationException $e) {
            $this->recordSidecarEvent(
                $transaction,
                PaymentTransactionEventType::GATEWAY_VERIFICATION_FAILED,
                $result,
                ['reason' => 'amount_or_currency_mismatch']
            );

            Log::info('ep.payment.verification_failed', [
                'transaction_uuid' => $transaction->uuid,
                'merchant_transaction_id' => $transaction->merchant_transaction_id,
                'provider' => $result->provider,
                'reason' => 'amount_or_currency_mismatch',
            ]);

            throw $e;
        }

        $newlySuccessful = false;

        $updated = DB::transaction(function () use ($transaction, $result, &$newlySuccessful) {
            $locked = PaymentTransaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status->isTerminal()) {
                if ($locked->status === $result->status) {
                    return $locked;
                }

                $this->recordSidecarEvent(
                    $locked,
                    PaymentTransactionEventType::GATEWAY_CONTRADICTORY_RESULT,
                    $result,
                    [
                        'current_status' => $locked->status->value,
                        'incoming_status' => $result->status->value,
                    ]
                );

                Log::info('ep.payment.callback_conflict_ignored', [
                    'transaction_uuid' => $locked->uuid,
                    'merchant_transaction_id' => $locked->merchant_transaction_id,
                    'current_status' => $locked->status->value,
                    'incoming_status' => $result->status->value,
                    'provider' => $result->provider,
                ]);

                return $locked;
            }

            if ($locked->status !== PaymentStatus::Processing) {
                return $locked;
            }

            $locked->status = $result->status;
            if ($result->providerReference !== null && $result->providerReference !== '') {
                $locked->gateway_reference = $result->providerReference;
            }

            PaymentTransaction::$allowCanonicalStatusTransition = true;
            try {
                $locked->save();
            } finally {
                PaymentTransaction::$allowCanonicalStatusTransition = false;
            }

            $this->recordTransition($locked, $result);

            if ($result->status === PaymentStatus::Successful) {
                $newlySuccessful = true;
            }

            Log::info('ep.payment.callback_applied', [
                'transaction_uuid' => $locked->uuid,
                'merchant_transaction_id' => $locked->merchant_transaction_id,
                'status' => $result->status->value,
                'provider' => $result->provider,
            ]);

            return $locked->fresh();
        });

        if ($newlySuccessful) {
            try {
                $this->confirmations->sendAfterNewSuccessfulTransition($updated);
            } catch (Throwable $e) {
                Log::info('ep.payment.confirmation_delivery_failed', [
                    'transaction_uuid' => $updated->uuid,
                    'merchant_transaction_id' => $updated->merchant_transaction_id,
                    'exception_class' => $e::class,
                ]);
            }
        }

        return $updated;
    }

    private function assertAmountAndCurrency(PaymentTransaction $transaction, PaymentGatewayVerifiedResult $result): void
    {
        if ($result->currency !== null && $result->currency !== '') {
            if ($result->currency !== 'EUR' || $result->currency !== (string) $transaction->currency) {
                throw new GatewayVerificationException('Payment result currency mismatch.');
            }
        }

        if ($result->amount !== null && $result->amount !== '') {
            if ($this->normalizeAmount($result->amount) !== $this->normalizeAmount((string) $transaction->amount)) {
                throw new GatewayVerificationException('Payment result amount mismatch.');
            }
        }
    }

    private function recordTransition(PaymentTransaction $transaction, PaymentGatewayVerifiedResult $result): void
    {
        $eventType = match ($result->status) {
            PaymentStatus::Successful => PaymentTransactionEventType::SUCCESSFUL,
            PaymentStatus::Failed => PaymentTransactionEventType::FAILED,
            PaymentStatus::Cancelled => PaymentTransactionEventType::CANCELLED,
            default => null,
        };

        if ($eventType === null) {
            return;
        }

        $already = PaymentTransactionEvent::query()
            ->where('payment_transaction_id', $transaction->id)
            ->where('event_type', $eventType)
            ->exists();

        if ($already) {
            return;
        }

        PaymentTransactionEvent::query()->create([
            'payment_transaction_id' => $transaction->id,
            'event_type' => $eventType,
            'provider_event_id' => $result->resultIdentity(),
            'payload' => [
                'status' => $result->status->value,
                'provider' => $result->provider,
            ],
            'occurred_at' => now(),
            'received_at' => now(),
        ]);
    }

    /**
     * @param  array<string, scalar|null>  $extra
     */
    private function recordSidecarEvent(
        PaymentTransaction $transaction,
        string $eventType,
        PaymentGatewayVerifiedResult $result,
        array $extra = []
    ): void {
        $identity = $result->resultIdentity();

        $already = PaymentTransactionEvent::query()
            ->where('payment_transaction_id', $transaction->id)
            ->where('event_type', $eventType)
            ->where('provider_event_id', $identity)
            ->exists();

        if ($already) {
            return;
        }

        PaymentTransactionEvent::query()->create([
            'payment_transaction_id' => $transaction->id,
            'event_type' => $eventType,
            'provider_event_id' => $identity,
            'payload' => array_merge($extra, [
                'provider' => $result->provider,
                'status' => $result->status->value,
            ]),
            'occurred_at' => now(),
            'received_at' => now(),
        ]);
    }

    private function normalizeAmount(string $amount): string
    {
        if (! str_contains($amount, '.')) {
            return $amount.'.00';
        }

        [$whole, $fraction] = explode('.', $amount, 2);

        return $whole.'.'.str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
