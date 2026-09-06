<?php

namespace App\Identity\Runtime;

use App\Identity\IdentityRollout;

/**
 * Targeted identity-write freeze + canonical HTTP write authorization.
 * Freeze denies legacy identity mutation and new subject creation via legacy.
 * Canonical HTTP identity writes require write AND read (D15 interlock).
 * Freeze does not reopen legacy writers when canonical_write turns ON.
 */
final class IdentityMutationGuard
{
    public function __construct(
        private readonly IdentityRollout $rollout = new IdentityRollout,
    ) {
    }

    public function legacyIdentityMutationAllowed(): bool
    {
        return ! $this->rollout->writesCanonical() && ! $this->rollout->identityWriteFrozen();
    }

    public function canonicalHttpWriteAllowed(): bool
    {
        return $this->rollout->writesCanonical() && $this->rollout->readsCanonical();
    }

    public function subjectCreationAllowed(): bool
    {
        if ($this->canonicalHttpWriteAllowed()) {
            return true;
        }

        return $this->legacyIdentityMutationAllowed();
    }

    public function assertLegacyIdentityMutationAllowed(): void
    {
        if (! $this->legacyIdentityMutationAllowed()) {
            throw new IdentityMutationDeniedException(
                'Izmjena naslijeđenog identiteta je onemogućena.'
            );
        }
    }

    public function assertCanonicalHttpWriteAllowed(): void
    {
        if (! $this->canonicalHttpWriteAllowed()) {
            throw new IdentityMutationDeniedException(
                'Kanonski upis identiteta nije autorizovan.'
            );
        }
    }

    public function assertSubjectCreationAllowed(): void
    {
        if (! $this->subjectCreationAllowed()) {
            throw new IdentityMutationDeniedException(
                'Registracija subjekta je trenutno onemogućena.'
            );
        }
    }
}
