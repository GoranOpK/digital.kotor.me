<?php

namespace App\Http\Controllers;

use App\Http\Requests\CulturalOrganizerCreationRequestStoreRequest;
use App\Http\Requests\CulturalRequestDecisionRequest;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Services\CulturalOrganizer\OrganizerCreationDecisionService;
use App\Services\CulturalOrganizer\OrganizerCreationRequestSubmissionService;
use App\Support\CulturalPortalAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class CulturalOrganizerCreationRequestController extends Controller
{
    public function create(): View
    {
        abort_unless(CulturalPortalAccess::isPlatformUserActive(auth()->user()), 403);

        return view('cultural-calendar.organizer-requests.create');
    }

    public function store(
        CulturalOrganizerCreationRequestStoreRequest $request,
        OrganizerCreationRequestSubmissionService $submissionService
    ): RedirectResponse {
        $submissionService->submit($request->user(), $request->validated());

        return redirect()
            ->route('cultural-organizer-creation-requests.create')
            ->with('status', OrganizerCreationRequestSubmissionService::NEUTRAL_SUBMIT_STATUS_MESSAGE);
    }

    public function index(\Illuminate\Http\Request $request): View
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        $query = CulturalOrganizerCreationRequest::query()
            ->visibleInEditorWorkspace()
            ->with(['submitter', 'proposedModerator', 'decisionUser', 'organizer']);

        $status = $request->query('status');
        if (is_string($status) && in_array($status, CulturalOrganizerCreationRequest::STATUSES, true)) {
            $query->where('status', $status);
        } else {
            // Default list: awaiting is not decision-ready (PO-ORG-06 / TS §15.7).
            $query->where(
                'status',
                '!=',
                CulturalOrganizerCreationRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY
            );
        }

        $requests = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('cultural-calendar.admin.organizer-creation-requests.index', [
            'requests' => $requests,
            'activeStatusFilter' => is_string($status) && in_array($status, CulturalOrganizerCreationRequest::STATUSES, true)
                ? $status
                : null,
        ]);
    }

    public function show(CulturalOrganizerCreationRequest $zahtjev): View
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        $zahtjev->load(['submitter', 'proposedModerator', 'decisionUser', 'organizer', 'editorDismissedBy']);

        return view('cultural-calendar.admin.organizer-creation-requests.show', [
            'requestItem' => $zahtjev,
        ]);
    }

    public function approve(
        CulturalRequestDecisionRequest $request,
        CulturalOrganizerCreationRequest $zahtjev,
        OrganizerCreationDecisionService $service
    ): RedirectResponse {
        try {
            $service->approve($zahtjev, $request->user(), $request->validated('decision_note'));
        } catch (InvalidArgumentException|RuntimeException $e) {
            return redirect()
                ->route('cultural-organizer-creation-requests.show', $zahtjev)
                ->withErrors(['decision' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-organizers.index')
            ->with('status', 'Zahtjev je odobren. Organizator i prvi Moderator su kreirani.');
    }

    public function reject(
        CulturalRequestDecisionRequest $request,
        CulturalOrganizerCreationRequest $zahtjev,
        OrganizerCreationDecisionService $service
    ): RedirectResponse {
        try {
            $service->reject($zahtjev, $request->user(), $request->validated('decision_note'));
        } catch (InvalidArgumentException|RuntimeException $e) {
            return redirect()
                ->route('cultural-organizer-creation-requests.show', $zahtjev)
                ->withErrors(['decision' => $e->getMessage()]);
        }

        $this->assertNoOrganizerCreated($zahtjev);

        return redirect()
            ->route('cultural-organizer-creation-requests.index')
            ->with('status', 'Zahtjev je odbijen. Organizator nije kreiran.');
    }

    /**
     * Hide rejected request from editor workspace (not hard delete).
     */
    public function dismiss(CulturalOrganizerCreationRequest $zahtjev): RedirectResponse
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        if (! $zahtjev->isRejected()) {
            return redirect()
                ->route('cultural-organizer-creation-requests.show', $zahtjev)
                ->withErrors(['decision' => 'Uklanjanje iz prikaza dozvoljeno je samo za odbijene zahtjeve.']);
        }

        if (! $zahtjev->isDismissedByEditor()) {
            $zahtjev->update([
                'editor_dismissed_at' => now(),
                'editor_dismissed_by_user_id' => auth()->id(),
            ]);
        }

        return redirect()
            ->route('cultural-editorial-requests.index', ['sekcija' => 'organizatori'])
            ->with('status', 'Odbijeni zahtjev je uklonjen iz prikaza.');
    }

    private function assertNoOrganizerCreated(CulturalOrganizerCreationRequest $zahtjev): void
    {
        if (CulturalOrganizer::query()->where('approved_creation_request_id', $zahtjev->id)->exists()) {
            throw new RuntimeException('Neusaglašenost: Organizator postoji nakon odbijanja.');
        }
    }
}
