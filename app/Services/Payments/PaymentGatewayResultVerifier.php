<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;

interface PaymentGatewayResultVerifier
{
    public function verify(PaymentTransaction $transaction, PaymentStatus $normalizedStatus): PaymentGatewayVerifiedResult;
}
