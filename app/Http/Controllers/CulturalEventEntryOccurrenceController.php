<?php

namespace App\Http\Controllers;

use App\Exceptions\CulturalEventDomainException;
use App\Http\Requests\CulturalOccurrenceStoreRequest;
use App\Http\Requests\CulturalOccurrenceUpdateRequest;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use Illuminate\Http\RedirectResponse;

/**
 * Sprint 3A.2 — Održavanja unutar Draft Event UI (N-TR-04 za delete).
 */
class CulturalEventEntryOccurrenceController extends Controller
{
    public function __construct(
        private readonly OccurrenceWriter $occurrenceWriter,
    ) {}

    public function store(
        CulturalOccurrenceStoreRequest $request,
        CulturalEventEntry $kanonski_dogadjaj,
    ): RedirectResponse {
        if (! $kanonski_dogadjaj->isDraft()) {
            return redirect()
                ->route('cultural-event-entries.index')
                ->withErrors(['domain' => 'Održavanja se mogu mijenjati samo dok je Događaj Nacrt.']);
        }

        try {
            $this->occurrenceWriter->create($kanonski_dogadjaj, $request->domainPayload());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['occurrence' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-entries.edit', $kanonski_dogadjaj)
            ->with('status', 'Održavanje je dodato.');
    }

    public function update(
        CulturalOccurrenceUpdateRequest $request,
        CulturalEventEntry $kanonski_dogadjaj,
        CulturalOccurrence $odrzavanje,
    ): RedirectResponse {
        $this->assertBelongsToDraft($kanonski_dogadjaj, $odrzavanje);

        try {
            $this->occurrenceWriter->update($odrzavanje, $request->domainPayload());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['occurrence' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-entries.edit', $kanonski_dogadjaj)
            ->with('status', 'Održavanje je ažurirano.');
    }

    public function destroy(
        CulturalEventEntry $kanonski_dogadjaj,
        CulturalOccurrence $odrzavanje,
    ): RedirectResponse {
        $this->assertBelongsToDraft($kanonski_dogadjaj, $odrzavanje);

        try {
            $this->occurrenceWriter->deletePhysically($odrzavanje);
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->route('cultural-event-entries.edit', $kanonski_dogadjaj)
                ->withErrors(['occurrence' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-entries.edit', $kanonski_dogadjaj)
            ->with('status', 'Održavanje je uklonjeno.');
    }

    private function assertBelongsToDraft(
        CulturalEventEntry $entry,
        CulturalOccurrence $occurrence,
    ): void {
        abort_unless($occurrence->event_entry_id === $entry->id, 404);
        abort_unless($entry->isDraft(), 404);
    }
}
