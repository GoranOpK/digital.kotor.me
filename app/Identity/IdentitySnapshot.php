<?php

namespace App\Identity;

/**
 * Canonical-neutral identity value object (D1 graph).
 * Not an Eloquent model. Not a runtime SSOT in Step 2.
 */
final readonly class IdentitySnapshot
{
    public function __construct(
        public int $userId,
        public bool $isRegisteredSubject,
        public ?string $subjectType,
        public ?string $mobilePhone,
        public ?string $streetAndNumber,
        public ?string $city,
        public ?PhysicalPersonSnapshot $physicalPerson = null,
        public ?LegalEntitySnapshot $legalEntity = null,
        public ?ForeignBranchSnapshot $foreignBranch = null,
        public ?LegacyUserFacts $legacyFacts = null,
    ) {
    }
}
