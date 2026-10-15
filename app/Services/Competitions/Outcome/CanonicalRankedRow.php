<?php

namespace App\Services\Competitions\Outcome;

use App\Models\Application;

/**
 * Frozen ranking facts of one canonical ranked application, as used by outcome validation.
 * The application id identifies the row only; it is never a funding priority.
 * Carries no business stage: canonical funding never depends on it.
 */
final class CanonicalRankedRow
{
    public function __construct(
        public readonly Application $application,
        public readonly int $rank,
        public readonly string $calc,
        public readonly string $display,
        public readonly ?string $pib,
        public readonly ?string $subsidyType,
        public readonly ?string $totalInvestment,
        public readonly ?string $legalMaximum,
    ) {
    }

    public function id(): int
    {
        return (int) $this->application->id;
    }

    public function belowThreshold(): bool
    {
        return CanonicalAllocationPolicy::belowThreshold($this->calc);
    }
}
