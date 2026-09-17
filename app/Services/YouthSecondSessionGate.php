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

        $first = $competition->firstCommissionSession();
        if ($first === null || ! $first->isConfirmed()) {
            return CommissionProfileConfig::SESSION_SECOND_REQUIRES_FIRST_MESSAGE;
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

        if ($secondSession !== null) {
            if ($secondSession->session_type !== CommissionSession::TYPE_SECOND) {
                return CommissionProfileConfig::ORAL_NOT_ELIGIBLE_MESSAGE;
            }
            if ((int) $secondSession->competition_id !== (int) $application->competition_id) {
                return CommissionProfileConfig::ORAL_NOT_ELIGIBLE_MESSAGE;
            }
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

    public function competitionHasUnresolvedPrigovor(Competition $competition): bool
    {
        return ApplicationPrigovor::query()
            ->where('status', ApplicationPrigovor::STATUS_PODNESEN)
            ->whereHas('application', fn ($query) => $query->where('competition_id', $competition->id))
            ->exists();
    }
}
