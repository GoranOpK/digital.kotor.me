<?php

namespace App\Services\Competitions\Lifecycle;

use App\Models\Application;
use App\Models\CanonicalAdministrativeEvent;
use App\Models\CanonicalPredlog;
use App\Models\Competition;
use App\Models\FinalApplicationClaim;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Carbon\Carbon;

/**
 * Generic admin edit/delete guard for profiles whose published call is fixed
 * (CompetitionProfilePolicy::locksPublishedCall). Such a call moves forward only through
 * the profile's own publication and close services; the generic edit can never reopen it.
 */
final class PublishedCallLifecycleLock
{
    public const TYPE_LOCKED_MESSAGE = 'Profil objavljenog Poziva se ne može mijenjati.';

    public const TYPE_TARGET_LOCKED_MESSAGE = 'Već objavljeni konkurs ne može se naknadno prevesti u kanonski MMSP profil.';

    public const YEAR_LOCKED_MESSAGE = 'Godina objavljenog Poziva se ne može mijenjati.';

    public const BUDGET_LOCKED_MESSAGE = 'Budžet objavljenog Poziva se ne može mijenjati.';

    public const REFERENCE_LOCKED_MESSAGE = 'Zavodni (UP) broj objavljenog Poziva se ne može mijenjati.';

    public const WINDOW_LOCKED_MESSAGE = 'Rok za prijave objavljenog Poziva je fiksan: datum početka se ne može mijenjati.';

    public const STATUS_LOCKED_MESSAGE = 'Objavljeni Poziv se ne može vratiti u nacrt niti ručno zatvoriti; zatvara se samo kroz kanonsko zatvaranje.';

    public const DELETE_PREDLOG_MESSAGE = 'Kanonski Poziv sa Predlogom je dio arhive i ne može se obrisati.';

    public const DELETE_PUBLISHED_MESSAGE = 'Objavljeni kanonski Poziv ne može se obrisati.';

    public const DELETE_APPLICATIONS_MESSAGE = 'Kanonski Poziv koji ima prijave ne može se obrisati.';

    public const DELETE_AUDITED_MESSAGE = 'Kanonski Poziv sa evidentiranim radnjama upravljanja (npr. Komisija) ne može se obrisati.';

    public function locks(Competition $competition): bool
    {
        return $competition->profile()->locksPublishedCall();
    }

    /**
     * All protected-field violations of one edit request. Any error rejects the whole write.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, string>
     */
    public function editErrors(Competition $competition, array $validated): array
    {
        if ($competition->status === 'draft') {
            return [];
        }

        if (! $this->locks($competition)) {
            return CompetitionProfileResolver::resolve($validated['type'] ?? null)->locksPublishedCall()
                ? ['type' => self::TYPE_TARGET_LOCKED_MESSAGE]
                : [];
        }

        $errors = [];

        if (($validated['type'] ?? null) !== $competition->type) {
            $errors['type'] = self::TYPE_LOCKED_MESSAGE;
        }

        if ((int) ($validated['year'] ?? 0) !== (int) $competition->year) {
            $errors['year'] = self::YEAR_LOCKED_MESSAGE;
        }

        if (! $this->decimalEquals($validated['budget'] ?? null, $competition->budget)) {
            $errors['budget'] = self::BUDGET_LOCKED_MESSAGE;
        }

        $reference = $competition->upNumber()->value('number');
        if ((string) ($validated['up_number'] ?? '') !== (string) ($reference ?? '')) {
            $errors['up_number'] = self::REFERENCE_LOCKED_MESSAGE;
        }

        if (! empty($validated['start_date'])
            && Carbon::parse($validated['start_date'])->toDateString() !== $competition->start_date?->toDateString()) {
            $errors['start_date'] = self::WINDOW_LOCKED_MESSAGE;
        }

        if (($validated['status'] ?? null) !== $competition->status) {
            $errors['status'] = self::STATUS_LOCKED_MESSAGE;
        }

        return $errors;
    }

    public function deleteBlockReason(Competition $competition): ?string
    {
        if (! $this->locks($competition)) {
            return null;
        }

        if (CanonicalPredlog::query()->where('competition_id', $competition->id)->exists()) {
            return self::DELETE_PREDLOG_MESSAGE;
        }

        if ($competition->status !== 'draft') {
            return self::DELETE_PUBLISHED_MESSAGE;
        }

        if (Application::query()->where('competition_id', $competition->id)->exists()
            || FinalApplicationClaim::query()->where('competition_id', $competition->id)->exists()) {
            return self::DELETE_APPLICATIONS_MESSAGE;
        }

        if (CanonicalAdministrativeEvent::query()->where('competition_id', $competition->id)->exists()) {
            return self::DELETE_AUDITED_MESSAGE;
        }

        return null;
    }

    private function decimalEquals(mixed $left, mixed $right): bool
    {
        if ($left === null || $right === null || $left === '' || $right === '') {
            return $left === $right;
        }

        return bccomp(bcadd((string) $left, '0', 2), bcadd((string) $right, '0', 2), 2) === 0;
    }
}
