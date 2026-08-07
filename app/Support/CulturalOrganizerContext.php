<?php

namespace App\Support;

use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOrganizer;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * TS-010.1 — aktivni Organizator kontekst za Moderatora (session).
 * Urednik ne koristi ovaj kontekst.
 */
final class CulturalOrganizerContext
{
    public const SESSION_KEY = 'cultural_active_organizer_id';

    /**
     * @return Collection<int, CulturalOrganizer>
     */
    public static function availableOrganizers(User $user): Collection
    {
        if (! CulturalPortalAccess::isPlatformUserActive($user)) {
            return collect();
        }

        return CulturalOrganizer::query()
            ->where('status', CulturalOrganizer::STATUS_ACTIVE)
            ->whereIn('id', CulturalModeratorAuthorization::query()
                ->where('user_id', $user->id)
                ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
                ->select('organizer_id'))
            ->orderBy('naziv')
            ->orderBy('id')
            ->get();
    }

    public static function get(User $user): ?CulturalOrganizer
    {
        $available = self::availableOrganizers($user);
        if ($available->isEmpty()) {
            self::clear();

            return null;
        }

        $sessionId = Session::get(self::SESSION_KEY);
        if ($sessionId !== null) {
            $fromSession = $available->firstWhere('id', (int) $sessionId);
            if ($fromSession !== null) {
                return $fromSession;
            }
            self::clear();
        }

        if ($available->count() === 1) {
            $only = $available->first();
            Session::put(self::SESSION_KEY, $only->id);

            return $only;
        }

        return null;
    }

    public static function require(User $user): CulturalOrganizer
    {
        $organizer = self::get($user);
        abort_if($organizer === null, 403, 'Aktivni Organizator kontekst nije izabran.');

        return $organizer;
    }

    public static function set(User $user, int $organizerId): CulturalOrganizer
    {
        $organizer = CulturalOrganizer::query()->findOrFail($organizerId);
        abort_unless(
            CulturalPortalAccess::canModerateOrganizer($user, $organizer),
            403,
            'Nemate aktivno ovlašćenje za izabrani Organizator.'
        );

        Session::put(self::SESSION_KEY, $organizer->id);

        return $organizer;
    }

    public static function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public static function matches(User $user, int $organizerId): bool
    {
        $active = self::get($user);

        return $active !== null && (int) $active->id === $organizerId;
    }
}
