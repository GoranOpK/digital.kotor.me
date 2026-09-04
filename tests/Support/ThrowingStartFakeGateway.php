<?php

namespace Tests\Support;

use App\Services\Payments\FakePaymentGateway;
use App\Services\Payments\GatewayStartException;
use App\Services\Payments\PaymentGatewayStartRequest;
use App\Services\Payments\PaymentGatewayStartResult;

class ThrowingStartFakeGateway extends FakePaymentGateway
{
    public function start(PaymentGatewayStartRequest $request): PaymentGatewayStartResult
    {
        throw new GatewayStartException('Synthetic start failure.');
    }
}
