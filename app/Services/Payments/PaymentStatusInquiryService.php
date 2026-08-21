<?php

namespace App\Services\Payments;

use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentStatusInquiryService
{
    public function __construct(
        private readonly PaymentGatewayResolver $gateways,
        private readonly PaymentResultProcessor $processor,
    ) {}

    public function checkStatus(PaymentTransaction $transaction, ?PaymentGateway $gateway = null): PaymentTransaction
    {
        $gateway ??= $this->gateways->resolve();

        if (! $gateway->capabilities()->statusInquiry || ! $gateway instanceof PaymentGatewayStatusInquiry) {
            $this->recordInquiry($transaction, $gateway->name(), PaymentGatewayInquiryOutcome::Unsupported->value);

            throw new GatewayInquiryException('Payment status inquiry is not supported.');
        }

        try {
            $inquiry = $gateway->inquire(PaymentGatewayStartRequest::fromTransaction($transaction));
        } catch (Throwable $e) {
            $this->recordInquiry($transaction, $gateway->name(), PaymentGatewayInquiryOutcome::TechnicalError->value);

            throw $e instanceof GatewayInquiryException
                ? $e
                : new GatewayInquiryException('Payment status inquiry failed.');
        }

        $this->recordInquiry($transaction, $gateway->name(), $inquiry->outcome->value, $inquiry->eventId);

        Log::info('ep.payment.inquiry', [
            'transaction_uuid' => $transaction->uuid,
            'merchant_transaction_id' => $transaction->merchant_transaction_id,
            'provider' => $gateway->name(),
            'inquiry_outcome' => $inquiry->outcome->value,
        ]);

        $verified = $inquiry->toVerifiedResult();
        if ($verified === null) {
            return $transaction->fresh() ?? $transaction;
        }

        return $this->processor->apply($transaction, $verified);
    }

    private function recordInquiry(
        PaymentTransaction $transaction,
        string $provider,
        string $outcome,
        ?string $eventId = null
    ): void {
        PaymentTransactionEvent::query()->create([
            'payment_transaction_id' => $transaction->id,
            'event_type' => PaymentTransactionEventType::GATEWAY_INQUIRY,
            'provider_event_id' => $eventId,
            'payload' => [
                'provider' => $provider,
                'inquiry_outcome' => $outcome,
            ],
            'occurred_at' => now(),
            'received_at' => now(),
        ]);
    }
}
