<?php

namespace App\Http\Controllers;

use App\Models\CulturalEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        $validated['featured'] = $request->boolean('featured');
        $this->assertFeaturedLimit($validated);

        if ($request->hasFile('slika')) {
            $validated['slika'] = $request->file('slika')->store('cultural-events', 'public');
        }

        $validated['created_by'] = auth()->id();

        CulturalEvent::create($validated);

        return redirect()
            ->route('cultural-events.index')
            ->with('status', 'Događaj je uspješno kreiran.');
    }

    public function edit(CulturalEvent $dogadjaji)
    {
        $culturalEvent = $dogadjaji;

        return view('cultural-calendar.admin.edit', [
            'event' => $culturalEvent,
            'categories' => CulturalEvent::CATEGORIES,
            'statuses' => CulturalEvent::STATUSES,
            'maxEventDate' => Carbon::today()->addYear()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function update(Request $request, CulturalEvent $dogadjaji)
    {
        $culturalEvent = $dogadjaji;
        $validated = $request->validate($this->rules());
        $validated['featured'] = $request->boolean('featured');
        $this->assertFeaturedLimit($validated, $culturalEvent);

        if ($request->hasFile('slika')) {
            if ($culturalEvent->slika) {
                Storage::disk('public')->delete($culturalEvent->slika);
            }
            $validated['slika'] = $request->file('slika')->store('cultural-events', 'public');
        }

        $validated['created_by'] = $culturalEvent->created_by ?? auth()->id();

        $culturalEvent->update($validated);

        return redirect()
            ->route('cultural-events.index')
            ->with('status', 'Događaj je uspješno ažuriran.');
    }

    public function destroy(CulturalEvent $dogadjaji)
    {
        $culturalEvent = $dogadjaji;
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

    private function assertFeaturedLimit(array $validated, ?CulturalEvent $currentEvent = null): void
    {
        if (empty($validated['featured'])) {
            return;
        }

        $status = $validated['status'] ?? 'draft';
        if ($status !== 'published') {
            return;
        }

        $today = Carbon::today();
        $start = Carbon::parse($validated['datum_od'])->startOfDay();
        $end = !empty($validated['datum_do'])
            ? Carbon::parse($validated['datum_do'])->startOfDay()
            : $start->copy();

        // Dozvoljeno je isticanje, ali ne ulazi u limit ako je događaj završen.
        if ($end->lt($today)) {
            return;
        }

        $activeFeaturedCount = CulturalEvent::query()
            ->where('featured', true)
            ->where('status', 'published')
            ->when($currentEvent, function ($query) use ($currentEvent) {
                $query->where('id', '!=', $currentEvent->id);
            })
            ->where(function ($query) use ($today) {
                $query->where(function ($q) use ($today) {
                    $q->whereNotNull('datum_do')
                        ->whereDate('datum_do', '>=', $today);
                })->orWhere(function ($q) use ($today) {
                    $q->whereNull('datum_do')
                        ->whereDate('datum_od', '>=', $today);
                });
            })
            ->count();

        if ($activeFeaturedCount >= 3) {
            throw ValidationException::withMessages([
                'featured' => 'Dozvoljeno je maksimalno 3 aktivna istaknuta događaja. Novi možete označiti kao istaknuti kada se jedan od trenutnih završi.',
            ]);
        }
    }
}
