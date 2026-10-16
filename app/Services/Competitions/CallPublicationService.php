<?php

namespace App\Services\Competitions;

use App\Models\Competition;
use App\Models\CompetitionPublicationClaim;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfilePolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Authoritative CALL publication path for guarded/unrestricted profiles.
 *
 * Quarter, slot, duration, and day-counting come from the resolved
 * profile policy. This service does not own those values.
 * Youth annual-instance publication stays outside this service.
 */
class CallPublicationService
{
    public function publish(Competition $competition, ?User $actor): CallPublicationResult
    {
        if (! $this->isAdmin($actor)) {
            return CallPublicationResult::failed('Nemate dozvolu za objavljivanje konkursa.');
        }

        try {
            return DB::transaction(function () use ($competition) {
                return $this->publishLocked($competition);
            });
        } catch (QueryException $exception) {
            if ($this->isSlotUniqueViolation($exception)) {
                return CallPublicationResult::failed('Objava za ovaj slot je već izvršena.');
            }

            throw $exception;
        }
    }

    private function publishLocked(Competition $competition): CallPublicationResult
    {
        $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->firstOrFail();
        $policy = $locked->profile();
        $now = CarbonImmutable::now();

        $existingClaim = CompetitionPublicationClaim::query()
            ->where('competition_id', $locked->id)
            ->lockForUpdate()
            ->first();

        if ($existingClaim && $locked->status === 'published') {
            return CallPublicationResult::alreadyPublished();
        }

        if ($existingClaim) {
            return CallPublicationResult::failed('Objava za ovaj slot je već izvršena.');
        }

        if ($locked->status !== 'draft') {
            return CallPublicationResult::failed('Samo nacrti konkursa mogu biti objavljeni.');
        }

        $mode = $policy->publicationMode();
        $slot = null;
        $calendarYear = null;

        if ($mode === CompetitionProfilePolicy::PUBLICATION_INACTIVE) {
            return CallPublicationResult::failed(
                $policy->isFailClosed()
                    ? 'Objava nije dozvoljena za ovaj profil.'
                    : 'Objava nije aktivirana za ovaj profil.'
            );
        }

        if ($mode === CompetitionProfilePolicy::PUBLICATION_GUARDED) {
            if (! $policy->isPublicationAllowedAt($now)) {
                return CallPublicationResult::failed('Objava nije dozvoljena u tekućem periodu za ovaj profil.');
            }

            $slot = $policy->publicationSlotFor('primary');
            if ($slot === null || $slot === '') {
                return CallPublicationResult::failed('Profil ne definiše slot objave.');
            }

            $calendarYear = $policy->publicationCalendarYear($now);
            if ((int) $locked->year !== $calendarYear) {
                return CallPublicationResult::failed('Godina konkursa ne odgovara kalendarskoj godini objave.');
            }
        } elseif ($mode !== CompetitionProfilePolicy::PUBLICATION_UNRESTRICTED) {
            return CallPublicationResult::failed('Objava nije dozvoljena za ovaj profil.');
        }

        $start = $locked->start_date
            ? CarbonImmutable::parse($locked->start_date->toDateString())
            : $policy->publicationCalendarDate($now);
        $end = $policy->publicationEndDate($start);
        $days = $policy->deadlineDays();

        if ($end === null || $days === null) {
            return CallPublicationResult::failed('Profil ne definiše trajanje objave.');
        }

        if ($mode === CompetitionProfilePolicy::PUBLICATION_GUARDED) {
            CompetitionPublicationClaim::query()->create([
                'competition_id' => $locked->id,
                'calendar_year' => $calendarYear,
                'competition_type' => $locked->type,
                'publication_slot' => $slot,
                'claimed_at' => $now,
            ]);
        }

        $locked->update([
            'status' => 'published',
            'published_at' => $now,
            'deadline_days' => $days,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ]);

        return CallPublicationResult::published();
    }

    private function isAdmin(?User $actor): bool
    {
        $role = $actor?->role?->name;

        return in_array($role, ['admin', 'konkurs_admin', 'superadmin'], true);
    }

    private function isSlotUniqueViolation(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return (string) $exception->getCode() === '23000'
            || str_contains($message, 'publication_claims_year_type_slot_unique')
            || str_contains($message, 'publication_claims_competition_unique')
            || str_contains($message, 'Duplicate entry');
    }
}
