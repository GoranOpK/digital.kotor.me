<?php

namespace App\Http\Controllers;

use App\Models\CulturalEvent;
use Carbon\Carbon;

class CulturalCalendarController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $weekEnd = Carbon::today()->endOfWeek();
        $monthEnd = Carbon::today()->endOfMonth();

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

        return view('cultural-calendar.index', compact(
            'todayCount',
            'weekCount',
            'monthCount',
            'featuredEvents'
        ));
    }

    public function events()
    {
        $events = CulturalEvent::query()
            ->where('status', 'published')
            ->orderBy('datum_od')
            ->paginate(12);

        return view('cultural-calendar.events', compact('events'));
    }
}
