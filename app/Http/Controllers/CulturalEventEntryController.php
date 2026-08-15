<?php

namespace App\Http\Controllers;

use App\Exceptions\CulturalEventDomainException;
use App\Http\Requests\CulturalEventEntryCancelRequest;
use App\Http\Requests\CulturalEventEntryCancellationReasonRequest;
use App\Http\Requests\CulturalEventEntryFeaturedRequest;
use App\Http\Requests\CulturalEventEntryLinkOrganizerRequest;
use App\Http\Requests\CulturalEventEntryReturnRequest;
use App\Http\Requests\CulturalEventEntryStoreRequest;
use App\Http\Requests\CulturalEventEntryUpdateRequest;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOrganizer;
use App\Models\CulturalTag;
use App\Services\CulturalEventDomain\EventCoverBinder;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Sprint 3A.2–3A.4 — Draft / lifecycle / published-cancelled ops (nije TS-010).
 */
class CulturalEventEntryController extends Controller
{
    public function __construct(
        private readonly EventWriter $eventWriter,
        private readonly EventLifecycle $eventLifecycle,
        private readonly EventCoverBinder $coverBinder,
    ) {}

    public function index(\Illuminate\Http\Request $request): View
    {
        $query = CulturalEventEntry::query()
            ->with(['organizer', 'category'])
            ->withCount('occurrences');

        $status = $request->query('status');
        if (is_string($status) && in_array($status, CulturalEventEntry::STATUSES, true)) {
            $query->where('status', $status);
        }

        if ($request->query('organizer') === 'none') {
            $query->whereNull('organizer_id');
        }

        $entries = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('cultural-calendar.admin.event-entries.index', [
            'entries' => $entries,
            'activeFilters' => [
                'status' => is_string($status) && in_array($status, CulturalEventEntry::STATUSES, true) ? $status : null,
                'organizer' => $request->query('organizer') === 'none' ? 'none' : null,
            ],
        ]);
    }

    public function create(): View
    {
        return view('cultural-calendar.admin.event-entries.create', $this->formCatalogs());
    }

    public function store(CulturalEventEntryStoreRequest $request): RedirectResponse
    {
        try {
            $entry = $this->coverBinder->persistDirectEvent(
                $request->domainPayload(),
                $request->user(),
                $request->file('cover_file'),
                $request->wantsCoverRemoved(),
                null,
                fn (array $payload) => $this->eventWriter->createDraft($request->user(), $payload),
            );
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-entries.edit', $entry)
            ->with('status', 'Događaj je sačuvan.');
    }

    public function edit(CulturalEventEntry $kanonski_dogadjaj): View|RedirectResponse
    {
        $kanonski_dogadjaj->load(['organizer', 'category', 'coverMedia', 'tags', 'occurrences.location']);

        if ($kanonski_dogadjaj->isPendingApproval()) {
            return view('cultural-calendar.admin.event-entries.show-pending', [
                'entry' => $kanonski_dogadjaj,
            ]);
        }

        if ($kanonski_dogadjaj->isPublished()) {
            if ($kanonski_dogadjaj->isDirectFlowPublishedContentEditable()) {
                $activeOrganizers = CulturalOrganizer::query()->active()->orderedByName()->get();

                return view('cultural-calendar.admin.event-entries.edit', array_merge(
                    $this->formCatalogs($kanonski_dogadjaj),
                    [
                        'entry' => $kanonski_dogadjaj,
                        'publishedDirectEdit' => true,
                        'activeOrganizers' => $activeOrganizers,
                        'locations' => \App\Models\CulturalLocation::query()
                            ->active()
                            ->orderedByName()
                            ->get(),
                    ]
                ));
            }

            return view('cultural-calendar.admin.event-entries.show-published', [
                'entry' => $kanonski_dogadjaj,
                'activeOrganizers' => collect(),
            ]);
        }

        if ($kanonski_dogadjaj->isCancelled()) {
            return view('cultural-calendar.admin.event-entries.show-cancelled', [
                'entry' => $kanonski_dogadjaj,
            ]);
        }

        if (! $kanonski_dogadjaj->isDraft()) {
            return redirect()
                ->route('cultural-event-entries.index')
                ->withErrors(['domain' => 'U ovom koraku mogu se uređivati samo nacrti.']);
        }

        return view('cultural-calendar.admin.event-entries.edit', array_merge(
            $this->formCatalogs($kanonski_dogadjaj),
            [
                'entry' => $kanonski_dogadjaj,
                'publishedDirectEdit' => false,
                'locations' => \App\Models\CulturalLocation::query()
                    ->active()
                    ->orderedByName()
                    ->get(),
            ]
        ));
    }

    public function update(
        CulturalEventEntryUpdateRequest $request,
        CulturalEventEntry $kanonski_dogadjaj,
    ): RedirectResponse {
        if (! $kanonski_dogadjaj->isDraft() && ! $kanonski_dogadjaj->isDirectFlowPublishedContentEditable()) {
            return redirect()
                ->route('cultural-event-entries.index')
                ->withErrors(['domain' => 'Izmjena sadržaja nije dozvoljena za ovaj događaj.']);
        }

        $flash = $kanonski_dogadjaj->isPublished()
            ? 'Izmjene događaja su sačuvane.'
            : 'Događaj je sačuvan.';

        try {
            $this->coverBinder->persistDirectEvent(
                $request->domainPayload(),
                $request->user(),
                $request->file('cover_file'),
                $request->wantsCoverRemoved(),
                $kanonski_dogadjaj,
                fn (array $payload) => $this->eventWriter->updateContent(
                    $kanonski_dogadjaj,
                    $request->user(),
                    $payload
                ),
            );
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-entries.edit', $kanonski_dogadjaj)
            ->with('status', $flash);
    }

    /**
     * PATCH-063 — trajno brisanje Urednik direct-flow Događaja u pripremi.
     */
    public function destroy(CulturalEventEntry $kanonski_dogadjaj): RedirectResponse
    {
        try {
            $this->eventWriter->destroyNeverPublishedDraft($kanonski_dogadjaj, request()->user());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-entries.index')
            ->with('status', 'Događaj je obrisan.');
    }

    public function submit(CulturalEventEntry $kanonski_dogadjaj): RedirectResponse
    {
        try {
            $this->eventLifecycle->submitForApproval($kanonski_dogadjaj, request()->user());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-entries.index')
            ->with('status', 'Događaj je poslat na odobrenje.');
    }

    public function approve(CulturalEventEntry $kanonski_dogadjaj): RedirectResponse
    {
        try {
            $this->eventLifecycle->approve($kanonski_dogadjaj, request()->user());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-entries.index')
            ->with('status', 'Događaj je odobren i objavljen.');
    }

    /**
     * Nacrt bez Organizatora → Objavljen (PO-DG-05 / BR-018). Domen = EventLifecycle::publishDirectly.
     */
    public function publish(CulturalEventEntry $kanonski_dogadjaj): RedirectResponse
    {
        try {
            $this->eventLifecycle->publishDirectly($kanonski_dogadjaj, request()->user());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-entries.edit', $kanonski_dogadjaj)
            ->with('status', 'Događaj je direktno objavljen.');
    }

    public function returnToDraft(
        CulturalEventEntryReturnRequest $request,
        CulturalEventEntry $kanonski_dogadjaj,
    ): RedirectResponse {
        try {
            $this->eventLifecycle->returnToDraft(
                $kanonski_dogadjaj,
                $request->user(),
                (string) $request->validated('return_reason')
            );
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['return_reason' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-entries.edit', $kanonski_dogadjaj)
            ->with('status', 'Događaj je vraćen na doradu.');
    }

    public function cancel(
        CulturalEventEntryCancelRequest $request,
        CulturalEventEntry $kanonski_dogadjaj,
    ): RedirectResponse {
        try {
            $this->eventLifecycle->cancel(
                $kanonski_dogadjaj,
                $request->user(),
                $request->optionalReason()
            );
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['cancellation_reason' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-entries.edit', $kanonski_dogadjaj)
            ->with('status', 'Događaj je otkazan.');
    }

    public function updateCancellationReason(
        CulturalEventEntryCancellationReasonRequest $request,
        CulturalEventEntry $kanonski_dogadjaj,
    ): RedirectResponse {
        if (! $kanonski_dogadjaj->isCancelled()) {
            return redirect()
                ->route('cultural-event-entries.index')
                ->withErrors(['domain' => 'Razlog otkazivanja može se mijenjati samo za otkazan Događaj.']);
        }

        try {
            $this->eventWriter->updateContent($kanonski_dogadjaj, $request->user(), [
                'cancellation_reason' => $request->validated('cancellation_reason'),
            ]);
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['cancellation_reason' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-entries.edit', $kanonski_dogadjaj)
            ->with('status', 'Razlog otkazivanja je sačuvan.');
    }

    public function updateFeatured(
        CulturalEventEntryFeaturedRequest $request,
        CulturalEventEntry $kanonski_dogadjaj,
    ): RedirectResponse {
        try {
            $this->eventWriter->updateContent($kanonski_dogadjaj, $request->user(), [
                'featured' => (bool) $request->validated('featured'),
            ]);
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withErrors(['domain' => $e->getMessage()]);
        }

        $on = (bool) $request->validated('featured');

        return redirect()
            ->route('cultural-event-entries.edit', $kanonski_dogadjaj)
            ->with('status', $on ? 'Događaj je istaknut.' : 'Isticanje je uklonjeno.');
    }

    /**
     * BR-052 — naknadno povezivanje Objavljenog Događaja bez Organizatora.
     */
    public function linkOrganizer(
        CulturalEventEntryLinkOrganizerRequest $request,
        CulturalEventEntry $kanonski_dogadjaj,
    ): RedirectResponse {
        try {
            $this->eventWriter->linkOrganizer(
                $kanonski_dogadjaj,
                $request->user(),
                (int) $request->validated('organizer_id')
            );
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-entries.edit', $kanonski_dogadjaj)
            ->with('status', 'Događaj je povezan sa Organizatorom.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formCatalogs(?CulturalEventEntry $entry = null): array
    {
        $organizers = CulturalOrganizer::query()->active()->orderedByName()->get();
        $categories = CulturalCategory::query()
            ->where('status', CulturalCategory::STATUS_ACTIVE)
            ->orderBy('naziv')
            ->orderBy('id')
            ->get();
        $tags = CulturalTag::query()
            ->where('status', CulturalTag::STATUS_ACTIVE)
            ->orderBy('naziv')
            ->orderBy('id')
            ->get();

        if ($entry !== null) {
            if ($entry->organizer && ! $organizers->contains('id', $entry->organizer_id)) {
                $organizers = $organizers->prepend($entry->organizer)->unique('id')->values();
            }
            if ($entry->category && ! $categories->contains('id', $entry->category_id)) {
                $categories = $categories->prepend($entry->category)->unique('id')->values();
            }
            foreach ($entry->tags as $tag) {
                if (! $tags->contains('id', $tag->id)) {
                    $tags = $tags->prepend($tag)->unique('id')->values();
                }
            }
        }

        return compact('organizers', 'categories', 'tags');
    }
}
