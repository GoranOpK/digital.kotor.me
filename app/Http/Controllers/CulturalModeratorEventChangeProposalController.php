<?php

namespace App\Http\Controllers;

use App\Exceptions\CulturalEventDomainException;
use App\Http\Requests\CulturalEventChangeProposalOccurrenceRequest;
use App\Http\Requests\CulturalEventChangeProposalUpdateRequest;
use App\Models\CulturalCategory;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventChangeProposalOccurrence;
use App\Models\CulturalEventEntry;
use App\Models\CulturalLocation;
use App\Models\CulturalOccurrence;
use App\Models\CulturalTag;
use App\Models\User;
use App\Services\CulturalEventDomain\EventChangeProposalLifecycle;
use App\Services\CulturalEventDomain\EventChangeProposalWriter;
use App\Services\CulturalEventDomain\EventCoverBinder;
use App\Support\CulturalModeratorEventAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * TS-010.3a/3b — Moderator tok prijedloga izmjene objavljenog Događaja.
 */
class CulturalModeratorEventChangeProposalController extends Controller
{
    public function __construct(
        private readonly EventChangeProposalWriter $writer,
        private readonly EventChangeProposalLifecycle $lifecycle,
        private readonly EventCoverBinder $coverBinder,
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
            'eventEntry.occurrences.location',
            'proposedCategory',
            'proposedCoverMedia',
            'tags',
            'organizer',
            'occurrenceOps.proposedLocation',
            'occurrenceOps.sourceOccurrence.location',
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
            $this->coverBinder->persistProposal(
                $request->domainPayload(),
                $request->user(),
                $request->file('cover_file'),
                $request->wantsCoverRemoved(),
                $prijedlog,
                fn (array $payload) => $this->writer->updateDraftContent(
                    $prijedlog,
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
            ->route('cultural-moderator-proposals.edit', $prijedlog)
            ->with('status', 'Prijedlog je ažuriran.');
    }

    public function storeOccurrence(
        CulturalEventChangeProposalOccurrenceRequest $request,
        CulturalEventChangeProposal $prijedlog,
    ): RedirectResponse {
        $this->assertModeratorCanAccessProposal($request->user(), $prijedlog);

        try {
            $this->writer->addOccurrenceOp($prijedlog, $request->user(), $request->domainPayload());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['occurrence' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-proposals.edit', $prijedlog)
            ->with('status', 'Predloženo dodavanje Održavanja je sačuvano.');
    }

    public function updateCanonicalOccurrence(
        CulturalEventChangeProposalOccurrenceRequest $request,
        CulturalEventChangeProposal $prijedlog,
        CulturalOccurrence $odrzavanje,
    ): RedirectResponse {
        $this->assertModeratorCanAccessProposal($request->user(), $prijedlog);

        try {
            $this->writer->upsertOccurrenceUpdateOp(
                $prijedlog,
                $request->user(),
                $odrzavanje,
                $request->domainPayload()
            );
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['occurrence' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-proposals.edit', $prijedlog)
            ->with('status', 'Predložena izmjena Održavanja je sačuvana.');
    }

    public function updateOccurrenceOp(
        CulturalEventChangeProposalOccurrenceRequest $request,
        CulturalEventChangeProposal $prijedlog,
        CulturalEventChangeProposalOccurrence $operacija,
    ): RedirectResponse {
        $this->assertModeratorCanAccessProposal($request->user(), $prijedlog);
        $this->assertOccurrenceOpBelongs($prijedlog, $operacija);

        try {
            $this->writer->updateOccurrenceOp($operacija, $request->user(), $request->domainPayload());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['occurrence' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-proposals.edit', $prijedlog)
            ->with('status', 'Operacija Održavanja je ažurirana.');
    }

    public function destroyOccurrenceOp(
        CulturalEventChangeProposal $prijedlog,
        CulturalEventChangeProposalOccurrence $operacija,
    ): RedirectResponse {
        $this->assertModeratorCanAccessProposal(auth()->user(), $prijedlog);
        $this->assertOccurrenceOpBelongs($prijedlog, $operacija);

        try {
            $this->writer->removeOccurrenceOp($operacija, auth()->user());
        } catch (CulturalEventDomainException $e) {
            return redirect()
                ->back()
                ->withErrors(['occurrence' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-proposals.edit', $prijedlog)
            ->with('status', 'Operacija Održavanja je uklonjena iz prijedloga.');
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

    private function assertOccurrenceOpBelongs(
        CulturalEventChangeProposal $proposal,
        CulturalEventChangeProposalOccurrence $op,
    ): void {
        abort_unless((int) $op->proposal_id === (int) $proposal->id, 404);
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
        $tags = CulturalTag::query()
            ->where('status', CulturalTag::STATUS_ACTIVE)
            ->orderBy('naziv')
            ->orderBy('id')
            ->get();
        $locations = CulturalLocation::query()
            ->active()
            ->orderBy('naziv')
            ->orderBy('id')
            ->get();

        if ($proposal->proposedCategory && ! $categories->contains('id', $proposal->proposed_category_id)) {
            $categories = $categories->prepend($proposal->proposedCategory)->unique('id')->values();
        }
        foreach ($proposal->tags as $tag) {
            if (! $tags->contains('id', $tag->id)) {
                $tags = $tags->prepend($tag)->unique('id')->values();
            }
        }
        foreach ($proposal->occurrenceOps as $op) {
            if ($op->proposedLocation && ! $locations->contains('id', $op->proposed_location_id)) {
                $locations = $locations->prepend($op->proposedLocation)->unique('id')->values();
            }
        }

        return compact('categories', 'tags', 'locations');
    }
}
