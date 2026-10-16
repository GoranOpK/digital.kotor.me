<?php

namespace App\Services\Competitions\Decision;

use App\Models\CanonicalAdministrativeEvent;
use App\Models\Competition;
use App\Models\User;
use App\Services\Competitions\Outcome\CanonicalPredlogService;
use App\Services\Competitions\ProcessWriteResult;
use Illuminate\Support\Facades\DB;

/**
 * KN-SYNC-009 — close/archive of a canonical annual call after the signed formal Odluka is public.
 * Ranks, outcomes and amounts are never rewritten on close. Residual funds stay with the
 * Municipality; there is no reopen, no second call and no later withdrawal reallocation.
 */
final class CanonicalCloseService
{
    public const DEADLINE_MESSAGE = 'Poziv se ne može zatvoriti prije isteka roka za prijave.';

    public const HANDOFF_REQUIRED_MESSAGE = 'Zatvaranje zahtijeva predaju finalizovanog Predloga Sekretarijatu.';

    public function __construct(
        private readonly CanonicalAdministrativeGate $gate,
        private readonly CanonicalSecretariatHandoffService $handoffs,
        private readonly CanonicalResultPublicationService $publication,
        private readonly CanonicalPredlogService $predlogs,
    ) {
    }

    public function blockReason(Competition $competition): ?string
    {
        $base = $this->gate->baseBlockReason($competition);
        if ($base !== null) {
            return $base;
        }

        $deadline = $competition->deadline;
        if (! $deadline || ! now()->isAfter($deadline)) {
            return self::DEADLINE_MESSAGE;
        }

        $predlogReason = $this->predlogs->finalizedPredlogBlockReason($competition);
        if ($predlogReason !== null) {
            return $predlogReason;
        }

        if ($this->handoffs->handoffFor($competition) === null) {
            return self::HANDOFF_REQUIRED_MESSAGE;
        }

        return $this->publication->publishedBlockReason($competition);
    }

    public function close(Competition $competition, User $actor): ProcessWriteResult
    {
        $base = $this->gate->baseBlockReason($competition);
        if ($base !== null) {
            return ProcessWriteResult::failed($base);
        }

        $actorReason = $this->gate->chairBlockReason($actor, $competition);
        if ($actorReason !== null) {
            return ProcessWriteResult::failed($actorReason);
        }

        return DB::transaction(function () use ($competition, $actor) {
            $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->firstOrFail();

            $reason = $this->blockReason($locked);
            if ($reason !== null) {
                return ProcessWriteResult::failed($reason);
            }

            $locked->forceFill([
                'status' => 'completed',
                'closed_at' => now(),
            ])->save();

            $this->gate->audit($locked, CanonicalAdministrativeEvent::COMPETITION_CLOSED, $actor, $locked, [
                'status' => 'completed',
            ]);

            return ProcessWriteResult::ok($locked->id);
        });
    }
}
