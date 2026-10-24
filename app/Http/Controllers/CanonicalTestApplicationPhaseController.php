<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Services\Competitions\CanonicalTestApplicationPhaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * One-way close of the canonical MMSP test application window.
 * Authorization and the irreversible write live in CanonicalTestApplicationPhaseService.
 */
class CanonicalTestApplicationPhaseController extends Controller
{
    public function __construct(
        private readonly CanonicalTestApplicationPhaseService $phases,
    ) {
    }

    public function close(Request $request, Competition $competition): RedirectResponse
    {
        $request->validate([
            'confirm_test_application_phase_close' => 'accepted',
        ], [
            'confirm_test_application_phase_close.accepted' => CanonicalTestApplicationPhaseService::CONFIRMATION_REQUIRED_MESSAGE,
        ]);

        $result = $this->phases->close($competition, $request->user(), true);
        if (! $result->succeeded) {
            return back()->withErrors(['test_application_phase' => $result->error]);
        }

        return back()->with('success', 'Testna prijavna faza je zatvorena. Nove prijave više nisu moguće.');
    }
}
