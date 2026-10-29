<?php

namespace App\Services\Competitions;

final class YouthDecisionProposal
{
    /**
     * @param  list<array{identity: string, plan: string, score: string, approved: string, total_value: string}>  $winners
     */
    public function __construct(
        public readonly int $competitionId,
        public readonly string $decisionNumber,
        public readonly string $decisionDate,
        public readonly string $competitionYear,
        public readonly string $publishedOn,
        public readonly string $endedOn,
        public readonly string $firstSessionDate,
        public readonly string $secondSessionDate,
        public readonly string $thirdSessionDate,
        public readonly string $oralDate,
        public readonly array $winners,
        public readonly int $receivedCount,
        public readonly int $incompleteCount,
        public readonly int $appealsAccepted,
        public readonly int $appealsRejected,
        public readonly int $proceedCount,
        public readonly string $awardedTotal,
        public readonly int $commissionMemberCount,
    ) {}

    public function downloadName(): string
    {
        return 'Odluka-o-raspodjeli-sredstava-mladi-'.$this->competitionYear.'-'.$this->competitionId.'.docx';
    }
}
