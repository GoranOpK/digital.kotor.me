<?php

namespace App\Identity\Runtime;

use App\Identity\CanonicalIdentityReadException;
use App\Identity\CanonicalIdentityReader;
use App\Identity\IdentitySnapshot;
use App\Identity\LegalEntitySnapshot;
use App\Identity\PersonInRoleSnapshot;
use App\Identity\PhysicalPersonSnapshot;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Support\UserType;
use Illuminate\Http\Request;

final class ProfileIdentityMapper
{
    public function __construct(
        private readonly CanonicalIdentityReader $reader = new CanonicalIdentityReader,
        private readonly RegistrationIdentityMapper $registrationMapper = new RegistrationIdentityMapper,
    ) {
    }

    public function snapshotForUpdate(User $user, Request $request): IdentitySnapshot
    {
        try {
            $current = $this->reader->forUser($user);
        } catch (CanonicalIdentityReadException $e) {
            throw new IdentityUseGateException(
                IdentityAccess::MISSING,
                'Identitet nije potpun i ne može se mijenjati dok se ne dopuni.'
            );
        }

        $incomingType = (string) $request->input('user_type', $user->user_type);
        if (UserType::isRetainedLegacy($incomingType)) {
            throw new IdentityMutationDeniedException(
                'Naslijeđena kategorija identiteta ne može se kanonski mijenjati.'
            );
        }

        $incomingSubject = $this->registrationMapper->subjectTypeForUserType($incomingType);
        if ($incomingSubject !== $current->subjectType) {
            throw new IdentityMutationDeniedException(
                'Prelaz identitetske grane nije dozvoljen.'
            );
        }

        if ($current->subjectType === PlatformIdentity::SUBJECT_PHYSICAL_PERSON) {
            return $this->overlayPhysical($user, $current, $request, $incomingType);
        }

        if ($current->subjectType === PlatformIdentity::SUBJECT_LEGAL_ENTITY) {
            return $this->overlayLegal($user, $current, $request, $incomingType);
        }

        throw new IdentityMutationDeniedException(
            'Ova grana identiteta ne može se mijenjati sa profila.'
        );
    }

    private function overlayPhysical(
        User $user,
        IdentitySnapshot $current,
        Request $request,
        string $incomingType,
    ): IdentitySnapshot {
        $fl = $current->physicalPerson;
        if ($fl === null) {
            throw new IdentityUseGateException(IdentityAccess::INVALID, 'Identitet nije validan i mora se ispraviti (D14).');
        }

        $legacyResidential = $request->input('residential_status', $user->residential_status);
        $canonicalResidential = $legacyResidential === 'non-resident'
            ? PhysicalPersonIdentity::RESIDENTIAL_NON_RESIDENT
            : PhysicalPersonIdentity::RESIDENTIAL_RESIDENT;

        $jmb = $request->has('jmb') ? ($request->input('jmb') ?: null) : $fl->jmb;
        $passport = $request->has('passport_number')
            ? ($request->input('passport_number') ? strtoupper((string) $request->input('passport_number')) : null)
            : $fl->passportNumber;
        $isEntrepreneur = $this->requestWantsEntrepreneur($request, $incomingType);
        $documentType = $jmb
            ? PhysicalPersonIdentity::DOCUMENT_JMB
            : ($passport ? PhysicalPersonIdentity::DOCUMENT_PASSPORT : $fl->idDocumentType);

        $businessName = $fl->entrepreneurBusinessName;
        $pib = $fl->pib;
        $crpsNumber = $fl->crpsNumber;

        if ($isEntrepreneur) {
            if ($request->exists('entrepreneur_business_name') || $request->exists('company_name')) {
                $incomingName = $request->filled('entrepreneur_business_name')
                    ? $request->input('entrepreneur_business_name')
                    : $request->input('company_name');
                $businessName = is_string($incomingName) && trim($incomingName) !== ''
                    ? trim($incomingName)
                    : $fl->entrepreneurBusinessName;
            }
            if ($request->exists('pib')) {
                $incomingPib = $request->input('pib');
                $pib = is_string($incomingPib) && $incomingPib !== '' ? $incomingPib : $fl->pib;
            }
            if ($request->exists('crps_number')) {
                $incomingCrps = $request->input('crps_number');
                $crpsNumber = is_string($incomingCrps) && $incomingCrps !== '' ? $incomingCrps : $fl->crpsNumber;
            }
        }

        return new IdentitySnapshot(
            userId: (int) $user->id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            mobilePhone: $request->input('phone'),
            streetAndNumber: (string) $request->input('address'),
            city: (string) $request->input('city'),
            physicalPerson: new PhysicalPersonSnapshot(
                firstName: (string) $request->input('first_name'),
                lastName: (string) $request->input('last_name'),
                residentialStatus: $canonicalResidential,
                streetAndNumber: (string) $request->input('address'),
                city: (string) $request->input('city'),
                idDocumentType: $documentType,
                jmb: $jmb,
                passportNumber: $passport,
                residenceCountryCode: $fl->residenceCountryCode,
                isEntrepreneur: $isEntrepreneur,
                entrepreneurBusinessName: $businessName,
                pib: $pib,
                crpsNumber: $crpsNumber,
            ),
        );
    }

    private function overlayLegal(
        User $user,
        IdentitySnapshot $current,
        Request $request,
        string $incomingType,
    ): IdentitySnapshot {
        $pl = $current->legalEntity;
        if ($pl === null || $pl->authorizedPerson === null) {
            throw new IdentityUseGateException(IdentityAccess::INVALID, 'Identitet nije validan i mora se ispraviti (D14).');
        }

        $person = $pl->authorizedPerson;

        return new IdentitySnapshot(
            userId: (int) $user->id,
            isRegisteredSubject: true,
            subjectType: PlatformIdentity::SUBJECT_LEGAL_ENTITY,
            mobilePhone: $request->input('phone'),
            streetAndNumber: (string) $request->input('address'),
            city: (string) $request->input('city'),
            legalEntity: new LegalEntitySnapshot(
                legalForm: $this->registrationMapper->legalForm($incomingType),
                legalName: $request->filled('company_name')
                    ? trim((string) $request->input('company_name'))
                    : $pl->legalName,
                streetAndNumber: (string) $request->input('address'),
                city: (string) $request->input('city'),
                authorizedPerson: new PersonInRoleSnapshot(
                    firstName: (string) $request->input('first_name'),
                    lastName: (string) $request->input('last_name'),
                    idDocumentType: $person->idDocumentType,
                    jmb: $person->jmb,
                    passportNumber: $person->passportNumber,
                    passportIssuingCountryCode: $person->passportIssuingCountryCode,
                ),
                pib: $request->has('pib') ? ($request->input('pib') ?: null) : $pl->pib,
                crpsNumber: $pl->crpsNumber,
            ),
        );
    }

    public function snapshotForAdminContact(User $user, string $firstName, string $lastName, ?string $phone): IdentitySnapshot
    {
        try {
            $current = $this->reader->forUser($user);
        } catch (CanonicalIdentityReadException $e) {
            throw new IdentityUseGateException(
                IdentityAccess::ACCOUNT_ONLY,
                'Identitetska polja nijesu dostupna za ovaj nalog.'
            );
        }

        if ($current->physicalPerson !== null) {
            $fl = $current->physicalPerson;

            return new IdentitySnapshot(
                userId: (int) $user->id,
                isRegisteredSubject: $current->isRegisteredSubject,
                subjectType: PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
                mobilePhone: $phone,
                streetAndNumber: $current->streetAndNumber,
                city: $current->city,
                physicalPerson: new PhysicalPersonSnapshot(
                    firstName: $firstName,
                    lastName: $lastName,
                    residentialStatus: $fl->residentialStatus,
                    streetAndNumber: $fl->streetAndNumber,
                    city: $fl->city,
                    idDocumentType: $fl->idDocumentType,
                    jmb: $fl->jmb,
                    passportNumber: $fl->passportNumber,
                    residenceCountryCode: $fl->residenceCountryCode,
                    isEntrepreneur: $fl->isEntrepreneur,
                    entrepreneurBusinessName: $fl->entrepreneurBusinessName,
                    pib: $fl->pib,
                    crpsNumber: $fl->crpsNumber,
                ),
            );
        }

        if ($current->legalEntity !== null && $current->legalEntity->authorizedPerson !== null) {
            $pl = $current->legalEntity;
            $person = $pl->authorizedPerson;

            return new IdentitySnapshot(
                userId: (int) $user->id,
                isRegisteredSubject: $current->isRegisteredSubject,
                subjectType: PlatformIdentity::SUBJECT_LEGAL_ENTITY,
                mobilePhone: $phone,
                streetAndNumber: $current->streetAndNumber,
                city: $current->city,
                legalEntity: new LegalEntitySnapshot(
                    legalForm: $pl->legalForm,
                    legalName: $pl->legalName,
                    streetAndNumber: $pl->streetAndNumber,
                    city: $pl->city,
                    authorizedPerson: new PersonInRoleSnapshot(
                        firstName: $firstName,
                        lastName: $lastName,
                        idDocumentType: $person->idDocumentType,
                        jmb: $person->jmb,
                        passportNumber: $person->passportNumber,
                        passportIssuingCountryCode: $person->passportIssuingCountryCode,
                    ),
                    pib: $pl->pib,
                    crpsNumber: $pl->crpsNumber,
                ),
            );
        }

        throw new IdentityMutationDeniedException(
            'Ova grana identiteta ne može se mijenjati sa administratorske forme.'
        );
    }

    private function requestWantsEntrepreneur(Request $request, string $incomingType): bool
    {
        if ($request->exists('registers_as_entrepreneur')) {
            return $this->affirmativeEntrepreneurChoice($request->input('registers_as_entrepreneur'));
        }

        return $incomingType === UserType::ENTREPRENEUR;
    }

    private function affirmativeEntrepreneurChoice(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (! is_string($value)) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['1', 'da', 'yes', 'true'], true);
    }
}
