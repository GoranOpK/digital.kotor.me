<?php

namespace App\Identity;

/**
 * Raw users.* facts. Adapter-only. Not a canonical classification.
 */
final readonly class LegacyUserFacts
{
    public function __construct(
        public ?string $userType,
        public ?string $residentialStatus,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $companyName,
        public ?string $jmb,
        public ?string $pib,
        public ?string $passportNumber,
        public ?string $phone,
        public ?string $address,
        public ?string $city,
    ) {
    }
}
