<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class CompetitionsController extends Controller
{
    /**
     * Prikaz liste aktivnih konkursa
     */
    public function index(): View
    {
        $now = now();
        $competitions = Competition::where('status', 'published')
            ->where('type', 'zensko')
            ->where(function ($query) use ($now) {
                // Konkurs je vidljiv korisnicima samo ako je datum početka danas ili u prošlosti
                $query->where('start_date', '<=', $now->toDateString())
                      ->orWhereNull('start_date');
            })
            ->orderBy('start_date', 'asc')
            ->get()
            ->map(function ($competition) {
                $competition->is_open = $competition->is_open;
                
                if ($competition->is_open) {
                    $diff = now()->diff($competition->deadline);
                    $competition->days_remaining = $diff->days;
                    $competition->hours_remaining = $diff->h;
                    $competition->minutes_remaining = $diff->i;
                } else {
                    $competition->days_remaining = 0;
                    $competition->hours_remaining = 0;
                    $competition->minutes_remaining = 0;
                }
                return $competition;
            });

        return view('competitions.index', compact('competitions'));
    }

    /**
     * Prikaz detalja konkursa
     */
    public function show(Competition $competition): View
    {
        $now = now();
        
        // Proveri da li je konkurs objavljen i da li je počeo
        if ($competition->status !== 'published' || ($competition->start_date && $competition->start_date->startOfDay() > $now)) {
            abort(404, 'Konkurs nije pronađen ili još uvijek nije počeo.');
        }

        $isOpen = $competition->is_open;
        $isUpcoming = $competition->is_upcoming;
        $deadline = $competition->deadline;
        
        $daysRemaining = 0;
        $hoursRemaining = 0;
        $minutesRemaining = 0;

        if ($isOpen) {
            $diff = now()->diff($deadline);
            $daysRemaining = $diff->days;
            $hoursRemaining = $diff->h;
            $minutesRemaining = $diff->i;
        }

        // ... ostatak koda ...
        $requiredDocuments = [
            'Prijava na konkurs (Obrazac 1a ili 1b)',
            'Popunjena forma za biznis plan (Obrazac 2)',
            'Ovjerena kopija lične karte',
            'Rješenje o upisu u CRPS',
            'Rješenje o registraciji PJ Uprave prihoda i carina',
            'Potvrda o neosuđivanosti',
            'Uvjerenje o urednom izmirivanju poreza',
            'Štampana i elektronska verzija biznis plana na USB-u',
        ];

        // Proveri da li korisnik već ima prijavu na ovaj konkurs
        $userApplication = null;
        if (auth()->check()) {
            $userApplication = $competition->applications()
                ->where('user_id', auth()->id())
                ->first();
        }

        return view('competitions.show', compact(
            'competition',
            'deadline',
            'daysRemaining',
            'hoursRemaining',
            'minutesRemaining',
            'isOpen',
            'isUpcoming',
            'requiredDocuments',
            'userApplication'
        ));
    }
}