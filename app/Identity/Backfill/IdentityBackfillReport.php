<?php

namespace App\Identity\Backfill;

final readonly class IdentityBackfillReport
{
    /**
     * @param  list<IdentityBackfillUserOutcome>  $outcomes
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $aggregates
     */
    public function __construct(
        public array $outcomes,
        public array $metadata,
        public array $aggregates,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function aggregateDocument(): array
    {
        return [
            'metadata' => $this->metadata,
            'aggregates' => $this->aggregates,
        ];
    }

    public function hasBlockingOutcomes(): bool
    {
        foreach ($this->outcomes as $outcome) {
            if (in_array($outcome->status, [
                IdentityBackfillUserOutcome::CONFLICT,
                IdentityBackfillUserOutcome::FAILED,
            ], true)) {
                return true;
            }
        }

        return false;
    }
}
