<?php

namespace App\Http\Controllers;

use App\Http\Requests\CulturalOrganizerUpdateRequest;
use App\Models\CulturalOrganizer;
use App\Support\CulturalPortalAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CulturalOrganizerController extends Controller
{
    public function index(): View
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        $organizers = CulturalOrganizer::query()
            ->withCount([
                'authorizations as active_moderators_count' => function ($query) {
                    $query->where('status', 'active');
                },
            ])
            ->orderedByName()
            ->paginate(20);

        return view('cultural-calendar.admin.organizers.index', compact('organizers'));
    }

    public function edit(CulturalOrganizer $organizatori): View
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        return view('cultural-calendar.admin.organizers.edit', [
            'organizer' => $organizatori->load(['activeAuthorizations.user', 'approvedCreationRequest']),
        ]);
    }

    public function update(CulturalOrganizerUpdateRequest $request, CulturalOrganizer $organizatori): RedirectResponse
    {
        $organizatori->update($request->validated());

        return redirect()
            ->route('cultural-organizers.index')
            ->with('status', 'Organizator je ažuriran.');
    }

    public function deactivate(CulturalOrganizer $organizatori): RedirectResponse
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        if ($organizatori->isDeactivated()) {
            return redirect()
                ->route('cultural-organizers.index')
                ->with('status', 'Organizator je već deaktiviran.');
        }

        $organizatori->update([
            'status' => CulturalOrganizer::STATUS_DEACTIVATED,
        ]);

        return redirect()
            ->route('cultural-organizers.index')
            ->with('status', 'Organizator je deaktiviran.');
    }
}
