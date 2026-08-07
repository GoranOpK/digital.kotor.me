<?php

namespace App\Services\CulturalMedia;

use App\Models\CulturalMedia;

/**
 * Provjera poslovnih veza prije hard-delete (TS-008).
 * Korak 1: delegira na model (uvijek 0 veza); kasnije se proširuje bez izmjene delete toka.
 */
class CulturalMediaLinkInspector
{
    public function hasLinks(CulturalMedia $media): bool
    {
        return $media->hasBusinessLinks();
    }
}
