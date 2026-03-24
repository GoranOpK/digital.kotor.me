<?php

namespace App\Http\Controllers;

use App\Models\CulturalEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CulturalCalendarController extends Controller
{
    public function index()
    {
        Carbon::setLocale('sr');

        $today = Carbon::today();
        $weekEnd = Carbon::today()->endOfWeek();
        $monthEnd = Carbon::today()->endOfMonth();
        $monthStart = Carbon::today()->startOfMonth();

        $todayCount = CulturalEvent::query()
            ->where('status', 'published')
            ->whereDate('datum_od', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('datum_do')
                    ->orWhereDate('datum_do', '>=', $today);
            })
            ->count();

        $weekCount = CulturalEvent::query()
            ->where('status', 'published')
            ->whereDate('datum_od', '<=', $weekEnd)
            ->where(function ($query) use ($today) {
                $query->whereNull('datum_do')
                    ->orWhereDate('datum_do', '>=', $today);
            })
            ->count();

        $monthCount = CulturalEvent::query()
            ->where('status', 'published')
            ->whereDate('datum_od', '<=', $monthEnd)
            ->where(function ($query) use ($today) {
                $query->whereNull('datum_do')
                    ->orWhereDate('datum_do', '>=', $today);
            })
            ->count();

        $featuredEvents = CulturalEvent::query()
            ->where('status', 'published')
            ->where('featured', true)
            ->orderBy('datum_od')
            ->take(4)
            ->get();

        $monthEvents = CulturalEvent::query()
            ->where('status', 'published')
            ->whereDate('datum_od', '<=', $monthEnd)
            ->where(function ($query) use ($monthStart) {
                $query->whereNull('datum_do')
                    ->orWhereDate('datum_do', '>=', $monthStart);
            })
            ->get(['datum_od', 'datum_do']);

        $eventDays = [];
        foreach ($monthEvents as $event) {
            $start = Carbon::parse($event->datum_od)->startOfDay();
            $end = $event->datum_do ? Carbon::parse($event->datum_do)->startOfDay() : $start->copy();

            if ($start->lt($monthStart)) {
                $start = $monthStart->copy();
            }
            if ($end->gt($monthEnd)) {
                $end = $monthEnd->copy();
            }

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $eventDays[$date->format('Y-m-d')] = true;
            }
        }

        $calendarDays = [];
        for ($date = $monthStart->copy(); $date->lte($monthEnd); $date->addDay()) {
            $calendarDays[] = [
                'day' => $date->day,
                'date' => $date->format('Y-m-d'),
                'has_event' => isset($eventDays[$date->format('Y-m-d')]),
            ];
        }

        $calendarMonthLabel = ucfirst($monthStart->translatedFormat('F Y'));

        return view('cultural-calendar.index', compact(
            'todayCount',
            'weekCount',
            'monthCount',
            'featuredEvents',
            'calendarDays',
            'calendarMonthLabel'
        ));
    }

    public function events(Request $request)
    {
        $date = $request->query('date');

        $eventsQuery = CulturalEvent::query()
            ->where('status', 'published')
            ->orderBy('datum_od');

        if ($date) {
            try {
                $selectedDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
                $eventsQuery
                    ->whereDate('datum_od', '<=', $selectedDate)
                    ->where(function ($query) use ($selectedDate) {
                        $query->whereNull('datum_do')
                            ->orWhereDate('datum_do', '>=', $selectedDate);
                    });
            } catch (\Throwable $e) {
                // Ignoriši nevalidan datum i prikaži regularnu listu.
            }
        }

        $events = $eventsQuery->paginate(12)->withQueryString();

        return view('cultural-calendar.events', compact('events', 'date'));
    }

    public function day(string $date)
    {
        try {
            $selectedDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        } catch (\Throwable $e) {
            abort(404);
        }

        if ($selectedDate->format('Y-m-d') !== $date) {
            abort(404);
        }

        $user = auth()->user();

        if ($user && $user->role && $user->role->name === 'kk_admin') {
            return redirect()->route('cultural-events.create', [
                'datum_od' => $selectedDate->format('Y-m-d'),
            ]);
        }

        $events = CulturalEvent::query()
            ->where('status', 'published')
            ->whereDate('datum_od', '<=', $selectedDate)
            ->where(function ($query) use ($selectedDate) {
                $query->whereNull('datum_do')
                    ->orWhereDate('datum_do', '>=', $selectedDate);
            })
            ->orderBy('vrijeme')
            ->orderBy('id')
            ->get();

        return view('cultural-calendar.day', [
            'events' => $events,
            'selectedDate' => $selectedDate,
        ]);
    }
}
