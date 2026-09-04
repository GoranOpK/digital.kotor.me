<?php

namespace App\Enums;

/**
 * Evaluation outcome for EP catalog availability.
 * Not a business transaction status. Not a documentation abbreviation.
 */
enum PaymentAvailabilityOutcome: string
{
    case Available = 'available';
    case NotAvailable = 'not_available';
    case ResidentialDeclarationRequired = 'residential_declaration_required';

    public function isUsable(): bool
    {
        return $this === self::Available;
    }
}
