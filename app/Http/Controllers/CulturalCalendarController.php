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
        if ($selectedDateParam && ! $isKkAdmin) {
            try {
                $selectedDate = Carbon::createFromFormat('Y-m-d', $selectedDateParam)->startOfDay();

                $selectedDateEvents = CulturalEvent::query()
                    ->publiclyVisible()
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
            ->publiclyVisible()
            ->whereDate('datum_od', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('datum_do')
                    ->orWhereDate('datum_do', '>=', $today);
            })
            ->count();

        $weekCount = CulturalEvent::query()
            ->publiclyVisible()
            ->whereDate('datum_od', '<=', $weekEnd)
            ->where(function ($query) use ($today) {
                $query->whereNull('datum_do')
                    ->orWhereDate('datum_do', '>=', $today);
            })
            ->count();

        $monthCount = CulturalEvent::query()
            ->publiclyVisible()
            ->where(function ($query) use ($monthStart, $monthEnd) {
                $query->where(function ($single) use ($monthStart, $monthEnd) {
                    $single->whereNull('datum_do')
                        ->whereDate('datum_od', '>=', $monthStart)
                        ->whereDate('datum_od', '<=', $monthEnd);
                })->orWhere(function ($range) use ($monthStart, $monthEnd) {
                    $range->whereNotNull('datum_do')
                        ->whereDate('datum_od', '<=', $monthEnd)
                        ->whereDate('datum_do', '>=', $monthStart);
                });
            })
            ->count();

        // Istaknuti: samo published (PO-CR4B-03) — cancelled isključeni iz prikaza.
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
            ->publiclyVisible()
            ->whereDate('datum_od', '>=', $today)
            ->orderBy('datum_od')
            ->orderBy('vrijeme')
            ->take(3)
            ->get();

        $monthEvents = CulturalEvent::query()
            ->publiclyVisible()
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
            'today',
            'weekEnd',
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
        Carbon::setLocale('sr');

        $today = Carbon::today();
        $date = null;
        $weekStart = null;
        $weekEnd = null;
        $selectedMonthStart = null;
        $selectedMonthLabel = null;
        $selectedMonthValue = null;

        $dateParam = $request->query('date');
        $weekStartParam = $request->query('week_start');
        $weekEndParam = $request->query('week_end');
        $monthParam = $request->query('month');

        if (is_string($dateParam) && $dateParam !== '') {
            try {
                $parsedDate = Carbon::createFromFormat('Y-m-d', $dateParam)->startOfDay();
                if ($parsedDate->format('Y-m-d') === $dateParam) {
                    $date = $dateParam;
                }
            } catch (\Throwable $e) {
                $date = null;
            }
        }

        if ($date === null && $weekStartParam && $weekEndParam) {
            try {
                $parsedWeekStart = Carbon::createFromFormat('Y-m-d', $weekStartParam)->startOfDay();
                $parsedWeekEnd = Carbon::createFromFormat('Y-m-d', $weekEndParam)->endOfDay();

                if (
                    $parsedWeekStart->format('Y-m-d') === $weekStartParam
                    && $parsedWeekEnd->format('Y-m-d') === $weekEndParam
                ) {
                    $weekStart = $parsedWeekStart;
                    $weekEnd = $parsedWeekEnd;

                    if ($weekStart->gt($weekEnd)) {
                        [$weekStart, $weekEnd] = [$weekEnd->copy()->startOfDay(), $weekStart->copy()->endOfDay()];
                    }
                }
            } catch (\Throwable $e) {
                $weekStart = null;
                $weekEnd = null;
            }
        }

        if ($date === null && $weekStart === null && is_string($monthParam) && $monthParam !== '') {
            if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthParam) === 1) {
                try {
                    $parsedMonth = Carbon::createFromFormat('!Y-m', $monthParam)->startOfMonth();
                    if ($parsedMonth->format('Y-m') === $monthParam) {
                        $selectedMonthStart = $parsedMonth;
                        $selectedMonthLabel = ucfirst($parsedMonth->translatedFormat('F Y'));
                        $selectedMonthValue = $monthParam;
                    }
                } catch (\Throwable $e) {
                    $selectedMonthStart = null;
                    $selectedMonthLabel = null;
                    $selectedMonthValue = null;
                }
            }
        }

        $q = null;
        $qParam = $request->query('q');
        if (is_string($qParam)) {
            $trimmedQ = trim($qParam);
            if ($trimmedQ !== '') {
                $q = $trimmedQ;
            }
        }

        $category = null;
        $categoryParam = $request->query('category');
        if (is_string($categoryParam) && $categoryParam !== '' && in_array($categoryParam, CulturalEvent::CATEGORIES, true)) {
            $category = $categoryParam;
        }

        // PO-CR3-04: dropdown lokacija ostaje iz published (CR-004B ne mijenja filter definiciju).
        $locationOptions = CulturalEvent::query()
            ->where('status', 'published')
            ->whereNotNull('lokacija')
            ->where('lokacija', '!=', '')
            ->distinct()
            ->orderBy('lokacija')
            ->pluck('lokacija')
            ->values()
            ->all();

        $location = null;
        $locationParam = $request->query('location');
        if (is_string($locationParam) && $locationParam !== '' && in_array($locationParam, $locationOptions, true)) {
            $location = $locationParam;
        }

        $eventsQuery = CulturalEvent::query()
            ->publiclyVisible();

        if ($date !== null) {
            $selectedDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();

            $eventsQuery
                ->where(function ($query) use ($today) {
                    $query->where(function ($inner) use ($today) {
                        $inner->whereNotNull('datum_do')
                            ->whereDate('datum_do', '>=', $today);
                    })->orWhere(function ($inner) use ($today) {
                        $inner->whereNull('datum_do')
                            ->whereDate('datum_od', '>=', $today);
                    });
                })
                ->whereDate('datum_od', '<=', $selectedDate)
                ->where(function ($query) use ($selectedDate) {
                    $query->whereNull('datum_do')
                        ->orWhereDate('datum_do', '>=', $selectedDate);
                });
        } elseif ($weekStart !== null && $weekEnd !== null) {
            $eventsQuery
                ->where(function ($query) use ($today) {
                    $query->where(function ($inner) use ($today) {
                        $inner->whereNotNull('datum_do')
                            ->whereDate('datum_do', '>=', $today);
                    })->orWhere(function ($inner) use ($today) {
                        $inner->whereNull('datum_do')
                            ->whereDate('datum_od', '>=', $today);
                    });
                })
                ->whereDate('datum_od', '<=', $weekEnd->toDateString())
                ->where(function ($query) use ($weekStart) {
                    $query->whereNull('datum_do')
                        ->orWhereDate('datum_do', '>=', $weekStart->toDateString());
                });
        } elseif ($selectedMonthStart !== null) {
            $monthEnd = $selectedMonthStart->copy()->endOfMonth();

            $eventsQuery->where(function ($query) use ($selectedMonthStart, $monthEnd) {
                $query->where(function ($single) use ($selectedMonthStart, $monthEnd) {
                    $single->whereNull('datum_do')
                        ->whereDate('datum_od', '>=', $selectedMonthStart)
                        ->whereDate('datum_od', '<=', $monthEnd);
                })->orWhere(function ($range) use ($selectedMonthStart, $monthEnd) {
                    $range->whereNotNull('datum_do')
                        ->whereDate('datum_od', '<=', $monthEnd)
                        ->whereDate('datum_do', '>=', $selectedMonthStart);
                });
            });
        } else {
            $eventsQuery->where(function ($query) use ($today) {
                $query->where(function ($inner) use ($today) {
                    $inner->whereNotNull('datum_do')
                        ->whereDate('datum_do', '>=', $today);
                })->orWhere(function ($inner) use ($today) {
                    $inner->whereNull('datum_do')
                        ->whereDate('datum_od', '>=', $today);
                });
            });
        }

        if ($q !== null) {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $eventsQuery->where(function ($query) use ($like) {
                $query->where('naslov', 'like', $like)
                    ->orWhere('opis', 'like', $like)
                    ->orWhere('lokacija', 'like', $like);
            });
        }

        if ($category !== null) {
            $eventsQuery->where('kategorija', $category);
        }

        if ($location !== null) {
            $eventsQuery->where('lokacija', $location);
        }

        $events = $eventsQuery
            ->orderBy('datum_od')
            ->paginate(12)
            ->withQueryString();

        $categoryOptions = CulturalEvent::CATEGORIES;

        return view('cultural-calendar.events', compact(
            'events',
            'date',
            'weekStart',
            'weekEnd',
            'selectedMonthLabel',
            'selectedMonthValue',
            'q',
            'category',
            'location',
            'categoryOptions',
            'locationOptions'
        ));
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
            ->publiclyVisible()
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
            ->publiclyVisible()
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

    public function show(Request $request, CulturalEvent $event)
    {
        if (! $event->isPubliclyVisible()) {
            abort(404);
        }

        $backUrl = (string) $request->query('back', '');
        if (! str_starts_with($backUrl, '/kalendar-kulture')) {
            $backUrl = route('cultural-calendar.events');
        }

        return view('cultural-calendar.show', compact('event', 'backUrl'));
    }
}
