<?php

namespace App\Services\CulturalOrganizer;

use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\User;
use App\Support\CulturalPortalAccess;
use App\Services\CulturalActivity\CulturalActivityCatalog;
use App\Services\CulturalActivity\CulturalActivityEmitter;
use App\Services\CulturalActivity\CulturalActivityEventId;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * PO-ORG-06 Packages 3/5 — awaiting → submitted when user is eligible.
 *
 * Resolves:
 * - CulturalOrganizerCreationRequest (FIRST Moderator)
 * - CulturalModeratorRequest type=add (subsequent ADD)
 *
 * Does not create grants and does not send mail.
 */
final class ModeratorEligibilityResolver
{
    public function __construct(
        private readonly CulturalActivityEmitter $activityEmitter,
    ) {}

    /**
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

        return $this->resolveOrganizerCreationRequests($user, $normalizedEmail)
            + $this->resolveModeratorAddRequests($user, $normalizedEmail);
    }

    private function resolveOrganizerCreationRequests(User $user, string $normalizedEmail): int
    {
        $requestIds = CulturalOrganizerCreationRequest::query()
            ->where('status', CulturalOrganizerCreationRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY)
            ->where('proposed_moderator_email', $normalizedEmail)
            ->whereNull('proposed_moderator_user_id')
            ->orderBy('id')
            ->pluck('id');

        $resolved = 0;

        foreach ($requestIds as $requestId) {
            try {
                if ($this->resolveOrganizerCreationRequestId((int) $requestId, $user, $normalizedEmail)) {
                    $resolved++;
                    $fresh = CulturalOrganizerCreationRequest::query()->find($requestId);
                    if ($fresh !== null) {
                        $this->activityEmitter->emitSystem(
                            CulturalActivityCatalog::MOD_07,
                            CulturalActivityEventId::of(CulturalActivityCatalog::MOD_07, 'org', (int) $fresh->id),
                            (int) $fresh->id,
                            $fresh->updated_at ?? now(),
                            ['request_id' => (int) $fresh->id],
                        );
                    }
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

    private function resolveModeratorAddRequests(User $user, string $normalizedEmail): int
    {
        $requestIds = CulturalModeratorRequest::query()
            ->where('type', CulturalModeratorRequest::TYPE_ADD)
            ->where('status', CulturalModeratorRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY)
            ->where('proposed_moderator_email', $normalizedEmail)
            ->whereNull('target_user_id')
            ->orderBy('id')
            ->pluck('id');

        $resolved = 0;

        foreach ($requestIds as $requestId) {
            try {
                if ($this->resolveModeratorAddRequestId((int) $requestId, $user, $normalizedEmail)) {
                    $resolved++;
                    $fresh = CulturalModeratorRequest::query()->find($requestId);
                    if ($fresh !== null) {
                        $this->activityEmitter->emitSystem(
                            CulturalActivityCatalog::MOD_07,
                            CulturalActivityEventId::of(CulturalActivityCatalog::MOD_07, 'mod', (int) $fresh->id),
                            (int) $fresh->id,
                            $fresh->updated_at ?? now(),
                            [
                                'request_id' => (int) $fresh->id,
                                'organizer_id' => (int) $fresh->organizer_id,
                            ],
                            (int) $fresh->organizer_id,
                        );
                    }
                }
            } catch (Throwable $e) {
                Log::error('PO-ORG-06 eligibility resolver failed for Moderator ADD request.', [
                    'moderator_request_id' => $requestId,
                    'user_id' => $user->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return $resolved;
    }

    private function resolveOrganizerCreationRequestId(int $requestId, User $user, string $normalizedEmail): bool
    {
        return (bool) DB::transaction(function () use ($requestId, $user, $normalizedEmail) {
            /** @var CulturalOrganizerCreationRequest|null $locked */
            $locked = CulturalOrganizerCreationRequest::query()
                ->whereKey($requestId)
                ->lockForUpdate()
                ->first();

            if ($locked === null
                || ! $locked->isAwaitingModeratorEligibility()
                || $locked->proposed_moderator_user_id !== null
                || $locked->proposed_moderator_email !== $normalizedEmail
            ) {
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

    private function resolveModeratorAddRequestId(int $requestId, User $user, string $normalizedEmail): bool
    {
        return (bool) DB::transaction(function () use ($requestId, $user, $normalizedEmail) {
            /** @var CulturalModeratorRequest|null $locked */
            $locked = CulturalModeratorRequest::query()
                ->whereKey($requestId)
                ->lockForUpdate()
                ->first();

            if ($locked === null
                || $locked->type !== CulturalModeratorRequest::TYPE_ADD
                || ! $locked->isAwaitingModeratorEligibility()
                || $locked->target_user_id !== null
                || $locked->proposed_moderator_email !== $normalizedEmail
            ) {
                return false;
            }

            $freshUser = $user->fresh() ?? $user;
            if (! CulturalPortalAccess::isPlatformUserActive($freshUser)) {
                return false;
            }

            if (OrganizerCreationRequestSubmissionService::normalizeEmail((string) $freshUser->email) !== $normalizedEmail) {
                return false;
            }

            $locked->target_user_id = $freshUser->id;
            $locked->status = CulturalModeratorRequest::STATUS_SUBMITTED;
            $locked->save();

            return true;
        });
    }
}
