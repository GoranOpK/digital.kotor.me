<?php

namespace App\Http\Controllers;

use App\Http\Requests\CulturalLocationRequest;
use App\Models\CulturalLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Urednički CRUD kataloga Lokacija (TS-006 Korak 1).
 * Uloga Urednika = kk_admin. Bez merge, audita, Moderatorskog workflow-a.
 */
class CulturalLocationController extends Controller
{
    public function index(): View
    {
        $locations = CulturalLocation::query()
            ->orderedByName()
            ->paginate(20);

        return view('cultural-calendar.admin.locations.index', compact('locations'));
    }

    public function create(): View
    {
        return view('cultural-calendar.admin.locations.create', [
            'statuses' => CulturalLocation::STATUSES,
            'statusLabels' => CulturalLocation::STATUS_LABELS,
        ]);
    }

    public function store(CulturalLocationRequest $request): RedirectResponse
    {
        CulturalLocation::create($request->validated());

        return redirect()
            ->route('cultural-locations.index')
            ->with('status', 'Lokacija je uspješno kreirana.');
    }

    public function edit(CulturalLocation $lokacije): View
    {
        return view('cultural-calendar.admin.locations.edit', [
            'location' => $lokacije,
            'statuses' => CulturalLocation::STATUSES,
            'statusLabels' => CulturalLocation::STATUS_LABELS,
        ]);
    }

    public function update(CulturalLocationRequest $request, CulturalLocation $lokacije): RedirectResponse
    {
        $lokacije->update($request->validated());

        return redirect()
            ->route('cultural-locations.index')
            ->with('status', 'Lokacija je uspješno ažurirana.');
    }

    public function deactivate(CulturalLocation $lokacije): RedirectResponse
    {
        if ($lokacije->isDeactivated()) {
            return redirect()
                ->route('cultural-locations.index')
                ->with('status', 'Lokacija je već deaktivirana.');
        }

        $lokacije->update([
            'status' => CulturalLocation::STATUS_DEACTIVATED,
        ]);

        return redirect()
            ->route('cultural-locations.index')
            ->with('status', 'Lokacija je deaktivirana.');
    }

    public function activate(CulturalLocation $lokacije): RedirectResponse
    {
        if ($lokacije->isActive()) {
            return redirect()
                ->route('cultural-locations.index')
                ->with('status', 'Lokacija je već aktivna.');
        }

        if (CulturalLocation::activeDuplicateExists($lokacije->naziv, $lokacije->id)) {
            return redirect()
                ->route('cultural-locations.edit', $lokacije)
                ->withErrors([
                    'naziv' => 'Ne može se aktivirati: već postoji aktivna lokacija sa istim nazivom.',
                ]);
        }

        $lokacije->update([
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        return redirect()
            ->route('cultural-locations.index')
            ->with('status', 'Lokacija je aktivirana.');
    }
}
