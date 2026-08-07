<?php

namespace App\Http\Controllers;

use App\Http\Requests\CulturalOrganizerCreationRequestStoreRequest;
use App\Http\Requests\CulturalRequestDecisionRequest;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\User;
use App\Services\CulturalOrganizer\OrganizerCreationDecisionService;
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

        $candidateModerators = User::query()
            ->where('activation_status', 'active')
            ->whereNotNull('email_verified_at')
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'email']);

        return view('cultural-calendar.organizer-requests.create', compact('candidateModerators'));
    }

    public function store(CulturalOrganizerCreationRequestStoreRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $proposedId = (int) $data['proposed_moderator_user_id'];

        CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $user->id,
            'proposed_moderator_user_id' => $proposedId,
            'proposed_moderator_is_submitter' => $proposedId === (int) $user->id,
            'proposed_naziv' => $data['naziv'],
            'proposed_opis' => $data['opis'] ?? null,
            'proposed_contact_email' => $data['contact_email'] ?? null,
            'proposed_contact_phone' => $data['contact_phone'] ?? null,
            'proposed_website' => $data['website'] ?? null,
            'status' => CulturalOrganizerCreationRequest::STATUS_SUBMITTED,
        ]);

        return redirect()
            ->route('cultural-organizer-creation-requests.create')
            ->with('status', 'Zahtjev za kreiranje Organizatora je podnesen. Organizator još nije kreiran.');
    }

    public function index(\Illuminate\Http\Request $request): View
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        $query = CulturalOrganizerCreationRequest::query()
            ->with(['submitter', 'proposedModerator', 'decisionUser', 'organizer']);

        $status = $request->query('status');
        if (is_string($status) && in_array($status, CulturalOrganizerCreationRequest::STATUSES, true)) {
            $query->where('status', $status);
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

        $zahtjev->load(['submitter', 'proposedModerator', 'decisionUser', 'organizer']);

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

    private function assertNoOrganizerCreated(CulturalOrganizerCreationRequest $zahtjev): void
    {
        if (CulturalOrganizer::query()->where('approved_creation_request_id', $zahtjev->id)->exists()) {
            throw new RuntimeException('Neusaglašenost: Organizator postoji nakon odbijanja.');
        }
    }
}
