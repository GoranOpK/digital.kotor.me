<?php

namespace App\Services\CulturalCalendar;

use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;

/**
 * Lightweight combined Pretraga hit (presentation/query only — not a domain model).
 */
final class CulturalPublicSearchHit
{
    public function __construct(
        public readonly string $type,
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $temporalKey,
        public readonly CulturalEventEntry|CulturalManifestation|null $model = null,
    ) {}

    public function isEvent(): bool
    {
        return $this->type === CulturalPublicSearchQuery::TYPE_EVENT;
    }

    public function isManifestation(): bool
    {
        return $this->type === CulturalPublicSearchQuery::TYPE_MANIFESTATION;
    }

    public function withModel(CulturalEventEntry|CulturalManifestation|null $model): self
    {
        return new self(
            type: $this->type,
            id: $this->id,
            title: $this->title,
            temporalKey: $this->temporalKey,
            model: $model,
        );
    }
}
