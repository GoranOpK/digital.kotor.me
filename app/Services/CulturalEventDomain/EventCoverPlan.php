<?php

namespace App\Services\CulturalEventDomain;

use App\Models\CulturalMedia;

/**
 * MED-I2 — plan cover mutacije prije Event/proposal persist-a.
 */
final class EventCoverPlan
{
    public function __construct(
        public readonly bool $changed,
        public readonly ?int $nextCoverMediaId,
        public readonly ?int $obsoleteMediaId,
        public readonly ?CulturalMedia $ingested,
    ) {}

    public static function unchanged(): self
    {
        return new self(false, null, null, null);
    }
}
