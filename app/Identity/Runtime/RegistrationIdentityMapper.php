<?php

namespace App\Identity\Runtime;

use App\Identity\IdentitySnapshot;
use App\Identity\LegalEntitySnapshot;
use App\Identity\PersonInRoleSnapshot;
use App\Identity\PhysicalPersonSnapshot;
use App\Identity\ForeignBranchSnapshot;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Support\UserType;

final class RegistrationIdentityMapper
{
    /**
     * @param  array<string, mixed>  $userData
     * @param  array<string, mixed>  $validated
     */
    public function snapshot(
        User $user,
        array $userData,
        array $validated,
        ?string $jmb,
        string $storedUserType,
    ): IdentitySnapshot {
        if (UserType::isForeignBranch($storedUserType)) {
            return $this->dspdSnapshot($user, $userData, $validated, $jmb);
        }

        if (UserType::isLegalEntity($storedUserType)) {
            return $this->legalSnapshot($user, $userData, $validated, $jmb, $storedUserType);
        }

        return $this->physicalSnapshot($user, $userData, $validated, $jmb, $storedUserType);
    }

    /**
     * @param  array<string, mixed>  $userData
     * @param  array<string, mixed>  $validated
     */
    private function physicalSnapshot(
        User $user,
        array $userData,
        array $validated,
        ?string $jmb,
        string $storedUserType,
    ): IdentitySnapshot {
        $legacyResidential = $userData['residential_status'] ?? $validated['residential_status'] ?? null;
        $canonicalResidential = $legacyResidential === 'non-resident'
            ? PhysicalPersonIdentity::RESIDENTIAL_NON_RESIDENT
            : PhysicalPersonIdentity::RESIDENTIAL_RESIDENT;

        $passport = isset($validated['passport_number']) && $validated['passport_number'] !== ''
            ? strtoupper((string) $validated['passport_number'])
            : null;
        $hasJmb = is_string($jmb) && $jmb !== '';
        $documentType = $hasJmb
            ? PhysicalPersonIdentity::DOCUMENT_JMB
            : ($passport !== null ? PhysicalPersonIdentity::DOCUMENT_PASSPORT : null);

        $residenceCountry = $canonicalResidential === PhysicalPersonIdentity::RESIDENTIAL_NON_RESIDENT
            ? ($validated['residence_country_code'] ?? null)
            : null;

        $isEntrepreneur = $storedUserType === UserType::ENTREPRENEUR;

        return new IdentitySnapshot(
            userId: (int) $user->id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            mobilePhone: $userData['phone'] ?? null,
            streetAndNumber: $userData['address'] ?? '',
            city: $userData['city'] ?? '',
            physicalPerson: new PhysicalPersonSnapshot(
                firstName: (string) $userData['first_name'],
                lastName: (string) $userData['last_name'],
                residentialStatus: $canonicalResidential,
                streetAndNumber: (string) $userData['address'],
                city: (string) $userData['city'],
                idDocumentType: $documentType,
                jmb: $hasJmb ? $jmb : null,
                passportNumber: $passport,
                residenceCountryCode: is_string($residenceCountry) && $residenceCountry !== ''
                    ? $residenceCountry
                    : null,
                isEntrepreneur: $isEntrepreneur,
                entrepreneurBusinessName: $isEntrepreneur
                    ? trim((string) ($validated['entrepreneur_business_name'] ?? ''))
                    : null,
                pib: $isEntrepreneur ? ($validated['pib'] ?? null) : null,
                crpsNumber: $isEntrepreneur ? ($validated['crps_number'] ?? null) : null,
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $userData
     * @param  array<string, mixed>  $validated
     */
    private function legalSnapshot(
        User $user,
        array $userData,
        array $validated,
        ?string $jmb,
        string $storedUserType,
    ): IdentitySnapshot {
        $authorized = $this->personInRole(
            (string) $validated['authorized_first_name'],
            (string) $validated['authorized_last_name'],
            $validated['authorized_id_document_type'] ?? null,
            $jmb,
            $validated['authorized_passport_number'] ?? null,
            $validated['authorized_passport_issuing_country_code'] ?? null,
        );

        $requiresCrps = UserType::requiresCrps($storedUserType);

        return new IdentitySnapshot(
            userId: (int) $user->id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_LEGAL_ENTITY,
            mobilePhone: $userData['phone'] ?? null,
            streetAndNumber: $userData['address'] ?? '',
            city: $userData['city'] ?? '',
            legalEntity: new LegalEntitySnapshot(
                legalForm: $this->legalForm($storedUserType),
                legalName: trim((string) ($validated['legal_name'] ?? '')),
                streetAndNumber: (string) $userData['address'],
                city: (string) $userData['city'],
                authorizedPerson: $authorized,
                pib: $validated['pib'] ?? null,
                crpsNumber: $requiresCrps ? ($validated['crps_number'] ?? null) : null,
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $userData
     * @param  array<string, mixed>  $validated
     */
    private function dspdSnapshot(
        User $user,
        array $userData,
        array $validated,
        ?string $jmb,
    ): IdentitySnapshot {
        $representative = $this->personInRole(
            (string) $validated['representative_first_name'],
            (string) $validated['representative_last_name'],
            $validated['representative_id_document_type'] ?? null,
            $jmb,
            $validated['representative_passport_number'] ?? null,
            $validated['representative_passport_issuing_country_code'] ?? null,
        );

        return new IdentitySnapshot(
            userId: (int) $user->id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_FOREIGN_BRANCH,
            mobilePhone: $userData['phone'] ?? null,
            streetAndNumber: $userData['address'] ?? '',
            city: $userData['city'] ?? '',
            foreignBranch: new ForeignBranchSnapshot(
                foreignCompanyName: trim((string) ($validated['foreign_company_name'] ?? '')),
                branchNameInMontenegro: trim((string) ($validated['branch_name_in_montenegro'] ?? '')),
                streetAndNumber: (string) $userData['address'],
                city: (string) $userData['city'],
                representative: $representative,
                pib: $validated['pib'] ?? null,
                crpsNumber: $validated['crps_number'] ?? null,
            ),
        );
    }

    private function personInRole(
        string $firstName,
        string $lastName,
        mixed $documentType,
        ?string $jmb,
        mixed $passportNumber,
        mixed $passportCountry,
    ): PersonInRoleSnapshot {
        $type = is_string($documentType) ? $documentType : null;
        $hasJmb = $type === PhysicalPersonIdentity::DOCUMENT_JMB && is_string($jmb) && $jmb !== '';
        $passport = is_string($passportNumber) && $passportNumber !== ''
            ? strtoupper($passportNumber)
            : null;
        $country = is_string($passportCountry) && $passportCountry !== ''
            ? $passportCountry
            : null;

        return new PersonInRoleSnapshot(
            firstName: $firstName,
            lastName: $lastName,
            idDocumentType: $type,
            jmb: $hasJmb ? $jmb : null,
            passportNumber: $type === PhysicalPersonIdentity::DOCUMENT_PASSPORT ? $passport : null,
            passportIssuingCountryCode: $type === PhysicalPersonIdentity::DOCUMENT_PASSPORT ? $country : null,
        );
    }

    public function legalForm(string $userType): string
    {
        return match ($userType) {
            UserType::LIMITED_LIABILITY_COMPANY => LegalEntityIdentity::FORM_DOO,
            UserType::JOINT_STOCK_COMPANY => LegalEntityIdentity::FORM_AD,
            UserType::GENERAL_PARTNERSHIP => LegalEntityIdentity::FORM_OD,
            UserType::LIMITED_PARTNERSHIP => LegalEntityIdentity::FORM_KD,
            UserType::NGO_ASSOCIATION => LegalEntityIdentity::FORM_NVO_ASSOCIATION,
            UserType::NGO_FOUNDATION => LegalEntityIdentity::FORM_NVO_FOUNDATION,
            UserType::SPORTS_ORGANIZATION => LegalEntityIdentity::FORM_SPORTS_ORGANIZATION,
            default => throw new IdentityMutationDeniedException('Nepodržani pravni oblik.'),
        };
    }

    public function subjectTypeForUserType(string $userType): string
    {
        if (UserType::isNaturalPerson($userType)) {
            return PlatformIdentity::SUBJECT_PHYSICAL_PERSON;
        }

        if (UserType::isForeignBranch($userType)) {
            return PlatformIdentity::SUBJECT_FOREIGN_BRANCH;
        }

        if (UserType::isLegalEntity($userType) && ($userType === UserType::NGO_FOUNDATION || UserType::isCanonical($userType))) {
            return PlatformIdentity::SUBJECT_LEGAL_ENTITY;
        }

        throw new IdentityMutationDeniedException('Prelaz identitetske grane nije dozvoljen.');
    }
}
