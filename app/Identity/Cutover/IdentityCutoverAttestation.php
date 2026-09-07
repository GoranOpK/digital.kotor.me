<?php

namespace App\Identity\Cutover;

use App\Identity\IdentityRollout;
use App\Identity\Runtime\EpIdentityFlowGuard;
use App\Identity\Runtime\IdentityMutationGuard;

/**
 * Observational Step 8 cutover attestation. Never authorizes cutover.
 * Never sets cutover_ready or population_boundary_protected to true.
 */
final class IdentityCutoverAttestation
{
    public function __construct(
        private readonly IdentityRollout $rollout = new IdentityRollout,
        private readonly IdentityMutationGuard $guard = new IdentityMutationGuard,
        private readonly EpIdentityFlowGuard $ep = new EpIdentityFlowGuard,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $capabilityDeployed = true;
        $switchesOff = ! $this->rollout->readsCanonical() && ! $this->rollout->writesCanonical();

        return [
            'schema_version' => 1,
            'step' => 8,
            'capability_deployed' => $capabilityDeployed,
            'canonical_switches_initially_off' => $switchesOff,
            'canonical_read' => $this->rollout->readsCanonical(),
            'canonical_write' => $this->rollout->writesCanonical(),
            'required_readers_capable' => $capabilityDeployed,
            'legacy_writers_denied' => ! $this->guard->legacyIdentityMutationAllowed(),
            'ep_durable_disable_active' => ! $this->ep->enabled(),
            'use_gates_safe' => $capabilityDeployed,
            'identity_write_freeze_active' => $this->rollout->identityWriteFrozen(),
            'protected_population_boundary_established' => false,
            'final_reconciliation_clean' => false,
            'canonical_graphs_verified' => false,
            'logical_cutover_completed' => false,
            'post_cutover_smoke_passed' => false,
            'population_boundary_protected' => false,
            'cutover_ready' => false,
            'cutover_authorized' => false,
            'standing_blockers' => [
                'step8_not_authorized',
                'population_boundary_unprotected',
            ],
            'notes' => [
                'Step 7 cannot authorize cutover.',
                'This attestation is observational and does not start cutover.',
            ],
        ];
    }
}
