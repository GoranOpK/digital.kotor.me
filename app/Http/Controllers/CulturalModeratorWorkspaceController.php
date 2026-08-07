<?php

namespace App\Http\Controllers;

use App\Models\CulturalModeratorAuthorization;
use App\Support\CulturalModeratorEventAccess;
use App\Support\CulturalOrganizerContext;
use App\Support\CulturalPortalAccess;
use Illuminate\View\View;

/**
 * Minimalni workspace za aktivnog Moderatora (PO-ORG-04 + TS-010.1 ulaz).
 */
class CulturalModeratorWorkspaceController extends Controller
{
    public function index(): View
    {
        abort_unless(CulturalPortalAccess::allows(auth()->user()), 403);

        $user = auth()->user();

        $authorizations = CulturalModeratorAuthorization::query()
            ->with('organizer')
            ->where('user_id', $user->id)
            ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
            ->whereHas('organizer', function ($query) {
                $query->where('status', 'active');
            })
            ->get();

        $activeOrganizer = CulturalModeratorEventAccess::isActiveModerator($user)
            ? CulturalOrganizerContext::get($user)
            : null;

        return view('cultural-calendar.moderator-workspace.index', [
            'authorizations' => $authorizations,
            'isEditor' => CulturalPortalAccess::isKkEditor($user),
            'isActiveModerator' => CulturalModeratorEventAccess::isActiveModerator($user),
            'activeOrganizer' => $activeOrganizer,
            'availableOrganizers' => CulturalModeratorEventAccess::isActiveModerator($user)
                ? CulturalOrganizerContext::availableOrganizers($user)
                : collect(),
        ]);
    }
}
