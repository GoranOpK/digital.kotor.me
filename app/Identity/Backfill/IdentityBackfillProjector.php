<?php

namespace App\Identity\Backfill;

use App\Identity\Census\IdentityCensusRow;
use App\Identity\IdentitySnapshot;
use App\Identity\PhysicalPersonSnapshot;
use App\Identity\Validation\JmbIdentifierValidator;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Security\JmbEncryptedReadException;
use App\Security\JmbEncryptedReadService;

/**
 * Deterministic users → canonical Step 4 snapshot. Create-input only.
 * Does not emit legacyFacts. Does not invent missing identity data.
 */
class IdentityBackfillProjector
{
    public function __construct(
        private readonly JmbIdentifierValidator $jmbValidator = new JmbIdentifierValidator,
    ) {
    }

    public function project(User $user, IdentityCensusRow $row): ?IdentitySnapshot
    {
        if ($row->userId !== (int) $user->id) {
            return null;
        }

        if ($row->rowStatus !== IdentityCensusRow::BACKFILLABLE || ! $row->fullyBackfillable) {
            return null;
        }

        if ($row->isStaffAccount) {
            return null;
        }

        if ($row->subjectType !== PlatformIdentity::SUBJECT_PHYSICAL_PERSON) {
            return null;
        }

        if ($row->isEntrepreneur) {
            return null;
        }

        if ($row->idDocumentType !== PhysicalPersonIdentity::DOCUMENT_JMB) {
            return null;
        }

        if ($this->outerTrim($user->residential_status) !== 'resident') {
            return null;
        }

        $firstName = $this->outerTrim($user->first_name);
        $lastName = $this->outerTrim($user->last_name);
        $street = $this->outerTrim($user->address);
        $city = $this->outerTrim($user->city);
        $jmb = $this->logicalUserJmb($user);
        $phone = $this->outerTrim($user->phone);

        if ($firstName === null || $lastName === null || $street === null || $city === null || $jmb === null) {
            return null;
        }

        if (! $this->jmbValidator->isValid($jmb)) {
            return null;
        }

        return new IdentitySnapshot(
            userId: (int) $user->id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            mobilePhone: $phone,
            streetAndNumber: $street,
            city: $city,
            physicalPerson: new PhysicalPersonSnapshot(
                firstName: $firstName,
                lastName: $lastName,
                residentialStatus: PhysicalPersonIdentity::RESIDENTIAL_RESIDENT,
                streetAndNumber: $street,
                city: $city,
                idDocumentType: PhysicalPersonIdentity::DOCUMENT_JMB,
                jmb: $jmb,
                passportNumber: null,
                residenceCountryCode: null,
                isEntrepreneur: false,
                entrepreneurBusinessName: null,
                pib: null,
                crpsNumber: null,
            ),
        );
    }

    private function logicalUserJmb(User $user): ?string
    {
        try {
            return $this->outerTrim(app(JmbEncryptedReadService::class)->readValue(
                $user->jmb_encrypted,
                $user->jmb,
                'users',
                $user->id,
                'jmb/jmb_encrypted',
            ));
        } catch (JmbEncryptedReadException) {
            return null;
        }
    }

    private function outerTrim(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
