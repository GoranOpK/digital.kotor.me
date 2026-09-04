<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionEvent;
use Illuminate\Support\Carbon;
use LogicException;

class PaymentConfirmationAssembler
{
    public function fromSuccessfulTransaction(PaymentTransaction $transaction): PaymentConfirmation
    {
        if ($transaction->status !== PaymentStatus::Successful) {
            throw new LogicException('Payment confirmation is issued only for successful transactions.');
        }

        $snapshot = is_array($transaction->snapshot) ? $transaction->snapshot : [];

        $merchantId = (string) $transaction->merchant_transaction_id;
        $filename = 'potvrda-'.$this->safeFilename($merchantId !== '' ? $merchantId : (string) $transaction->uuid).'.pdf';

        $succeededAtRaw = PaymentTransactionEvent::query()
            ->where('payment_transaction_id', $transaction->id)
            ->where('event_type', PaymentTransactionEventType::SUCCESSFUL)
            ->orderBy('id')
            ->value('occurred_at');
        $succeededAt = $succeededAtRaw !== null ? Carbon::parse($succeededAtRaw) : null;

        $gatewayReference = $transaction->gateway_reference;
        $gatewayReference = is_string($gatewayReference) && $gatewayReference !== '' ? $gatewayReference : null;

        $accountName = $this->optionalString($snapshot['account_name'] ?? null);
        $payerLabel = $this->optionalString($snapshot['payer_label'] ?? null) ?? '';
        $userTypeLabel = $this->optionalString($snapshot['user_type_label'] ?? null) ?? '';
        $typeName = $this->optionalString($snapshot['payment_type_name'] ?? null) ?? '';
        $accountNumber = $this->optionalString($snapshot['account_number'] ?? null) ?? '';
        $amount = $this->optionalString($snapshot['amount'] ?? null) ?? (string) $transaction->amount;
        $currency = $this->optionalString($snapshot['currency'] ?? null) ?? (string) $transaction->currency;

        return new PaymentConfirmation(
            title: 'Potvrda o uspješnoj transakciji',
            statusLabel: PaymentStatus::Successful->label(),
            succeededAtLabel: $succeededAt !== null
                ? $succeededAt->timezone(config('app.timezone'))->format('d.m.Y. H:i')
                : null,
            payerLabel: $payerLabel,
            userTypeLabel: $userTypeLabel,
            paymentTypeName: $typeName,
            accountNumber: $accountNumber,
            accountName: $accountName,
            amount: $this->normalizeAmount($amount),
            currency: $currency !== '' ? $currency : 'EUR',
            merchantTransactionId: $merchantId,
            gatewayReference: $gatewayReference,
            disclaimer: PaymentConfirmation::disclaimer(),
            issuer: 'Opština Kotor — Digital Kotor',
            pdfFilename: $filename,
        );
    }

    private function optionalString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function safeFilename(string $value): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '-', $value) ?? 'transakcija';

        return $safe !== '' ? $safe : 'transakcija';
    }

    private function normalizeAmount(string $amount): string
    {
        if (! str_contains($amount, '.')) {
            return $amount.'.00';
        }

        [$whole, $fraction] = explode('.', $amount, 2);

        return $whole.'.'.str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
