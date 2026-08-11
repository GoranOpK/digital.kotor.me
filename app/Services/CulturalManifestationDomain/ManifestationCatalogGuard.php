<?php

namespace App\Services\CulturalManifestationDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalMedia;
use App\Models\CulturalOrganizer;

final class ManifestationCatalogGuard
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
                'Deaktivirani Organizator ne može biti korišćen za novu vezu Manifestacije.'
            );
        }

        return $organizer;
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
                'Neaktivan Medij se ne može vezati kao naslovna fotografija Manifestacije.'
            );
        }

        if ($media->namjena !== CulturalMedia::PURPOSE_MANIFESTATION_COVER) {
            throw new CulturalEventDomainException(
                'Naslovna fotografija Manifestacije mora imati namjenu „Naslovna fotografija manifestacije“.'
            );
        }

        return $media;
    }
}

