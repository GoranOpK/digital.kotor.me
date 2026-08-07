<?php

namespace App\Http\Controllers;

use App\Http\Requests\CulturalCategoryRequest;
use App\Models\CulturalCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Urednički CRUD kataloga Kategorija (TS-007 Korak 1).
 */
class CulturalCategoryController extends Controller
{
    public function index(): View
    {
        $categories = CulturalCategory::query()
            ->orderedByName()
            ->paginate(20);

        return view('cultural-calendar.admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('cultural-calendar.admin.categories.create', [
            'statuses' => CulturalCategory::STATUSES,
            'statusLabels' => CulturalCategory::STATUS_LABELS,
        ]);
    }

    public function store(CulturalCategoryRequest $request): RedirectResponse
    {
        CulturalCategory::create($request->validated());

        return redirect()
            ->route('cultural-categories.index')
            ->with('status', 'Kategorija je uspješno kreirana.');
    }

    public function edit(CulturalCategory $kategorije): View
    {
        return view('cultural-calendar.admin.categories.edit', [
            'category' => $kategorije,
            'statuses' => CulturalCategory::STATUSES,
            'statusLabels' => CulturalCategory::STATUS_LABELS,
        ]);
    }

    public function update(CulturalCategoryRequest $request, CulturalCategory $kategorije): RedirectResponse
    {
        $kategorije->update($request->validated());

        return redirect()
            ->route('cultural-categories.index')
            ->with('status', 'Kategorija je uspješno ažurirana.');
    }

    public function deactivate(CulturalCategory $kategorije): RedirectResponse
    {
        if ($kategorije->isInactive()) {
            return redirect()
                ->route('cultural-categories.index')
                ->with('status', 'Kategorija je već neaktivna.');
        }

        $kategorije->update([
            'status' => CulturalCategory::STATUS_INACTIVE,
        ]);

        return redirect()
            ->route('cultural-categories.index')
            ->with('status', 'Kategorija je deaktivirana.');
    }

    public function activate(CulturalCategory $kategorije): RedirectResponse
    {
        if ($kategorije->isActive()) {
            return redirect()
                ->route('cultural-categories.index')
                ->with('status', 'Kategorija je već aktivna.');
        }

        if (CulturalCategory::isForbiddenName($kategorije->naziv)) {
            return redirect()
                ->route('cultural-categories.edit', $kategorije)
                ->withErrors([
                    'naziv' => 'Kategorija „Nešto drugo“ nije dozvoljena. Promijenite naziv prije aktivacije.',
                ]);
        }

        if (CulturalCategory::activeDuplicateExists($kategorije->naziv, $kategorije->id)) {
            return redirect()
                ->route('cultural-categories.edit', $kategorije)
                ->withErrors([
                    'naziv' => 'Ne može se aktivirati: već postoji aktivna kategorija sa istim nazivom.',
                ]);
        }

        $kategorije->update([
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        return redirect()
            ->route('cultural-categories.index')
            ->with('status', 'Kategorija je aktivirana.');
    }
}
