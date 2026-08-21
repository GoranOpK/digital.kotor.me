<?php

namespace App\Services\Payments;

final class PaymentTransactionEventType
{
    public const STARTED = 'transaction.started';

    public const GATEWAY_REDIRECTED = 'gateway.redirected';

    public const GATEWAY_START_FAILED = 'gateway.start_failed';

    public const GATEWAY_VERIFICATION_FAILED = 'gateway.verification_failed';

    public const GATEWAY_CONTRADICTORY_RESULT = 'gateway.contradictory_result';

    public const GATEWAY_INQUIRY = 'gateway.inquiry';

    public const SUCCESSFUL = 'payment.successful';

    public const FAILED = 'payment.failed';

    public const CANCELLED = 'payment.cancelled';
}
