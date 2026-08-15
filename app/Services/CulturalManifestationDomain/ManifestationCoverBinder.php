<?php

namespace App\Services\CulturalManifestationDomain;

use App\Models\CulturalManifestation;
use App\Models\CulturalMedia;
use App\Models\User;
use App\Services\CulturalEventDomain\EventCoverService;
use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * HTTP sloj: ingest manifestation_cover, persist MF, discard ako persist padne.
 */
final class ManifestationCoverBinder
{
    public function __construct(
        private readonly EventCoverService $covers,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  callable(array<string, mixed>): mixed  $persist
     */
    public function persist(
        array $payload,
        User $actor,
        ?UploadedFile $file,
        bool $remove,
        ?CulturalManifestation $manifestation,
        callable $persist,
    ): mixed {
        $currentId = $manifestation?->cover_media_id !== null ? (int) $manifestation->cover_media_id : null;
        $plan = $this->covers->planDirect(
            $currentId,
            $file,
            $remove,
            $actor,
            CulturalMedia::PURPOSE_MANIFESTATION_COVER,
        );
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
}
