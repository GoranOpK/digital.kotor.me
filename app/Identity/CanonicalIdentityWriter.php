<?php

namespace App\Identity;

use App\Identity\Validation\CrpsIdentifierValidator;
use App\Identity\Validation\JmbIdentifierValidator;
use App\Identity\Validation\PibIdentifierValidator;
use App\Models\ForeignBranchIdentity;
use App\Models\ForeignBranchRepresentative;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Canonical identity writer. Not rollout-gated. If invoked, it persists.
 * Step 2 runtime never invokes it.
 * createForUser() preserves Step 4 create-only semantics.
 * updatePhysicalPersonGraph() is the narrow Step 7 same-branch FL updater.
 */
final class CanonicalIdentityWriter
{
    /**
     * @var list<string>
     */
    private const LEGAL_FORMS = [
        LegalEntityIdentity::FORM_OD,
        LegalEntityIdentity::FORM_KD,
        LegalEntityIdentity::FORM_AD,
        LegalEntityIdentity::FORM_DOO,
        LegalEntityIdentity::FORM_NVO_ASSOCIATION,
        LegalEntityIdentity::FORM_NVO_FOUNDATION,
        LegalEntityIdentity::FORM_SPORTS_ORGANIZATION,
    ];

    /**
     * @var list<string>
     */
    private const DOCUMENT_TYPES = [
        PhysicalPersonIdentity::DOCUMENT_JMB,
        PhysicalPersonIdentity::DOCUMENT_PASSPORT,
    ];

    public function __construct(
        private readonly JmbIdentifierValidator $jmbValidator = new JmbIdentifierValidator,
        private readonly PibIdentifierValidator $pibValidator = new PibIdentifierValidator,
        private readonly CrpsIdentifierValidator $crpsValidator = new CrpsIdentifierValidator,
    ) {
    }

    public function createForUser(User $user, IdentitySnapshot $snapshot): PlatformIdentity
    {
        $this->assertCreateable($user, $snapshot);

        try {
            return DB::transaction(function () use ($user, $snapshot) {
                if (PlatformIdentity::query()->where('user_id', $user->id)->exists()) {
                    throw new CanonicalIdentityWriteException('Platform identity already exists for this user.');
                }

                $platform = PlatformIdentity::query()->create([
                    'user_id' => $user->id,
                    'subject_type' => $snapshot->subjectType,
                    'mobile_phone' => $snapshot->mobilePhone,
                ]);

                match ($snapshot->subjectType) {
                    PlatformIdentity::SUBJECT_PHYSICAL_PERSON => $this->insertPhysicalPerson($platform, $snapshot),
                    PlatformIdentity::SUBJECT_LEGAL_ENTITY => $this->insertLegalEntity($platform, $snapshot),
                    PlatformIdentity::SUBJECT_FOREIGN_BRANCH => $this->insertForeignBranch($platform, $snapshot),
                    default => throw new CanonicalIdentityWriteException('Unsupported subject type.'),
                };

                return $platform->refresh();
            });
        } catch (CanonicalIdentityWriteException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new CanonicalIdentityWriteException('Canonical identity create failed.', 0, $e);
        }
    }

    /**
     * Additive Step 7 same-branch FL in-place update. Not a general updater.
     * Does not delete/recreate the graph. Does not mutate users.
     */
    public function updatePhysicalPersonGraph(User $user, IdentitySnapshot $snapshot): PlatformIdentity
    {
        $this->assertSameBranchFlUpdateSnapshot($user, $snapshot);

        try {
            return DB::transaction(function () use ($user, $snapshot) {
                $platform = PlatformIdentity::query()
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if ($platform === null) {
                    throw new CanonicalIdentityWriteException('Platform identity does not exist for this user.');
                }

                $this->assertPersistedSameBranchFlGraph($platform);

                $fl = PhysicalPersonIdentity::query()
                    ->where('platform_identity_id', $platform->id)
                    ->lockForUpdate()
                    ->first();

                if ($fl === null) {
                    throw new CanonicalIdentityWriteException('Physical person identity does not exist for this platform.');
                }

                $person = $snapshot->physicalPerson;
                $platform->mobile_phone = $snapshot->mobilePhone;
                $platform->save();

                $fl->first_name = $person->firstName;
                $fl->last_name = $person->lastName;
                $fl->residential_status = $person->residentialStatus;
                $fl->id_document_type = $person->idDocumentType;
                $fl->jmb = $person->jmb;
                $fl->passport_number = $person->passportNumber;
                $fl->residence_country_code = $person->residenceCountryCode;
                $fl->is_entrepreneur = $person->isEntrepreneur;
                $fl->entrepreneur_business_name = $person->entrepreneurBusinessName;
                $fl->pib = $person->pib;
                $fl->crps_number = $person->crpsNumber;
                $fl->street_and_number = $person->streetAndNumber;
                $fl->city = $person->city;
                $fl->save();

                return $platform->refresh();
            });
        } catch (CanonicalIdentityWriteException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new CanonicalIdentityWriteException('Canonical identity update failed.', 0, $e);
        }
    }

    private function assertSameBranchFlUpdateSnapshot(User $user, IdentitySnapshot $snapshot): void
    {
        if ($snapshot->userId !== $user->id) {
            throw new CanonicalIdentityWriteException('Snapshot user id does not match the target user.');
        }

        if (! $snapshot->isRegisteredSubject) {
            throw new CanonicalIdentityWriteException('Canonical update requires a registered subject.');
        }

        if ($snapshot->legacyFacts !== null) {
            throw new CanonicalIdentityWriteException('Canonical update does not accept legacy facts.');
        }

        if ($snapshot->subjectType !== PlatformIdentity::SUBJECT_PHYSICAL_PERSON) {
            throw new CanonicalIdentityWriteException('Canonical update requires the physical person subject type.');
        }

        if ($snapshot->legalEntity !== null || $snapshot->foreignBranch !== null) {
            throw new CanonicalIdentityWriteException('Canonical update refuses non-FL branches.');
        }

        $this->assertPhysicalPerson($snapshot);

        $fl = $snapshot->physicalPerson;
        if ($fl->isEntrepreneur) {
            throw new CanonicalIdentityWriteException('Canonical update refuses entrepreneur graphs.');
        }

        if ($fl->residentialStatus !== PhysicalPersonIdentity::RESIDENTIAL_RESIDENT) {
            throw new CanonicalIdentityWriteException('Canonical update requires resident status.');
        }

        if ($fl->idDocumentType !== PhysicalPersonIdentity::DOCUMENT_JMB) {
            throw new CanonicalIdentityWriteException('Canonical update requires a JMB document.');
        }

        $this->assertOptionalIdentifier($fl->jmb);
        $this->assertOptionalPib($fl->pib);
        $this->assertOptionalCrps($fl->crpsNumber);
        $this->assertOptionalCountry($fl->residenceCountryCode);
    }

    private function assertPersistedSameBranchFlGraph(PlatformIdentity $platform): void
    {
        if ($platform->subject_type !== PlatformIdentity::SUBJECT_PHYSICAL_PERSON) {
            throw new CanonicalIdentityWriteException('Persisted graph is not a physical person branch.');
        }

        $flCount = PhysicalPersonIdentity::query()->where('platform_identity_id', $platform->id)->count();
        $leCount = LegalEntityIdentity::query()->where('platform_identity_id', $platform->id)->count();
        $fbCount = ForeignBranchIdentity::query()->where('platform_identity_id', $platform->id)->count();

        if ($flCount !== 1 || $leCount !== 0 || $fbCount !== 0) {
            throw new CanonicalIdentityWriteException('Persisted graph is not a same-branch Step-4 FL graph.');
        }

        $fl = PhysicalPersonIdentity::query()->where('platform_identity_id', $platform->id)->first();
        if ($fl === null) {
            throw new CanonicalIdentityWriteException('Persisted physical person row is missing.');
        }

        if ($fl->is_entrepreneur === true) {
            throw new CanonicalIdentityWriteException('Persisted graph is not a non-entrepreneur FL branch.');
        }

        if ($fl->residential_status !== PhysicalPersonIdentity::RESIDENTIAL_RESIDENT) {
            throw new CanonicalIdentityWriteException('Persisted graph is not a resident FL branch.');
        }

        if ($fl->id_document_type !== PhysicalPersonIdentity::DOCUMENT_JMB) {
            throw new CanonicalIdentityWriteException('Persisted graph is not a JMB FL branch.');
        }
    }

    private function assertCreateable(User $user, IdentitySnapshot $snapshot): void
    {
        if ($snapshot->userId !== $user->id) {
            throw new CanonicalIdentityWriteException('Snapshot user id does not match the target user.');
        }

        if (! $snapshot->isRegisteredSubject) {
            throw new CanonicalIdentityWriteException('Canonical create requires a registered subject.');
        }

        if ($snapshot->legacyFacts !== null) {
            throw new CanonicalIdentityWriteException('Canonical create does not accept legacy facts.');
        }

        $branches = array_filter([
            $snapshot->physicalPerson,
            $snapshot->legalEntity,
            $snapshot->foreignBranch,
        ]);

        if (count($branches) !== 1) {
            throw new CanonicalIdentityWriteException('Exactly one subject branch is required.');
        }

        if (PlatformIdentity::query()->where('user_id', $user->id)->exists()) {
            throw new CanonicalIdentityWriteException('Platform identity already exists for this user.');
        }

        match ($snapshot->subjectType) {
            PlatformIdentity::SUBJECT_PHYSICAL_PERSON => $this->assertPhysicalPerson($snapshot),
            PlatformIdentity::SUBJECT_LEGAL_ENTITY => $this->assertLegalEntity($snapshot),
            PlatformIdentity::SUBJECT_FOREIGN_BRANCH => $this->assertForeignBranch($snapshot),
            default => throw new CanonicalIdentityWriteException('Unsupported subject type.'),
        };

        $this->assertOptionalIdentifier($snapshot->physicalPerson?->jmb, $snapshot->legalEntity?->authorizedPerson?->jmb, $snapshot->foreignBranch?->representative?->jmb);
        $this->assertOptionalPib($snapshot->physicalPerson?->pib ?? $snapshot->legalEntity?->pib ?? $snapshot->foreignBranch?->pib);
        $this->assertOptionalCrps($snapshot->physicalPerson?->crpsNumber ?? $snapshot->legalEntity?->crpsNumber ?? $snapshot->foreignBranch?->crpsNumber);
        $this->assertOptionalCountry($snapshot->physicalPerson?->residenceCountryCode);
        $this->assertOptionalCountry($snapshot->legalEntity?->authorizedPerson?->passportIssuingCountryCode);
        $this->assertOptionalCountry($snapshot->foreignBranch?->representative?->passportIssuingCountryCode);
    }

    private function assertPhysicalPerson(IdentitySnapshot $snapshot): void
    {
        if ($snapshot->physicalPerson === null || $snapshot->legalEntity !== null || $snapshot->foreignBranch !== null) {
            throw new CanonicalIdentityWriteException('Physical person create requires only the FL branch.');
        }

        $fl = $snapshot->physicalPerson;
        if ($fl->firstName === '' || $fl->lastName === '' || $fl->streetAndNumber === '' || $fl->city === '') {
            throw new CanonicalIdentityWriteException('Physical person required name/address fields are missing.');
        }

        if (! in_array($fl->residentialStatus, [
            PhysicalPersonIdentity::RESIDENTIAL_RESIDENT,
            PhysicalPersonIdentity::RESIDENTIAL_NON_RESIDENT,
        ], true)) {
            throw new CanonicalIdentityWriteException('Invalid canonical residential status.');
        }

        $this->assertDocumentType($fl->idDocumentType);
    }

    private function assertLegalEntity(IdentitySnapshot $snapshot): void
    {
        if ($snapshot->legalEntity === null || $snapshot->physicalPerson !== null || $snapshot->foreignBranch !== null) {
            throw new CanonicalIdentityWriteException('Legal entity create requires only the PL branch.');
        }

        $pl = $snapshot->legalEntity;
        if ($pl->authorizedPerson === null) {
            throw new CanonicalIdentityWriteException('Legal entity create requires exactly one authorized person.');
        }

        if ($pl->legalName === '' || $pl->streetAndNumber === '' || $pl->city === '') {
            throw new CanonicalIdentityWriteException('Legal entity required name/address fields are missing.');
        }

        if (! in_array($pl->legalForm, self::LEGAL_FORMS, true)) {
            throw new CanonicalIdentityWriteException('Unsupported legal form.');
        }

        $this->assertPersonInRole($pl->authorizedPerson);
    }

    private function assertForeignBranch(IdentitySnapshot $snapshot): void
    {
        if ($snapshot->foreignBranch === null || $snapshot->physicalPerson !== null || $snapshot->legalEntity !== null) {
            throw new CanonicalIdentityWriteException('Foreign branch create requires only the DSPD branch.');
        }

        $dspd = $snapshot->foreignBranch;
        if ($dspd->representative === null) {
            throw new CanonicalIdentityWriteException('Foreign branch create requires exactly one representative.');
        }

        if ($dspd->foreignCompanyName === '' || $dspd->branchNameInMontenegro === '' || $dspd->streetAndNumber === '' || $dspd->city === '') {
            throw new CanonicalIdentityWriteException('Foreign branch required name/address fields are missing.');
        }

        $this->assertPersonInRole($dspd->representative);
    }

    private function assertPersonInRole(PersonInRoleSnapshot $person): void
    {
        if ($person->firstName === '' || $person->lastName === '') {
            throw new CanonicalIdentityWriteException('Person-in-role required name fields are missing.');
        }

        $this->assertDocumentType($person->idDocumentType);
    }

    private function assertDocumentType(?string $type): void
    {
        if ($type !== null && ! in_array($type, self::DOCUMENT_TYPES, true)) {
            throw new CanonicalIdentityWriteException('Unsupported id document type.');
        }
    }

    private function assertOptionalIdentifier(?string ...$jmbs): void
    {
        foreach ($jmbs as $jmb) {
            if ($jmb !== null && ! $this->jmbValidator->isValid($jmb)) {
                throw new CanonicalIdentityWriteException('Invalid JMB.');
            }
        }
    }

    private function assertOptionalPib(?string $pib): void
    {
        if ($pib !== null && ! $this->pibValidator->isValid($pib)) {
            throw new CanonicalIdentityWriteException('Invalid PIB.');
        }
    }

    private function assertOptionalCrps(?string $crps): void
    {
        if ($crps !== null && ! $this->crpsValidator->isValid($crps)) {
            throw new CanonicalIdentityWriteException('Invalid CRPS number.');
        }
    }

    private function assertOptionalCountry(?string $code): void
    {
        if ($code !== null && ! CountryCatalog::isValidCode($code)) {
            throw new CanonicalIdentityWriteException('Invalid country code.');
        }
    }

    private function insertPhysicalPerson(PlatformIdentity $platform, IdentitySnapshot $snapshot): void
    {
        $fl = $snapshot->physicalPerson;
        PhysicalPersonIdentity::query()->create([
            'platform_identity_id' => $platform->id,
            'first_name' => $fl->firstName,
            'last_name' => $fl->lastName,
            'residential_status' => $fl->residentialStatus,
            'id_document_type' => $fl->idDocumentType,
            'jmb' => $fl->jmb,
            'passport_number' => $fl->passportNumber,
            'residence_country_code' => $fl->residenceCountryCode,
            'is_entrepreneur' => $fl->isEntrepreneur,
            'entrepreneur_business_name' => $fl->entrepreneurBusinessName,
            'pib' => $fl->pib,
            'crps_number' => $fl->crpsNumber,
            'street_and_number' => $fl->streetAndNumber,
            'city' => $fl->city,
        ]);
    }

    private function insertLegalEntity(PlatformIdentity $platform, IdentitySnapshot $snapshot): void
    {
        $pl = $snapshot->legalEntity;
        $legalEntity = LegalEntityIdentity::query()->create([
            'platform_identity_id' => $platform->id,
            'legal_form' => $pl->legalForm,
            'legal_name' => $pl->legalName,
            'pib' => $pl->pib,
            'crps_number' => $pl->crpsNumber,
            'street_and_number' => $pl->streetAndNumber,
            'city' => $pl->city,
        ]);

        $person = $pl->authorizedPerson;
        LegalEntityAuthorizedPerson::query()->create([
            'legal_entity_identity_id' => $legalEntity->id,
            'first_name' => $person->firstName,
            'last_name' => $person->lastName,
            'id_document_type' => $person->idDocumentType,
            'jmb' => $person->jmb,
            'passport_number' => $person->passportNumber,
            'passport_issuing_country_code' => $person->passportIssuingCountryCode,
        ]);
    }

    private function insertForeignBranch(PlatformIdentity $platform, IdentitySnapshot $snapshot): void
    {
        $dspd = $snapshot->foreignBranch;
        $branch = ForeignBranchIdentity::query()->create([
            'platform_identity_id' => $platform->id,
            'foreign_company_name' => $dspd->foreignCompanyName,
            'branch_name_in_montenegro' => $dspd->branchNameInMontenegro,
            'pib' => $dspd->pib,
            'crps_number' => $dspd->crpsNumber,
            'street_and_number' => $dspd->streetAndNumber,
            'city' => $dspd->city,
        ]);

        $person = $dspd->representative;
        ForeignBranchRepresentative::query()->create([
            'foreign_branch_identity_id' => $branch->id,
            'first_name' => $person->firstName,
            'last_name' => $person->lastName,
            'id_document_type' => $person->idDocumentType,
            'jmb' => $person->jmb,
            'passport_number' => $person->passportNumber,
            'passport_issuing_country_code' => $person->passportIssuingCountryCode,
        ]);
    }
}
