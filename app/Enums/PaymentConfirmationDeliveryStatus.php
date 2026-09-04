<?php

namespace App\Enums;

enum PaymentConfirmationDeliveryStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
}
