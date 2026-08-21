<?php

namespace App\Services\Payments;

final class PaymentGatewayInquiryResult
{
    public function __construct(
        public readonly PaymentGatewayInquiryOutcome $outcome,
        public readonly ?string $amount = null,
        public readonly ?string $currency = null,
        public readonly ?string $providerReference = null,
        public readonly ?string $eventId = null,
        public readonly ?string $merchantTransactionId = null,
        public readonly string $provider = 'unknown',
    ) {}

    public function toVerifiedResult(): ?PaymentGatewayVerifiedResult
    {
        $status = match ($this->outcome) {
            PaymentGatewayInquiryOutcome::Successful => \App\Enums\PaymentStatus::Successful,
            PaymentGatewayInquiryOutcome::Failed => \App\Enums\PaymentStatus::Failed,
            PaymentGatewayInquiryOutcome::Cancelled => \App\Enums\PaymentStatus::Cancelled,
            default => null,
        };

        if ($status === null) {
            return null;
        }

        return new PaymentGatewayVerifiedResult(
            status: $status,
            amount: $this->amount,
            currency: $this->currency,
            providerReference: $this->providerReference,
            eventId: $this->eventId,
            merchantTransactionId: $this->merchantTransactionId,
            provider: $this->provider,
        );
    }
}
