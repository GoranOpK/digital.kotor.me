<?php

namespace App\Services\Payments;

interface PaymentGateway
{
    public function name(): string;

    public function capabilities(): PaymentGatewayCapabilities;

    public function start(PaymentGatewayStartRequest $request): PaymentGatewayStartResult;
}
