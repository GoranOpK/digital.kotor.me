<?php

namespace App\Http\Controllers;

use App\Exceptions\CulturalEventDomainException;
use App\Http\Requests\CulturalManifestationEventLinkRequest;
use App\Http\Requests\CulturalModeratorManifestationStoreRequest;
use App\Http\Requests\CulturalModeratorManifestationUpdateRequest;
use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\CulturalMedia;
use App\Services\CulturalManifestationDomain\ManifestationLifecycle;
use App\Services\CulturalManifestationDomain\ManifestationPeriodCalculator;
use App\Services\CulturalManifestationDomain\ManifestationWriter;
use App\Support\CulturalModeratorManifestationAccess;
use App\Support\CulturalOrganizerContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CulturalModeratorManifestationController extends Controller
{
    public function __construct(
        private readonly ManifestationWriter $writer,
        private readonly ManifestationLifecycle $lifecycle,
        private readonly ManifestationPeriodCalculator $periodCalculator,
    ) {}

    public function index(): View|RedirectResponse
    {
        $user = auth()->user();
        CulturalModeratorManifestationAccess::assertActiveModerator($user);

        $available = CulturalOrganizerContext::availableOrganizers($user);
        $active = CulturalOrganizerContext::get($user);

        if ($active === null) {
            return view('cultural-calendar.moderator-manifestations.select-context', [
                'organizers' => $available,
            ]);
        }

        $query = CulturalManifestation::query()
            ->where('organizer_id', $active->id)
            ->withCount('events');

        $status = request()->query('status');
        if (is_string($status) && in_array($status, CulturalManifestation::STATUSES, true)) {
            $query->where('status', $status);
        }

        $manifestations = $query
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $periods = [];
        foreach ($manifestations as $manifestation) {
            $periods[$manifestation->id] = $this->periodCalculator->calculate($manifestation);
        }

        return view('cultural-calendar.moderator-manifestations.index', [
            'manifestations' => $manifestations,
            'periods' => $periods,
            'activeOrganizer' => $active,
            'availableOrganizers' => $available,
            'filterStatus' => is_string($status) && in_array($status, CulturalManifestation::STATUSES, true) ? $status : null,
        ]);
    }

    public function create(): View
    {
        $user = auth()->user();
        CulturalModeratorManifestationAccess::assertActiveModerator($user);
        $active = CulturalOrganizerContext::require($user);

        return view('cultural-calendar.moderator-manifestations.create', array_merge(
            $this->formCatalogs(),
            ['activeOrganizer' => $active]
        ));
    }

    public function store(CulturalModeratorManifestationStoreRequest $request): RedirectResponse
    {
        try {
            $manifestation = $this->writer->createDraft($request->user(), $request->domainPayload());
        } catch (CulturalEventDomainException $e) {
            return redirect()->back()->withInput()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-manifestations.edit', $manifestation)
            ->with('status', 'Manifestacija je sačuvana.');
    }

    public function edit(CulturalManifestation $moderator_manifestacija): View
    {
        $user = auth()->user();
        CulturalModeratorManifestationAccess::assertCanAccessManifestation($user, $moderator_manifestacija);

        $moderator_manifestacija->load(['organizer', 'coverMedia', 'events.organizer']);
        $period = $this->periodCalculator->calculate($moderator_manifestacija);
        $active = CulturalOrganizerContext::require($user);

        if ($moderator_manifestacija->isPendingApproval()) {
            return view('cultural-calendar.moderator-manifestations.show-readonly', [
                'manifestation' => $moderator_manifestacija,
                'period' => $period,
                'activeOrganizer' => $active,
                'mode' => 'pending',
            ]);
        }

        if ($moderator_manifestacija->isCancelled() || $moderator_manifestacija->isArchived()) {
            return view('cultural-calendar.moderator-manifestations.show-readonly', [
                'manifestation' => $moderator_manifestacija,
                'period' => $period,
                'activeOrganizer' => $active,
                'mode' => $moderator_manifestacija->isCancelled() ? 'cancelled' : 'archived',
            ]);
        }

        $contentEditable = CulturalModeratorManifestationAccess::canEditContent($user, $moderator_manifestacija);
        $linksEditable = CulturalModeratorManifestationAccess::canMutateLinks($user, $moderator_manifestacija);

        return view('cultural-calendar.moderator-manifestations.edit', array_merge(
            $this->formCatalogs($moderator_manifestacija),
            [
                'manifestation' => $moderator_manifestacija,
                'period' => $period,
                'activeOrganizer' => $active,
                'contentEditable' => $contentEditable,
                'linksEditable' => $linksEditable,
                'canSubmit' => $contentEditable,
                'canCancel' => CulturalModeratorManifestationAccess::canCancel($user, $moderator_manifestacija),
            ]
        ));
    }

    public function update(
        CulturalModeratorManifestationUpdateRequest $request,
        CulturalManifestation $moderator_manifestacija,
    ): RedirectResponse {
        CulturalModeratorManifestationAccess::assertCanEditContent($request->user(), $moderator_manifestacija);

        try {
            $this->writer->updateContent($moderator_manifestacija, $request->user(), $request->domainPayload());
        } catch (CulturalEventDomainException $e) {
            return redirect()->back()->withInput()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-manifestations.edit', $moderator_manifestacija)
            ->with('status', 'Manifestacija je sačuvana.');
    }

    public function submit(CulturalManifestation $moderator_manifestacija): RedirectResponse
    {
        $user = auth()->user();
        CulturalModeratorManifestationAccess::assertCanSubmit($user, $moderator_manifestacija);

        try {
            $this->lifecycle->submitForApproval($moderator_manifestacija, $user);
        } catch (CulturalEventDomainException $e) {
            return redirect()->back()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-manifestations.edit', $moderator_manifestacija)
            ->with('status', 'Manifestacija je poslata na odobrenje.');
    }

    public function cancel(CulturalManifestation $moderator_manifestacija): RedirectResponse
    {
        $user = auth()->user();
        CulturalModeratorManifestationAccess::assertCanCancel($user, $moderator_manifestacija);

        try {
            $this->lifecycle->cancel($moderator_manifestacija, $user);
        } catch (CulturalEventDomainException $e) {
            return redirect()->back()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-manifestations.edit', $moderator_manifestacija)
            ->with('status', 'Manifestacija je otkazana.');
    }

    public function linkEvent(
        CulturalManifestationEventLinkRequest $request,
        CulturalManifestation $moderator_manifestacija,
    ): RedirectResponse {
        CulturalModeratorManifestationAccess::assertCanMutateLinks($request->user(), $moderator_manifestacija);

        try {
            $this->writer->linkEvent(
                $moderator_manifestacija,
                (int) $request->validated('event_entry_id'),
                $request->user()
            );
        } catch (CulturalEventDomainException $e) {
            return redirect()->back()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-manifestations.edit', $moderator_manifestacija)
            ->with('status', 'Događaj je povezan sa Manifestacijom.');
    }

    public function unlinkEvent(
        CulturalManifestationEventLinkRequest $request,
        CulturalManifestation $moderator_manifestacija,
    ): RedirectResponse {
        CulturalModeratorManifestationAccess::assertCanMutateLinks($request->user(), $moderator_manifestacija);

        try {
            $this->writer->unlinkEvent(
                $moderator_manifestacija,
                (int) $request->validated('event_entry_id'),
                $request->user()
            );
        } catch (CulturalEventDomainException $e) {
            return redirect()->back()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-manifestations.edit', $moderator_manifestacija)
            ->with('status', 'Događaj je uklonjen iz Manifestacije.');
    }

    public function moveEvent(
        CulturalManifestationEventLinkRequest $request,
        CulturalManifestation $moderator_manifestacija,
    ): RedirectResponse {
        CulturalModeratorManifestationAccess::assertCanMutateLinks($request->user(), $moderator_manifestacija);

        try {
            $this->writer->moveEvent(
                $moderator_manifestacija,
                (int) $request->validated('event_entry_id'),
                $request->user()
            );
        } catch (CulturalEventDomainException $e) {
            return redirect()->back()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-manifestations.edit', $moderator_manifestacija)
            ->with('status', 'Događaj je premješten u ovu Manifestaciju.');
    }

    /**
     * @return array{
     *     mediaItems: \Illuminate\Support\Collection,
     *     linkableEvents: \Illuminate\Support\Collection,
     *     moveCandidates: \Illuminate\Support\Collection
     * }
     */
    private function formCatalogs(?CulturalManifestation $manifestation = null): array
    {
        $mediaItems = CulturalMedia::query()
            ->active()
            ->where('namjena', CulturalMedia::PURPOSE_MANIFESTATION_COVER)
            ->orderedByName()
            ->get();

        if ($manifestation?->coverMedia && ! $mediaItems->contains('id', $manifestation->cover_media_id)) {
            $mediaItems = $mediaItems->prepend($manifestation->coverMedia)->unique('id')->values();
        }

        $eligible = ManifestationWriter::NEW_LINK_ELIGIBLE_EVENT_STATUSES;
        $currentId = $manifestation?->id;

        $linkableEvents = CulturalEventEntry::query()
            ->with('organizer')
            ->whereIn('status', $eligible)
            ->whereNull('manifestation_id')
            ->orderBy('naslov')
            ->orderBy('id')
            ->limit(200)
            ->get();

        $moveCandidates = CulturalEventEntry::query()
            ->with(['organizer', 'manifestation'])
            ->whereIn('status', $eligible)
            ->whereNotNull('manifestation_id')
            ->when($currentId !== null, fn ($q) => $q->where('manifestation_id', '!=', $currentId))
            ->orderBy('naslov')
            ->orderBy('id')
            ->limit(200)
            ->get();

        return [
            'mediaItems' => $mediaItems,
            'linkableEvents' => $linkableEvents,
            'moveCandidates' => $moveCandidates,
        ];
    }
}
