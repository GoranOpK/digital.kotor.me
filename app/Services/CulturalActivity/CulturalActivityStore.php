<?php

namespace App\Services\CulturalActivity;

use App\Models\CulturalActivityRecord;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * Strict TS-012 store. Baca izuzetak na nevalidan input / neočekivanu DB grešku.
 *
 * Pozivni contract za F8-03: nakon uspješnog persist-a poslovne radnje,
 * van poslovne transakcije (ili nakon commit-a). Ne wrap-ovati insert
 * u istu DB transakciju čiji rollback bi poništio poslovnu radnju zbog audita.
 */
class CulturalActivityStore
{
    public function write(CulturalActivityRecordInput $input): CulturalActivityRecordWriteResult
    {
        $existing = $this->findExisting($input);
        if ($existing !== null) {
            return $this->duplicateOrMismatch($existing, $input);
        }

        try {
            $record = $this->insert($input);
        } catch (QueryException $e) {
            if (! $this->isDuplicateKey($e)) {
                throw $e;
            }

            $existing = $this->findExisting($input);
            if ($existing === null) {
                throw $e;
            }

            return $this->duplicateOrMismatch($existing, $input);
        }

        return CulturalActivityRecordWriteResult::inserted($record);
    }

    private function insert(CulturalActivityRecordInput $input): CulturalActivityRecord
    {
        $record = new CulturalActivityRecord;
        $record->forceFill([
            'source_module' => $input->sourceModule,
            'event_id' => $input->eventId,
            'event_type' => $input->eventType,
            'occurred_at' => $input->occurredAt,
            'actor_type' => $input->actor->type,
            'actor_user_id' => $input->actor->userId,
            'target_type' => $input->targetType,
            'target_id' => $input->targetId,
            'organizer_context_id' => $input->organizerContextId,
            'context' => $input->normalizedContext() === [] ? null : $input->normalizedContext(),
        ]);
        $record->save();

        return $record->refresh();
    }

    private function findExisting(CulturalActivityRecordInput $input): ?CulturalActivityRecord
    {
        return CulturalActivityRecord::query()
            ->where('source_module', $input->sourceModule)
            ->where('event_id', $input->eventId)
            ->first();
    }

    private function duplicateOrMismatch(
        CulturalActivityRecord $existing,
        CulturalActivityRecordInput $input
    ): CulturalActivityRecordWriteResult {
        if ($this->matchesFingerprint($existing, $input)) {
            return CulturalActivityRecordWriteResult::duplicate($existing);
        }

        Log::warning('cultural_activity.duplicate_mismatch', [
            'source_module' => $input->sourceModule,
            'event_id' => $input->eventId,
        ]);

        return CulturalActivityRecordWriteResult::mismatch($existing);
    }

    private function matchesFingerprint(
        CulturalActivityRecord $existing,
        CulturalActivityRecordInput $input
    ): bool {
        $incoming = $input->fingerprintPayload();

        $existingContext = $existing->context ?? [];
        if (is_array($existingContext)) {
            ksort($existingContext);
        }

        return $existing->event_type === $incoming['event_type']
            && $existing->actor_type === $incoming['actor_type']
            && $this->sameNullableId($existing->actor_user_id, $incoming['actor_user_id'])
            && $existing->target_type === $incoming['target_type']
            && $this->sameNullableId($existing->target_id, $incoming['target_id'])
            && $this->sameNullableId($existing->organizer_context_id, $incoming['organizer_context_id'])
            && $existingContext === $incoming['context'];
    }

    private function sameNullableId(mixed $stored, ?int $incoming): bool
    {
        if ($stored === null || $stored === '') {
            return $incoming === null;
        }

        return $incoming !== null && (int) $stored === $incoming;
    }

    private function isDuplicateKey(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = $e->errorInfo[1] ?? null;

        return $sqlState === '23000' || $driverCode === 1062;
    }
}
