<?php

namespace App\Support;

use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOrganizer;
use App\Models\User;

/**
 * PO-ORG-04 — pristup uredničkom portalu bez nove platformske uloge.
 */
final class CulturalPortalAccess
{
    public static function isKkEditor(?User $user): bool
    {
        if (! $user || ! $user->role) {
            return false;
        }

        return in_array($user->role->name, ['kk_admin', 'superadmin'], true);
    }

    public static function isPlatformUserActive(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return ($user->activation_status ?? 'active') === 'active'
            && $user->email_verified_at !== null;
    }

    /**
     * Pristup privileged KK površinama: kk_admin/superadmin ili aktivno ovlašćenje na aktivnom Org.
     */
    public static function allows(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (self::isKkEditor($user)) {
            return true;
        }

        return self::hasActiveModeratorPortalGrant($user);
    }

    public static function hasActiveModeratorPortalGrant(User $user): bool
    {
        if (! self::isPlatformUserActive($user)) {
            return false;
        }

        return CulturalModeratorAuthorization::query()
            ->where('user_id', $user->id)
            ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
            ->whereHas('organizer', function ($query) {
                $query->where('status', CulturalOrganizer::STATUS_ACTIVE);
            })
            ->exists();
    }

    /**
     * Aktivan Moderator konkretnog aktivnog Organizatora (za podnošenje Mod zahtjeva).
     */
    public static function canModerateOrganizer(User $user, CulturalOrganizer $organizer): bool
    {
        if (! $organizer->isActive() || ! self::isPlatformUserActive($user)) {
            return false;
        }

        return CulturalModeratorAuthorization::query()
            ->where('user_id', $user->id)
            ->where('organizer_id', $organizer->id)
            ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
            ->exists();
    }

    public static function activeModeratorCount(CulturalOrganizer $organizer): int
    {
        return CulturalModeratorAuthorization::query()
            ->where('organizer_id', $organizer->id)
            ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
            ->count();
    }
}
