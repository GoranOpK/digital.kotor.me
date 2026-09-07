<?php

namespace App\Services\Payments;

use App\Models\User;

/**
 * Identity inputs consumed by EP catalog availability. Not a persistence model.
 */
final readonly class PaymentAvailabilityFacts
{
    public function __construct(
        public mixed $userType,
        public mixed $residentialStatus,
        public bool $isStaffAccount,
    ) {
    }

    public static function fromUser(User $user): self
    {
        return new self(
            $user->user_type,
            $user->residential_status,
            $user->isStaffAccount(),
        );
    }
}
