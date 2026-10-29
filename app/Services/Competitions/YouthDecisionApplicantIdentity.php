<?php

namespace App\Services\Competitions;

use App\Models\Application;

/**
 * Identitet podnosioca u PM Odluci. Ne koristi ženski tekst odluke i ne dopisuje podatke kojih nema.
 */
class YouthDecisionApplicantIdentity
{
    public function line(Application $application): ?string
    {
        $this->loadLegalIdentity($application);

        if ($application->applicant_type === 'fizicko_lice') {
            return $this->clean($application->physical_person_name);
        }

        if (in_array($application->applicant_type, ['preduzetnik', 'preduzetnica'], true)) {
            return $this->clean($application->preduzetnik_name);
        }

        if (! in_array($application->applicant_type, ['doo', 'ostalo', 'privredno_drustvo'], true)) {
            return null;
        }

        if (! $application->is_registered) {
            return null;
        }

        $legalName = $this->clean($application->user?->identity?->legalEntity?->legal_name);
        $founder = $this->clean($application->founder_name);
        $director = $this->clean($application->director_name);
        if ($legalName === null || $founder === null || $director === null) {
            return null;
        }

        $samePerson = mb_strtolower($founder) === mb_strtolower($director);
        if ($samePerson) {
            return $legalName.', koga zastupa osnivač/ica i izvršni direktor/ica '.$founder;
        }

        return $legalName.', koga zastupa osnivač/ica '.$founder.' i izvršni direktor/ica '.$director;
    }

    private function loadLegalIdentity(Application $application): void
    {
        if (! $application->exists) {
            return;
        }

        $application->loadMissing(['user.identity.legalEntity']);
    }

    private function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $name = trim($value);

        return $name === '' ? null : $name;
    }
}
