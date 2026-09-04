<?php

namespace App\Services\Payments;

final class PaymentGatewayStartResult
{
    public function __construct(
        public readonly PaymentGatewayStartOutcome $outcome,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $providerReference = null,
    ) {}

    public static function redirectReady(string $redirectUrl, ?string $providerReference = null): self
    {
        return new self(
            outcome: PaymentGatewayStartOutcome::RedirectReady,
            redirectUrl: $redirectUrl,
            providerReference: $providerReference,
        );
    }

    public static function technicalFailure(?string $providerReference = null): self
    {
        return new self(
            outcome: PaymentGatewayStartOutcome::TechnicalFailure,
            providerReference: $providerReference,
        );
    }

    public static function unsupported(): self
    {
        return new self(outcome: PaymentGatewayStartOutcome::Unsupported);
    }

    public function isRedirectReady(): bool
    {
        return $this->outcome === PaymentGatewayStartOutcome::RedirectReady
            && is_string($this->redirectUrl)
            && $this->redirectUrl !== '';
    }
}
