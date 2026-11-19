<?php

namespace App\Services\Competitions;

final class MmspDecisionProposal
{
    /**
     * @param  list<array{identity: string, subsidy: string, score: string, approved: string, total_value: string}>  $winners
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
        public readonly array $winners,
        public readonly int $receivedCount,
        public readonly int $incompleteCount,
        public readonly int $appealsAccepted,
        public readonly int $appealsRejected,
        public readonly int $proceedCount,
        public readonly string $awardedTotal,
    ) {}

    public function downloadName(): string
    {
        return 'Odluka-o-dodjeli-subvencija-MMSP-'.$this->competitionYear.'-'.$this->competitionId.'.docx';
    }
}
