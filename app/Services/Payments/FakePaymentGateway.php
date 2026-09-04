<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\URL;

class FakePaymentGateway implements PaymentGateway, PaymentGatewayResultVerifier
{
    public const SYNTHETIC_REFERENCE_PREFIX = 'SYN-GW-';

    public function __construct(
        private readonly PaymentGatewayResolver $resolver
    ) {}

    public function name(): string
    {
        return 'fake';
    }

    public function capabilities(): PaymentGatewayCapabilities
    {
        return new PaymentGatewayCapabilities(
            start: true,
            resultVerification: true,
            statusInquiry: false,
            hostedRedirect: true,
        );
    }

    public function start(PaymentGatewayStartRequest $request): PaymentGatewayStartResult
    {
        if (! $this->resolver->fakeIsAllowed()) {
            throw new FakePaymentGatewayUnavailableException('Fake payment gateway is not available.');
        }

        return PaymentGatewayStartResult::redirectReady(
            URL::signedRoute('payments.fake.show', [
                'payment_transaction' => $request->transactionUuid,
            ]),
        );
    }

    public function verify(PaymentTransaction $transaction, PaymentStatus $normalizedStatus): PaymentGatewayVerifiedResult
    {
        return $this->verifiedResult($transaction, $normalizedStatus);
    }

    public function verifiedResult(PaymentTransaction $transaction, PaymentStatus $status): PaymentGatewayVerifiedResult
    {
        return new PaymentGatewayVerifiedResult(
            status: $status,
            amount: (string) $transaction->amount,
            currency: (string) $transaction->currency,
            providerReference: self::SYNTHETIC_REFERENCE_PREFIX.$transaction->uuid,
            eventId: 'SYN-EVT-'.$transaction->uuid.'-'.$status->value,
            merchantTransactionId: (string) $transaction->merchant_transaction_id,
            provider: $this->name(),
        );
    }
}
