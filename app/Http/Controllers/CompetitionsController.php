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
        $competitions = Competition::where('status', 'published')
            ->where('type', 'zensko')
            ->where(function ($query) {
                $query->whereNull('closed_at')
                    ->orWhere('closed_at', '>', now());
            })
            ->orderBy('published_at', 'desc')
            ->get()
            ->map(function ($competition) {
                // Izračunaj preostalo vreme
                if ($competition->published_at) {
                    $deadline = $competition->published_at->addDays($competition->deadline_days ?? 20);
                    $competition->deadline = $deadline;
                    $competition->days_remaining = max(0, now()->diffInDays($deadline, false));
                    $competition->is_open = $deadline->isFuture();
                } else {
                    $competition->deadline = null;
                    $competition->days_remaining = 0;
                    $competition->is_open = false;
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
        // Proveri da li je konkurs objavljen
        if ($competition->status !== 'published') {
            abort(404, 'Konkurs nije pronađen ili nije objavljen.');
        }

        // Izračunaj preostalo vreme
        $deadline = null;
        $daysRemaining = 0;
        $isOpen = false;

        if ($competition->published_at) {
            $deadline = $competition->published_at->copy()->addDays($competition->deadline_days ?? 20);
            $daysRemaining = max(0, now()->diffInDays($deadline, false));
            $isOpen = $deadline->isFuture();
        }

        // Lista obaveznih dokumenata (opšta lista - detalji će biti u formi za prijavu)
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
            'isOpen',
            'requiredDocuments',
            'userApplication'
        ));
    }
}