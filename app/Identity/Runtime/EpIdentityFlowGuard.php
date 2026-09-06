<?php

namespace App\Identity\Runtime;

use App\Identity\IdentityRollout;

/**
 * Durable EP identity-flow disable. Independent of ep_settings and schema.
 * Default deny. Admin new_payments_enabled cannot bypass this.
 */
final class EpIdentityFlowGuard
{
    public function __construct(
        private readonly IdentityRollout $rollout = new IdentityRollout,
    ) {
    }

    public function enabled(): bool
    {
        return $this->rollout->epIdentityFlowsEnabled();
    }

    public function assertEnabled(): void
    {
        if (! $this->enabled()) {
            throw new IdentityMutationDeniedException(
                'Tokovi e-Plaćanja koji zavise od identiteta nijesu dostupni.'
            );
        }
    }
}
