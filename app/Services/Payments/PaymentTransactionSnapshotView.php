<?php

namespace App\Services\Payments;

use App\Models\PaymentTransaction;

final class PaymentTransactionSnapshotView
{
    public function __construct(
        public readonly string $payerLabel,
        public readonly string $userTypeLabel,
        public readonly string $paymentTypeName,
        public readonly string $accountNumber,
        public readonly string $accountName,
        public readonly string $amount,
        public readonly string $currency,
    ) {}

    public static function from(PaymentTransaction $transaction): self
    {
        $snapshot = is_array($transaction->snapshot) ? $transaction->snapshot : [];

        return new self(
            payerLabel: self::string($snapshot['payer_label'] ?? null),
            userTypeLabel: self::string($snapshot['user_type_label'] ?? null),
            paymentTypeName: self::string($snapshot['payment_type_name'] ?? null),
            accountNumber: self::string($snapshot['account_number'] ?? null),
            accountName: self::string($snapshot['account_name'] ?? null),
            amount: self::string($snapshot['amount'] ?? null) !== ''
                ? self::string($snapshot['amount'] ?? null)
                : (string) $transaction->amount,
            currency: self::string($snapshot['currency'] ?? null) !== ''
                ? self::string($snapshot['currency'] ?? null)
                : ((string) $transaction->currency !== '' ? (string) $transaction->currency : 'EUR'),
        );
    }

    public function amountWithCurrency(): string
    {
        return $this->amount.' '.$this->currency;
    }

    private static function string(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return trim($value);
    }
}
