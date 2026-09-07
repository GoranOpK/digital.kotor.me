<?php

namespace App\Identity\Reconcile;

use App\Identity\Census\IdentityCensusRow;

/**
 * Step 7 reconcile report. cutover_ready and population_boundary_protected
 * are hardcoded false; this class has no setter that can make them true.
 */
final readonly class IdentityReconcileReport
{
    /**
     * @param  list<IdentityReconcileUserOutcome>  $outcomes
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
        $byStatus = is_array($this->aggregates['by_status'] ?? null) ? $this->aggregates['by_status'] : [];
        $liveRowStatus = is_array($this->aggregates['live_row_status'] ?? null)
            ? $this->aggregates['live_row_status']
            : [];
        $blockers = is_array($this->aggregates['cutover_blockers'] ?? null)
            ? $this->aggregates['cutover_blockers']
            : [];

        return [
            'spec' => 'DK-TS-002 D15 Step 7',
            'mode' => $this->metadata['mode'] ?? null,
            'environment' => $this->metadata['environment'] ?? null,
            'started_at' => $this->metadata['started_at'] ?? null,
            'finished_at' => $this->metadata['finished_at'] ?? null,
            'commit_hash' => $this->metadata['commit_hash'] ?? null,
            'deploy_revision' => $this->metadata['deploy_revision'] ?? null,
            'census_max_user_id' => $this->metadata['census_max_user_id'] ?? null,
            'live_max_user_id' => $this->metadata['live_max_user_id'] ?? null,
            'prior_census_max_user_id' => $this->metadata['prior_census_max_user_id'] ?? null,
            'canonical_read' => false,
            'canonical_write' => false,
            'row_count' => $this->metadata['row_count'] ?? 0,
            'by_status' => $byStatus,
            'live_row_status' => $liveRowStatus,
            'reconcile_passed' => $this->reconcilePassed(),
            'graph_readiness_passed' => $this->graphReadinessPassed(),
            'cutover_ready' => false,
            'population_boundary_protected' => false,
            'cutover_blocker_count' => count($blockers),
            'cutover_blockers' => $blockers,
            'would_create' => (int) ($byStatus[IdentityReconcileUserOutcome::WOULD_CREATE] ?? 0),
            'created' => (int) ($byStatus[IdentityReconcileUserOutcome::CREATED] ?? 0),
            'would_update' => (int) ($byStatus[IdentityReconcileUserOutcome::WOULD_UPDATE] ?? 0),
            'updated' => (int) ($byStatus[IdentityReconcileUserOutcome::UPDATED] ?? 0),
            'skipped_idempotent' => (int) ($byStatus[IdentityReconcileUserOutcome::SKIPPED_IDEMPOTENT] ?? 0),
            'source_drift' => (int) ($byStatus[IdentityReconcileUserOutcome::SOURCE_DRIFT] ?? 0),
            'conflict' => (int) ($byStatus[IdentityReconcileUserOutcome::CONFLICT] ?? 0),
            'canonical_invalid' => (int) ($byStatus[IdentityReconcileUserOutcome::CANONICAL_INVALID] ?? 0),
            'failed' => (int) ($byStatus[IdentityReconcileUserOutcome::FAILED] ?? 0),
            'backfillable_in_boundary' => (int) ($this->aggregates['backfillable_in_boundary'] ?? 0),
            'graphs_ready_count' => (int) ($this->aggregates['graphs_ready_count'] ?? 0),
            'expected_ready' => (int) ($this->aggregates['expected_ready'] ?? 0),
            'ep_gate' => $this->epGateMetadata(),
            'connection' => $this->metadata['connection'] ?? null,
            'census_reference_date' => $this->metadata['census_reference_date'] ?? null,
            'metadata' => array_merge($this->metadata, [
                'spec' => 'DK-TS-002 D15 Step 7',
                'canonical_read' => false,
                'canonical_write' => false,
                'cutover_ready' => false,
                'population_boundary_protected' => false,
                'reconcile_passed' => $this->reconcilePassed(),
                'graph_readiness_passed' => $this->graphReadinessPassed(),
                'ep_gate' => $this->epGateMetadata(),
            ]),
            'aggregates' => $this->aggregates,
        ];
    }

    public function reconcilePassed(): bool
    {
        $byStatus = is_array($this->aggregates['by_status'] ?? null) ? $this->aggregates['by_status'] : [];

        foreach ([
            IdentityReconcileUserOutcome::FAILED,
            IdentityReconcileUserOutcome::CONFLICT,
            IdentityReconcileUserOutcome::CANONICAL_INVALID,
        ] as $blocking) {
            if ((int) ($byStatus[$blocking] ?? 0) > 0) {
                return false;
            }
        }

        return true;
    }

    public function graphReadinessPassed(): bool
    {
        $expected = (int) ($this->aggregates['expected_ready'] ?? 0);
        $ready = (int) ($this->aggregates['graphs_ready_count'] ?? 0);
        $byStatus = is_array($this->aggregates['by_status'] ?? null) ? $this->aggregates['by_status'] : [];

        if ($expected !== $ready) {
            return false;
        }

        if ((int) ($byStatus[IdentityReconcileUserOutcome::WOULD_CREATE] ?? 0) > 0) {
            return false;
        }

        if ((int) ($byStatus[IdentityReconcileUserOutcome::WOULD_UPDATE] ?? 0) > 0) {
            return false;
        }

        foreach ([
            IdentityReconcileUserOutcome::FAILED,
            IdentityReconcileUserOutcome::CONFLICT,
            IdentityReconcileUserOutcome::CANONICAL_INVALID,
        ] as $blocking) {
            if ((int) ($byStatus[$blocking] ?? 0) > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Observational EP gate metadata. Never queries EP tables.
     *
     * @return array<string, mixed>
     */
    public function epGateMetadata(): array
    {
        return [
            'status' => 'OPEN',
            'deferred' => true,
            'reason' => 'ep_module_undeployed',
        ];
    }

    /**
     * @return list<string>
     */
    public static function subjectNotEligibleBlockerStatuses(): array
    {
        return [
            IdentityCensusRow::MISSING_REQUIRED,
            IdentityCensusRow::INVALID_LEGACY,
            IdentityCensusRow::UNSUPPORTED,
            IdentityCensusRow::AMBIGUOUS_MAPPING,
        ];
    }
}
