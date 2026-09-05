<?php

namespace App\Identity;

final readonly class PersonInRoleSnapshot
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public ?string $idDocumentType = null,
        public ?string $jmb = null,
        public ?string $passportNumber = null,
        public ?string $passportIssuingCountryCode = null,
    ) {
    }
}
