<?php

namespace App\Services\CulturalEventDomain;

use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventEntry;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * HTTP sloj: ingest nove fotografije, persist Event/proposal, discard ako persist padne.
 */
final class EventCoverBinder
{
    public function __construct(
        private readonly EventCoverService $covers,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  callable(array<string, mixed>): mixed  $persist
     */
    public function persistDirectEvent(
        array $payload,
        User $actor,
        ?UploadedFile $file,
        bool $remove,
        ?CulturalEventEntry $entry,
        callable $persist,
    ): mixed {
        $currentId = $entry?->cover_media_id !== null ? (int) $entry->cover_media_id : null;
        $plan = $this->covers->planDirect($currentId, $file, $remove, $actor);
        if ($plan->changed) {
            $payload['cover_media_id'] = $plan->nextCoverMediaId;
        }

        try {
            return $persist($payload);
        } catch (Throwable $e) {
            $this->covers->discardIngested($plan);
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  callable(array<string, mixed>): mixed  $persist
     */
    public function persistProposal(
        array $payload,
        User $actor,
        ?UploadedFile $file,
        bool $remove,
        CulturalEventChangeProposal $proposal,
        callable $persist,
    ): mixed {
        $proposal->loadMissing('eventEntry');
        $plan = $this->covers->planProposal($proposal, $file, $remove, $actor);
        if ($plan->changed) {
            $payload['proposed_cover_media_id'] = $plan->nextCoverMediaId;
        }

        try {
            return $persist($payload);
        } catch (Throwable $e) {
            $this->covers->discardIngested($plan);
            throw $e;
        }
    }
}
