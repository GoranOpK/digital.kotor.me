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

        $user = auth()->user();
        $isKkAdmin = $user && $user->role && $user->role->name === 'kk_admin';

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

        $selectedDate = null;
        $selectedDateEvents = null;
        $selectedDateParam = $request->query('date');

        // Samo za korisnički pregled: kad korisnik klikne datum, podvučemo događaje za taj datum ispod kalendara.
        if ($selectedDateParam && !$isKkAdmin) {
            try {
                $selectedDate = Carbon::createFromFormat('Y-m-d', $selectedDateParam)->startOfDay();

                $selectedDateEvents = CulturalEvent::query()
                    ->where('status', 'published')
                    ->whereDate('datum_od', '<=', $selectedDate)
                    ->where(function ($query) use ($selectedDate) {
                        $query->whereNull('datum_do')
                            ->orWhereDate('datum_do', '>=', $selectedDate);
                    })
                    ->orderBy('vrijeme')
                    ->orderBy('id')
                    ->get();
            } catch (\Throwable $e) {
                $selectedDate = null;
                $selectedDateEvents = null;
            }
        }

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
            ->where(function ($query) use ($today) {
                $query->where(function ($q) use ($today) {
                    $q->whereNotNull('datum_do')
                        ->whereDate('datum_do', '>=', $today);
                })->orWhere(function ($q) use ($today) {
                    $q->whereNull('datum_do')
                        ->whereDate('datum_od', '>=', $today);
                });
            })
            ->orderBy('datum_od')
            ->take(3)
            ->get();

        $upcomingEvents = CulturalEvent::query()
            ->where('status', 'published')
            ->whereDate('datum_od', '>=', $today)
            ->orderBy('datum_od')
            ->orderBy('vrijeme')
            ->take(2)
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
        $firstWeekdayIso = $monthStart->dayOfWeekIso; // 1 = ponedjeljak, 7 = nedjelja

        // Prazna mjesta prije prvog dana mjeseca radi pravilnog poravnanja kolona.
        for ($i = 1; $i < $firstWeekdayIso; $i++) {
            $calendarDays[] = [
                'is_placeholder' => true,
            ];
        }

        for ($date = $monthStart->copy(); $date->lte($monthEnd); $date->addDay()) {
            $dateKey = $date->format('Y-m-d');
            $eventCount = $eventDayCounts[$dateKey] ?? 0;
            $calendarDays[] = [
                'is_placeholder' => false,
                'day' => $date->day,
                'date' => $dateKey,
                'event_count' => $eventCount,
                'has_event' => $eventCount > 0,
                'is_today' => $date->isSameDay($today),
            ];
        }

        $calendarMonthLabel = ucfirst($monthStart->translatedFormat('F Y'));
        $monthOptions = [];
        for ($cursor = $minMonthStart->copy(); $cursor->lte($maxMonthStart); $cursor->addMonth()) {
            $monthOptions[] = [
                'value' => $cursor->format('Y-m'),
                'label' => ucfirst($cursor->translatedFormat('F Y')),
            ];
        }
        $selectedMonthValue = $monthStart->format('Y-m');

        return view('cultural-calendar.index', compact(
            'todayCount',
            'weekCount',
            'monthCount',
            'featuredEvents',
            'upcomingEvents',
            'calendarDays',
            'calendarMonthLabel',
            'monthOptions',
            'selectedMonthValue',
            'selectedDate',
            'selectedDateEvents',
            'isKkAdmin'
        ));
    }

    public function events(Request $request)
    {
        $date = $request->query('date');
        $weekStart = null;
        $weekEnd = null;
        $today = Carbon::today();

        $eventsQuery = CulturalEvent::query()
            ->where('status', 'published')
            ->where(function ($query) use ($today) {
                $query->where(function ($q) use ($today) {
                    $q->whereNotNull('datum_do')
                        ->whereDate('datum_do', '>=', $today);
                })->orWhere(function ($q) use ($today) {
                    $q->whereNull('datum_do')
                        ->whereDate('datum_od', '>=', $today);
                });
            })
            ->orderBy('datum_od');

        $weekStartParam = $request->query('week_start');
        $weekEndParam = $request->query('week_end');

        if ($weekStartParam && $weekEndParam) {
            try {
                $weekStart = Carbon::createFromFormat('Y-m-d', $weekStartParam)->startOfDay();
                $weekEnd = Carbon::createFromFormat('Y-m-d', $weekEndParam)->endOfDay();

                if ($weekStart->gt($weekEnd)) {
                    [$weekStart, $weekEnd] = [$weekEnd->copy()->startOfDay(), $weekStart->copy()->endOfDay()];
                }

                $eventsQuery
                    ->whereDate('datum_od', '<=', $weekEnd->toDateString())
                    ->where(function ($query) use ($weekStart) {
                        $query->whereNull('datum_do')
                            ->orWhereDate('datum_do', '>=', $weekStart->toDateString());
                    });
            } catch (\Throwable $e) {
                $weekStart = null;
                $weekEnd = null;
            }
        }

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

        return view('cultural-calendar.events', compact('events', 'date', 'weekStart', 'weekEnd'));
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

    public function archive(Request $request)
    {
        $today = Carbon::today();
        $events = CulturalEvent::query()
            ->where('status', 'published')
            ->where(function ($query) use ($today) {
                $query->where(function ($q) use ($today) {
                    $q->whereNotNull('datum_do')
                        ->whereDate('datum_do', '<', $today);
                })->orWhere(function ($q) use ($today) {
                    $q->whereNull('datum_do')
                        ->whereDate('datum_od', '<', $today);
                });
            })
            ->orderByDesc('datum_od')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('cultural-calendar.archive', compact('events'));
    }
}
