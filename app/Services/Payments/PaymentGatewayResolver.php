<?php

namespace App\Services\Payments;

use App\Models\PaymentTransaction;
use Illuminate\Contracts\Foundation\Application;

class PaymentGatewayResolver
{
    public function __construct(
        private readonly Application $app
    ) {}

    public function resolve(): PaymentGateway
    {
        return $this->byName($this->configuredName());
    }

    public function forTransaction(PaymentTransaction $transaction): PaymentGateway
    {
        $provider = $transaction->provider;
        if (! is_string($provider) || $provider === '') {
            throw new PaymentGatewayNotConfiguredException('Historical payment provider is unknown.');
        }

        return $this->byName($provider);
    }

    public function fakeIsAllowed(): bool
    {
        return (bool) config('payments.fake.enabled')
            && config('app.env') !== 'production';
    }

    private function configuredName(): string
    {
        $name = config('payments.gateway');
        if (! is_string($name) || $name === '') {
            throw new PaymentGatewayNotConfiguredException('Payment gateway provider is not configured.');
        }

        return $name;
    }

    private function byName(string $name): PaymentGateway
    {
        if ($name === 'fake') {
            if (! $this->fakeIsAllowed()) {
                throw new FakePaymentGatewayUnavailableException('Fake payment gateway is not available.');
            }

            return $this->app->make(FakePaymentGateway::class);
        }

        throw new PaymentGatewayNotConfiguredException('Payment gateway provider is not configured.');
    }
}
