<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;

final class PaymentGatewayVerifiedResult
{
    /**
     * @param  array<string, scalar|null>  $metadata
     */
    public function __construct(
        public readonly PaymentStatus $status,
        public readonly ?string $amount = null,
        public readonly ?string $currency = null,
        public readonly ?string $providerReference = null,
        public readonly ?string $eventId = null,
        public readonly ?string $merchantTransactionId = null,
        public readonly string $provider = 'unknown',
        public readonly array $metadata = [],
    ) {}

    public function resultIdentity(): string
    {
        if (is_string($this->eventId) && $this->eventId !== '') {
            return $this->eventId;
        }

        return hash('sha256', implode('|', [
            $this->merchantTransactionId ?? '',
            $this->status->value,
            $this->providerReference ?? '',
            $this->amount ?? '',
            $this->currency ?? '',
        ]));
    }
}
