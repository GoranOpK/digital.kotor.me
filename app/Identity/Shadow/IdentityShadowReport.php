<?php

namespace App\Identity\Shadow;

final readonly class IdentityShadowReport
{
    /**
     * @param  list<IdentityShadowUserOutcome>  $outcomes
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
        foreach (IdentityShadowStatus::BLOCKING_STATUSES as $status) {
            if ((int) ($this->aggregates['by_status'][$status] ?? 0) > 0) {
                return false;
            }
        }

        $eligible = (int) ($this->aggregates['eligible_user_count'] ?? -1);
        $requiredFlows = (int) ($this->aggregates['required_flow_count'] ?? -1);
        $evaluable = (int) ($this->aggregates['evaluable_comparison_count'] ?? -1);
        $expected = (int) ($this->aggregates['expected_match_comparisons'] ?? -1);
        $matched = (int) ($this->aggregates['by_status'][IdentityShadowStatus::MATCH] ?? 0);

        if ($requiredFlows !== count(IdentityShadowFlow::REQUIRED)) {
            return false;
        }

        if ($evaluable !== $expected) {
            return false;
        }

        if ($evaluable < 0 || $eligible < 0) {
            return false;
        }

        return $matched === $expected;
    }
}
