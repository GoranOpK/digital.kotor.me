<?php

namespace App\Identity\Runtime;

final readonly class SubjectIdentityView
{
    public function __construct(
        public string $access,
        public bool $isRegisteredSubject,
        public ?string $userType,
        public ?string $residentialStatus,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $phone,
        public ?string $address,
        public ?string $city,
        public ?string $jmb,
        public ?string $pib,
        public ?string $companyName,
        public ?string $passportNumber,
        public ?string $crpsNumber = null,
    ) {
    }

    public function hasCurrentSubjectIdentity(): bool
    {
        return $this->access === IdentityAccess::CURRENT;
    }

    public function displayName(): string
    {
        $combined = trim((string) $this->firstName.' '.(string) $this->lastName);

        return $combined !== '' ? $combined : 'N/A';
    }
}
