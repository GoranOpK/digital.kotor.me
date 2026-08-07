<?php

namespace App\Http\Controllers;

use App\Http\Requests\CulturalModeratorRequestStoreRequest;
use App\Http\Requests\CulturalRequestDecisionRequest;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizer;
use App\Models\User;
use App\Services\CulturalOrganizer\ModeratorRequestDecisionService;
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

        $candidateUsers = User::query()
            ->where('activation_status', 'active')
            ->whereNotNull('email_verified_at')
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'email']);

        $activeModerators = CulturalModeratorAuthorization::query()
            ->with('user')
            ->where('organizer_id', $organizatori->id)
            ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
            ->get();

        return view('cultural-calendar.moderator-requests.create', [
            'organizer' => $organizatori,
            'candidateUsers' => $candidateUsers,
            'activeModerators' => $activeModerators,
        ]);
    }

    public function store(CulturalModeratorRequestStoreRequest $request, CulturalOrganizer $organizatori): RedirectResponse
    {
        $data = $request->validated();

        if ($data['type'] === CulturalModeratorRequest::TYPE_REMOVE) {
            $activeCount = CulturalPortalAccess::activeModeratorCount($organizatori);
            $isTargetActive = CulturalModeratorAuthorization::query()
                ->where('organizer_id', $organizatori->id)
                ->where('user_id', $data['target_user_id'])
                ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
                ->exists();

            if (! $isTargetActive) {
                return back()->withErrors(['target_user_id' => 'Ciljni korisnik nije aktivan Moderator ovog Organizatora.']);
            }

            if ($activeCount <= 1) {
                return back()->withErrors(['target_user_id' => 'Nije dozvoljeno podnijeti uklanjanje posljednjeg aktivnog Moderatora.']);
            }
        }

        if ($data['type'] === CulturalModeratorRequest::TYPE_ADD) {
            $alreadyActive = CulturalModeratorAuthorization::query()
                ->where('organizer_id', $organizatori->id)
                ->where('user_id', $data['target_user_id'])
                ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
                ->exists();

            if ($alreadyActive) {
                return back()->withErrors(['target_user_id' => 'Korisnik već ima aktivno ovlašćenje za ovog Organizatora.']);
            }
        }

        CulturalModeratorRequest::create([
            'organizer_id' => $organizatori->id,
            'submitter_user_id' => $request->user()->id,
            'target_user_id' => $data['target_user_id'],
            'type' => $data['type'],
            'status' => CulturalModeratorRequest::STATUS_SUBMITTED,
            'decision_note' => null,
        ]);

        return redirect()
            ->route('cultural-moderator-workspace.index')
            ->with('status', 'Zahtjev za Moderatora je podnesen.');
    }

    public function index(): View
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        $requests = CulturalModeratorRequest::query()
            ->with(['organizer', 'submitter', 'targetUser', 'decisionUser'])
            ->latest('id')
            ->paginate(20);

        return view('cultural-calendar.admin.moderator-requests.index', compact('requests'));
    }

    public function show(CulturalModeratorRequest $zahtjev): View
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        $zahtjev->load(['organizer', 'submitter', 'targetUser', 'decisionUser']);

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
}
