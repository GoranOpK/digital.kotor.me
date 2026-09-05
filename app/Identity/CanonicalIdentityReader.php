<?php

namespace App\Identity;

use App\Models\PlatformIdentity;
use App\Models\User;

/**
 * Canonical aggregate reader capability. Not rollout-gated.
 * Step 2 runtime never invokes it. No legacy fallback.
 */
final class CanonicalIdentityReader
{
    public function forUser(User $user): IdentitySnapshot
    {
        $platform = PlatformIdentity::query()
            ->where('user_id', $user->id)
            ->with(['physicalPerson', 'legalEntity.authorizedPerson', 'foreignBranch.representative'])
            ->first();

        if ($platform === null) {
            throw new CanonicalIdentityReadException('No canonical identity exists for this user.');
        }

        return $this->fromPlatformIdentity($platform);
    }

    public function fromPlatformIdentity(PlatformIdentity $platform): IdentitySnapshot
    {
        $platform->loadMissing(['physicalPerson', 'legalEntity.authorizedPerson', 'foreignBranch.representative']);

        $present = collect([
            $platform->physicalPerson !== null ? PlatformIdentity::SUBJECT_PHYSICAL_PERSON : null,
            $platform->legalEntity !== null ? PlatformIdentity::SUBJECT_LEGAL_ENTITY : null,
            $platform->foreignBranch !== null ? PlatformIdentity::SUBJECT_FOREIGN_BRANCH : null,
        ])->filter()->values();

        if ($present->count() !== 1) {
            throw new CanonicalIdentityReadException('Canonical identity does not have exactly one subject branch.');
        }

        $branch = $present->first();
        if ($branch !== $platform->subject_type) {
            throw new CanonicalIdentityReadException('Canonical subject_type does not match stored branch.');
        }

        return match ($platform->subject_type) {
            PlatformIdentity::SUBJECT_PHYSICAL_PERSON => $this->physicalPersonSnapshot($platform),
            PlatformIdentity::SUBJECT_LEGAL_ENTITY => $this->legalEntitySnapshot($platform),
            PlatformIdentity::SUBJECT_FOREIGN_BRANCH => $this->foreignBranchSnapshot($platform),
            default => throw new CanonicalIdentityReadException('Unsupported canonical subject type.'),
        };
    }

    private function physicalPersonSnapshot(PlatformIdentity $platform): IdentitySnapshot
    {
        $fl = $platform->physicalPerson;

        return new IdentitySnapshot(
            userId: (int) $platform->user_id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            mobilePhone: $platform->mobile_phone,
            streetAndNumber: $fl->street_and_number,
            city: $fl->city,
            physicalPerson: new PhysicalPersonSnapshot(
                firstName: $fl->first_name,
                lastName: $fl->last_name,
                residentialStatus: $fl->residential_status,
                streetAndNumber: $fl->street_and_number,
                city: $fl->city,
                idDocumentType: $fl->id_document_type,
                jmb: $fl->jmb,
                passportNumber: $fl->passport_number,
                residenceCountryCode: $fl->residence_country_code,
                isEntrepreneur: (bool) $fl->is_entrepreneur,
                entrepreneurBusinessName: $fl->entrepreneur_business_name,
                pib: $fl->pib,
                crpsNumber: $fl->crps_number,
            ),
        );
    }

    private function legalEntitySnapshot(PlatformIdentity $platform): IdentitySnapshot
    {
        $pl = $platform->legalEntity;
        if ($pl->authorizedPerson === null) {
            throw new CanonicalIdentityReadException('Legal entity identity is missing authorized person.');
        }

        $person = $pl->authorizedPerson;

        return new IdentitySnapshot(
            userId: (int) $platform->user_id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_LEGAL_ENTITY,
            mobilePhone: $platform->mobile_phone,
            streetAndNumber: $pl->street_and_number,
            city: $pl->city,
            legalEntity: new LegalEntitySnapshot(
                legalForm: $pl->legal_form,
                legalName: $pl->legal_name,
                streetAndNumber: $pl->street_and_number,
                city: $pl->city,
                authorizedPerson: $this->personInRole(
                    $person->first_name,
                    $person->last_name,
                    $person->id_document_type,
                    $person->jmb,
                    $person->passport_number,
                    $person->passport_issuing_country_code,
                ),
                pib: $pl->pib,
                crpsNumber: $pl->crps_number,
            ),
        );
    }

    private function foreignBranchSnapshot(PlatformIdentity $platform): IdentitySnapshot
    {
        $dspd = $platform->foreignBranch;
        if ($dspd->representative === null) {
            throw new CanonicalIdentityReadException('Foreign branch identity is missing representative.');
        }

        $person = $dspd->representative;

        return new IdentitySnapshot(
            userId: (int) $platform->user_id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_FOREIGN_BRANCH,
            mobilePhone: $platform->mobile_phone,
            streetAndNumber: $dspd->street_and_number,
            city: $dspd->city,
            foreignBranch: new ForeignBranchSnapshot(
                foreignCompanyName: $dspd->foreign_company_name,
                branchNameInMontenegro: $dspd->branch_name_in_montenegro,
                streetAndNumber: $dspd->street_and_number,
                city: $dspd->city,
                representative: $this->personInRole(
                    $person->first_name,
                    $person->last_name,
                    $person->id_document_type,
                    $person->jmb,
                    $person->passport_number,
                    $person->passport_issuing_country_code,
                ),
                pib: $dspd->pib,
                crpsNumber: $dspd->crps_number,
            ),
        );
    }

    private function personInRole(
        string $firstName,
        string $lastName,
        ?string $idDocumentType,
        ?string $jmb,
        ?string $passportNumber,
        ?string $passportIssuingCountryCode,
    ): PersonInRoleSnapshot {
        return new PersonInRoleSnapshot(
            firstName: $firstName,
            lastName: $lastName,
            idDocumentType: $idDocumentType,
            jmb: $jmb,
            passportNumber: $passportNumber,
            passportIssuingCountryCode: $passportIssuingCountryCode,
        );
    }
}
