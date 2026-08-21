<?php

namespace App\Services\Payments;

use App\Enums\PaymentConfirmationDeliveryStatus;
use App\Enums\PaymentStatus;
use App\Mail\PaymentSuccessfulConfirmationMail;
use App\Models\PaymentConfirmationDelivery;
use App\Models\PaymentTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PaymentConfirmationDeliveryService
{
    public function __construct(
        private readonly PaymentConfirmationAssembler $assembler,
        private readonly PaymentConfirmationPdfRenderer $pdf,
    ) {}

    /**
     * First processing → successful transition only. Replay must no-op.
     * Mail/PDF/DB failure must not change PaymentTransaction status.
     */
    public function sendAfterNewSuccessfulTransition(PaymentTransaction $transaction): void
    {
        $transaction = $transaction->fresh() ?? $transaction;

        if ($transaction->status !== PaymentStatus::Successful) {
            return;
        }

        $user = $transaction->user;
        $recipient = is_string($user?->email) ? trim($user->email) : '';
        if ($recipient === '') {
            Log::info('ep.payment.confirmation_email_skipped', [
                'transaction_uuid' => $transaction->uuid,
                'merchant_transaction_id' => $transaction->merchant_transaction_id,
                'reason' => 'missing_recipient',
            ]);

            return;
        }

        try {
            $delivery = PaymentConfirmationDelivery::query()->create([
                'payment_transaction_id' => $transaction->id,
                'channel' => PaymentConfirmationDelivery::CHANNEL_EMAIL,
                'status' => PaymentConfirmationDeliveryStatus::Pending,
                'recipient_email' => $recipient,
            ]);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                Log::info('ep.payment.confirmation_email_skipped', [
                    'transaction_uuid' => $transaction->uuid,
                    'merchant_transaction_id' => $transaction->merchant_transaction_id,
                    'reason' => 'duplicate_delivery',
                ]);

                return;
            }

            Log::info('ep.payment.confirmation_delivery_unavailable', [
                'transaction_uuid' => $transaction->uuid,
                'merchant_transaction_id' => $transaction->merchant_transaction_id,
                'exception_class' => $e::class,
                'sql_state' => $this->sqlState($e),
            ]);

            return;
        }

        try {
            $confirmation = $this->assembler->fromSuccessfulTransaction($transaction);
            $pdfBinary = $this->safePdf($confirmation);

            Mail::to($recipient)->send(new PaymentSuccessfulConfirmationMail(
                confirmation: $confirmation,
                pdfBinary: $pdfBinary,
            ));

            $delivery->status = PaymentConfirmationDeliveryStatus::Sent;
            $delivery->sent_at = now();
            $delivery->error_class = null;
            $delivery->save();

            Log::info('ep.payment.confirmation_email_sent', [
                'transaction_uuid' => $transaction->uuid,
                'merchant_transaction_id' => $transaction->merchant_transaction_id,
            ]);
        } catch (Throwable $e) {
            $delivery->status = PaymentConfirmationDeliveryStatus::Failed;
            $delivery->failed_at = now();
            $delivery->error_class = $e::class;
            $delivery->save();

            Log::info('ep.payment.confirmation_email_failed', [
                'transaction_uuid' => $transaction->uuid,
                'merchant_transaction_id' => $transaction->merchant_transaction_id,
                'exception_class' => $e::class,
            ]);
        }
    }

    public function emailWasSent(PaymentTransaction $transaction): bool
    {
        try {
            return PaymentConfirmationDelivery::query()
                ->where('payment_transaction_id', $transaction->id)
                ->where('channel', PaymentConfirmationDelivery::CHANNEL_EMAIL)
                ->where('status', PaymentConfirmationDeliveryStatus::Sent)
                ->exists();
        } catch (QueryException $e) {
            Log::info('ep.payment.confirmation_delivery_lookup_failed', [
                'transaction_uuid' => $transaction->uuid,
                'merchant_transaction_id' => $transaction->merchant_transaction_id,
                'exception_class' => $e::class,
                'sql_state' => $this->sqlState($e),
            ]);

            return false;
        }
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $sqlState = $this->sqlState($e);
        $driverCode = (int) ($e->errorInfo[1] ?? 0);
        $message = strtolower($e->getMessage());

        if ($driverCode === 1062) {
            return true;
        }

        return $sqlState === '23000'
            && ($driverCode === 19 || str_contains($message, 'unique') || str_contains($message, 'duplicate'));
    }

    private function sqlState(QueryException $e): string
    {
        $state = $e->errorInfo[0] ?? $e->getCode();

        return is_string($state) || is_int($state) ? (string) $state : '';
    }

    private function safePdf(PaymentConfirmation $confirmation): ?string
    {
        try {
            return $this->pdf->render($confirmation);
        } catch (Throwable $e) {
            Log::info('ep.payment.confirmation_pdf_attach_failed', [
                'merchant_transaction_id' => $confirmation->merchantTransactionId,
                'exception_class' => $e::class,
            ]);

            return null;
        }
    }
}
