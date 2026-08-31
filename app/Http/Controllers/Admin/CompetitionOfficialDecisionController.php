<?php

namespace App\Http\Controllers\Admin;

use App\Events\OfficialContentReadyForPublicPublication;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionOfficialDecisionCopy;
use App\Services\Competitions\CompetitionOfficialDecisionCopyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompetitionOfficialDecisionController extends Controller
{
    public function store(
        Request $request,
        Competition $competition,
        CompetitionOfficialDecisionCopyService $service,
    ): RedirectResponse {
        $this->assertKonkursAdmin();
        $this->assertCompetitionAllowsOfficialDecisionAction($competition);

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

    public function publish(
        Competition $competition,
        CompetitionOfficialDecisionCopy $copy,
    ): RedirectResponse {
        $this->assertKonkursAdmin();
        $this->assertCompetitionAllowsOfficialDecisionAction($competition);

        if ((int) $copy->competition_id !== (int) $competition->id) {
            abort(404);
        }

        $storagePath = $copy->storage_path;

        if (! is_string($storagePath) || $storagePath === '' || ! Storage::disk('local')->exists($storagePath)) {
            abort(404);
        }

        if ($copy->hasBeenPublished()) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Ovaj primjerak je već objavljen.']);
        }

        if (CompetitionOfficialDecisionCopy::competitionHasPublishedSignedCopy($competition->id)) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Zvanična Odluka je već objavljena za ovaj Konkurs.']);
        }

        event(new OfficialContentReadyForPublicPublication(
            'Odluka o dodjeli sredstava',
            $competition->title,
            'competition_decision',
            $competition->id,
            'competition_decision_signed_copy',
            null,
            false,
            $copy->id,
        ));

        return redirect()
            ->route('admin.competitions.show', $competition)
            ->with('success', 'Zvanična Odluka je objavljena.');
    }

    public function correct(
        Competition $competition,
        CompetitionOfficialDecisionCopy $copy,
    ): RedirectResponse {
        $this->assertKonkursAdmin();
        $this->assertCompetitionAllowsOfficialDecisionAction($competition);

        if ((int) $copy->competition_id !== (int) $competition->id) {
            abort(404);
        }

        $storagePath = $copy->storage_path;

        if (! is_string($storagePath) || $storagePath === '' || ! Storage::disk('local')->exists($storagePath)) {
            abort(404);
        }

        $activeNotices = CompetitionOfficialDecisionCopy::activeSignedCopyNotices($competition->id);

        if ($activeNotices->count() === 0) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Nema aktivne objave zvanične Odluke za korekciju.']);
        }

        if ($activeNotices->count() > 1) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Nije moguće korigovati objavu jer postoji više aktivnih objava.']);
        }

        $oldNotice = $activeNotices->first();

        if ((int) $oldNotice->source_object_id === (int) $copy->id) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Korekcija mora koristiti drugi primjerak.']);
        }

        if ($copy->hasBeenPublished()) {
            return redirect()
                ->route('admin.competitions.show', $competition)
                ->withErrors(['error' => 'Ovaj primjerak je već objavljen.']);
        }

        event(new OfficialContentReadyForPublicPublication(
            'Odluka o dodjeli sredstava',
            $competition->title,
            'competition_decision',
            $competition->id,
            'competition_decision_signed_copy',
            $oldNotice->id,
            true,
            $copy->id,
        ));

        return redirect()
            ->route('admin.competitions.show', $competition)
            ->with('success', 'Objava zvanične Odluke je korigovana.');
    }

    private function assertKonkursAdmin(): void
    {
        $roleName = auth()->user()?->role?->name;

        if ($roleName !== 'konkurs_admin') {
            abort(403);
        }
    }

    private function assertCompetitionAllowsOfficialDecisionAction(Competition $competition): void
    {
        if (! in_array($competition->status, ['closed', 'completed'], true)) {
            abort(403);
        }
    }
}
