<?php

namespace App\Http\Controllers;

use App\Exceptions\CulturalEventDomainException;
use App\Http\Requests\CulturalModeratorOccurrenceStoreRequest;
use App\Http\Requests\CulturalModeratorOccurrenceUpdateRequest;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalModeratorEventAccess;
use Illuminate\Http\RedirectResponse;

/**
 * TS-010.1 — Moderator Održavanja na Draft Eventu.
 */
class CulturalModeratorEventEntryOccurrenceController extends Controller
{
    public function __construct(
        private readonly OccurrenceWriter $occurrenceWriter,
    ) {}

    public function store(
        CulturalModeratorOccurrenceStoreRequest $request,
        CulturalEventEntry $moderator_dogadjaj,
    ): RedirectResponse {
        CulturalModeratorEventAccess::assertCanEditDraft($request->user(), $moderator_dogadjaj);

        try {
            $this->occurrenceWriter->create($moderator_dogadjaj, $request->domainPayload());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['occurrence' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-events.edit', $moderator_dogadjaj)
            ->with('status', 'Održavanje je dodato.');
    }

    public function update(
        CulturalModeratorOccurrenceUpdateRequest $request,
        CulturalEventEntry $moderator_dogadjaj,
        CulturalOccurrence $odrzavanje,
    ): RedirectResponse {
        CulturalModeratorEventAccess::assertCanMutateOccurrence(
            $request->user(),
            $moderator_dogadjaj,
            $odrzavanje
        );

        try {
            $this->occurrenceWriter->update($odrzavanje, $request->domainPayload());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['occurrence' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-events.edit', $moderator_dogadjaj)
            ->with('status', 'Održavanje je ažurirano.');
    }

    public function destroy(
        CulturalEventEntry $moderator_dogadjaj,
        CulturalOccurrence $odrzavanje,
    ): RedirectResponse {
        $user = auth()->user();
        CulturalModeratorEventAccess::assertCanMutateOccurrence($user, $moderator_dogadjaj, $odrzavanje);

        try {
            $this->occurrenceWriter->deletePhysically($odrzavanje);
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->route('cultural-moderator-events.edit', $moderator_dogadjaj)
                ->withErrors(['occurrence' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-events.edit', $moderator_dogadjaj)
            ->with('status', 'Održavanje je uklonjeno.');
    }
}
