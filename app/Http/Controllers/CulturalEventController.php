<?php

namespace App\Http\Controllers;

use App\Models\CulturalEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CulturalEventController extends Controller
{
    public function index()
    {
        $events = CulturalEvent::query()
            ->latest('datum_od')
            ->latest('id')
            ->paginate(12);

        return view('cultural-calendar.admin.index', compact('events'));
    }

    public function create()
    {
        return view('cultural-calendar.admin.create', [
            'categories' => CulturalEvent::CATEGORIES,
            'statuses' => CulturalEvent::STATUSES,
            'maxEventDate' => Carbon::today()->addYear()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        if ($request->hasFile('slika')) {
            $validated['slika'] = $request->file('slika')->store('cultural-events', 'public');
        }

        $validated['featured'] = $request->boolean('featured');
        $validated['created_by'] = auth()->id();

        CulturalEvent::create($validated);

        return redirect()
            ->route('cultural-events.index')
            ->with('status', 'Događaj je uspješno kreiran.');
    }

    public function edit(CulturalEvent $culturalEvent)
    {
        return view('cultural-calendar.admin.edit', [
            'event' => $culturalEvent,
            'categories' => CulturalEvent::CATEGORIES,
            'statuses' => CulturalEvent::STATUSES,
            'maxEventDate' => Carbon::today()->addYear()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function update(Request $request, CulturalEvent $culturalEvent)
    {
        $validated = $request->validate($this->rules());

        if ($request->hasFile('slika')) {
            if ($culturalEvent->slika) {
                Storage::disk('public')->delete($culturalEvent->slika);
            }
            $validated['slika'] = $request->file('slika')->store('cultural-events', 'public');
        }

        $validated['featured'] = $request->boolean('featured');
        $validated['created_by'] = $culturalEvent->created_by ?? auth()->id();

        $culturalEvent->update($validated);

        return redirect()
            ->route('cultural-events.index')
            ->with('status', 'Događaj je uspješno ažuriran.');
    }

    public function destroy(CulturalEvent $culturalEvent)
    {
        if ($culturalEvent->slika) {
            Storage::disk('public')->delete($culturalEvent->slika);
        }

        $culturalEvent->delete();

        return redirect()
            ->route('cultural-events.index')
            ->with('status', 'Događaj je obrisan.');
    }

    private function rules(): array
    {
        $maxDate = Carbon::today()->addYear()->endOfMonth()->format('Y-m-d');

        return [
            'naslov' => ['required', 'string', 'max:255'],
            'opis' => ['nullable', 'string'],
            'datum_od' => ['required', 'date', 'before_or_equal:' . $maxDate],
            'datum_do' => ['nullable', 'date', 'after_or_equal:datum_od', 'before_or_equal:' . $maxDate],
            'vrijeme' => ['nullable', 'date_format:H:i'],
            'lokacija' => ['nullable', 'string', 'max:255'],
            'kategorija' => ['required', Rule::in(CulturalEvent::CATEGORIES)],
            'slika' => ['nullable', 'image', 'max:5120'],
            'status' => ['required', Rule::in(CulturalEvent::STATUSES)],
            'featured' => ['nullable', 'boolean'],
        ];
    }
}
