<?php

namespace App\Http\Controllers;

use App\Exceptions\CulturalEventDomainException;
use App\Http\Requests\CulturalManifestationEventLinkRequest;
use App\Http\Requests\CulturalManifestationStoreRequest;
use App\Http\Requests\CulturalManifestationUpdateRequest;
use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\CulturalMedia;
use App\Models\CulturalOrganizer;
use App\Services\CulturalManifestationDomain\ManifestationLifecycle;
use App\Services\CulturalManifestationDomain\ManifestationPeriodCalculator;
use App\Services\CulturalManifestationDomain\ManifestationWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CulturalManifestationController extends Controller
{
    public function __construct(
        private readonly ManifestationWriter $writer,
        private readonly ManifestationLifecycle $lifecycle,
        private readonly ManifestationPeriodCalculator $periodCalculator,
    ) {}

    public function index(Request $request): View
    {
        $query = CulturalManifestation::query()
            ->with(['organizer', 'creator.role'])
            ->withCount('events');

        $status = $request->query('status');
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

        return view('cultural-calendar.admin.manifestations.index', [
            'manifestations' => $manifestations,
            'periods' => $periods,
            'activeFilters' => [
                'status' => is_string($status) && in_array($status, CulturalManifestation::STATUSES, true) ? $status : null,
            ],
        ]);
    }

    public function create(): View
    {
        return view('cultural-calendar.admin.manifestations.create', $this->formCatalogs());
    }

    public function store(CulturalManifestationStoreRequest $request): RedirectResponse
    {
        try {
            $manifestation = $this->writer->createDraft($request->user(), $request->domainPayload());
        } catch (CulturalEventDomainException $e) {
            return redirect()->back()->withInput()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-manifestations.edit', $manifestation)
            ->with('status', 'Manifestacija je sačuvana.');
    }

    public function edit(CulturalManifestation $kanonska_manifestacija): View
    {
        $kanonska_manifestacija->load(['organizer', 'coverMedia', 'events.organizer', 'creator.role']);

        $period = $this->periodCalculator->calculate($kanonska_manifestacija);
        $catalogs = $this->formCatalogs($kanonska_manifestacija);
        $editorCreated = $kanonska_manifestacija->isEditorCreated();

        if ($kanonska_manifestacija->isPendingApproval()) {
            return view('cultural-calendar.admin.manifestations.show-pending', [
                'manifestation' => $kanonska_manifestacija,
                'period' => $period,
                'canReturn' => ! $editorCreated,
                'canPublish' => true,
            ]);
        }

        if ($kanonska_manifestacija->isCancelled()) {
            return view('cultural-calendar.admin.manifestations.show-readonly', [
                'manifestation' => $kanonska_manifestacija,
                'period' => $period,
                'mode' => 'cancelled',
            ]);
        }

        if ($kanonska_manifestacija->isArchived()) {
            return view('cultural-calendar.admin.manifestations.show-readonly', [
                'manifestation' => $kanonska_manifestacija,
                'period' => $period,
                'mode' => 'archived',
            ]);
        }

        $isDraftOrReturned = $kanonska_manifestacija->isDraft()
            || $kanonska_manifestacija->isReturnedForRevision();

        return view('cultural-calendar.admin.manifestations.edit', array_merge($catalogs, [
            'manifestation' => $kanonska_manifestacija,
            'period' => $period,
            'contentEditable' => true,
            'linksEditable' => true,
            'canCancel' => $kanonska_manifestacija->isPublished(),
            'canSubmit' => ! $editorCreated && $isDraftOrReturned,
            'canPublishDirectly' => $editorCreated && $kanonska_manifestacija->isDraft(),
        ]));
    }

    public function update(
        CulturalManifestationUpdateRequest $request,
        CulturalManifestation $kanonska_manifestacija,
    ): RedirectResponse {
        try {
            $this->writer->updateContent($kanonska_manifestacija, $request->user(), $request->domainPayload());
        } catch (CulturalEventDomainException $e) {
            return redirect()->back()->withInput()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-manifestations.edit', $kanonska_manifestacija)
            ->with('status', 'Manifestacija je sačuvana.');
    }

    public function submit(CulturalManifestation $kanonska_manifestacija): RedirectResponse
    {
        try {
            $this->lifecycle->submitForApproval($kanonska_manifestacija, request()->user());
        } catch (CulturalEventDomainException $e) {
            return redirect()->back()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-manifestations.edit', $kanonska_manifestacija)
            ->with('status', 'Manifestacija je poslata na odobrenje.');
    }

    public function returnToRevision(CulturalManifestation $kanonska_manifestacija): RedirectResponse
    {
        try {
            $this->lifecycle->returnToRevision($kanonska_manifestacija, request()->user());
        } catch (CulturalEventDomainException $e) {
            return redirect()->back()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-manifestations.edit', $kanonska_manifestacija)
            ->with('status', 'Manifestacija je vraćena na doradu.');
    }

    public function publish(CulturalManifestation $kanonska_manifestacija): RedirectResponse
    {
        try {
            $kanonska_manifestacija->loadMissing('creator.role');
            if ($kanonska_manifestacija->isDraft() && $kanonska_manifestacija->isEditorCreated()) {
                $this->lifecycle->publishDirectly($kanonska_manifestacija, request()->user());
            } else {
                $this->lifecycle->publish($kanonska_manifestacija, request()->user());
            }
        } catch (CulturalEventDomainException $e) {
            return redirect()->back()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-manifestations.edit', $kanonska_manifestacija)
            ->with('status', 'Manifestacija je objavljena.');
    }

    public function cancel(CulturalManifestation $kanonska_manifestacija): RedirectResponse
    {
        try {
            $this->lifecycle->cancel($kanonska_manifestacija, request()->user());
        } catch (CulturalEventDomainException $e) {
            return redirect()->back()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-manifestations.edit', $kanonska_manifestacija)
            ->with('status', 'Manifestacija je otkazana.');
    }

    public function linkEvent(
        CulturalManifestationEventLinkRequest $request,
        CulturalManifestation $kanonska_manifestacija,
    ): RedirectResponse {
        try {
            $this->writer->linkEvent(
                $kanonska_manifestacija,
                (int) $request->validated('event_entry_id'),
                $request->user()
            );
        } catch (CulturalEventDomainException $e) {
            return redirect()->back()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-manifestations.edit', $kanonska_manifestacija)
            ->with('status', 'Događaj je povezan sa Manifestacijom.');
    }

    public function unlinkEvent(
        CulturalManifestationEventLinkRequest $request,
        CulturalManifestation $kanonska_manifestacija,
    ): RedirectResponse {
        try {
            $this->writer->unlinkEvent(
                $kanonska_manifestacija,
                (int) $request->validated('event_entry_id'),
                $request->user()
            );
        } catch (CulturalEventDomainException $e) {
            return redirect()->back()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-manifestations.edit', $kanonska_manifestacija)
            ->with('status', 'Događaj je uklonjen iz Manifestacije.');
    }

    public function moveEvent(
        CulturalManifestationEventLinkRequest $request,
        CulturalManifestation $kanonska_manifestacija,
    ): RedirectResponse {
        try {
            $this->writer->moveEvent(
                $kanonska_manifestacija,
                (int) $request->validated('event_entry_id'),
                $request->user()
            );
        } catch (CulturalEventDomainException $e) {
            return redirect()->back()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-manifestations.edit', $kanonska_manifestacija)
            ->with('status', 'Događaj je premješten u ovu Manifestaciju.');
    }

    /**
     * @return array{
     *     organizers: \Illuminate\Support\Collection,
     *     mediaItems: \Illuminate\Support\Collection,
     *     linkableEvents: \Illuminate\Support\Collection,
     *     moveCandidates: \Illuminate\Support\Collection
     * }
     */
    private function formCatalogs(?CulturalManifestation $manifestation = null): array
    {
        $organizers = CulturalOrganizer::query()->active()->orderedByName()->get();

        if ($manifestation?->organizer && ! $organizers->contains('id', $manifestation->organizer_id)) {
            $organizers = $organizers->prepend($manifestation->organizer)->unique('id')->values();
        }

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
            ->where(function ($q) use ($currentId) {
                $q->whereNull('manifestation_id');
                if ($currentId !== null) {
                    $q->orWhere('manifestation_id', $currentId);
                }
            })
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
            'organizers' => $organizers,
            'mediaItems' => $mediaItems,
            'linkableEvents' => $linkableEvents->filter(fn ($e) => $e->manifestation_id === null)->values(),
            'moveCandidates' => $moveCandidates,
        ];
    }
}
