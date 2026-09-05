<?php

namespace App\Identity;

final readonly class LegalEntitySnapshot
{
    public function __construct(
        public string $legalForm,
        public string $legalName,
        public string $streetAndNumber,
        public string $city,
        public ?PersonInRoleSnapshot $authorizedPerson = null,
        public ?string $pib = null,
        public ?string $crpsNumber = null,
    ) {
    }
}
