<?php

namespace App\Services\Payments;

final class PaymentConfirmation
{
    public function __construct(
        public readonly string $title,
        public readonly string $statusLabel,
        public readonly ?string $succeededAtLabel,
        public readonly string $payerLabel,
        public readonly string $userTypeLabel,
        public readonly string $paymentTypeName,
        public readonly string $accountNumber,
        public readonly ?string $accountName,
        public readonly string $amount,
        public readonly string $currency,
        public readonly string $merchantTransactionId,
        public readonly ?string $gatewayReference,
        public readonly string $disclaimer,
        public readonly string $issuer,
        public readonly string $pdfFilename,
    ) {}

    public static function disclaimer(): string
    {
        return 'Ova potvrda potvrđuje uspješno izvršenu transakciju kroz e-Plaćanje Digital Kotor. '
            .'Ne predstavlja fiskalni račun niti potvrdu da je obaveza prema Opštini izmirena u izvornom sistemu.';
    }

    public function amountWithCurrency(): string
    {
        return $this->amount.' '.$this->currency;
    }
}
