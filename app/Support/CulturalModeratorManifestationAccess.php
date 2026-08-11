<?php

namespace App\Support;

use App\Models\CulturalManifestation;
use App\Models\CulturalOrganizer;
use App\Models\User;

/**
 * Serverska autorizacija Moderator Manifestacija (6B-02).
 * Ovlašćenje + aktivni Organizator + poklapanje sa session kontekstom.
 * Platform MF (organizer_id null) — Moderator nema pristup.
 */
final class CulturalModeratorManifestationAccess
{
    public static function isActiveModerator(User $user): bool
    {
        return CulturalPortalAccess::hasActiveModeratorPortalGrant($user);
    }

    public static function assertActiveModerator(User $user): void
    {
        abort_unless(self::isActiveModerator($user), 403);
    }

    public static function canAccessOrganizer(User $user, CulturalOrganizer $organizer): bool
    {
        return CulturalPortalAccess::canModerateOrganizer($user, $organizer);
    }

    public static function assertCanAccessOrganizer(User $user, CulturalOrganizer $organizer): void
    {
        abort_unless(self::canAccessOrganizer($user, $organizer), 403);
    }

    public static function canAccessManifestation(User $user, CulturalManifestation $manifestation): bool
    {
        if ($manifestation->organizer_id === null) {
            return false;
        }

        $organizer = $manifestation->organizer;
        if ($organizer === null) {
            $organizer = CulturalOrganizer::query()->find($manifestation->organizer_id);
        }

        if ($organizer === null) {
            return false;
        }

        if (! self::canAccessOrganizer($user, $organizer)) {
            return false;
        }

        return CulturalOrganizerContext::matches($user, (int) $manifestation->organizer_id);
    }

    public static function assertCanAccessManifestation(User $user, CulturalManifestation $manifestation): void
    {
        abort_unless(self::canAccessManifestation($user, $manifestation), 403);
    }

    public static function canEditContent(User $user, CulturalManifestation $manifestation): bool
    {
        if (! self::canAccessManifestation($user, $manifestation)) {
            return false;
        }

        return $manifestation->isDraft() || $manifestation->isReturnedForRevision();
    }

    public static function assertCanEditContent(User $user, CulturalManifestation $manifestation): void
    {
        abort_unless(self::canEditContent($user, $manifestation), 403);
    }

    public static function canSubmit(User $user, CulturalManifestation $manifestation): bool
    {
        return self::canEditContent($user, $manifestation);
    }

    public static function assertCanSubmit(User $user, CulturalManifestation $manifestation): void
    {
        abort_unless(self::canSubmit($user, $manifestation), 403);
    }

    public static function canMutateLinks(User $user, CulturalManifestation $manifestation): bool
    {
        if (! self::canAccessManifestation($user, $manifestation)) {
            return false;
        }

        return $manifestation->isDraft()
            || $manifestation->isReturnedForRevision()
            || $manifestation->isPublished();
    }

    public static function assertCanMutateLinks(User $user, CulturalManifestation $manifestation): void
    {
        abort_unless(self::canMutateLinks($user, $manifestation), 403);
    }

    public static function canCancel(User $user, CulturalManifestation $manifestation): bool
    {
        return self::canAccessManifestation($user, $manifestation)
            && $manifestation->isPublished();
    }

    public static function assertCanCancel(User $user, CulturalManifestation $manifestation): void
    {
        abort_unless(self::canCancel($user, $manifestation), 403);
    }
}
