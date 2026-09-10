<?php

namespace App\Identity\Runtime;

use App\Identity\CanonicalIdentityReadException;
use App\Identity\CanonicalIdentityReader;
use App\Identity\IdentityRollout;
use App\Identity\IdentitySnapshot;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Security\JmbEncryptedReadException;
use App\Security\JmbEncryptedReadService;

/**
 * Runtime identity presentation. Legacy-authoritative while canonical_read is OFF.
 * Canonical-first with no stale users.* fallback while canonical_read is ON.
 */
final class CurrentIdentityResolver
{
    public function __construct(
        private readonly IdentityRollout $rollout = new IdentityRollout,
        private readonly CanonicalIdentityReader $reader = new CanonicalIdentityReader,
        private readonly DerivedUserTypeMirror $mirror = new DerivedUserTypeMirror,
    ) {
    }

    public function viewFor(?User $user): SubjectIdentityView
    {
        if ($user === null) {
            return $this->empty(IdentityAccess::MISSING, false);
        }

        if (! $this->rollout->readsCanonical()) {
            return $this->fromLegacyUser($user);
        }

        if ($user->isStaffAccount()) {
            try {
                return $this->fromCanonicalSnapshot($this->reader->forUser($user));
            } catch (JmbEncryptedReadException) {
                return $this->empty(IdentityAccess::INVALID, false);
            } catch (CanonicalIdentityReadException) {
                return $this->empty(IdentityAccess::ACCOUNT_ONLY, false);
            }
        }

        try {
            return $this->fromCanonicalSnapshot($this->reader->forUser($user));
        } catch (JmbEncryptedReadException) {
            return $this->empty(IdentityAccess::INVALID, true);
        } catch (CanonicalIdentityReadException) {
            return $this->empty(IdentityAccess::MISSING, true);
        }
    }

    public function requireCurrentSubject(User $user): SubjectIdentityView
    {
        $view = $this->viewFor($user);

        if ($view->access === IdentityAccess::ACCOUNT_ONLY) {
            throw new IdentityUseGateException(
                IdentityAccess::ACCOUNT_ONLY,
                'Ovaj nalog nema predmetni identitet.'
            );
        }

        if ($view->access === IdentityAccess::INVALID) {
            throw new IdentityUseGateException(
                IdentityAccess::INVALID,
                'Identitet nije validan i mora se ispraviti (D14).'
            );
        }

        if ($view->access !== IdentityAccess::CURRENT) {
            throw new IdentityUseGateException(
                IdentityAccess::MISSING,
                'Dopunite korisnički identitet prije nastavka.'
            );
        }

        return $view;
    }

    public function applicantTypeFor(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        if (! $this->rollout->readsCanonical()) {
            return \App\Support\CompetitionApplicantType::fromUserType($user->user_type ?? null);
        }

        $view = $this->viewFor($user);
        if (! $view->hasCurrentSubjectIdentity()) {
            return null;
        }

        return \App\Support\CompetitionApplicantType::fromUserType($view->userType);
    }

    public function gatesSubjectFlows(): bool
    {
        return $this->rollout->readsCanonical();
    }

    private function fromLegacyUser(User $user): SubjectIdentityView
    {
        $isSubject = ! $user->isStaffAccount() && $user->user_type !== null;

        try {
            $jmb = app(JmbEncryptedReadService::class)->readValue(
                $user->jmb_encrypted,
                $user->jmb,
                'users',
                $user->id,
                'jmb/jmb_encrypted',
            );
        } catch (JmbEncryptedReadException) {
            return $this->empty(IdentityAccess::INVALID, $isSubject);
        }

        return new SubjectIdentityView(
            access: $isSubject || $user->user_type !== null ? IdentityAccess::CURRENT : (
                $user->isStaffAccount() ? IdentityAccess::ACCOUNT_ONLY : IdentityAccess::MISSING
            ),
            isRegisteredSubject: $isSubject,
            userType: $user->user_type,
            residentialStatus: $user->residential_status,
            firstName: $user->first_name,
            lastName: $user->last_name,
            phone: $user->phone,
            address: $user->address,
            city: $user->city,
            jmb: $jmb,
            pib: $user->pib,
            companyName: $user->company_name,
            passportNumber: $user->passport_number,
            crpsNumber: null,
        );
    }

    private function fromCanonicalSnapshot(IdentitySnapshot $snapshot): SubjectIdentityView
    {
        $userType = $this->mirror->representableType($snapshot);
        $residential = null;
        $firstName = null;
        $lastName = null;
        $jmb = null;
        $pib = null;
        $company = null;
        $passport = null;
        $crpsNumber = null;
        $address = $snapshot->streetAndNumber;
        $city = $snapshot->city;

        if ($snapshot->physicalPerson !== null) {
            $fl = $snapshot->physicalPerson;
            $firstName = $fl->firstName;
            $lastName = $fl->lastName;
            $jmb = $fl->jmb;
            $pib = $fl->pib;
            $company = $fl->entrepreneurBusinessName;
            $passport = $fl->passportNumber;
            $crpsNumber = $fl->crpsNumber;
            $residential = $fl->residentialStatus === PhysicalPersonIdentity::RESIDENTIAL_NON_RESIDENT
                ? 'non-resident'
                : ($fl->residentialStatus === PhysicalPersonIdentity::RESIDENTIAL_RESIDENT ? 'resident' : $fl->residentialStatus);
        } elseif ($snapshot->legalEntity !== null) {
            $pl = $snapshot->legalEntity;
            $firstName = $pl->authorizedPerson?->firstName;
            $lastName = $pl->authorizedPerson?->lastName;
            $jmb = $pl->authorizedPerson?->jmb;
            $pib = $pl->pib;
            $company = $pl->legalName;
            $passport = $pl->authorizedPerson?->passportNumber;
            $crpsNumber = $pl->crpsNumber;
        } elseif ($snapshot->foreignBranch !== null) {
            $fb = $snapshot->foreignBranch;
            $firstName = $fb->representative?->firstName;
            $lastName = $fb->representative?->lastName;
            $jmb = $fb->representative?->jmb;
            $pib = $fb->pib;
            $company = $fb->foreignCompanyName;
            $passport = $fb->representative?->passportNumber;
            $crpsNumber = $fb->crpsNumber;
        }

        return new SubjectIdentityView(
            access: IdentityAccess::CURRENT,
            isRegisteredSubject: $snapshot->isRegisteredSubject,
            userType: $userType,
            residentialStatus: $residential,
            firstName: $firstName,
            lastName: $lastName,
            phone: $snapshot->mobilePhone,
            address: $address,
            city: $city,
            jmb: $jmb,
            pib: $pib,
            companyName: $company,
            passportNumber: $passport,
            crpsNumber: $crpsNumber,
        );
    }

    private function empty(string $access, bool $isRegisteredSubject): SubjectIdentityView
    {
        return new SubjectIdentityView(
            access: $access,
            isRegisteredSubject: $isRegisteredSubject,
            userType: null,
            residentialStatus: null,
            firstName: null,
            lastName: null,
            phone: null,
            address: null,
            city: null,
            jmb: null,
            pib: null,
            companyName: null,
            passportNumber: null,
            crpsNumber: null,
        );
    }
}
