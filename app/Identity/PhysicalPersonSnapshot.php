<?php

namespace App\Identity;

final readonly class PhysicalPersonSnapshot
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $residentialStatus,
        public string $streetAndNumber,
        public string $city,
        public ?string $idDocumentType = null,
        public ?string $jmb = null,
        public ?string $passportNumber = null,
        public ?string $residenceCountryCode = null,
        public bool $isEntrepreneur = false,
        public ?string $entrepreneurBusinessName = null,
        public ?string $pib = null,
        public ?string $crpsNumber = null,
    ) {
    }
}
