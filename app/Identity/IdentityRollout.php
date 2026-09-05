<?php

namespace App\Identity;

/**
 * Rollout authorization checks. Not a silent no-op wrapper around writer/reader.
 * Step 2 runtime does not consult this in HTTP flows.
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
}
