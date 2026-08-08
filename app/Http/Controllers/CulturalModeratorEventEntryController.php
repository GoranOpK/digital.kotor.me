<?php

namespace App\Http\Controllers;

use App\Exceptions\CulturalEventDomainException;
use App\Http\Requests\CulturalModeratorEventEntryCancelRequest;
use App\Http\Requests\CulturalModeratorEventEntryStoreRequest;
use App\Http\Requests\CulturalModeratorEventEntryUpdateRequest;
use App\Models\CulturalCategory;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventEntry;
use App\Models\CulturalMedia;
use App\Models\CulturalTag;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Support\CulturalModeratorEventAccess;
use App\Support\CulturalOrganizerContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * TS-010.1 — Moderator Draft Event tok (nije Urednik CRUD / nije TS-010 dashboard).
 */
class CulturalModeratorEventEntryController extends Controller
{
    public function __construct(
        private readonly EventWriter $eventWriter,
        private readonly EventLifecycle $eventLifecycle,
    ) {}

    public function index(): View|RedirectResponse
    {
        $user = auth()->user();
        CulturalModeratorEventAccess::assertActiveModerator($user);

        $available = CulturalOrganizerContext::availableOrganizers($user);
        $active = CulturalOrganizerContext::get($user);

        if ($active === null) {
            return view('cultural-calendar.moderator-events.select-context', [
                'organizers' => $available,
            ]);
        }

        $entriesQuery = CulturalEventEntry::query()
            ->where('organizer_id', $active->id);

        $status = request()->query('status');
        if (is_string($status) && in_array($status, CulturalEventEntry::STATUSES, true)) {
            $entriesQuery->where('status', $status);
        }

        $hasActiveProposal = request()->query('has_active_proposal');
        if ($hasActiveProposal === '1' || $hasActiveProposal === 1 || $hasActiveProposal === true) {
            $entriesQuery
                ->where('status', CulturalEventEntry::STATUS_PUBLISHED)
                ->whereHas('changeProposals', function ($q) {
                    $q->active()->whereNotNull('active_for_event_id');
                });
        }

        $entries = $entriesQuery
            ->with(['category'])
            ->withCount('occurrences')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('cultural-calendar.moderator-events.index', [
            'entries' => $entries,
            'activeOrganizer' => $active,
            'availableOrganizers' => $available,
            'filterStatus' => is_string($status) && in_array($status, CulturalEventEntry::STATUSES, true) ? $status : null,
            'filterHasActiveProposal' => $hasActiveProposal === '1' || $hasActiveProposal === 1 || $hasActiveProposal === true,
        ]);
    }

    public function create(): View|RedirectResponse
    {
        $user = auth()->user();
        CulturalModeratorEventAccess::assertActiveModerator($user);
        $active = CulturalOrganizerContext::require($user);

        return view('cultural-calendar.moderator-events.create', array_merge(
            $this->formCatalogs(),
            ['activeOrganizer' => $active]
        ));
    }

    public function store(CulturalModeratorEventEntryStoreRequest $request): RedirectResponse
    {
        try {
            $entry = $this->eventWriter->createDraft($request->user(), $request->domainPayload());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-events.edit', $entry)
            ->with('status', 'Nacrt događaja je kreiran.');
    }

    public function edit(CulturalEventEntry $moderator_dogadjaj): View|RedirectResponse
    {
        $user = auth()->user();
        CulturalModeratorEventAccess::assertCanAccessEntry($user, $moderator_dogadjaj);

        $moderator_dogadjaj->load(['organizer', 'category', 'coverMedia', 'tags', 'occurrences.location']);

        if ($moderator_dogadjaj->isPendingApproval()) {
            return view('cultural-calendar.moderator-events.show-pending', [
                'entry' => $moderator_dogadjaj,
                'activeOrganizer' => $moderator_dogadjaj->organizer,
            ]);
        }

        if ($moderator_dogadjaj->isPublished()) {
            $activeProposal = CulturalEventChangeProposal::query()
                ->where('active_for_event_id', $moderator_dogadjaj->id)
                ->first();

            return view('cultural-calendar.moderator-events.show-published', [
                'entry' => $moderator_dogadjaj,
                'activeOrganizer' => $moderator_dogadjaj->organizer,
                'activeProposal' => $activeProposal,
            ]);
        }

        CulturalModeratorEventAccess::assertCanEditDraft($user, $moderator_dogadjaj);

        return view('cultural-calendar.moderator-events.edit', array_merge(
            $this->formCatalogs($moderator_dogadjaj),
            [
                'entry' => $moderator_dogadjaj,
                'activeOrganizer' => $moderator_dogadjaj->organizer,
                'locations' => \App\Models\CulturalLocation::query()
                    ->active()
                    ->orderedByName()
                    ->get(),
            ]
        ));
    }

    public function update(
        CulturalModeratorEventEntryUpdateRequest $request,
        CulturalEventEntry $moderator_dogadjaj,
    ): RedirectResponse {
        CulturalModeratorEventAccess::assertCanEditDraft($request->user(), $moderator_dogadjaj);

        try {
            $this->eventWriter->updateContent(
                $moderator_dogadjaj,
                $request->user(),
                $request->domainPayload()
            );
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-events.edit', $moderator_dogadjaj)
            ->with('status', 'Nacrt je ažuriran.');
    }

    public function submit(CulturalEventEntry $moderator_dogadjaj): RedirectResponse
    {
        $user = auth()->user();
        CulturalModeratorEventAccess::assertCanSubmit($user, $moderator_dogadjaj);

        try {
            $this->eventLifecycle->submitForApproval($moderator_dogadjaj, $user);
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-events.edit', $moderator_dogadjaj)
            ->with('status', 'Događaj je poslat na odobrenje.');
    }

    /**
     * Objavljen → Otkazan (BM-MOD-16 / BR-063). Poslovna logika u EventLifecycle::cancel.
     */
    public function cancel(
        CulturalModeratorEventEntryCancelRequest $request,
        CulturalEventEntry $moderator_dogadjaj,
    ): RedirectResponse {
        CulturalModeratorEventAccess::assertCanAccessEntry($request->user(), $moderator_dogadjaj);

        try {
            $this->eventLifecycle->cancel(
                $moderator_dogadjaj,
                $request->user(),
                (string) $request->validated('cancellation_reason')
            );
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['cancellation_reason' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-events.index')
            ->with('status', 'Događaj je otkazan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formCatalogs(?CulturalEventEntry $entry = null): array
    {
        $categories = CulturalCategory::query()
            ->where('status', CulturalCategory::STATUS_ACTIVE)
            ->orderBy('naziv')
            ->orderBy('id')
            ->get();
        $mediaItems = CulturalMedia::query()
            ->active()
            ->where('namjena', CulturalMedia::PURPOSE_EVENT_COVER)
            ->orderedByName()
            ->get();
        $tags = CulturalTag::query()
            ->where('status', CulturalTag::STATUS_ACTIVE)
            ->orderBy('naziv')
            ->orderBy('id')
            ->get();

        if ($entry !== null) {
            if ($entry->category && ! $categories->contains('id', $entry->category_id)) {
                $categories = $categories->prepend($entry->category)->unique('id')->values();
            }
            if ($entry->coverMedia && ! $mediaItems->contains('id', $entry->cover_media_id)) {
                $mediaItems = $mediaItems->prepend($entry->coverMedia)->unique('id')->values();
            }
            foreach ($entry->tags as $tag) {
                if (! $tags->contains('id', $tag->id)) {
                    $tags = $tags->prepend($tag)->unique('id')->values();
                }
            }
        }

        return compact('categories', 'mediaItems', 'tags');
    }
}
