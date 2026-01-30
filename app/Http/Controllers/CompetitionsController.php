<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Application;
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

        // Proveri da li korisnik već ima prijavu na ovaj konkurs
        $userApplication = null;
        $userType = null;
        $applicantType = null;
        if (auth()->check()) {
            $user = auth()->user();
            $userType = $user->user_type ?? null;
            $userApplication = $competition->applications()
                ->where('user_id', auth()->id())
                ->first();
            
            // Odredi applicant_type na osnovu user_type
            if ($userType === 'Preduzetnik' || $userType === 'Preduzetnica') {
                $applicantType = 'preduzetnica';
            } elseif ($userType === 'Društvo sa ograničenom odgovornošću' || $userType === 'DOO') {
                $applicantType = 'doo';
            } elseif ($userType === 'Fizičko lice' || $userType === 'Rezident') {
                $applicantType = 'fizicko_lice';
            } else {
                $applicantType = 'ostalo';
            }
        }

        // Dokument labels za mapiranje
        $documentLabels = [
            'licna_karta' => 'Ovjerena kopija lične karte',
            'crps_resenje' => 'Rješenje o upisu u CRPS',
            'pib_resenje' => 'Rješenje o registraciji PJ Uprave prihoda i carina',
            'pdv_resenje' => 'Rješenje o registraciji za PDV',
            'statut' => 'Važeći Statut društva',
            'karton_potpisa' => 'Važeći karton deponovanih potpisa',
            'potvrda_neosudjivanost' => 'Potvrda o neosuđivanosti za krivična djela',
            'uvjerenje_opstina_porezi' => 'Uvjerenje od organa lokalne uprave o urednom izmirivanju poreza po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada',
            'uvjerenje_opstina_nepokretnost' => 'Uvjerenje od organa lokalne uprave o urednom izmirivanju poreza na nepokretnost',
            'potvrda_upc_porezi' => 'Potvrda Uprave prihoda i carina o urednom izmirivanju poreza i doprinosa ne stariju od 30 dana',
            'ioppd_obrazac' => 'Obrazac za poslijednji mjesec uplate poreza i doprinosa za zaposlene ovjeren od Uprave prihoda i carina (IOPPD Obrazac)',
            'godisnji_racuni' => 'Komplet obrazaca za godišnje račune (Bilans stanja, Bilans uspjeha, Analitika kupaca i dobavljača) za prethodnu godinu',
            'biznis_plan_usb' => 'Jedna štampana i jedna elektronska verzija biznis plana na USB-u',
            'izvjestaj_realizacija' => 'Izvještaj o realizaciji prethodne podrške',
            'finansijski_izvjestaj' => 'Finansijski izvještaj',
        ];

        // Generiši početnu listu dokumenata (za preduzetnice koje započinju, bez registracije)
        $defaultDocuments = [];
        if ($applicantType === 'preduzetnica') {
            $defaultDocuments = Application::getRequiredDocumentsForType('preduzetnica', 'započinjanje', false);
        } elseif ($applicantType === 'doo' || $applicantType === 'ostalo') {
            $defaultDocuments = Application::getRequiredDocumentsForType($applicantType, 'započinjanje', false);
        } elseif ($applicantType === 'fizicko_lice') {
            // Za fizičko lice, ne prikazujemo listu dokumenata dok korisnik ne izabere business_stage
            $defaultDocuments = [];
        }

        // Mapiraj dokumente u ljudski čitljive nazive
        $requiredDocuments = array_map(function($docType) use ($documentLabels) {
            return $documentLabels[$docType] ?? $docType;
        }, $defaultDocuments);

        // Dodaj obavezne dokumente koje svi moraju imati
        array_unshift($requiredDocuments, 'Prijava na konkurs (Obrazac 1a ili 1b)');
        array_unshift($requiredDocuments, 'Popunjena forma za biznis plan (Obrazac 2)');

        return view('competitions.show', compact(
            'competition',
            'deadline',
            'daysRemaining',
            'hoursRemaining',
            'minutesRemaining',
            'isOpen',
            'isUpcoming',
            'requiredDocuments',
            'userApplication',
            'userType',
            'applicantType',
            'documentLabels'
        ));
    }
}