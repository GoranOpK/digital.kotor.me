<?php

namespace App\Services\CulturalCalendar;

use App\Models\CulturalEventEntry;
use Illuminate\Database\Eloquent\Builder;

/**
 * Kanonski SSOT ulaz za javne query-je Faze 6A (PO-TS9-08J / TS-009 §11–12).
 *
 * 6A-02: samo statusna javna vidljivost (fail-closed).
 * Kasniji paketi: next OCC, filteri, active/archive, featured — nadograđuju ovaj baza query.
 */
final class CulturalPublicEventQuery
{
    /**
     * Bazni kanonski public query — uvijek sa statusnom vidljivošću.
     *
     * @return Builder<CulturalEventEntry>
     */
    public function base(): Builder
    {
        return CulturalEventEntry::query()->publiclyVisible();
    }

    /**
     * @return Builder<CulturalEventEntry>
     */
    public function entries(): Builder
    {
        return $this->base();
    }
}
