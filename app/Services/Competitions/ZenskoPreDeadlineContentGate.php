<?php

namespace App\Services\Competitions;

use App\Models\Competition;

/**
 * Serverska zabrana uvida u pojedinačnu žensku prijavu dok stvarni rok nije istekao.
 * Status konkursa ne zamjenjuje kalendarski rok.
 */
class ZenskoPreDeadlineContentGate
{
    public const INDIVIDUAL_CONTENT_LOCKED_MESSAGE = 'Pojedinačna prijava nije dostupna prije isteka roka za prijavljivanje.';

    public static function individualContentIsLocked(Competition $competition): bool
    {
        if ($competition->type !== 'zensko') {
            return false;
        }

        $deadline = $competition->deadline;
        if ($deadline === null) {
            return true;
        }

        return ! now()->isAfter($deadline);
    }

    /**
     * Zbir ženskih prijava ostaje skriven članici Komisije dok traje stvarni rok,
     * i poslije roka dok Komisija nije potpuna i valjana.
     * Druge profile konkursa ova provjera ne zadržava.
     */
    public static function commissionApplicationCountIsWithheld(Competition $competition): bool
    {
        if ($competition->type !== 'zensko') {
            return false;
        }

        if (self::individualContentIsLocked($competition)) {
            return true;
        }

        return ! $competition->hasCompleteValidCommission();
    }

    /**
     * @return list<int>
     */
    public static function lockedCompetitionIds(): array
    {
        return Competition::query()
            ->where('type', 'zensko')
            ->get(['id', 'type', 'status', 'start_date', 'deadline_days', 'published_at'])
            ->filter(fn (Competition $competition) => self::individualContentIsLocked($competition))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
