<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\ApplicationOralPresentation;
use App\Models\Competition;
use App\Models\EvaluationScore;
use Illuminate\Support\Carbon;

/**
 * Datumi sjednica za jedan omladinski konkurs.
 * Ručna korekcija u Odluci ne prolazi kroz ovu klasu i ne upisuje se u bazu.
 */
class YouthDecisionSessionDates
{
    /**
     * @return array{first: ?string, second: ?string, third: ?string, oral: ?string}
     */
    public function forCompetition(Competition $competition): array
    {
        return [
            'first' => $this->calendarDate($this->latestEliminatoryConfirmation($competition)),
            'second' => $this->calendarDate($this->latestCompletedScore($competition)),
            'third' => $this->dateColumn($this->latestAllocationDecision($competition)),
            'oral' => $this->reliableOralDate($competition),
        ];
    }

    private function latestEliminatoryConfirmation(Competition $competition): ?string
    {
        $value = ApplicationEliminatoryCheck::query()
            ->whereHas('application', function ($query) use ($competition) {
                $query->where('competition_id', $competition->id);
            })
            ->whereNotNull('confirmed_at')
            ->max('confirmed_at');

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function latestCompletedScore(Competition $competition): ?string
    {
        $value = EvaluationScore::query()
            ->whereHas('application', function ($query) use ($competition) {
                $query->where('competition_id', $competition->id);
            })
            ->whereNotNull('completed_at')
            ->max('completed_at');

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function latestAllocationDecision(Competition $competition): ?string
    {
        $value = Application::query()
            ->where('competition_id', $competition->id)
            ->whereIn('commission_decision', ['podrzava_potpuno', 'odbija'])
            ->whereNotNull('commission_decision_date')
            ->max('commission_decision_date');

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Jedan datum samo kada sva evidentirana održavanja padaju na isti kalendarski dan.
     */
    private function reliableOralDate(Competition $competition): ?string
    {
        $values = ApplicationOralPresentation::query()
            ->whereHas('application', function ($query) use ($competition) {
                $query->where('competition_id', $competition->id);
            })
            ->whereNotNull('held_at')
            ->pluck('held_at');

        $days = $values
            ->map(fn ($value) => $this->calendarDate(is_string($value) ? $value : (string) $value))
            ->filter()
            ->unique()
            ->values();

        if ($days->count() !== 1) {
            return null;
        }

        return $days->first();
    }

    private function calendarDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->timezone(config('app.timezone'))->toDateString();
    }

    private function dateColumn(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr($value, 0, 10);
    }
}
