<?php

namespace App\Services\Payments;

interface PaymentGatewayStatusInquiry
{
    public function inquire(PaymentGatewayStartRequest $identity): PaymentGatewayInquiryResult;
}
