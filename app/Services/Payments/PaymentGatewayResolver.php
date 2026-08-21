<?php

namespace App\Services\Payments;

use Illuminate\Contracts\Foundation\Application;

class PaymentGatewayResolver
{
    public function __construct(
        private readonly Application $app
    ) {}

    public function resolve(): PaymentGateway
    {
        $name = config('payments.gateway');

        if (! is_string($name) || $name === '') {
            throw new PaymentGatewayNotConfiguredException('Payment gateway provider is not configured.');
        }

        if ($name === 'fake') {
            if (! $this->fakeIsAllowed()) {
                throw new FakePaymentGatewayUnavailableException('Fake payment gateway is not available.');
            }

            return $this->app->make(FakePaymentGateway::class);
        }

        throw new PaymentGatewayNotConfiguredException('Payment gateway provider is not configured.');
    }

    public function fakeIsAllowed(): bool
    {
        return (bool) config('payments.fake.enabled')
            && config('app.env') !== 'production';
    }
}
