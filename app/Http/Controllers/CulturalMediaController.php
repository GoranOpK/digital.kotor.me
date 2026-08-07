<?php

namespace App\Http\Controllers;

use App\Http\Requests\CulturalMediaStoreRequest;
use App\Http\Requests\CulturalMediaUpdateRequest;
use App\Models\CulturalMedia;
use App\Services\CulturalMedia\CulturalMediaFileValidator;
use App\Services\CulturalMedia\CulturalMediaLinkInspector;
use App\Services\CulturalMedia\CulturalMediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Urednički CRUD kataloga Medija (TS-008 Korak 1).
 * Bez cutover-a CulturalEvent.slika, bez veza, bez audita.
 */
class CulturalMediaController extends Controller
{
    public function __construct(
        private CulturalMediaFileValidator $fileValidator,
        private CulturalMediaStorage $storage,
        private CulturalMediaLinkInspector $linkInspector,
    ) {}

    public function index(): View
    {
        $mediaItems = CulturalMedia::query()
            ->orderedByName()
            ->paginate(20);

        return view('cultural-calendar.admin.media.index', compact('mediaItems'));
    }

    public function create(): View
    {
        return view('cultural-calendar.admin.media.create', [
            'statuses' => CulturalMedia::STATUSES,
            'statusLabels' => CulturalMedia::STATUS_LABELS,
            'purposes' => CulturalMedia::PURPOSES,
            'purposeLabels' => CulturalMedia::PURPOSE_LABELS,
        ]);
    }

    public function store(CulturalMediaStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $file = $request->file('fajl');
        $meta = $this->fileValidator->validate($file);
        $stored = $this->storage->store($file, $meta);

        CulturalMedia::create([
            'naziv' => $validated['naziv'],
            'alt_tekst' => $validated['alt_tekst'],
            'namjena' => $validated['namjena'],
            'status' => $validated['status'],
            'opis' => $validated['opis'] ?? null,
            'autor' => $validated['autor'] ?? null,
            'izvor' => $validated['izvor'] ?? null,
            'licenca' => $validated['licenca'] ?? null,
            'tagovi' => null,
            'originalni_naziv' => $meta['originalni_naziv'],
            'interni_naziv' => $stored['interni_naziv'],
            'mime' => $meta['mime'],
            'format' => $meta['format'],
            'sirina' => $meta['sirina'],
            'visina' => $meta['visina'],
            'velicina' => $meta['velicina'],
            'storage_path' => $stored['storage_path'],
            'creator_id' => auth()->id(),
        ]);

        return redirect()
            ->route('cultural-media.index')
            ->with('status', 'Medij je uspješno kreiran.');
    }

    public function edit(CulturalMedia $mediji): View
    {
        return view('cultural-calendar.admin.media.edit', [
            'media' => $mediji,
            'statuses' => CulturalMedia::STATUSES,
            'statusLabels' => CulturalMedia::STATUS_LABELS,
            'purposes' => CulturalMedia::PURPOSES,
            'purposeLabels' => CulturalMedia::PURPOSE_LABELS,
        ]);
    }

    public function update(CulturalMediaUpdateRequest $request, CulturalMedia $mediji): RedirectResponse
    {
        $mediji->update($request->validated());

        return redirect()
            ->route('cultural-media.index')
            ->with('status', 'Medij je uspješno ažuriran.');
    }

    public function deactivate(CulturalMedia $mediji): RedirectResponse
    {
        if ($mediji->isInactive()) {
            return redirect()
                ->route('cultural-media.index')
                ->with('status', 'Medij je već neaktivan.');
        }

        $mediji->update([
            'status' => CulturalMedia::STATUS_INACTIVE,
        ]);

        return redirect()
            ->route('cultural-media.index')
            ->with('status', 'Medij je deaktiviran.');
    }

    public function activate(CulturalMedia $mediji): RedirectResponse
    {
        if ($mediji->isActive()) {
            return redirect()
                ->route('cultural-media.index')
                ->with('status', 'Medij je već aktivan.');
        }

        $mediji->update([
            'status' => CulturalMedia::STATUS_ACTIVE,
        ]);

        return redirect()
            ->route('cultural-media.index')
            ->with('status', 'Medij je aktiviran.');
    }

    public function destroy(CulturalMedia $mediji): RedirectResponse
    {
        if ($this->linkInspector->hasLinks($mediji)) {
            return redirect()
                ->route('cultural-media.index')
                ->withErrors([
                    'medij' => 'Medij se ne može trajno obrisati dok postoje poslovne veze.',
                ]);
        }

        $path = $mediji->storage_path;
        $mediji->delete();
        $this->storage->deleteFile(new CulturalMedia(['storage_path' => $path]));

        return redirect()
            ->route('cultural-media.index')
            ->with('status', 'Medij je trajno obrisan.');
    }
}
