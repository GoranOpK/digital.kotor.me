<?php

namespace App\Identity\Runtime;

use App\Identity\IdentitySnapshot;
use App\Identity\LegalEntitySnapshot;
use App\Identity\PersonInRoleSnapshot;
use App\Identity\PhoneCallingCodeCatalog;
use App\Identity\PhysicalPersonSnapshot;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;

class ExistingSubjectIdentitySnapshotMapper
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function fromValidated(User $user, string $branch, array $validated): IdentitySnapshot
    {
        $mobile = PhoneCallingCodeCatalog::compose(
            (string) $validated['phone_calling_code'],
            (string) $validated['phone_national'],
        );
        $street = trim((string) $validated['street_and_number']);
        $city = trim((string) $validated['city']);

        if ($branch === ExistingSubjectIdentityEligibilityResult::BRANCH_DOO) {
            return $this->dooSnapshot($user, $validated, $mobile, $street, $city);
        }

        return $this->preduzetnikSnapshot($user, $validated, $mobile, $street, $city);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function dooSnapshot(User $user, array $validated, string $mobile, string $street, string $city): IdentitySnapshot
    {
        $documentType = (string) $validated['authorized_id_document_type'];
        $isJmb = $documentType === PhysicalPersonIdentity::DOCUMENT_JMB;

        return new IdentitySnapshot(
            userId: (int) $user->id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_LEGAL_ENTITY,
            mobilePhone: $mobile,
            streetAndNumber: $street,
            city: $city,
            legalEntity: new LegalEntitySnapshot(
                legalForm: LegalEntityIdentity::FORM_DOO,
                legalName: trim((string) $validated['legal_name']),
                streetAndNumber: $street,
                city: $city,
                authorizedPerson: new PersonInRoleSnapshot(
                    firstName: trim((string) $validated['authorized_first_name']),
                    lastName: trim((string) $validated['authorized_last_name']),
                    idDocumentType: $documentType,
                    jmb: $isJmb ? (string) $validated['authorized_jmb'] : null,
                    passportNumber: $isJmb ? null : strtoupper((string) $validated['authorized_passport_number']),
                    passportIssuingCountryCode: $isJmb ? null : strtoupper((string) $validated['authorized_passport_issuing_country_code']),
                ),
                pib: (string) $validated['pib'],
                crpsNumber: (string) $validated['crps_number'],
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function preduzetnikSnapshot(User $user, array $validated, string $mobile, string $street, string $city): IdentitySnapshot
    {
        $httpResidential = (string) $validated['residential_status'];
        $canonicalResidential = $httpResidential === 'non-resident'
            ? PhysicalPersonIdentity::RESIDENTIAL_NON_RESIDENT
            : PhysicalPersonIdentity::RESIDENTIAL_RESIDENT;

        $isResident = $canonicalResidential === PhysicalPersonIdentity::RESIDENTIAL_RESIDENT;
        $documentType = $isResident
            ? PhysicalPersonIdentity::DOCUMENT_JMB
            : (string) $validated['id_document_type'];
        $isJmb = $documentType === PhysicalPersonIdentity::DOCUMENT_JMB;

        return new IdentitySnapshot(
            userId: (int) $user->id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            mobilePhone: $mobile,
            streetAndNumber: $street,
            city: $city,
            physicalPerson: new PhysicalPersonSnapshot(
                firstName: trim((string) $validated['first_name']),
                lastName: trim((string) $validated['last_name']),
                residentialStatus: $canonicalResidential,
                streetAndNumber: $street,
                city: $city,
                idDocumentType: $documentType,
                jmb: $isJmb ? (string) $validated['jmb'] : null,
                passportNumber: $isJmb ? null : strtoupper((string) ($validated['passport_number'] ?? '')),
                residenceCountryCode: $isResident ? null : strtoupper((string) $validated['residence_country_code']),
                isEntrepreneur: true,
                entrepreneurBusinessName: trim((string) $validated['entrepreneur_business_name']),
                pib: (string) $validated['pib'],
                crpsNumber: (string) $validated['crps_number'],
            ),
        );
    }
}
