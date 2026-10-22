<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationPrigovor;
use App\Models\CommissionSession;
use App\Models\Competition;
use App\Support\CommissionProfileConfig;
use Illuminate\Support\Collection;

class YouthSecondSessionGate
{
    public function secondSessionMayStart(Competition $competition): bool
    {
        return $this->secondSessionBlockMessage($competition) === null;
    }

    public function secondSessionBlockMessage(Competition $competition): ?string
    {
        if (! $competition->isOmladinskoProfile()) {
            return CommissionProfileConfig::SESSION_SECOND_NOT_YOUTH_MESSAGE;
        }

        if (! $competition->hasCompleteValidCommission()) {
            return CommissionProfileConfig::SESSION_SECOND_INCOMPLETE_COMMISSION_MESSAGE;
        }

        $applications = $competition->applications()
            ->with(['eliminatoryNotice', 'prigovor'])
            ->get();

        foreach ($applications as $application) {
            if ($application->status === 'submitted' && $this->appealWindowIsOpen($application)) {
                return CommissionProfileConfig::SESSION_SECOND_APPEAL_GATE_MESSAGE;
            }
            if ($application->prigovor?->isPodnesen() === true) {
                return CommissionProfileConfig::SESSION_SECOND_APPEAL_GATE_MESSAGE;
            }
        }

        return null;
    }

    public function oralPresentationIsAllowed(Application $application, ?CommissionSession $secondSession = null): bool
    {
        return $this->oralPresentationBlockMessage($application, $secondSession) === null;
    }

    public function oralPresentationBlockMessage(Application $application, ?CommissionSession $secondSession = null): ?string
    {
        $application->loadMissing(['competition', 'eliminatoryCheck', 'eliminatoryNotice', 'prigovor']);

        if (! $application->competition?->isOmladinskoProfile()) {
            return CommissionProfileConfig::SESSION_SECOND_NOT_YOUTH_MESSAGE;
        }

        $phaseMessage = $this->secondSessionBlockMessage($application->competition);
        if ($phaseMessage !== null) {
            return $phaseMessage === CommissionProfileConfig::SESSION_SECOND_NOT_YOUTH_MESSAGE
                ? $phaseMessage
                : CommissionProfileConfig::ORAL_NOT_ELIGIBLE_MESSAGE;
        }

        if ($application->status !== 'submitted') {
            return CommissionProfileConfig::ORAL_NOT_ELIGIBLE_MESSAGE;
        }

        if ($application->eliminatoryCheck?->isConfirmed() !== true) {
            return CommissionProfileConfig::ORAL_NOT_ELIGIBLE_MESSAGE;
        }

        if ($this->hasFinalRemainingReason($application)) {
            return CommissionProfileConfig::ORAL_NOT_ELIGIBLE_MESSAGE;
        }

        if ($this->appealWindowIsOpen($application)) {
            return CommissionProfileConfig::ORAL_NOT_ELIGIBLE_MESSAGE;
        }

        if ($application->prigovor?->isPodnesen() === true) {
            return CommissionProfileConfig::ORAL_NOT_ELIGIBLE_MESSAGE;
        }

        $passPath = $application->eliminatoryCheck->isConfirmedPass();
        $liftPath = $application->prigovor?->liftsEliminatoryBar() === true;
        if (! $passPath && ! $liftPath) {
            return CommissionProfileConfig::ORAL_NOT_ELIGIBLE_MESSAGE;
        }

        return null;
    }

    /**
     * @return Collection<int, Application>
     */
    public function eligibleOralApplications(Competition $competition, ?CommissionSession $secondSession = null): Collection
    {
        return $competition->applications()
            ->where('status', 'submitted')
            ->with(['eliminatoryCheck', 'eliminatoryNotice', 'prigovor', 'oralPresentation'])
            ->orderBy('id')
            ->get()
            ->filter(fn (Application $application) => $this->oralPresentationIsAllowed($application, $secondSession))
            ->values();
    }

    public function appealWindowIsOpen(Application $application): bool
    {
        $application->loadMissing('eliminatoryNotice');

        return $application->eliminatoryNotice?->prigovorWindowIsOpen() === true;
    }

    public function hasFinalRemainingReason(Application $application): bool
    {
        $application->loadMissing(['eliminatoryCheck', 'prigovor']);

        if ($application->eliminatoryCheck?->isConfirmedPass() === true) {
            return false;
        }

        if ($application->prigovor?->liftsEliminatoryBar() === true) {
            return false;
        }

        return $application->eliminatoryCheck?->isConfirmedFail() === true;
    }

    public function youthDraftScoringIsAllowed(Application $application): bool
    {
        return $this->youthDraftScoringBlockMessage($application) === null;
    }

    /**
     * Omladinsko bodovanje se otvara po prijavi, po istom principu kao ženski tok:
     * M3 potvrđen bez aktivnog eliminatornog razloga, ili konačna odluka po prigovoru
     * koja otkloni sve razloge. Otvoren rok ili neriješen prigovor na drugoj prijavi
     * ne blokira ovu prijavu. Usmeno i rangiranje ostaju konkursne kapije.
     */
    public function youthDraftScoringBlockMessage(Application $application): ?string
    {
        $application->loadMissing(['competition', 'eliminatoryCheck', 'prigovor']);

        if (! $application->competition?->isOmladinskoProfile()) {
            return CommissionProfileConfig::SESSION_SECOND_NOT_YOUTH_MESSAGE;
        }

        if (! in_array($application->status, ['submitted', 'evaluated'], true)) {
            return \App\Support\ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE;
        }

        if ($application->eliminatoryCheck?->isConfirmed() !== true) {
            return \App\Support\ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE;
        }

        if ($this->hasFinalRemainingReason($application)) {
            return \App\Support\ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE;
        }

        $passPath = $application->eliminatoryCheck->isConfirmedPass();
        $liftPath = $application->prigovor?->liftsEliminatoryBar() === true;
        if (! $passPath && ! $liftPath) {
            return \App\Support\ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE;
        }

        if (! $application->competition->hasCompleteValidCommission()) {
            return \App\Support\ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE;
        }

        return null;
    }

    public function youthLockEvidenceIsComplete(Application $application): bool
    {
        return $this->youthLockEvidenceBlockMessage($application) === null;
    }

    public function youthLockEvidenceBlockMessage(Application $application): ?string
    {
        $application->loadMissing(['competition', 'oralPresentation']);

        $oral = $application->oralPresentation;
        if ($oral === null || ! $oral->isCompleted() || $oral->applicant_attended === null) {
            return \App\Support\ScoringProfileConfig::YOUTH_ORAL_REQUIRED_FOR_LOCK_MESSAGE;
        }

        return null;
    }

    public function competitionHasUnresolvedPrigovor(Competition $competition): bool
    {
        return ApplicationPrigovor::query()
            ->where('status', ApplicationPrigovor::STATUS_PODNESEN)
            ->whereHas('application', fn ($query) => $query->where('competition_id', $competition->id))
            ->exists();
    }
}
