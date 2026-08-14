<?php

namespace App\Services\CulturalActivity;

use App\Exceptions\CulturalActivityRecordException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Safe facade za F8-03 emitere: audit failure ne smije srušiti poslovnu radnju.
 */
final class CulturalActivityRecorder
{
    public function __construct(
        private readonly CulturalActivityStore $store,
    ) {}

    public function record(CulturalActivityRecordInput $input): CulturalActivityRecordWriteResult
    {
        try {
            return $this->store->write($input);
        } catch (Throwable $e) {
            Log::error('cultural_activity.store_failed', [
                'source_module' => $input->sourceModule,
                'event_id' => $input->eventId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return CulturalActivityRecordWriteResult::failed($e->getMessage());
        }
    }

    /**
     * Strict put za foundation testove / internu dijagnostiku. Ne koristiti iz poslovnog toka.
     */
    public function recordOrFail(CulturalActivityRecordInput $input): CulturalActivityRecordWriteResult
    {
        $result = $this->store->write($input);
        if ($result->wasFailed()) {
            throw new CulturalActivityRecordException($result->error ?? 'Upis Evidencije aktivnosti nije uspio.');
        }

        return $result;
    }
}
