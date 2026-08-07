<?php

namespace App\Services\CulturalEventDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalCategory;
use App\Models\CulturalMedia;
use App\Models\CulturalOrganizer;
use App\Models\CulturalTag;
use Illuminate\Support\Collection;

/**
 * Validacija kataloških referenci za NOVE veze (TS-001 / TS-007 / TS-008).
 * Istorijske veze se ne diraju pri deaktivaciji kataloga.
 */
final class EventCatalogGuard
{
    public function assertOrganizerAllowedForNewLink(?int $organizerId): ?CulturalOrganizer
    {
        if ($organizerId === null) {
            return null;
        }

        $organizer = CulturalOrganizer::query()->find($organizerId);
        if ($organizer === null) {
            throw new CulturalEventDomainException('Organizator ne postoji.');
        }

        if (! $organizer->isActive()) {
            throw new CulturalEventDomainException(
                'Deaktivirani Organizator ne može biti korišćen za novi Događaj.'
            );
        }

        return $organizer;
    }

    public function assertCategoryAllowedForNewLink(?int $categoryId): ?CulturalCategory
    {
        if ($categoryId === null) {
            return null;
        }

        $category = CulturalCategory::query()->find($categoryId);
        if ($category === null) {
            throw new CulturalEventDomainException('Kategorija ne postoji.');
        }

        if (! $category->isActive()) {
            throw new CulturalEventDomainException(
                'Neaktivna Kategorija se ne može izabrati za novu vezu.'
            );
        }

        return $category;
    }

    public function assertCoverMediaAllowedForNewLink(?int $mediaId): ?CulturalMedia
    {
        if ($mediaId === null) {
            return null;
        }

        $media = CulturalMedia::query()->find($mediaId);
        if ($media === null) {
            throw new CulturalEventDomainException('Medij ne postoji.');
        }

        if (! $media->isActive()) {
            throw new CulturalEventDomainException(
                'Neaktivan Medij se ne može vezati kao naslovna fotografija.'
            );
        }

        if ($media->namjena !== CulturalMedia::PURPOSE_EVENT_COVER) {
            throw new CulturalEventDomainException(
                'Naslovna fotografija mora imati namjenu „Naslovna fotografija događaja“.'
            );
        }

        return $media;
    }

    /**
     * @param  list<int>  $tagIds
     * @return Collection<int, CulturalTag>
     */
    public function assertTagsAllowedForNewLinks(array $tagIds): Collection
    {
        $tagIds = array_values(array_unique(array_map('intval', $tagIds)));
        if ($tagIds === []) {
            return collect();
        }

        $tags = CulturalTag::query()->whereIn('id', $tagIds)->get();
        if ($tags->count() !== count($tagIds)) {
            throw new CulturalEventDomainException('Jedna ili više oznaka ne postoji.');
        }

        foreach ($tags as $tag) {
            if (! $tag->isActive()) {
                throw new CulturalEventDomainException(
                    'Neaktivna Oznaka se ne može izabrati za novu vezu.'
                );
            }
        }

        return $tags;
    }
}
