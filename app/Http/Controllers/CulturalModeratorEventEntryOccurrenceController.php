<?php

namespace App\Http\Controllers;

use App\Exceptions\CulturalEventDomainException;
use App\Http\Requests\CulturalModeratorOccurrenceCancelRequest;
use App\Http\Requests\CulturalModeratorOccurrenceGenerateRequest;
use App\Http\Requests\CulturalModeratorOccurrencePostponeRequest;
use App\Http\Requests\CulturalModeratorOccurrenceResumeRequest;
use App\Http\Requests\CulturalModeratorOccurrenceStoreRequest;
use App\Http\Requests\CulturalModeratorOccurrenceUpdateRequest;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Services\CulturalEventDomain\OccurrenceGenerator;
use App\Services\CulturalEventDomain\OccurrenceLifecycle;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalModeratorEventAccess;
use Illuminate\Http\RedirectResponse;

/**
 * TS-010.1 — Moderator Održavanja na Draft Eventu.
 * Statusne akcije na Objavljenom — BR-132 / OccurrenceLifecycle.
 * PO-N-TR-02-04 — generator na Nacrtu.
 */
class CulturalModeratorEventEntryOccurrenceController extends Controller
{
    public function __construct(
        private readonly OccurrenceWriter $occurrenceWriter,
        private readonly OccurrenceLifecycle $occurrenceLifecycle,
        private readonly OccurrenceGenerator $occurrenceGenerator,
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

    public function generate(
        CulturalModeratorOccurrenceGenerateRequest $request,
        CulturalEventEntry $moderator_dogadjaj,
    ): RedirectResponse {
        CulturalModeratorEventAccess::assertCanEditDraft($request->user(), $moderator_dogadjaj);

        try {
            $created = $this->occurrenceGenerator->generate($moderator_dogadjaj, $request->domainPayload());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['generator' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-events.edit', $moderator_dogadjaj)
            ->with('status', 'Generisano Održavanja: '.$created->count().'.');
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

    public function postpone(
        CulturalModeratorOccurrencePostponeRequest $request,
        CulturalEventEntry $moderator_dogadjaj,
        CulturalOccurrence $odrzavanje,
    ): RedirectResponse {
        CulturalModeratorEventAccess::assertCanMutatePublishedOccurrenceStatus(
            $request->user(),
            $moderator_dogadjaj,
            $odrzavanje
        );

        try {
            $this->occurrenceLifecycle->postpone($odrzavanje, $request->optionalReason());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->route('cultural-moderator-events.edit', $moderator_dogadjaj)
                ->withInput()
                ->withErrors(['occurrence' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-events.edit', $moderator_dogadjaj)
            ->with('status', 'Održavanje je odgođeno.');
    }

    public function cancel(
        CulturalModeratorOccurrenceCancelRequest $request,
        CulturalEventEntry $moderator_dogadjaj,
        CulturalOccurrence $odrzavanje,
    ): RedirectResponse {
        CulturalModeratorEventAccess::assertCanMutatePublishedOccurrenceStatus(
            $request->user(),
            $moderator_dogadjaj,
            $odrzavanje
        );

        try {
            $this->occurrenceLifecycle->cancel($odrzavanje, $request->optionalReason());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->route('cultural-moderator-events.edit', $moderator_dogadjaj)
                ->withInput()
                ->withErrors(['occurrence' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-events.edit', $moderator_dogadjaj)
            ->with('status', 'Održavanje je otkazano.');
    }

    public function resume(
        CulturalModeratorOccurrenceResumeRequest $request,
        CulturalEventEntry $moderator_dogadjaj,
        CulturalOccurrence $odrzavanje,
    ): RedirectResponse {
        CulturalModeratorEventAccess::assertCanMutatePublishedOccurrenceStatus(
            $request->user(),
            $moderator_dogadjaj,
            $odrzavanje
        );

        try {
            $this->occurrenceLifecycle->resumeWithNewTermin($odrzavanje, $request->terminPayload());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->route('cultural-moderator-events.edit', $moderator_dogadjaj)
                ->withInput()
                ->withErrors(['occurrence' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-events.edit', $moderator_dogadjaj)
            ->with('status', 'Održavanje je vraćeno u Planirano sa novim terminom.');
    }
}
