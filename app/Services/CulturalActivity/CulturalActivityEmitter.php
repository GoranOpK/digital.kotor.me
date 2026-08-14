<?php

namespace App\Services\CulturalActivity;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * F8-03 canonical emitter helper. Never throws into the business flow.
 */
final class CulturalActivityEmitter
{
    public function __construct(
        private readonly CulturalActivityRecorder $recorder,
    ) {}

    /**
     * @param  array<string, scalar|null>  $context
     */
    public function emitUser(
        string $catalogId,
        string $eventId,
        User $actor,
        ?int $targetId,
        CarbonInterface $occurredAt,
        array $context = [],
        ?int $organizerContextId = null,
    ): void {
        $this->emit($catalogId, $eventId, CulturalActivityActor::user($actor), $targetId, $occurredAt, $context, $organizerContextId);
    }

    /**
     * @param  array<string, scalar|null>  $context
     */
    public function emitSystem(
        string $catalogId,
        string $eventId,
        ?int $targetId,
        CarbonInterface $occurredAt,
        array $context = [],
        ?int $organizerContextId = null,
    ): void {
        $this->emit($catalogId, $eventId, CulturalActivityActor::system(), $targetId, $occurredAt, $context, $organizerContextId);
    }

    /**
     * @param  array<string, scalar|null>  $context
     */
    private function emit(
        string $catalogId,
        string $eventId,
        CulturalActivityActor $actor,
        ?int $targetId,
        CarbonInterface $occurredAt,
        array $context,
        ?int $organizerContextId,
    ): void {
        try {
            $row = CulturalActivityCatalog::row($catalogId);
            $this->recorder->record(new CulturalActivityRecordInput(
                sourceModule: $row['source'],
                eventId: $eventId,
                eventType: $row['type'],
                occurredAt: $occurredAt,
                actor: $actor,
                targetType: $row['target'],
                targetId: $targetId,
                organizerContextId: $organizerContextId,
                context: $context,
            ));
        } catch (Throwable $e) {
            Log::error('cultural_activity.emit_failed', [
                'catalog_id' => $catalogId,
                'event_id' => $eventId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
