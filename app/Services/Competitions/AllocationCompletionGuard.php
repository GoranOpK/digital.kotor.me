<?php

namespace App\Services\Competitions;

use App\Models\Application;
use Illuminate\Support\Collection;

/**
 * Prostor za novu podršku: budžet umanjen za već sačuvane iznose i za
 * neriješene prijave strogo višeg ranga. Isti rang ne rezerviše sredstva.
 * Ne bira dobitnika i ne mijenja iznos.
 */
final class AllocationCompletionGuard
{
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

    public function youthMaxClaim(Application $application, string $budget): string
    {
        return $this->capClaim($application, $budget, null);
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
        return $otherRank < $candidateRank;
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
