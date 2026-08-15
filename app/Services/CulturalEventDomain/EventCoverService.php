<?php

namespace App\Services\CulturalEventDomain;

use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\CulturalMedia;
use App\Models\User;
use App\Services\CulturalMedia\CulturalMediaIngestor;
use App\Services\CulturalMedia\CulturalMediaStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * MED-I2 — Event cover ingest + MED-21/22 cleanup (bez Media katalog reuse-a).
 */
final class EventCoverService
{
    public function __construct(
        private readonly CulturalMediaIngestor $ingestor,
        private readonly CulturalMediaStorage $storage,
    ) {}

    public function planDirect(?int $currentCoverId, ?UploadedFile $file, bool $remove, User $actor): EventCoverPlan
    {
        if ($file !== null) {
            $ingested = $this->ingestEventCover($file, $actor);

            return new EventCoverPlan(
                true,
                (int) $ingested->id,
                $currentCoverId,
                $ingested,
            );
        }

        if ($remove && $currentCoverId !== null) {
            return new EventCoverPlan(true, null, $currentCoverId, null);
        }

        return EventCoverPlan::unchanged();
    }

    public function planProposal(
        CulturalEventChangeProposal $proposal,
        ?UploadedFile $file,
        bool $remove,
        User $actor,
    ): EventCoverPlan {
        $liveId = $proposal->eventEntry?->cover_media_id !== null
            ? (int) $proposal->eventEntry->cover_media_id
            : null;
        $proposedId = $proposal->proposed_cover_media_id !== null
            ? (int) $proposal->proposed_cover_media_id
            : null;

        if ($file !== null) {
            $ingested = $this->ingestEventCover($file, $actor);
            $obsolete = ($proposedId !== null && $proposedId !== $liveId && $proposedId !== (int) $ingested->id)
                ? $proposedId
                : null;

            return new EventCoverPlan(true, (int) $ingested->id, $obsolete, $ingested);
        }

        if ($remove && $proposedId !== null) {
            $obsolete = ($proposedId !== $liveId) ? $proposedId : null;

            return new EventCoverPlan(true, null, $obsolete, null);
        }

        return EventCoverPlan::unchanged();
    }

    public function ingestEventCover(UploadedFile $file, User $actor): CulturalMedia
    {
        try {
            return $this->ingestor->ingest($file, [
                'namjena' => CulturalMedia::PURPOSE_EVENT_COVER,
                'creator_id' => $actor->id,
            ]);
        } catch (ValidationException $e) {
            $messages = $e->errors();
            if (isset($messages['fajl'])) {
                throw ValidationException::withMessages([
                    'cover_file' => $messages['fajl'],
                ]);
            }

            throw $e;
        }
    }

    public function discardIngested(EventCoverPlan $plan): void
    {
        if ($plan->ingested === null) {
            return;
        }

        $this->deleteUnreferenced((int) $plan->ingested->id);
    }

    /**
     * MED-22: Event/proposal vrijednost je već commitovana; cleanup failure se ne rollbackuje.
     *
     * @param  list<int>  $exceptProposalIds
     * @param  list<int>  $exceptEventIds
     */
    public function deleteUnreferenced(
        ?int $mediaId,
        array $exceptProposalIds = [],
        array $exceptEventIds = [],
    ): void {
        if ($mediaId === null || $mediaId < 1) {
            return;
        }

        $media = CulturalMedia::query()->find($mediaId);
        if ($media === null) {
            return;
        }

        if ($this->isReferenced((int) $media->id, $exceptProposalIds, $exceptEventIds)) {
            return;
        }

        $path = (string) $media->storage_path;

        try {
            $media->delete();
        } catch (Throwable $e) {
            Log::warning('cultural_media_cleanup_db_failed', [
                'media_id' => $mediaId,
                'storage_path' => $path,
                'message' => $e->getMessage(),
            ]);

            return;
        }

        try {
            $this->storage->deletePath($path);
        } catch (Throwable $e) {
            Log::warning('cultural_media_cleanup_file_failed', [
                'media_id' => $mediaId,
                'storage_path' => $path,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  list<int>  $exceptProposalIds
     * @param  list<int>  $exceptEventIds
     */
    public function isReferenced(int $mediaId, array $exceptProposalIds = [], array $exceptEventIds = []): bool
    {
        $eventQuery = CulturalEventEntry::query()->where('cover_media_id', $mediaId);
        if ($exceptEventIds !== []) {
            $eventQuery->whereNotIn('id', $exceptEventIds);
        }
        if ($eventQuery->exists()) {
            return true;
        }

        if (CulturalManifestation::query()->where('cover_media_id', $mediaId)->exists()) {
            return true;
        }

        $proposalQuery = CulturalEventChangeProposal::query()->where('proposed_cover_media_id', $mediaId);
        if ($exceptProposalIds !== []) {
            $proposalQuery->whereNotIn('id', $exceptProposalIds);
        }

        return $proposalQuery->exists();
    }
}
