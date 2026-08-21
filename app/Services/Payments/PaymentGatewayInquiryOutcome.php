<?php

namespace App\Services\Payments;

enum PaymentGatewayInquiryOutcome: string
{
    case Successful = 'successful';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Processing = 'processing';
    case Unknown = 'unknown';
    case NotFound = 'not_found';
    case Unsupported = 'unsupported';
    case TechnicalError = 'technical_error';
}
