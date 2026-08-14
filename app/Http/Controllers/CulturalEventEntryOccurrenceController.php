<?php

namespace App\Http\Controllers;

use App\Exceptions\CulturalEventDomainException;
use App\Http\Requests\CulturalOccurrenceCancelRequest;
use App\Http\Requests\CulturalOccurrenceGenerateRequest;
use App\Http\Requests\CulturalOccurrencePostponeRequest;
use App\Http\Requests\CulturalOccurrenceResumeRequest;
use App\Http\Requests\CulturalOccurrenceStoreRequest;
use App\Http\Requests\CulturalOccurrenceUpdateRequest;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Services\CulturalEventDomain\OccurrenceGenerator;
use App\Services\CulturalEventDomain\OccurrenceLifecycle;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use Illuminate\Http\RedirectResponse;

/**
 * Sprint 3A.2 / 3A.4 — Održavanja: draft CRUD + published status actions.
 * PO-N-TR-02-04 — generator na Nacrtu.
 */
class CulturalEventEntryOccurrenceController extends Controller
{
    public function __construct(
        private readonly OccurrenceWriter $occurrenceWriter,
        private readonly OccurrenceLifecycle $occurrenceLifecycle,
        private readonly OccurrenceGenerator $occurrenceGenerator,
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

    public function generate(
        CulturalOccurrenceGenerateRequest $request,
        CulturalEventEntry $kanonski_dogadjaj,
    ): RedirectResponse {
        if (! $kanonski_dogadjaj->isDraft()) {
            return redirect()
                ->route('cultural-event-entries.index')
                ->withErrors(['domain' => 'Generator je dostupan samo dok je Događaj Nacrt.']);
        }

        try {
            $created = $this->occurrenceGenerator->generate($kanonski_dogadjaj, $request->domainPayload());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['generator' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-entries.edit', $kanonski_dogadjaj)
            ->with('status', 'Generisano Održavanja: '.$created->count().'.');
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

    public function postpone(
        CulturalOccurrencePostponeRequest $request,
        CulturalEventEntry $kanonski_dogadjaj,
        CulturalOccurrence $odrzavanje,
    ): RedirectResponse {
        $this->assertBelongsToEntry($kanonski_dogadjaj, $odrzavanje);

        try {
            $this->occurrenceLifecycle->postpone($odrzavanje, $request->optionalReason(), $request->user());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['occurrence' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-entries.edit', $kanonski_dogadjaj)
            ->with('status', 'Održavanje je odgođeno.');
    }

    public function cancel(
        CulturalOccurrenceCancelRequest $request,
        CulturalEventEntry $kanonski_dogadjaj,
        CulturalOccurrence $odrzavanje,
    ): RedirectResponse {
        $this->assertBelongsToEntry($kanonski_dogadjaj, $odrzavanje);

        try {
            $this->occurrenceLifecycle->cancel($odrzavanje, $request->optionalReason(), $request->user());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['occurrence' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-entries.edit', $kanonski_dogadjaj)
            ->with('status', 'Održavanje je otkazano.');
    }

    public function resume(
        CulturalOccurrenceResumeRequest $request,
        CulturalEventEntry $kanonski_dogadjaj,
        CulturalOccurrence $odrzavanje,
    ): RedirectResponse {
        $this->assertBelongsToEntry($kanonski_dogadjaj, $odrzavanje);

        try {
            $this->occurrenceLifecycle->resumeWithNewTermin($odrzavanje, $request->terminPayload(), $request->user());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['occurrence' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-entries.edit', $kanonski_dogadjaj)
            ->with('status', 'Održavanje je vraćeno u Planirano sa novim terminom.');
    }

    private function assertBelongsToDraft(
        CulturalEventEntry $entry,
        CulturalOccurrence $occurrence,
    ): void {
        abort_unless($occurrence->event_entry_id === $entry->id, 404);
        abort_unless($entry->isDraft(), 404);
    }

    private function assertBelongsToEntry(
        CulturalEventEntry $entry,
        CulturalOccurrence $occurrence,
    ): void {
        abort_unless($occurrence->event_entry_id === $entry->id, 404);
    }
}
