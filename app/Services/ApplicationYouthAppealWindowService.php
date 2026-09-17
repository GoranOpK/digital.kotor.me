<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\ApplicationEliminatoryNotice;
use App\Models\ApplicationPrigovor;
use Illuminate\Support\Facades\DB;

/**
 * Omladinski istek roka za prigovor bez podnesenog prigovora → rejected.
 * Idempotentno. Bez schedulera. Ženski tok se ne dira.
 *
 * Redoslijed katanaca: applications → notice → M3 check → prigovor → mutacija.
 */
class ApplicationYouthAppealWindowService
{
    public function finalizeExpiredWithoutPrigovor(Application $application): Application
    {
        return DB::transaction(function () use ($application) {
            return $this->finalizeExpiredWithoutPrigovorLocked($application->id);
        });
    }

    /**
     * Pozivalac već mora biti u transakciji i ne smije držati drugi redoslijed katanaca.
     */
    public function finalizeExpiredWithoutPrigovorLocked(int $applicationId): Application
    {
        /** @var Application $locked */
        $locked = Application::query()
            ->whereKey($applicationId)
            ->lockForUpdate()
            ->firstOrFail();

        $locked->load('competition');
        if (! $locked->competition?->isOmladinskoProfile()) {
            return $locked;
        }

        if ($locked->status !== 'submitted') {
            return $locked;
        }

        $notice = ApplicationEliminatoryNotice::query()
            ->where('application_id', $locked->id)
            ->lockForUpdate()
            ->first();

        $check = ApplicationEliminatoryCheck::query()
            ->where('application_id', $locked->id)
            ->lockForUpdate()
            ->first();

        $prigovor = ApplicationPrigovor::query()
            ->where('application_id', $locked->id)
            ->lockForUpdate()
            ->first();

        if (! $this->shouldRejectExpiredWindow($locked, $notice, $check, $prigovor)) {
            return $locked;
        }

        $locked->status = 'rejected';
        $locked->rejection_reason = $this->rejectionReason($check);
        $locked->save();

        return $locked->fresh(['eliminatoryCheck', 'eliminatoryNotice', 'prigovor', 'competition']);
    }

    /**
     * @param  list<int|string>  $competitionIds
     */
    public function finalizeExpiredWithoutPrigovorForCompetitionIds(array $competitionIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $competitionIds))));
        if ($ids === []) {
            return;
        }

        $deadline = now()->subDays(ApplicationEliminatoryNotice::APPEAL_WINDOW_DAYS);

        $applicationIds = Application::query()
            ->whereIn('competition_id', $ids)
            ->where('status', 'submitted')
            ->whereHas('competition', function ($query) {
                $query->where('type', 'omladinsko');
            })
            ->whereHas('eliminatoryCheck', function ($query) {
                $query->whereNotNull('confirmed_at')
                    ->where(function ($inner) {
                        $inner->where('criterion_1', false)
                            ->orWhere('criterion_2', false)
                            ->orWhere('criterion_3', false);
                    });
            })
            ->whereHas('eliminatoryNotice', function ($query) use ($deadline) {
                $query->where('sent_at', '<', $deadline);
            })
            ->whereDoesntHave('prigovor')
            ->pluck('id');

        foreach ($applicationIds as $applicationId) {
            $this->finalizeExpiredWithoutPrigovor(Application::query()->findOrFail($applicationId));
        }
    }

    private function shouldRejectExpiredWindow(
        Application $application,
        ?ApplicationEliminatoryNotice $notice,
        ?ApplicationEliminatoryCheck $check,
        ?ApplicationPrigovor $prigovor,
    ): bool {
        if (! $application->competition?->isOmladinskoProfile()) {
            return false;
        }

        if ($application->status !== 'submitted') {
            return false;
        }

        if ($prigovor !== null) {
            return false;
        }

        if ($check?->isConfirmedFail() !== true) {
            return false;
        }

        if ($notice === null || $notice->prigovorWindowIsOpen()) {
            return false;
        }

        return true;
    }

    private function rejectionReason(?ApplicationEliminatoryCheck $check): string
    {
        $labels = $check?->failedCriterionLabels() ?? [];
        $joined = $labels === [] ? 'aktivirani eliminatorni razlozi' : implode('; ', $labels);

        return 'Istekao je rok za prigovor bez podnošenja. Aktivirani razlozi: '.$joined;
    }
}
