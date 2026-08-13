<?php

namespace App\Http\Controllers;

use App\Http\Requests\CulturalModeratorRequestStoreRequest;
use App\Http\Requests\CulturalRequestDecisionRequest;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizer;
use App\Services\CulturalOrganizer\ModeratorRequestDecisionService;
use App\Services\CulturalOrganizer\ModeratorRequestSubmissionService;
use App\Services\CulturalOrganizer\OrganizerCreationRequestSubmissionService;
use App\Support\CulturalPortalAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class CulturalModeratorRequestController extends Controller
{
    public function create(CulturalOrganizer $organizatori): View
    {
        abort_unless(CulturalPortalAccess::canModerateOrganizer(auth()->user(), $organizatori), 403);

        $activeModerators = CulturalModeratorAuthorization::query()
            ->with('user')
            ->where('organizer_id', $organizatori->id)
            ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
            ->get();

        return view('cultural-calendar.moderator-requests.create', [
            'organizer' => $organizatori,
            'activeModerators' => $activeModerators,
        ]);
    }

    public function store(
        CulturalModeratorRequestStoreRequest $request,
        CulturalOrganizer $organizatori,
        ModeratorRequestSubmissionService $submissionService
    ): RedirectResponse {
        try {
            $moderatorRequest = $submissionService->submit(
                $request->user(),
                $organizatori,
                $request->validated()
            );
        } catch (InvalidArgumentException $e) {
            $field = ($request->input('type') === CulturalModeratorRequest::TYPE_REMOVE)
                ? 'target_user_id'
                : 'proposed_moderator_email';

            return back()->withErrors([$field => $e->getMessage()])->withInput();
        }

        $flash = $moderatorRequest->type === CulturalModeratorRequest::TYPE_ADD
            ? OrganizerCreationRequestSubmissionService::NEUTRAL_SUBMIT_STATUS_MESSAGE
            : 'Zahtjev za uklanjanje Moderatora je podnesen.';

        return redirect()
            ->route('cultural-moderator-workspace.index')
            ->with('status', $flash);
    }

    public function index(\Illuminate\Http\Request $request): View
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        $query = CulturalModeratorRequest::query()
            ->visibleInEditorWorkspace()
            ->with(['organizer', 'submitter', 'targetUser', 'decisionUser']);

        $status = $request->query('status');
        if (is_string($status) && in_array($status, CulturalModeratorRequest::STATUSES, true)) {
            $query->where('status', $status);
        } else {
            $query->where(
                'status',
                '!=',
                CulturalModeratorRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY
            );
        }

        $requests = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('cultural-calendar.admin.moderator-requests.index', [
            'requests' => $requests,
            'activeStatusFilter' => is_string($status) && in_array($status, CulturalModeratorRequest::STATUSES, true)
                ? $status
                : null,
        ]);
    }

    public function show(CulturalModeratorRequest $zahtjev): View
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        $zahtjev->load(['organizer', 'submitter', 'targetUser', 'decisionUser', 'editorDismissedBy']);

        return view('cultural-calendar.admin.moderator-requests.show', [
            'requestItem' => $zahtjev,
        ]);
    }

    public function approve(
        CulturalRequestDecisionRequest $request,
        CulturalModeratorRequest $zahtjev,
        ModeratorRequestDecisionService $service
    ): RedirectResponse {
        try {
            $service->approve($zahtjev, $request->user(), $request->validated('decision_note'));
        } catch (InvalidArgumentException|RuntimeException $e) {
            return redirect()
                ->route('cultural-moderator-requests.show', $zahtjev)
                ->withErrors(['decision' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-requests.index')
            ->with('status', 'Zahtjev za Moderatora je odobren.');
    }

    public function reject(
        CulturalRequestDecisionRequest $request,
        CulturalModeratorRequest $zahtjev,
        ModeratorRequestDecisionService $service
    ): RedirectResponse {
        try {
            $service->reject($zahtjev, $request->user(), $request->validated('decision_note'));
        } catch (InvalidArgumentException|RuntimeException $e) {
            return redirect()
                ->route('cultural-moderator-requests.show', $zahtjev)
                ->withErrors(['decision' => $e->getMessage()]);
        }

        return redirect()
            ->route('cultural-moderator-requests.index')
            ->with('status', 'Zahtjev za Moderatora je odbijen.');
    }

    /**
     * Hide rejected request from editor workspace (not hard delete).
     * Same rule for rejected ADD and rejected REMOVE.
     */
    public function dismiss(CulturalModeratorRequest $zahtjev): RedirectResponse
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        if (! $zahtjev->isRejected()) {
            return redirect()
                ->route('cultural-moderator-requests.show', $zahtjev)
                ->withErrors(['decision' => 'Uklanjanje iz prikaza dozvoljeno je samo za odbijene zahtjeve.']);
        }

        if (! $zahtjev->isDismissedByEditor()) {
            $zahtjev->update([
                'editor_dismissed_at' => now(),
                'editor_dismissed_by_user_id' => auth()->id(),
            ]);
        }

        return redirect()
            ->route('cultural-editorial-requests.index', ['sekcija' => 'moderatori'])
            ->with('status', 'Odbijeni zahtjev je uklonjen iz prikaza.');
    }
}
