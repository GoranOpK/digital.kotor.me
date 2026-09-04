<?php

namespace App\Services\Payments;

final class PaymentGatewayCapabilities
{
    public function __construct(
        public readonly bool $start = true,
        public readonly bool $resultVerification = true,
        public readonly bool $statusInquiry = false,
        public readonly bool $hostedRedirect = true,
    ) {}
}
