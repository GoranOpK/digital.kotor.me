<?php

namespace App\Services\Payments;

use App\Models\PaymentTransaction;

final class PaymentGatewayStartRequest
{
    public function __construct(
        public readonly string $merchantTransactionId,
        public readonly string $transactionUuid,
        public readonly string $amount,
        public readonly string $currency,
    ) {}

    public static function fromTransaction(PaymentTransaction $transaction): self
    {
        return new self(
            merchantTransactionId: (string) $transaction->merchant_transaction_id,
            transactionUuid: (string) $transaction->uuid,
            amount: (string) $transaction->amount,
            currency: (string) $transaction->currency,
        );
    }
}
