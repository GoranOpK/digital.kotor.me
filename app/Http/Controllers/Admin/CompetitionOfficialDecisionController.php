<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Services\Competitions\CompetitionOfficialDecisionCopyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompetitionOfficialDecisionController extends Controller
{
    public function store(
        Request $request,
        Competition $competition,
        CompetitionOfficialDecisionCopyService $service,
    ): RedirectResponse {
        $this->assertKonkursAdmin();
        $this->assertCompetitionAllowsOfficialDecisionUpload($competition);

        $validated = $request->validate([
            'official_decision_copy' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'official_decision_copy.required' => 'Potpisani primjerak zvanične Odluke je obavezan.',
            'official_decision_copy.mimes' => 'Potpisani primjerak mora biti PDF fajl.',
            'official_decision_copy.max' => 'Potpisani primjerak ne može biti veći od 10MB.',
        ]);

        $service->store($competition, $validated['official_decision_copy'], $request->user());

        return redirect()
            ->route('admin.competitions.show', $competition)
            ->with('success', 'Potpisani primjerak zvanične Odluke je postavljen.');
    }

    private function assertKonkursAdmin(): void
    {
        $roleName = auth()->user()?->role?->name;

        if ($roleName !== 'konkurs_admin') {
            abort(403);
        }
    }

    private function assertCompetitionAllowsOfficialDecisionUpload(Competition $competition): void
    {
        if (! in_array($competition->status, ['closed', 'completed'], true)) {
            abort(403);
        }
    }
}
