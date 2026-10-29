<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Support\KnApplicationClassification;
use Illuminate\Support\Collection;

/**
 * Prostor za novu podršku tako da već zaključane odluke i obavezni prioritet
 * i dalje ostavljaju bar jedan zakonit završetak. Ne bira dobitnika i ne mijenja iznos.
 */
final class AllocationCompletionGuard
{
    public const DEVELOPMENT_INSTEAD_OF_STARTUP_MESSAGE =
        'Razvoj se ne bira umjesto prioritetne prijave otpočinjanja.';

    /**
     * @param  Collection<int, Application>  $pool
     * @param  callable(Application): string  $maxClaim
     */
    public function supportRoom(string $budget, Collection $pool, Application $candidate, callable $maxClaim): string
    {
        $budget = $this->money($budget);
        $used = '0.00';
        $reserved = '0.00';

        foreach ($pool as $other) {
            if ((int) $other->id === (int) $candidate->id) {
                continue;
            }

            if ($this->isSupported($other)) {
                $used = bcadd($used, $this->money((string) $other->approved_amount), 2);

                continue;
            }

            if ($this->isDecided($other) || ! $this->isStrictlyHigher($other, $candidate)) {
                continue;
            }

            $reserved = bcadd($reserved, $this->money($maxClaim($other)), 2);
        }

        $room = bcsub(bcsub($budget, $used, 2), $reserved, 2);
        if (bccomp($room, '0.00', 2) === -1) {
            return '0.00';
        }

        return $room;
    }

    /**
     * @param  Collection<int, Application>  $pool
     */
    public function startupRejectionBlocked(Collection $pool, Application $candidate): bool
    {
        if (! $this->isStartup($candidate) || $candidate->ranking_position === null) {
            return false;
        }

        $rank = (int) $candidate->ranking_position;

        return $pool->contains(function (Application $other) use ($candidate, $rank) {
            return (int) $other->id !== (int) $candidate->id
                && (int) $other->ranking_position === $rank
                && ! $this->isStartup($other)
                && $this->isSupported($other);
        });
    }

    /**
     * @param  Collection<int, Application>  $pool
     */
    public function developmentSupportBlocked(Collection $pool, Application $candidate): bool
    {
        if ($this->isStartup($candidate) || $candidate->ranking_position === null) {
            return false;
        }

        $rank = (int) $candidate->ranking_position;

        return $pool->contains(function (Application $other) use ($candidate, $rank) {
            return (int) $other->id !== (int) $candidate->id
                && (int) $other->ranking_position === $rank
                && $this->isStartup($other)
                && $other->commission_decision === 'odbija';
        });
    }

    public function youthMaxClaim(Application $application, string $budget): string
    {
        $percent = 30;
        if ($this->youthCapFactsConfirmed($application)) {
            $startup = (int) $application->getAttributes()['youth_innovative_tech_startup'] === 1;
            $prior = (int) $application->getAttributes()['youth_prior_municipal_youth_funding'] === 1;
            $percent = $startup ? 30 : ($prior ? 15 : 20);
        }

        return $this->capClaim($application, $budget, $this->percentOfBudget($this->money($budget), $percent));
    }

    public function zenskoMaxClaim(Application $application, string $budget): string
    {
        $budget = $this->money($budget);
        $percentCap = null;
        if ((bool) $application->bonus_green_innovative) {
            $percentCap = $this->money(number_format(round((float) $budget * 0.20, 2), 2, '.', ''));
        }

        return $this->capClaim($application, $budget, $percentCap);
    }

    public function isStartup(Application $application): bool
    {
        return (string) $application->business_stage === KnApplicationClassification::STAGE_ZAPOCINJANJE;
    }

    private function capClaim(Application $application, string $budget, ?string $percentCap): string
    {
        $budget = $this->money($budget);
        $claim = $budget;

        if ($application->requested_amount !== null) {
            $claim = $this->minMoney($claim, $this->money((string) $application->requested_amount));
        }

        if ($percentCap !== null) {
            $claim = $this->minMoney($claim, $percentCap);
        }

        if (bccomp($claim, '0.00', 2) === -1) {
            return '0.00';
        }

        return $claim;
    }

    private function isSupported(Application $application): bool
    {
        return $application->commission_decision === 'podrzava_potpuno'
            && $application->approved_amount !== null
            && bccomp($this->money((string) $application->approved_amount), '0.00', 2) === 1;
    }

    private function isDecided(Application $application): bool
    {
        return in_array($application->commission_decision, ['podrzava_potpuno', 'odbija'], true);
    }

    private function isStrictlyHigher(Application $other, Application $candidate): bool
    {
        if ($other->ranking_position === null || $candidate->ranking_position === null) {
            return false;
        }

        $otherRank = (int) $other->ranking_position;
        $candidateRank = (int) $candidate->ranking_position;
        if ($otherRank < $candidateRank) {
            return true;
        }

        if ($otherRank > $candidateRank) {
            return false;
        }

        return $this->isStartup($other) && ! $this->isStartup($candidate);
    }

    private function youthCapFactsConfirmed(Application $application): bool
    {
        $startup = $application->getAttributes()['youth_innovative_tech_startup'] ?? null;
        $prior = $application->getAttributes()['youth_prior_municipal_youth_funding'] ?? null;

        return $startup !== null
            && $startup !== ''
            && $prior !== null
            && $prior !== ''
            && $application->youth_innovative_tech_startup_confirmed_at !== null
            && $application->youth_prior_municipal_youth_funding_confirmed_at !== null;
    }

    private function percentOfBudget(string $budget, int $percent): string
    {
        return bcmul($budget, bcdiv((string) $percent, '100', 2), 2);
    }

    private function minMoney(string $left, string $right): string
    {
        return bccomp($left, $right, 2) <= 0 ? $left : $right;
    }

    private function money(string $value): string
    {
        return bcadd($value, '0', 2);
    }
}
