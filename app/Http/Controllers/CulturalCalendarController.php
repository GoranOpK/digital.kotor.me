<?php

namespace App\Http\Controllers;

use App\Models\CulturalEventEntry;
use App\Services\CulturalCalendar\CulturalPublicEventQuery;
use App\Services\CulturalCalendar\CulturalPublicManifestationQuery;
use App\Services\CulturalCalendar\CulturalPublicSearchQuery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CulturalCalendarController extends Controller
{
    public function index(Request $request): View
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
        $selectedMonthValue = $monthStart->format('Y-m');

        $calendarMonthLabel = ucfirst($monthStart->translatedFormat('F Y'));
        $monthOptions = [];
        for ($cursor = $minMonthStart->copy(); $cursor->lte($maxMonthStart); $cursor->addMonth()) {
            $monthOptions[] = [
                'value' => $cursor->format('Y-m'),
                'label' => ucfirst($cursor->translatedFormat('F Y')),
            ];
        }

        $selectedDateParam = $request->query('date');

        return $this->indexCanonical(
            $request,
            $today,
            $weekEnd,
            $monthStart,
            $monthEnd,
            $selectedMonthValue,
            $calendarMonthLabel,
            $monthOptions,
            $selectedDateParam,
            $isKkAdmin
        );
    }

    /**
     * Canonical naslovna — CulturalEventEntry (6A-07).
     *
     * @param  list<array{value: string, label: string}>  $monthOptions
     */
    private function indexCanonical(
        Request $request,
        Carbon $today,
        Carbon $weekEnd,
        Carbon $monthStart,
        Carbon $monthEnd,
        string $selectedMonthValue,
        string $calendarMonthLabel,
        array $monthOptions,
        mixed $selectedDateParam,
        bool $isKkAdmin
    ): View {
        $publicQuery = app(CulturalPublicEventQuery::class);
        $cardEager = ['category', 'coverMedia', 'occurrences.location'];

        $todayCount = $publicQuery->filterByDate($today->toDateString())->count();
        $weekCount = $publicQuery->filterByWeek(
            $today->toDateString(),
            $weekEnd->toDateString()
        )->count();
        $monthCount = $publicQuery->filterByMonth($selectedMonthValue)->count();

        $featuredEvents = $publicQuery
            ->featuredForPublicIndex()
            ->with($cardEager)
            ->take(CulturalEventEntry::MAX_FEATURED)
            ->get();

        $upcomingEvents = $publicQuery->homepageUpcomingCards(
            null,
            3,
            $cardEager
        );

        $selectedDate = null;
        $selectedDateEvents = null;
        if ($selectedDateParam && ! $isKkAdmin) {
            try {
                $parsedSelected = Carbon::createFromFormat('Y-m-d', $selectedDateParam)->startOfDay();
                if ($parsedSelected->format('Y-m-d') === $selectedDateParam) {
                    $selectedDate = $parsedSelected;
                    $selectedDateEvents = $publicQuery
                        ->filterByDate($selectedDateParam)
                        ->with($cardEager)
                        ->get()
                        ->sortBy(function (CulturalEventEntry $entry) use ($selectedDateParam): string {
                            $occ = $entry->occurrenceOnDate($selectedDateParam);
                            $time = trim((string) ($occ?->vrijeme_od ?? ''));

                            return ($time !== '' ? $time : '00:00:00').'|'.$entry->id;
                        })
                        ->values();
                }
            } catch (\Throwable $e) {
                $selectedDate = null;
                $selectedDateEvents = null;
            }
        }

        $eventDayCounts = $publicQuery->distinctPublicEntryCountsByOccurrenceDate(
            $monthStart->toDateString(),
            $monthEnd->toDateString()
        );

        $calendarDays = $this->buildCalendarDays($monthStart, $monthEnd, $today, $eventDayCounts);

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

    /**
     * @param  array<string, int>  $eventDayCounts
     * @return list<array<string, mixed>>
     */
    private function buildCalendarDays(
        Carbon $monthStart,
        Carbon $monthEnd,
        Carbon $today,
        array $eventDayCounts
    ): array {
        $calendarDays = [];
        $firstWeekdayIso = $monthStart->dayOfWeekIso;

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

        return $calendarDays;
    }

    public function events(Request $request): View
    {
        Carbon::setLocale('sr');

        $parsed = $this->parseEventsListFilters($request);

        return $this->eventsCanonical($request, $parsed);
    }

    /**
     * Shared URL filter parsing for Pretraga (date/week/month/q).
     * Category/location validation is applied in the canonical list query.
     *
     * @return array{
     *     today: Carbon,
     *     date: ?string,
     *     weekStart: ?Carbon,
     *     weekEnd: ?Carbon,
     *     selectedMonthStart: ?Carbon,
     *     selectedMonthLabel: ?string,
     *     selectedMonthValue: ?string,
     *     q: ?string,
     *     categoryParam: mixed,
     *     locationParam: mixed
     * }
     */
    private function parseEventsListFilters(Request $request): array
    {
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

        return [
            'today' => $today,
            'date' => $date,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'selectedMonthStart' => $selectedMonthStart,
            'selectedMonthLabel' => $selectedMonthLabel,
            'selectedMonthValue' => $selectedMonthValue,
            'q' => $q,
            'categoryParam' => $request->query('category'),
            'locationParam' => $request->query('location'),
        ];
    }

    /**
     * Canonical Pretraga — CulturalEventEntry via CulturalPublicEventQuery (6A-06).
     * PHASE 6B-04: Tip sadržaja + connected Event/MF search (PO-6B-01/04/05/10).
     *
     * @param  array<string, mixed>  $parsed
     */
    private function eventsCanonical(Request $request, array $parsed): View
    {
        $date = $parsed['date'];
        $weekStart = $parsed['weekStart'];
        $weekEnd = $parsed['weekEnd'];
        $selectedMonthLabel = $parsed['selectedMonthLabel'];
        $selectedMonthValue = $parsed['selectedMonthValue'];
        $q = $parsed['q'];

        $publicQuery = app(CulturalPublicEventQuery::class);
        $searchQuery = app(CulturalPublicSearchQuery::class);
        $manifestationQuery = app(CulturalPublicManifestationQuery::class);

        $tip = $searchQuery->normalizeTip($request->query('tip'));
        $eventFiltersApplicable = $searchQuery->eventFiltersApplicable($tip);

        $categoryOptions = $publicQuery->categoryOptions()->pluck('naziv')->values()->all();
        $locationOptions = $publicQuery->locationDisplayOptions();

        $category = null;
        $location = null;
        if ($eventFiltersApplicable) {
            $categoryParam = $parsed['categoryParam'];
            if (is_string($categoryParam) && $categoryParam !== '' && in_array($categoryParam, $categoryOptions, true)) {
                $category = $categoryParam;
            }

            $locationParam = $parsed['locationParam'];
            if (is_string($locationParam) && $locationParam !== '' && in_array($locationParam, $locationOptions, true)) {
                $location = $locationParam;
            }
        } else {
            // PO-6B-04: non-applicable Event filters ignored for Sve / Manifestacije.
            $date = null;
            $weekStart = null;
            $weekEnd = null;
            $selectedMonthLabel = null;
            $selectedMonthValue = null;
        }

        if ($tip === CulturalPublicSearchQuery::TIP_DOGADJAJI) {
            $query = $publicQuery->base();
            $query = $publicQuery->filterByQ($q, $query);
            $query = $publicQuery->filterByCategoryName($category, $query);
            $query = $publicQuery->filterByLocationDisplayName($location, $query);

            if ($date !== null) {
                $query = $publicQuery->filterByDate($date, $query);
            } elseif ($weekStart !== null && $weekEnd !== null) {
                $query = $publicQuery->filterByWeek(
                    $weekStart->toDateString(),
                    $weekEnd->toDateString(),
                    $query
                );
            } elseif ($selectedMonthValue !== null) {
                $query = $publicQuery->filterByMonth($selectedMonthValue, $query);
            }

            $results = $publicQuery
                ->orderedByNextRelevantOccurrence(null, $query)
                ->with(['category', 'coverMedia', 'occurrences.location'])
                ->paginate(12)
                ->withQueryString();
        } elseif ($tip === CulturalPublicSearchQuery::TIP_MANIFESTACIJE) {
            $results = $searchQuery->paginateManifestations($q, 12);
        } else {
            $queryParams = $request->query();
            unset($queryParams['page']);
            $results = $searchQuery->paginateCombined(
                q: $q,
                perPage: 12,
                page: max(1, (int) $request->query('page', 1)),
                queryParams: is_array($queryParams) ? $queryParams : [],
            );
        }

        return view('cultural-calendar.events', [
            'results' => $results,
            'events' => $results,
            'tip' => $tip,
            'eventFiltersApplicable' => $eventFiltersApplicable,
            'manifestationQuery' => $manifestationQuery,
            'date' => $date,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'selectedMonthLabel' => $selectedMonthLabel,
            'selectedMonthValue' => $selectedMonthValue,
            'q' => $q,
            'category' => $category,
            'location' => $location,
            'categoryOptions' => $categoryOptions,
            'locationOptions' => $locationOptions,
        ]);
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
            // Entry create ne prima unaprijed datum (OCC se dodaje ručno na edit nacrta).
            return redirect()->route('cultural-event-entries.create');
        }

        $dateYmd = $selectedDate->toDateString();
        $events = app(CulturalPublicEventQuery::class)
            ->filterByDate($dateYmd)
            ->with(['category', 'coverMedia', 'occurrences.location'])
            ->get()
            ->sortBy(function (CulturalEventEntry $entry) use ($dateYmd): string {
                $occ = $entry->occurrenceOnDate($dateYmd);
                $time = trim((string) ($occ?->vrijeme_od ?? ''));

                return ($time !== '' ? $time : '00:00:00').'|'.$entry->id;
            })
            ->values();

        return view('cultural-calendar.day', [
            'events' => $events,
            'selectedDate' => $selectedDate,
        ]);
    }

    public function archive(Request $request)
    {
        $events = app(CulturalPublicEventQuery::class)
            ->orderedByLastHistoricalOccurrence()
            ->with([
                'category',
                'coverMedia',
                'occurrences.location',
            ])
            ->paginate(12)
            ->withQueryString();

        return view('cultural-calendar.archive', compact('events'));
    }

    /**
     * Detalj događaja (6A-08 DATA SWITCH).
     *
     * Parametar ostaje {event}; bez route cutover-a (6A-10).
     * Canonical: CulturalEventEntry preko public query SSOT.
     * PO-6B-09: javna veza ka Manifestaciji samo kada je MF public-linkable.
     */
    public function show(Request $request, string $event)
    {
        $publicManifestation = null;
        $manifestationQuery = app(CulturalPublicManifestationQuery::class);

        $event = app(CulturalPublicEventQuery::class)->findPublicEntryForShow($event);
        if ($manifestationQuery->isPubliclyLinkable($event->manifestation)) {
            $publicManifestation = $event->manifestation;
        }

        $backUrl = (string) $request->query('back', '');
        if (! str_starts_with($backUrl, '/kalendar-kulture')) {
            $backUrl = route('cultural-calendar.events');
        }

        return view('cultural-calendar.show', compact('event', 'backUrl', 'publicManifestation'));
    }

    /**
     * Javna aktivna lista Manifestacija (PHASE 6B-03 / PO-TS9-07B / PO-6B-08).
     */
    public function manifestations(Request $request): View
    {
        $query = app(CulturalPublicManifestationQuery::class);
        $manifestations = $query
            ->orderedForActiveList()
            ->paginate(12)
            ->withQueryString();

        return view('cultural-calendar.manifestations.index', [
            'manifestations' => $manifestations,
            'manifestationQuery' => $query,
        ]);
    }

    /**
     * Canonical javni detalj Manifestacije (PHASE 6B-03 / PO-TS9-07C/D / PO-6B-08).
     */
    public function manifestationShow(Request $request, string $manifestacija): View
    {
        $query = app(CulturalPublicManifestationQuery::class);
        $manifestation = $query->findPublicForShow($manifestacija);
        $period = $query->period($manifestation);
        $periodLabel = $query->formatPeriodLabel($period);
        $programByDate = $query->programGroupedByDate($manifestation);

        $backUrl = (string) $request->query('back', '');
        if (! str_starts_with($backUrl, '/kalendar-kulture')) {
            $backUrl = route('cultural-calendar.manifestations');
        }

        return view('cultural-calendar.manifestations.show', [
            'manifestation' => $manifestation,
            'periodLabel' => $periodLabel,
            'programByDate' => $programByDate,
            'backUrl' => $backUrl,
        ]);
    }
}
