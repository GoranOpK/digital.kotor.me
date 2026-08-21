<?php

namespace Tests\Support;

use App\Services\Payments\GatewayInquiryException;
use App\Services\Payments\GatewayStartException;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentGatewayCapabilities;
use App\Services\Payments\PaymentGatewayInquiryResult;
use App\Services\Payments\PaymentGatewayStartRequest;
use App\Services\Payments\PaymentGatewayStartResult;
use App\Services\Payments\PaymentGatewayStatusInquiry;

class SyntheticInquiryGateway implements PaymentGateway, PaymentGatewayStatusInquiry
{
    public function __construct(
        private readonly PaymentGatewayInquiryResult $result,
        private readonly bool $throwTechnical = false,
    ) {}

    public function name(): string
    {
        return 'synthetic-inquiry';
    }

    public function capabilities(): PaymentGatewayCapabilities
    {
        return new PaymentGatewayCapabilities(
            start: false,
            resultVerification: true,
            statusInquiry: true,
            hostedRedirect: false,
        );
    }

    public function start(PaymentGatewayStartRequest $request): PaymentGatewayStartResult
    {
        throw new GatewayStartException('Inquiry test double does not start payments.');
    }

    public function inquire(PaymentGatewayStartRequest $identity): PaymentGatewayInquiryResult
    {
        if ($this->throwTechnical) {
            throw new GatewayInquiryException('Synthetic inquiry technical error.');
        }

        return $this->result;
    }
}
