<?php

namespace App\Http\Controllers;

use App\Exceptions\CulturalEventDomainException;
use App\Http\Requests\CulturalEventChangeProposalUpdateRequest;
use App\Models\CulturalCategory;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventEntry;
use App\Models\CulturalMedia;
use App\Models\CulturalTag;
use App\Models\User;
use App\Services\CulturalEventDomain\EventChangeProposalLifecycle;
use App\Services\CulturalEventDomain\EventChangeProposalWriter;
use App\Support\CulturalModeratorEventAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * TS-010.3a — Moderator tok prijedloga izmjene objavljenog Događaja.
 */
class CulturalModeratorEventChangeProposalController extends Controller
{
    public function __construct(
        private readonly EventChangeProposalWriter $writer,
        private readonly EventChangeProposalLifecycle $lifecycle,
    ) {}

    public function store(CulturalEventEntry $moderator_dogadjaj): RedirectResponse
    {
        $user = auth()->user();
        CulturalModeratorEventAccess::assertCanAccessEntry($user, $moderator_dogadjaj);

        try {
            $proposal = $this->writer->createFromPublished($moderator_dogadjaj, $user);
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-proposals.edit', $proposal)
            ->with('status', 'Prijedlog izmjene je kreiran.');
    }

    public function edit(CulturalEventChangeProposal $prijedlog): View
    {
        $this->assertModeratorCanAccessProposal(auth()->user(), $prijedlog);

        $prijedlog->load([
            'eventEntry.organizer',
            'eventEntry.category',
            'eventEntry.coverMedia',
            'eventEntry.tags',
            'proposedCategory',
            'proposedCoverMedia',
            'tags',
            'organizer',
        ]);

        if ($prijedlog->isDraft()) {
            return view('cultural-calendar.moderator-proposals.edit', array_merge(
                $this->formCatalogs($prijedlog),
                [
                    'proposal' => $prijedlog,
                    'entry' => $prijedlog->eventEntry,
                ]
            ));
        }

        return view('cultural-calendar.moderator-proposals.show', [
            'proposal' => $prijedlog,
            'entry' => $prijedlog->eventEntry,
        ]);
    }

    public function update(
        CulturalEventChangeProposalUpdateRequest $request,
        CulturalEventChangeProposal $prijedlog,
    ): RedirectResponse {
        $this->assertModeratorCanAccessProposal($request->user(), $prijedlog);

        if (! $prijedlog->isDraft()) {
            return redirect()
                ->route('cultural-moderator-proposals.edit', $prijedlog)
                ->withErrors(['domain' => 'Moderator može uređivati samo nacrt prijedloga.']);
        }

        try {
            $this->writer->updateDraftContent(
                $prijedlog,
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
            ->route('cultural-moderator-proposals.edit', $prijedlog)
            ->with('status', 'Prijedlog je ažuriran.');
    }

    public function submit(CulturalEventChangeProposal $prijedlog): RedirectResponse
    {
        $this->assertModeratorCanAccessProposal(auth()->user(), $prijedlog);

        try {
            $this->lifecycle->submit($prijedlog, auth()->user());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-proposals.edit', $prijedlog)
            ->with('status', 'Prijedlog je poslat na pregled.');
    }

    public function withdraw(CulturalEventChangeProposal $prijedlog): RedirectResponse
    {
        $this->assertModeratorCanAccessProposal(auth()->user(), $prijedlog);

        try {
            $this->lifecycle->withdraw($prijedlog, auth()->user());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-proposals.edit', $prijedlog)
            ->with('status', 'Prijedlog je povučen u nacrt.');
    }

    private function assertProposalBelongs(CulturalEventChangeProposal $proposal): void
    {
        $proposal->loadMissing('eventEntry');
        abort_unless($proposal->eventEntry !== null, 404);
    }

    private function assertModeratorCanAccessProposal(User $user, CulturalEventChangeProposal $proposal): void
    {
        $this->assertProposalBelongs($proposal);
        CulturalModeratorEventAccess::assertCanAccessEntry($user, $proposal->eventEntry);
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
