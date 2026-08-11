<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\CulturalOrganizer\ModeratorEligibilityResolver;
use Illuminate\Auth\Events\Verified;

/**
 * PO-ORG-06 Package 3 — primary trigger: email verification.
 *
 * Resolver re-checks full eligibility (verified + active). Inactive verified users stay awaiting.
 */
class ResolveModeratorEligibilityOnVerified
{
    public function __construct(
        private readonly ModeratorEligibilityResolver $resolver,
    ) {}

    public function handle(Verified $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $this->resolver->resolveForUser($user);
    }
}
