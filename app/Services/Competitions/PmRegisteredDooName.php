<?php

namespace App\Services\Competitions;

use App\Identity\CanonicalIdentityReader;
use App\Identity\CanonicalIdentityReadException;
use App\Models\Application;
use App\Models\ForeignBranchIdentity;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Security\JmbEncryptedReadException;
use App\Support\KnCommercialCompanyForm;
use Illuminate\Validation\ValidationException;

final class PmRegisteredDooName
{
    public function isRegisteredDoo(Application $application): bool
    {
        if (! $application->is_registered || ! in_array($application->applicant_type, ['doo', 'privredno_drustvo', 'ostalo'], true)) {
            return false;
        }
        $code = $application->company_legal_form
            ?? KnCommercialCompanyForm::fromRegistrationForm($application->registration_form)
            ?? ($application->applicant_type === 'doo' ? 'doo' : null);
        return $code === KnCommercialCompanyForm::DOO;
    }

    public function historicalName(Application $application): ?string
    {
        $value = $application->businessPlan?->company_name;
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /** Preview only. FINAL always re-reads and locks the canonical aggregate. */
    public function comparisonName(Application $application): ?string
    {
        try {
            return $this->canonicalName($application, false);
        } catch (CanonicalIdentityReadException|JmbEncryptedReadException) {
            return null;
        }
    }

    public function assertMatchesForSubmission(Application $application): void
    {
        if (! $application->isOmladinskoProfile() || ! $this->isRegisteredDoo($application)) {
            return;
        }
        if ($application->getConnection()->transactionLevel() === 0) {
            throw new \LogicException('PM name comparison must run inside the FINAL transaction.');
        }
        try {
            $canonical = $this->canonicalName($application, true);
        } catch (CanonicalIdentityReadException|JmbEncryptedReadException) {
            throw ValidationException::withMessages(['company_name' => 'Naziv firme iz profila nije dostupan ili identitet nije citljiv. Provjerite profil prije predaje.']);
        }
        $saved = $this->historicalName($application);
        if ($saved === null) {
            throw ValidationException::withMessages(['company_name' => 'Unesite naziv privrednog drustva u M2, tacka 4. Naziv iz profila: '.$canonical.'.']);
        }
        // Legal names are compared exactly after trimming boundary whitespace only.
        if ($saved !== $canonical) {
            throw ValidationException::withMessages(['company_name' => 'Nazivi firme se ne podudaraju. M2: "'.$saved.'"; profil: "'.$canonical.'". Uskladite podatak u M2 ili profilu prije predaje.']);
        }
    }

    private function canonicalName(Application $application, bool $lock): string
    {
        if (! $lock) {
            $user = User::query()->find($application->user_id);
            if (! $user) throw new CanonicalIdentityReadException('Account is missing.');
            $snapshot = app(CanonicalIdentityReader::class)->forUser($user);
        } else {
            // Match canonical writers' lock order: account, platform, branch, person.
            if (! User::query()->whereKey($application->user_id)->lockForUpdate()->first()) {
                throw new CanonicalIdentityReadException('Account is missing.');
            }
            $platform = PlatformIdentity::query()->where('user_id', $application->user_id)->lockForUpdate()->first();
            if (! $platform) throw new CanonicalIdentityReadException('Canonical identity is missing.');
            $legal = LegalEntityIdentity::query()->where('platform_identity_id', $platform->id)->lockForUpdate()->first();
            if ($legal) {
                $legal->setRelation('authorizedPerson', LegalEntityAuthorizedPerson::query()->where('legal_entity_identity_id', $legal->id)->lockForUpdate()->first());
            }
            $platform->setRelation('legalEntity', $legal);
            $platform->setRelation('physicalPerson', PhysicalPersonIdentity::query()->where('platform_identity_id', $platform->id)->lockForUpdate()->first());
            $platform->setRelation('foreignBranch', ForeignBranchIdentity::query()->where('platform_identity_id', $platform->id)->lockForUpdate()->first());
            $snapshot = app(CanonicalIdentityReader::class)->fromPlatformIdentity($platform);
        }
        $legal = $snapshot->legalEntity;
        if (! $legal || $legal->legalForm !== LegalEntityIdentity::FORM_DOO || trim($legal->legalName) === '') {
            throw new CanonicalIdentityReadException('A readable DOO legal name is required.');
        }
        return trim($legal->legalName);
    }
}
