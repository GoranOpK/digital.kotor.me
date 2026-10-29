<?php

namespace App\Services\Competitions;

use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\User;

class YouthDecisionProposalGate
{
    public const NOT_YOUTH_MESSAGE = 'Predlog odluke nije dostupan za ovaj konkurs.';

    public const CHAIRMAN_ONLY_MESSAGE = 'Samo predsjednik Komisije može generisati odluku.';

    public const RANKING_NOT_FINAL_MESSAGE = 'Odluka se može generisati tek kada je konačna rang-lista spremna.';

    public function denyReason(Competition $competition, ?User $user): ?string
    {
        if (! $competition->isOmladinskoProfile()) {
            return self::NOT_YOUTH_MESSAGE;
        }

        if ($user === null || ! $this->isActiveChairman($competition, $user)) {
            return self::CHAIRMAN_ONLY_MESSAGE;
        }

        if (! $competition->hasChairmanCompletedDecisions()) {
            return self::RANKING_NOT_FINAL_MESSAGE;
        }

        return null;
    }

    private function isActiveChairman(Competition $competition, User $user): bool
    {
        if (! $competition->commission_id) {
            return false;
        }

        $member = CommissionMember::activeForCommission((int) $user->id, (int) $competition->commission_id);

        return $member !== null && $member->position === 'predsjednik' && $member->status === 'active';
    }
}
