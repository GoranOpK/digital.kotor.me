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
        $scope = (string) ($this->metadata['scope'] ?? IdentityShadowScope::FULL);

        if ($eligible < 0 || $requiredFlows < 0) {
            return false;
        }

        if (IdentityShadowScope::isActiveIdentityWave($scope)) {
            return $this->scopedWavePassed($eligible, $requiredFlows, $expected, $matched);
        }

        if ($requiredFlows !== count(IdentityShadowFlow::REQUIRED)) {
            return false;
        }

        if ($evaluable !== $expected) {
            return false;
        }

        if ($evaluable < 0) {
            return false;
        }

        return $matched === $expected;
    }

    public function fiveFlowClosed(): bool
    {
        $scope = (string) ($this->metadata['scope'] ?? IdentityShadowScope::FULL);

        return $scope === IdentityShadowScope::FULL && $this->passed();
    }

    private function scopedWavePassed(int $eligible, int $requiredFlows, int $expected, int $matched): bool
    {
        if ($requiredFlows !== count(IdentityShadowFlow::ACTIVE_IDENTITY_WAVE)) {
            return false;
        }

        if ($expected !== $eligible * $requiredFlows) {
            return false;
        }

        $gate = $this->metadata['deferred_gates'][IdentityShadowFlow::EP_AVAILABILITY]
            ?? $this->aggregates['deferred_gates'][IdentityShadowFlow::EP_AVAILABILITY]
            ?? null;
        if (! is_array($gate)) {
            return false;
        }
        if (($gate['status'] ?? null) !== 'OPEN') {
            return false;
        }
        if (($gate['reason'] ?? null) !== 'ep_module_undeployed') {
            return false;
        }

        $requiredBefore = $gate['required_before'] ?? null;
        if (! is_array($requiredBefore)) {
            return false;
        }
        if (($requiredBefore['mode'] ?? null) !== 'earliest_of') {
            return false;
        }
        if (($requiredBefore['events'] ?? null) !== [
            'ep_production_activation',
            'canonical_writer_authority',
        ]) {
            return false;
        }

        return $matched === $expected;
    }
}
