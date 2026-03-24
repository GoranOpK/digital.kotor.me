<?php

namespace App\Http\Controllers;

use App\Models\CulturalEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CulturalCalendarController extends Controller
{
    public function index(Request $request)
    {
        Carbon::setLocale('sr');

        $today = Carbon::today();
        $weekEnd = Carbon::today()->endOfWeek();
        $minMonthStart = Carbon::today()->startOfMonth();
        $maxMonthStart = Carbon::today()->copy()->addYear()->startOfMonth();
        $maxAllowedDate = Carbon::today()->copy()->addYear()->endOfMonth();

        $selectedMonth = $request->query('month');
        $monthStart = $minMonthStart->copy();

        if ($selectedMonth) {
            try {
                $candidate = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
                if ($candidate->lt($minMonthStart)) {
                    $monthStart = $minMonthStart->copy();
                } elseif ($candidate->gt($maxMonthStart)) {
                    $monthStart = $maxMonthStart->copy();
                } else {
                    $monthStart = $candidate;
                }
            } catch (\Throwable $e) {
                $monthStart = $minMonthStart->copy();
            }
        }

        $monthEnd = $monthStart->copy()->endOfMonth();

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

        $eventDayCounts = [];
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
                $dateKey = $date->format('Y-m-d');
                $eventDayCounts[$dateKey] = ($eventDayCounts[$dateKey] ?? 0) + 1;
            }
        }

        $calendarDays = [];
        for ($date = $monthStart->copy(); $date->lte($monthEnd); $date->addDay()) {
            $dateKey = $date->format('Y-m-d');
            $eventCount = $eventDayCounts[$dateKey] ?? 0;
            $calendarDays[] = [
                'day' => $date->day,
                'date' => $dateKey,
                'event_count' => $eventCount,
                'has_event' => $eventCount > 0,
                'is_today' => $date->isSameDay($today),
            ];
        }

        $calendarMonthLabel = ucfirst($monthStart->translatedFormat('F Y'));
        $previousMonth = $monthStart->copy()->subMonth();
        $nextMonth = $monthStart->copy()->addMonth();
        $previousMonthQuery = $previousMonth->gte($minMonthStart) ? $previousMonth->format('Y-m') : null;
        $nextMonthQuery = $nextMonth->lte($maxMonthStart) ? $nextMonth->format('Y-m') : null;
        $calendarWindowLabel = $today->format('d.m.Y') . ' - ' . $maxAllowedDate->format('d.m.Y');

        return view('cultural-calendar.index', compact(
            'todayCount',
            'weekCount',
            'monthCount',
            'featuredEvents',
            'calendarDays',
            'calendarMonthLabel',
            'previousMonthQuery',
            'nextMonthQuery',
            'calendarWindowLabel'
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
