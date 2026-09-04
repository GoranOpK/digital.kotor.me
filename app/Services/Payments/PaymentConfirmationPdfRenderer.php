<?php

namespace App\Services\Payments;

use App\Models\PaymentTransaction;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentConfirmationPdfRenderer
{
    public function __construct(
        private readonly PaymentConfirmationAssembler $assembler,
    ) {}

    public function render(PaymentConfirmation $confirmation): string
    {
        return Pdf::loadView('payments.confirmation-pdf', [
            'confirmation' => $confirmation,
        ])->setPaper('a4')->output();
    }

    public function renderForTransaction(PaymentTransaction $transaction): string
    {
        return $this->render($this->assembler->fromSuccessfulTransaction($transaction));
    }
}
