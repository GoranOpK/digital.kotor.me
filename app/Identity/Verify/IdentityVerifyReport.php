<?php

namespace App\Identity\Verify;

final readonly class IdentityVerifyReport
{
    /**
     * @param  list<IdentityVerifyUserOutcome>  $outcomes
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

    public function passed(): bool
    {
        foreach (IdentityVerifyUserOutcome::BLOCKING_STATUSES as $status) {
            if (($this->aggregates['by_status'][$status] ?? 0) > 0) {
                return false;
            }
        }

        if (($this->aggregates['global_unassigned_failures'] ?? 0) > 0) {
            return false;
        }

        $verified = (int) ($this->aggregates['by_status'][IdentityVerifyUserOutcome::VERIFIED] ?? 0);
        $expected = (int) ($this->aggregates['live_backfillable_in_boundary'] ?? -1);
        if ($verified !== $expected) {
            return false;
        }

        $counts = $this->aggregates['canonical_table_counts'] ?? [];
        if (! is_array($counts)) {
            return false;
        }

        return (int) ($counts['platform_identities'] ?? -1) === $verified
            && (int) ($counts['physical_person_identities'] ?? -1) === $verified
            && (int) ($counts['legal_entity_identities'] ?? -1) === 0
            && (int) ($counts['legal_entity_authorized_persons'] ?? -1) === 0
            && (int) ($counts['foreign_branch_identities'] ?? -1) === 0
            && (int) ($counts['foreign_branch_representatives'] ?? -1) === 0;
    }
}
