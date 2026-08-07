<?php

namespace App\Http\Controllers;

use App\Exceptions\CulturalEventDomainException;
use App\Http\Requests\CulturalEventChangeProposalReturnRequest;
use App\Http\Requests\CulturalEventChangeProposalUpdateRequest;
use App\Models\CulturalCategory;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalMedia;
use App\Models\CulturalTag;
use App\Services\CulturalEventDomain\EventChangeProposalApplicator;
use App\Services\CulturalEventDomain\EventChangeProposalLifecycle;
use App\Services\CulturalEventDomain\EventChangeProposalWriter;
use App\Support\CulturalPortalAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * TS-010.3a — Urednik (kk_admin) pregled i odluka o prijedlogu izmjene.
 */
class CulturalEventChangeProposalController extends Controller
{
    public function __construct(
        private readonly EventChangeProposalWriter $writer,
        private readonly EventChangeProposalLifecycle $lifecycle,
        private readonly EventChangeProposalApplicator $applicator,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        $query = CulturalEventChangeProposal::query()
            ->with(['eventEntry', 'organizer', 'creator']);

        if ($request->query('proposal_status') === CulturalEventChangeProposal::STATUS_PENDING_REVIEW) {
            $query->where('status', CulturalEventChangeProposal::STATUS_PENDING_REVIEW);
        }

        $proposals = $query
            ->orderByDesc('last_submitted_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('cultural-calendar.admin.change-proposals.index', [
            'proposals' => $proposals,
            'pendingFilter' => $request->query('proposal_status') === CulturalEventChangeProposal::STATUS_PENDING_REVIEW,
        ]);
    }

    public function show(CulturalEventChangeProposal $prijedlog): View
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        $prijedlog->load([
            'eventEntry.organizer',
            'eventEntry.category',
            'eventEntry.coverMedia',
            'eventEntry.tags',
            'proposedCategory',
            'proposedCoverMedia',
            'tags',
            'organizer',
            'creator',
            'reviewStartedBy',
        ]);

        return view('cultural-calendar.admin.change-proposals.show', [
            'proposal' => $prijedlog,
            'entry' => $prijedlog->eventEntry,
        ]);
    }

    public function edit(CulturalEventChangeProposal $prijedlog): View|RedirectResponse
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        $prijedlog->load([
            'eventEntry',
            'proposedCategory',
            'proposedCoverMedia',
            'tags',
        ]);

        if (! $prijedlog->isUnderEditorialReview()) {
            return redirect()
                ->route('cultural-event-change-proposals.show', $prijedlog)
                ->withErrors(['domain' => 'Urednik može uređivati prijedlog tek nakon početka pregleda.']);
        }

        return view('cultural-calendar.admin.change-proposals.edit', array_merge(
            $this->formCatalogs($prijedlog),
            [
                'proposal' => $prijedlog,
                'entry' => $prijedlog->eventEntry,
            ]
        ));
    }

    public function startReview(CulturalEventChangeProposal $prijedlog): RedirectResponse
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        try {
            $this->lifecycle->startReview($prijedlog, auth()->user());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-change-proposals.show', $prijedlog)
            ->with('status', 'Pregled prijedloga je pokrenut.');
    }

    public function update(
        CulturalEventChangeProposalUpdateRequest $request,
        CulturalEventChangeProposal $prijedlog,
    ): RedirectResponse {
        abort_unless(CulturalPortalAccess::isKkEditor($request->user()), 403);

        try {
            $this->writer->updateDraftContent(
                $prijedlog,
                $request->user(),
                $request->domainPayload(),
                asEditor: true
            );
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-change-proposals.show', $prijedlog)
            ->with('status', 'Prijedlog je ažuriran.');
    }

    public function approve(CulturalEventChangeProposal $prijedlog): RedirectResponse
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        try {
            $this->applicator->approve($prijedlog, auth()->user());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-event-change-proposals.index')
            ->with('status', 'Prijedlog je odobren i primijenjen na događaj.');
    }

    public function returnToDraft(
        CulturalEventChangeProposalReturnRequest $request,
        CulturalEventChangeProposal $prijedlog,
    ): RedirectResponse {
        abort_unless(CulturalPortalAccess::isKkEditor($request->user()), 403);

        try {
            $this->lifecycle->returnToDraft(
                $prijedlog,
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
            ->route('cultural-event-change-proposals.index')
            ->with('status', 'Prijedlog je vraćen na doradu.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formCatalogs(CulturalEventChangeProposal $proposal): array
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

        if ($proposal->proposedCategory && ! $categories->contains('id', $proposal->proposed_category_id)) {
            $categories = $categories->prepend($proposal->proposedCategory)->unique('id')->values();
        }
        if ($proposal->proposedCoverMedia && ! $mediaItems->contains('id', $proposal->proposed_cover_media_id)) {
            $mediaItems = $mediaItems->prepend($proposal->proposedCoverMedia)->unique('id')->values();
        }
        foreach ($proposal->tags as $tag) {
            if (! $tags->contains('id', $tag->id)) {
                $tags = $tags->prepend($tag)->unique('id')->values();
            }
        }

        return compact('categories', 'mediaItems', 'tags');
    }
}
