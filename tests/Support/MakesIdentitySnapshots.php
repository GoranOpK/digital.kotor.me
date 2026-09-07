<?php

namespace Tests\Support;

use App\Identity\ForeignBranchSnapshot;
use App\Identity\IdentitySnapshot;
use App\Identity\LegalEntitySnapshot;
use App\Identity\PersonInRoleSnapshot;
use App\Identity\PhysicalPersonSnapshot;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;

trait MakesIdentitySnapshots
{
    protected function flSnapshot(User $user, array $overrides = []): IdentitySnapshot
    {
        $person = array_merge([
            'firstName' => 'Ana',
            'lastName' => 'Anić',
            'residentialStatus' => PhysicalPersonIdentity::RESIDENTIAL_RESIDENT,
            'streetAndNumber' => 'Njegoševa 12',
            'city' => 'Podgorica',
            'idDocumentType' => PhysicalPersonIdentity::DOCUMENT_JMB,
            'jmb' => '0000000000000',
            'passportNumber' => null,
            'residenceCountryCode' => null,
            'isEntrepreneur' => false,
            'entrepreneurBusinessName' => null,
            'pib' => null,
            'crpsNumber' => null,
        ], $overrides['person'] ?? []);

        return new IdentitySnapshot(
            userId: $user->id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            mobilePhone: $overrides['mobilePhone'] ?? '+38267000001',
            streetAndNumber: $person['streetAndNumber'],
            city: $person['city'],
            physicalPerson: new PhysicalPersonSnapshot(...$person),
        );
    }

    protected function entrepreneurSnapshot(User $user): IdentitySnapshot
    {
        return $this->flSnapshot($user, [
            'person' => [
                'isEntrepreneur' => true,
                'entrepreneurBusinessName' => 'Radnja Ana',
                'pib' => '12345672',
                'crpsNumber' => '10000001',
            ],
        ]);
    }

    protected function plSnapshot(User $user, array $personOverrides = []): IdentitySnapshot
    {
        $authorized = array_merge([
            'firstName' => 'Marko',
            'lastName' => 'Marković',
            'idDocumentType' => 'jmb',
            'jmb' => '0000000000000',
            'passportNumber' => null,
            'passportIssuingCountryCode' => null,
        ], $personOverrides);

        return new IdentitySnapshot(
            userId: $user->id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_LEGAL_ENTITY,
            mobilePhone: '+38267000002',
            streetAndNumber: 'Slobode 1',
            city: 'Podgorica',
            legalEntity: new LegalEntitySnapshot(
                legalForm: LegalEntityIdentity::FORM_DOO,
                legalName: 'Primjer DOO',
                streetAndNumber: 'Slobode 1',
                city: 'Podgorica',
                authorizedPerson: new PersonInRoleSnapshot(
                    firstName: $authorized['firstName'],
                    lastName: $authorized['lastName'],
                    idDocumentType: $authorized['idDocumentType'],
                    jmb: $authorized['jmb'],
                    passportNumber: $authorized['passportNumber'],
                    passportIssuingCountryCode: $authorized['passportIssuingCountryCode'],
                ),
                pib: '12345672',
                crpsNumber: '50000001',
            ),
        );
    }

    protected function plSnapshotWithoutAuthorizedPerson(User $user): IdentitySnapshot
    {
        return new IdentitySnapshot(
            userId: $user->id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_LEGAL_ENTITY,
            mobilePhone: '+38267000002',
            streetAndNumber: 'Slobode 1',
            city: 'Podgorica',
            legalEntity: new LegalEntitySnapshot(
                legalForm: LegalEntityIdentity::FORM_DOO,
                legalName: 'Primjer DOO',
                streetAndNumber: 'Slobode 1',
                city: 'Podgorica',
                authorizedPerson: null,
                pib: '12345672',
                crpsNumber: '50000001',
            ),
        );
    }

    protected function dspdSnapshot(User $user, bool $withRepresentative = true): IdentitySnapshot
    {
        return new IdentitySnapshot(
            userId: $user->id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_FOREIGN_BRANCH,
            mobilePhone: '+38267000003',
            streetAndNumber: 'Bulevar 8',
            city: 'Podgorica',
            foreignBranch: new ForeignBranchSnapshot(
                foreignCompanyName: 'Foreign Co',
                branchNameInMontenegro: 'Ogranak CG',
                streetAndNumber: 'Bulevar 8',
                city: 'Podgorica',
                representative: $withRepresentative ? new PersonInRoleSnapshot(
                    firstName: 'Jelena',
                    lastName: 'Jovanović',
                    idDocumentType: 'passport',
                    passportNumber: 'AB123456',
                    passportIssuingCountryCode: 'IT',
                ) : null,
                pib: '00000007',
                crpsNumber: '60000001',
            ),
        );
    }
}
