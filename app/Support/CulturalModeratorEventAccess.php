<?php

namespace App\Support;

use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\User;

/**
 * TS-010.1 — serverska autorizacija Moderator Event/Occurrence akcija.
 * Ovlašćenje + aktivni Organizator + poklapanje sa session kontekstom.
 */
final class CulturalModeratorEventAccess
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

    /**
     * Event mora imati organizer_id, korisnik mora imati aktivno ovlašćenje nad tim Org,
     * Organizator mora biti aktivan, i Org mora biti trenutni aktivni session kontekst.
     */
    public static function canAccessEntry(User $user, CulturalEventEntry $entry): bool
    {
        if ($entry->organizer_id === null) {
            return false;
        }

        $organizer = $entry->organizer;
        if ($organizer === null) {
            $organizer = CulturalOrganizer::query()->find($entry->organizer_id);
        }

        if ($organizer === null) {
            return false;
        }

        if (! self::canAccessOrganizer($user, $organizer)) {
            return false;
        }

        return CulturalOrganizerContext::matches($user, (int) $entry->organizer_id);
    }

    public static function assertCanAccessEntry(User $user, CulturalEventEntry $entry): void
    {
        abort_unless(self::canAccessEntry($user, $entry), 403);
    }

    public static function canEditDraft(User $user, CulturalEventEntry $entry): bool
    {
        return self::canAccessEntry($user, $entry) && $entry->isDraft();
    }

    public static function assertCanEditDraft(User $user, CulturalEventEntry $entry): void
    {
        abort_unless(self::canEditDraft($user, $entry), 403);
    }

    public static function canSubmit(User $user, CulturalEventEntry $entry): bool
    {
        return self::canEditDraft($user, $entry);
    }

    public static function assertCanSubmit(User $user, CulturalEventEntry $entry): void
    {
        abort_unless(self::canSubmit($user, $entry), 403);
    }

    public static function assertOccurrenceBelongsToEntry(
        CulturalEventEntry $entry,
        CulturalOccurrence $occurrence,
    ): void {
        abort_unless($occurrence->event_entry_id === $entry->id, 404);
    }

    public static function assertCanMutateOccurrence(
        User $user,
        CulturalEventEntry $entry,
        CulturalOccurrence $occurrence,
    ): void {
        self::assertOccurrenceBelongsToEntry($entry, $occurrence);
        self::assertCanEditDraft($user, $entry);
    }
}
