<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\User;

/**
 * Identitet podnosioca u PM Odluci. Ne koristi ženski tekst odluke i ne dopisuje podatke kojih nema.
 */
class YouthDecisionApplicantIdentity
{
    public const COMPANY_NAME_AS_FOUNDER = 'Unesite ime i prezime fizičkog lica koje je osnivač. Naziv privrednog društva nije ime osnivača.';

    public const COMPANY_NAME_AS_DIRECTOR = 'Unesite ime i prezime fizičkog lica koje je izvršni direktor. Naziv privrednog društva nije ime izvršnog direktora.';

    public const REGISTERED_PERSON_NAME_MISSING = 'Profil registrovanog korisnika nema ime i prezime fizičkog lica. Unesite ime drugog fizičkog lica ili dopunite profil ovlašćenog lica.';

    public function line(Application $application): ?string
    {
        $this->loadLegalIdentity($application);

        if ($application->applicant_type === 'fizicko_lice') {
            return $this->savedNaturalPersonName($application, $application->physical_person_name);
        }

        if (in_array($application->applicant_type, ['preduzetnik', 'preduzetnica'], true)) {
            return $this->savedNaturalPersonName($application, $application->preduzetnik_name);
        }

        if (! in_array($application->applicant_type, ['doo', 'ostalo', 'privredno_drustvo'], true)) {
            return null;
        }

        if (! $application->is_registered) {
            return $this->savedNaturalPersonName($application, $application->doo_name);
        }

        $legalName = $this->clean($application->user?->identity?->legalEntity?->legal_name);
        $founder = $this->acceptablePersonName($application->founder_name, $application);
        $director = $this->acceptablePersonName($application->director_name, $application);
        if ($legalName !== null && $founder !== null && $director !== null) {
            $samePerson = mb_strtolower($founder) === mb_strtolower($director);
            if ($samePerson) {
                return $legalName.', koga zastupa osnivač/ica i izvršni direktor/ica '.$founder;
            }

            return $legalName.', koga zastupa osnivač/ica '.$founder.' i izvršni direktor/ica '.$director;
        }

        return $founder
            ?? $director
            ?? $this->savedNaturalPersonName($application, $application->doo_name);
    }

    public function companyNameUsedAsOfficer(?string $officer, ?string $companyName): bool
    {
        $officerName = $this->normalizeComparableName($officer);
        $company = $this->normalizeComparableName($companyName);

        return $officerName !== null && $company !== null && $officerName === $company;
    }

    public function companyOfficerConflictMessage(Application $application): ?string
    {
        $this->loadLegalIdentity($application);
        if (! $application->is_registered || ! in_array($application->applicant_type, ['doo', 'ostalo', 'privredno_drustvo'], true)) {
            return null;
        }

        $legalName = $this->clean($application->user?->identity?->legalEntity?->legal_name);
        $messages = [];
        if ($this->companyNameUsedAsOfficer($application->founder_name, $legalName)) {
            $messages[] = self::COMPANY_NAME_AS_FOUNDER;
        }
        if ($this->companyNameUsedAsOfficer($application->director_name, $legalName)) {
            $messages[] = self::COMPANY_NAME_AS_DIRECTOR;
        }

        return $messages === [] ? null : implode(' ', $messages);
    }

    public function registeredNaturalPersonName(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        $user->loadMissing('identity.legalEntity.authorizedPerson');
        $person = $user->identity?->legalEntity?->authorizedPerson;
        $name = $this->clean(trim((string) ($person?->first_name ?? '').' '.(string) ($person?->last_name ?? '')));
        if ($name === null) {
            return null;
        }

        $legalName = $user->identity?->legalEntity?->legal_name;
        if ($this->companyNameUsedAsOfficer($name, $legalName)) {
            return null;
        }

        return $name;
    }

    private function savedNaturalPersonName(Application $application, mixed $formValue): ?string
    {
        return $this->acceptablePersonName($formValue, $application)
            ?? $this->acceptablePersonName($this->canonicalNaturalPersonName($application), $application);
    }

    private function acceptablePersonName(mixed $value, Application $application): ?string
    {
        $name = $this->clean($value);
        if ($name === null) {
            return null;
        }

        $legalName = $this->clean($application->user?->identity?->legalEntity?->legal_name);
        $plan = $this->clean($application->business_plan_name);
        if ($this->companyNameUsedAsOfficer($name, $legalName) || $this->companyNameUsedAsOfficer($name, $plan)) {
            return null;
        }

        return $name;
    }

    private function canonicalNaturalPersonName(Application $application): ?string
    {
        $user = $application->user;
        if (! $user instanceof User) {
            return null;
        }

        if ($user->exists) {
            $user->loadMissing(['identity.physicalPerson', 'identity.legalEntity.authorizedPerson']);
        } elseif (! $user->relationLoaded('identity')) {
            return null;
        }

        $identity = $user->identity;
        if ($identity === null) {
            return null;
        }

        if ($identity->exists && ! $identity->relationLoaded('physicalPerson')) {
            $identity->loadMissing('physicalPerson');
        }

        if ($identity->relationLoaded('physicalPerson') || $identity->exists) {
            $person = $identity->physicalPerson;
            if ($person !== null) {
                $name = $this->clean(trim((string) ($person->first_name ?? '').' '.(string) ($person->last_name ?? '')));
                if ($name !== null) {
                    return $name;
                }
            }
        }

        $legalEntity = $identity->relationLoaded('legalEntity') ? $identity->legalEntity : null;
        if (! $user->exists && ($legalEntity === null || ! $legalEntity->relationLoaded('authorizedPerson'))) {
            return null;
        }

        return $this->registeredNaturalPersonName($user);
    }

    private function loadLegalIdentity(Application $application): void
    {
        if (! $application->exists) {
            return;
        }

        $application->loadMissing(['user.identity.legalEntity.authorizedPerson', 'user.identity.physicalPerson']);
    }

    private function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $name = trim($value);

        return $name === '' ? null : $name;
    }

    private function normalizeComparableName(mixed $value): ?string
    {
        $cleaned = $this->clean($value);
        if ($cleaned === null) {
            return null;
        }

        $collapsed = preg_replace('/\s+/u', ' ', $cleaned);

        return mb_strtolower(is_string($collapsed) ? $collapsed : $cleaned);
    }
}
