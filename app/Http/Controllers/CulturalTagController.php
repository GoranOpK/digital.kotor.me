<?php

namespace App\Http\Controllers;

use App\Http\Requests\CulturalTagRequest;
use App\Models\CulturalTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Urednički CRUD kataloga Oznaka (TS-007 Korak 1).
 */
class CulturalTagController extends Controller
{
    public function index(): View
    {
        $tags = CulturalTag::query()
            ->orderedByName()
            ->paginate(20);

        return view('cultural-calendar.admin.tags.index', compact('tags'));
    }

    public function create(): View
    {
        return view('cultural-calendar.admin.tags.create', [
            'statuses' => CulturalTag::STATUSES,
            'statusLabels' => CulturalTag::STATUS_LABELS,
        ]);
    }

    public function store(CulturalTagRequest $request): RedirectResponse
    {
        CulturalTag::create($request->validated());

        return redirect()
            ->route('cultural-tags.index')
            ->with('status', 'Oznaka je uspješno kreirana.');
    }

    public function edit(CulturalTag $oznake): View
    {
        return view('cultural-calendar.admin.tags.edit', [
            'tag' => $oznake,
            'statuses' => CulturalTag::STATUSES,
            'statusLabels' => CulturalTag::STATUS_LABELS,
        ]);
    }

    public function update(CulturalTagRequest $request, CulturalTag $oznake): RedirectResponse
    {
        $oznake->update($request->validated());

        return redirect()
            ->route('cultural-tags.index')
            ->with('status', 'Oznaka je uspješno ažurirana.');
    }

    public function deactivate(CulturalTag $oznake): RedirectResponse
    {
        if ($oznake->isInactive()) {
            return redirect()
                ->route('cultural-tags.index')
                ->with('status', 'Oznaka je već neaktivna.');
        }

        $oznake->update([
            'status' => CulturalTag::STATUS_INACTIVE,
        ]);

        return redirect()
            ->route('cultural-tags.index')
            ->with('status', 'Oznaka je deaktivirana.');
    }

    public function activate(CulturalTag $oznake): RedirectResponse
    {
        if ($oznake->isActive()) {
            return redirect()
                ->route('cultural-tags.index')
                ->with('status', 'Oznaka je već aktivna.');
        }

        if (CulturalTag::activeDuplicateExists($oznake->naziv, $oznake->id)) {
            return redirect()
                ->route('cultural-tags.edit', $oznake)
                ->withErrors([
                    'naziv' => 'Ne može se aktivirati: već postoji aktivna oznaka sa istim nazivom.',
                ]);
        }

        $oznake->update([
            'status' => CulturalTag::STATUS_ACTIVE,
        ]);

        return redirect()
            ->route('cultural-tags.index')
            ->with('status', 'Oznaka je aktivirana.');
    }
}
