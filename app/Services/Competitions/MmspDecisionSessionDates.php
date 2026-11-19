<?php

namespace App\Services\Competitions;

use App\Models\CommissionSession;
use App\Models\Competition;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use Illuminate\Support\Carbon;

/**
 * Predlog datuma sjednica iz evidentiranih MMSP sjednica.
 * Ručni unos na stranici odluke ne prolazi kroz ovu klasu i ne upisuje se u bazu.
 */
class MmspDecisionSessionDates
{
    /**
     * @return array{first: ?string, second: ?string, third: ?string}
     */
    public function forCompetition(Competition $competition): array
    {
        return [
            'first' => $this->sessionDate($competition, CanonicalCommissionGovernance::PURPOSE_ORDINARY),
            'second' => $this->sessionDate($competition, CanonicalCommissionGovernance::PURPOSE_INTERVIEW),
            'third' => $this->sessionDate($competition, CanonicalCommissionGovernance::PURPOSE_FINAL_DECISION),
        ];
    }

    private function sessionDate(Competition $competition, string $purpose): ?string
    {
        $value = CommissionSession::query()
            ->where('competition_id', $competition->id)
            ->where('canonical_purpose', $purpose)
            ->whereNotNull('held_at')
            ->max('held_at');

        if (! is_string($value) || $value === '') {
            return null;
        }

        return Carbon::parse($value)->timezone(config('app.timezone'))->toDateString();
    }
}
