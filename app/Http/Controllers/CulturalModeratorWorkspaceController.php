<?php

namespace App\Http\Controllers;

use App\Models\CulturalModeratorAuthorization;
use App\Support\CulturalPortalAccess;
use Illuminate\View\View;

/**
 * Minimalni workspace za aktivnog Moderatora (PO-ORG-04 portal pristup).
 * Pun TS-010 UX ostaje van Koraka 1.
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

        return view('cultural-calendar.moderator-workspace.index', [
            'authorizations' => $authorizations,
            'isEditor' => CulturalPortalAccess::isKkEditor($user),
        ]);
    }
}
