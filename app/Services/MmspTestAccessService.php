<?php

namespace App\Services;

use App\Models\MmspTestAccessGrant;
use App\Models\User;

/**
 * Current MMSP test access is the latest append-only event for the account,
 * and only while that account still has the korisnik role.
 */
class MmspTestAccessService
{
    public function allows(User $user): bool
    {
        $user->loadMissing('role');

        if ($user->role?->name !== 'korisnik') {
            return false;
        }

        return $this->latestAction($user->id) === MmspTestAccessGrant::ACTION_GRANTED;
    }

    public function latest(User $user): ?MmspTestAccessGrant
    {
        return MmspTestAccessGrant::query()
            ->where('user_id', $user->id)
            ->with('actor')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Role change and the matching grant event share the caller's transaction.
     * The subject row must already be locked.
     */
    public function reconcile(User $lockedUser, User $actor, ?string $previousRole, ?bool $requestedGrant): void
    {
        $lockedUser->unsetRelation('role');
        $lockedUser->load('role');
        $newRole = $lockedUser->role?->name;
        $active = $this->latestAction((int) $lockedUser->id) === MmspTestAccessGrant::ACTION_GRANTED;

        if ($previousRole === 'korisnik' && $newRole !== 'korisnik') {
            if ($active) {
                $this->insert(
                    (int) $lockedUser->id,
                    (int) $actor->id,
                    MmspTestAccessGrant::ACTION_REVOKED,
                    MmspTestAccessGrant::REASON_ROLE_CHANGED,
                );
            }

            return;
        }

        if ($newRole !== 'korisnik' || $requestedGrant === null) {
            return;
        }

        if ($requestedGrant && ! $active) {
            $this->insert(
                (int) $lockedUser->id,
                (int) $actor->id,
                MmspTestAccessGrant::ACTION_GRANTED,
                MmspTestAccessGrant::REASON_MANUAL,
            );

            return;
        }

        if (! $requestedGrant && $active) {
            $this->insert(
                (int) $lockedUser->id,
                (int) $actor->id,
                MmspTestAccessGrant::ACTION_REVOKED,
                MmspTestAccessGrant::REASON_MANUAL,
            );
        }
    }

    private function latestAction(int $userId): ?string
    {
        $action = MmspTestAccessGrant::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->value('action');

        return is_string($action) ? $action : null;
    }

    private function insert(int $userId, int $actorId, string $action, string $reason): void
    {
        MmspTestAccessGrant::query()->create([
            'user_id' => $userId,
            'actor_user_id' => $actorId,
            'action' => $action,
            'reason' => $reason,
        ]);
    }
}
