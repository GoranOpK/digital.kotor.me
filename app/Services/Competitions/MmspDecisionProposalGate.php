<?php

namespace App\Services\Competitions;

use App\Models\CanonicalFinalRanking;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\User;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use App\Services\Competitions\Profile\CompetitionProfileResolver;

class MmspDecisionProposalGate
{
    public const NOT_MMSP_MESSAGE = 'Predlog odluke nije dostupan za ovaj konkurs.';

    public const CHAIRMAN_ONLY_MESSAGE = 'Samo predsjednik Komisije može generisati odluku.';

    public const RANKING_NOT_FINAL_MESSAGE = 'Odluka se može generisati tek kada je konačna rang-lista spremna.';

    public const CLOSED_MESSAGE = 'Konkurs je zatvoren. Odluka se više ne može generisati.';

    public function denyReason(Competition $competition, ?User $user): ?string
    {
        if ($competition->type !== CompetitionProfileResolver::TYPE_CANONICAL) {
            return self::NOT_MMSP_MESSAGE;
        }

        if ($user === null || ! $this->isActiveChairman($competition, $user)) {
            return self::CHAIRMAN_ONLY_MESSAGE;
        }

        if (in_array($competition->status, ['closed', 'completed'], true)) {
            return self::CLOSED_MESSAGE;
        }

        if (! $this->rankingIsFinal($competition)) {
            return self::RANKING_NOT_FINAL_MESSAGE;
        }

        return null;
    }

    public function rankingIsFinal(Competition $competition): bool
    {
        if ($competition->type !== CompetitionProfileResolver::TYPE_CANONICAL) {
            return false;
        }

        $ranking = CanonicalFinalRanking::query()
            ->where('competition_id', $competition->id)
            ->first();

        return $ranking !== null && $ranking->isFinal();
    }

    private function isActiveChairman(Competition $competition, User $user): bool
    {
        if (! $competition->commission_id) {
            return false;
        }

        $member = CommissionMember::activeForCommission((int) $user->id, (int) $competition->commission_id);

        return $member !== null
            && $member->canonical_seat === CanonicalCommissionGovernance::ROLE_PREDSJEDNIK
            && $member->position === 'predsjednik'
            && $member->status === 'active';
    }
}
