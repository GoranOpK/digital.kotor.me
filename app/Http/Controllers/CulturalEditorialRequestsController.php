<?php

namespace App\Http\Controllers;

use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizerCreationRequest;
use App\Support\CulturalPortalAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Unified kk_admin entrypoint for Org + Moderator request lists (UX only).
 * Decision flows remain on existing Org/Mod controllers.
 */
class CulturalEditorialRequestsController extends Controller
{
    public const SECTION_ORGANIZERS = 'organizatori';

    public const SECTION_MODERATORS = 'moderatori';

    public function index(Request $request): View
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        $section = $request->query('sekcija', self::SECTION_ORGANIZERS);
        if (! in_array($section, [self::SECTION_ORGANIZERS, self::SECTION_MODERATORS], true)) {
            $section = self::SECTION_ORGANIZERS;
        }

        $status = $request->query('status');

        if ($section === self::SECTION_MODERATORS) {
            $query = CulturalModeratorRequest::query()
                ->with(['organizer', 'submitter', 'targetUser', 'decisionUser']);

            if (is_string($status) && in_array($status, CulturalModeratorRequest::STATUSES, true)) {
                $query->where('status', $status);
            } else {
                $query->where(
                    'status',
                    '!=',
                    CulturalModeratorRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY
                );
                $status = null;
            }

            $requests = $query->latest('id')->paginate(20)->withQueryString();

            return view('cultural-calendar.admin.editorial-requests.index', [
                'section' => $section,
                'requests' => $requests,
                'activeStatusFilter' => $status,
            ]);
        }

        $query = CulturalOrganizerCreationRequest::query()
            ->with(['submitter', 'proposedModerator', 'decisionUser', 'organizer']);

        if (is_string($status) && in_array($status, CulturalOrganizerCreationRequest::STATUSES, true)) {
            $query->where('status', $status);
        } else {
            $query->where(
                'status',
                '!=',
                CulturalOrganizerCreationRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY
            );
            $status = null;
        }

        $requests = $query->latest('id')->paginate(20)->withQueryString();

        return view('cultural-calendar.admin.editorial-requests.index', [
            'section' => $section,
            'requests' => $requests,
            'activeStatusFilter' => $status,
        ]);
    }
}
