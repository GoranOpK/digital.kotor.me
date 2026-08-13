<?php

namespace App\Http\Controllers;

use App\Support\CulturalModeratorEventAccess;
use App\Support\CulturalOrganizerContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * TS-010.1 — izbor aktivnog Organizator konteksta.
 */
class CulturalModeratorOrganizerContextController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        CulturalModeratorEventAccess::assertActiveModerator($user);

        $validated = $request->validate([
            'organizer_id' => ['required', 'integer'],
        ]);

        CulturalOrganizerContext::set($user, (int) $validated['organizer_id']);

        return redirect()
            ->route('cultural-moderator-dashboard.index')
            ->with('status', 'Aktivni Organizator je postavljen.');
    }
}
