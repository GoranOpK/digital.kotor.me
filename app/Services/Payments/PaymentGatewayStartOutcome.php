<?php

namespace App\Services\Payments;

enum PaymentGatewayStartOutcome: string
{
    case RedirectReady = 'redirect_ready';
    case TechnicalFailure = 'technical_failure';
    case Unsupported = 'unsupported';
}
