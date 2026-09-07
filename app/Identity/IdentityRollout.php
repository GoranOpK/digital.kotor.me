<?php

namespace App\Identity;

/**
 * Rollout authorization checks. Not a silent no-op wrapper around writer/reader.
 * Flags default OFF. Deploy alone cannot switch authority.
 */
final class IdentityRollout
{
    public function readsCanonical(): bool
    {
        return (bool) config('identity.canonical_read');
    }

    public function writesCanonical(): bool
    {
        return (bool) config('identity.canonical_write');
    }

    public function identityWriteFrozen(): bool
    {
        return (bool) config('identity.identity_write_freeze');
    }

    public function epIdentityFlowsEnabled(): bool
    {
        return (bool) config('identity.ep_identity_flows');
    }
}
