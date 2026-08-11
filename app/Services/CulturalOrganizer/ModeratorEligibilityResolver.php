<?php

namespace App\Services\CulturalOrganizer;

use App\Models\CulturalOrganizerCreationRequest;
use App\Models\User;
use App\Support\CulturalPortalAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * PO-ORG-06 Package 3 — awaiting_moderator_eligibility → submitted when user is eligible.
 *
 * Scope: CulturalOrganizerCreationRequest (FIRST Moderator). Reusable later for Package 5 ADD.
 * Does not create Organizer/grant and does not send mail.
 */
final class ModeratorEligibilityResolver
{
    /**
     * Resolve all waiting Organizer-creation requests matching the user's normalized email.
     *
     * @return int Number of requests transitioned to submitted
     */
    public function resolveForUser(User $user): int
    {
        $user = $user->fresh() ?? $user;

        if (! CulturalPortalAccess::isPlatformUserActive($user)) {
            return 0;
        }

        $normalizedEmail = OrganizerCreationRequestSubmissionService::normalizeEmail((string) $user->email);

        if ($normalizedEmail === '') {
            return 0;
        }

        $requestIds = CulturalOrganizerCreationRequest::query()
            ->where('status', CulturalOrganizerCreationRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY)
            ->where('proposed_moderator_email', $normalizedEmail)
            ->whereNull('proposed_moderator_user_id')
            ->orderBy('id')
            ->pluck('id');

        $resolved = 0;

        foreach ($requestIds as $requestId) {
            try {
                if ($this->resolveRequestId((int) $requestId, $user, $normalizedEmail)) {
                    $resolved++;
                }
            } catch (Throwable $e) {
                Log::error('PO-ORG-06 eligibility resolver failed for Organizer creation request.', [
                    'creation_request_id' => $requestId,
                    'user_id' => $user->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return $resolved;
    }

    private function resolveRequestId(int $requestId, User $user, string $normalizedEmail): bool
    {
        return (bool) DB::transaction(function () use ($requestId, $user, $normalizedEmail) {
            /** @var CulturalOrganizerCreationRequest|null $locked */
            $locked = CulturalOrganizerCreationRequest::query()
                ->whereKey($requestId)
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                return false;
            }

            if (! $locked->isAwaitingModeratorEligibility()) {
                return false;
            }

            if ($locked->proposed_moderator_user_id !== null) {
                return false;
            }

            if ($locked->proposed_moderator_email !== $normalizedEmail) {
                return false;
            }

            $freshUser = $user->fresh() ?? $user;
            if (! CulturalPortalAccess::isPlatformUserActive($freshUser)) {
                return false;
            }

            if (OrganizerCreationRequestSubmissionService::normalizeEmail((string) $freshUser->email) !== $normalizedEmail) {
                return false;
            }

            $locked->proposed_moderator_user_id = $freshUser->id;
            $locked->status = CulturalOrganizerCreationRequest::STATUS_SUBMITTED;
            $locked->save();

            return true;
        });
    }
}
