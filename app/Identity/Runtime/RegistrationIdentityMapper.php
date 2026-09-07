<?php

namespace App\Identity\Runtime;

use App\Identity\IdentitySnapshot;
use App\Identity\LegalEntitySnapshot;
use App\Identity\PersonInRoleSnapshot;
use App\Identity\PhysicalPersonSnapshot;
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

        $passport = isset($validated['passport_number'])
            ? strtoupper((string) $validated['passport_number'])
            : null;
        $hasJmb = is_string($jmb) && $jmb !== '';
        $documentType = $hasJmb
            ? PhysicalPersonIdentity::DOCUMENT_JMB
            : ($passport !== null ? PhysicalPersonIdentity::DOCUMENT_PASSPORT : null);

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
                residenceCountryCode: null,
                isEntrepreneur: $isEntrepreneur,
                entrepreneurBusinessName: $isEntrepreneur
                    ? (isset($validated['company_name']) ? trim((string) $validated['company_name']) : null)
                    : null,
                pib: $validated['pib'] ?? null,
                crpsNumber: null,
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
        $hasJmb = is_string($jmb) && $jmb !== '';

        return new IdentitySnapshot(
            userId: (int) $user->id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_LEGAL_ENTITY,
            mobilePhone: $userData['phone'] ?? null,
            streetAndNumber: $userData['address'] ?? '',
            city: $userData['city'] ?? '',
            legalEntity: new LegalEntitySnapshot(
                legalForm: $this->legalForm($storedUserType),
                legalName: trim((string) ($validated['company_name'] ?? $userData['company_name'] ?? '')),
                streetAndNumber: (string) $userData['address'],
                city: (string) $userData['city'],
                authorizedPerson: new PersonInRoleSnapshot(
                    firstName: (string) $userData['first_name'],
                    lastName: (string) $userData['last_name'],
                    idDocumentType: $hasJmb ? PhysicalPersonIdentity::DOCUMENT_JMB : null,
                    jmb: $hasJmb ? $jmb : null,
                    passportNumber: isset($validated['passport_number'])
                        ? strtoupper((string) $validated['passport_number'])
                        : null,
                    passportIssuingCountryCode: null,
                ),
                pib: $validated['pib'] ?? $userData['pib'] ?? null,
                crpsNumber: null,
            ),
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
            UserType::SPORTS_ORGANIZATION => LegalEntityIdentity::FORM_SPORTS_ORGANIZATION,
            default => throw new IdentityMutationDeniedException('Nepodržani pravni oblik.'),
        };
    }

    public function subjectTypeForUserType(string $userType): string
    {
        if (UserType::isNaturalPerson($userType)) {
            return PlatformIdentity::SUBJECT_PHYSICAL_PERSON;
        }

        if (UserType::isCanonical($userType) && UserType::isLegalEntity($userType)) {
            return PlatformIdentity::SUBJECT_LEGAL_ENTITY;
        }

        throw new IdentityMutationDeniedException('Prelaz identitetske grane nije dozvoljen.');
    }
}
