<?php

namespace App\Services\Competitions\Decision;

use App\Models\CanonicalAdministrativeEvent;
use App\Models\CanonicalPredlog;
use App\Models\CanonicalSecretariatHandoff;
use App\Models\Competition;
use App\Models\User;
use App\Services\Competitions\Outcome\CanonicalPredlogService;
use App\Services\Competitions\ProcessWriteResult;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * KN-SYNC-009 — explicit handoff of the finalized Predlog to the responsible Secretariat.
 * Not a publication; creates no Notice, Odluka, contract or payment.
 */
final class CanonicalSecretariatHandoffService
{
    public const RECIPIENT_REQUIRED_MESSAGE = 'Nadležni Sekretarijat (primalac) je obavezan.';

    public const DUPLICATE_MESSAGE = 'Predlog je već predat Sekretarijatu.';

    public function __construct(
        private readonly CanonicalAdministrativeGate $gate,
        private readonly CanonicalPredlogService $predlogs,
    ) {
    }

    public function blockReason(Competition $competition): ?string
    {
        $base = $this->gate->baseBlockReason($competition);
        if ($base !== null) {
            return $base;
        }

        $predlogReason = $this->predlogs->finalizedPredlogBlockReason($competition);
        if ($predlogReason !== null) {
            return $predlogReason;
        }

        if ($this->handoffFor($competition) !== null) {
            return self::DUPLICATE_MESSAGE;
        }

        return null;
    }

    public function handOff(Competition $competition, User $actor, string $recipient, ?string $reference = null, ?string $note = null): ProcessWriteResult
    {
        $recipient = trim($recipient);
        $reference = self::nullableTrim($reference);
        $note = self::nullableTrim($note);

        $base = $this->gate->baseBlockReason($competition);
        if ($base !== null) {
            return ProcessWriteResult::failed($base);
        }

        $actorReason = $this->gate->chairBlockReason($actor, $competition);
        if ($actorReason !== null) {
            return ProcessWriteResult::failed($actorReason);
        }

        if ($recipient === '') {
            return ProcessWriteResult::failed(self::RECIPIENT_REQUIRED_MESSAGE);
        }

        try {
            return DB::transaction(function () use ($competition, $actor, $recipient, $reference, $note) {
                $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->firstOrFail();

                $reason = $this->blockReason($locked);
                if ($reason !== null) {
                    return ProcessWriteResult::failed($reason);
                }

                $predlog = CanonicalPredlog::query()->where('competition_id', $locked->id)->firstOrFail();

                $handoff = CanonicalSecretariatHandoff::query()->create([
                    'competition_id' => $locked->id,
                    'predlog_id' => $predlog->id,
                    'recipient' => $recipient,
                    'reference' => $reference,
                    'note' => $note,
                    'handed_off_by_user_id' => $actor->id,
                    'handed_off_at' => now(),
                ]);

                $this->gate->audit($locked, CanonicalAdministrativeEvent::SECRETARIAT_HANDOFF, $actor, $handoff, [
                    'predlog_id' => (int) $predlog->id,
                    'recipient' => $recipient,
                    'reference' => $reference,
                ]);

                return ProcessWriteResult::ok($handoff->id);
            });
        } catch (UniqueConstraintViolationException) {
            return ProcessWriteResult::failed(self::DUPLICATE_MESSAGE);
        }
    }

    public function handoffFor(Competition $competition): ?CanonicalSecretariatHandoff
    {
        return CanonicalSecretariatHandoff::query()->where('competition_id', $competition->id)->first();
    }

    private static function nullableTrim(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
