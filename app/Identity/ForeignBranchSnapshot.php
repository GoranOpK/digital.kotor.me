<?php

namespace App\Identity;

final readonly class ForeignBranchSnapshot
{
    public function __construct(
        public string $foreignCompanyName,
        public string $branchNameInMontenegro,
        public string $streetAndNumber,
        public string $city,
        public ?PersonInRoleSnapshot $representative = null,
        public ?string $pib = null,
        public ?string $crpsNumber = null,
    ) {
    }
}
