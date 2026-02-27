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
            'pib_resenje' => 'Rješenje o PIB-u PJ Poreske uprave',
            'pdv_resenje' => 'Rješenje o registraciji za PDV',
            'statut' => 'Važeći Statut društva',
            'karton_potpisa' => 'Važeći karton deponovanih potpisa',
            'potvrda_neosudjivanost' => 'Potvrda da se ne vodi krivični postupak na ime podnositeljke prijave odnosno preduzetnice izdatu od Osnovnog suda',
            'uvjerenje_opstina_porezi' => 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime podnositeljke prijave odnosno preduzetnice po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada',
            'uvjerenje_opstina_nepokretnost' => 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime podnositeljke prijave odnosno preduzetnice',
            'potvrda_upc_porezi' => 'Potvrda Uprave prihoda i carina o urednom izmirivanju poreza i doprinosa ne stariju od 30 dana, na ime preduzetnice',
            'ioppd_obrazac' => 'Odgovarajući obrazac za posljednji mjesec uplate poreza i doprinosa za zaposlene ovjeren od Uprave prihoda i carina, kao dokaz o broju zaposlenih (IOPPD Obrazac)',
            'godisnji_racuni' => 'Komplet obrazaca za godišnje račune (Bilans stanja, Bilans uspjeha, Analitika kupaca i dobavljača) za prethodnu godinu',
            'biznis_plan_usb' => 'Jedna štampana i jedna elektronska verzija biznis plana na USB-u',
            'izvjestaj_realizacija' => 'Izvještaj o realizaciji prethodne podrške',
            'finansijski_izvjestaj' => 'Finansijski izvještaj',
            'dokaz_ziro_racun' => 'Dokaz o broju poslovnog žiro računa preduzetnice',
            'predracuni_nabavka' => 'Predračuni za planiranu nabavku',
            'izvjestaj_registar_kase' => 'Izvještaj sa registra kase',
        ];

        // Generiši početnu listu dokumenata (za preduzetnice i fizičko lice koje započinje, sa SVIM dokumentima)
        // Za preduzetnice i fizičko lice koje započinje, prikazujemo sve dokumente sa napomenama za opcione
        $defaultDocuments = [];
        if ($applicantType === 'preduzetnica') {
            // Uzmi sve dokumente (uključujući CRPS, PIB, PDV) - JavaScript će dodati napomene
            $defaultDocuments = Application::getRequiredDocumentsForType('preduzetnica', 'započinjanje', true);
        } elseif ($applicantType === 'fizicko_lice') {
            // Za fizičko lice (Rezident), uzmi sve dokumente kao za preduzetnicu koja započinje
            $defaultDocuments = Application::getRequiredDocumentsForType('fizicko_lice', 'započinjanje', true);
        } elseif ($applicantType === 'doo' || $applicantType === 'ostalo') {
            // Prikaži sve dokumente (sa opcionim napomenama) - JavaScript ažurira na osnovu izbora faze
            $defaultDocuments = Application::getRequiredDocumentsForType($applicantType, 'započinjanje', true);
        }

        // Mapiraj dokumente u ljudski čitljive nazive
        $requiredDocuments = array_map(function($docType) use ($documentLabels, $applicantType) {
            $label = $documentLabels[$docType] ?? $docType;
            
            // Za preduzetnice i fizičko lice, dodaj napomene za opcione dokumente i ažuriraj tekstove
            if ($applicantType === 'preduzetnica' || $applicantType === 'fizicko_lice') {
                if ($docType === 'crps_resenje') {
                    $label = 'Rješenje o upisu u CRPS (ukoliko ima registrovanu djelatnost)';
                } elseif ($docType === 'pib_resenje') {
                    $label = 'Rješenje o PIB-u PJ Poreske uprave (ukoliko ima registrovanu djelatnost)';
                } elseif ($docType === 'pdv_resenje') {
                    $label = 'Rješenje o registraciji za PDV (ukoliko ima registrovanu djelatnost i ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)';
                } elseif ($docType === 'potvrda_neosudjivanost') {
                    $label = 'Potvrda da se ne vodi krivični postupak na ime podnositeljke prijave odnosno preduzetnice izdatu od Osnovnog suda';
                } elseif ($docType === 'uvjerenje_opstina_porezi') {
                    $label = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime podnositeljke prijave odnosno preduzetnice po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada';
                } elseif ($docType === 'uvjerenje_opstina_nepokretnost') {
                    $label = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime podnositeljke prijave odnosno preduzetnice';
                } elseif ($docType === 'dokaz_ziro_racun') {
                    $label = 'Dokaz o broju poslovnog žiro računa preduzetnice (ukoliko ima registrovanu djelatnost)';
                } elseif ($docType === 'predracuni_nabavka') {
                    $label = 'Predračuni za planiranu nabavku';
                } elseif ($docType === 'potvrda_upc_porezi') {
                    $label = 'Potvrda Uprave prihoda i carina o urednom izmirivanju poreza i doprinosa ne stariju od 30 dana, na ime preduzetnice';
                } elseif ($docType === 'ioppd_obrazac') {
                    $label = 'Odgovarajući obrazac za posljednji mjesec uplate poreza i doprinosa za zaposlene ovjeren od Uprave prihoda i carina, kao dokaz o broju zaposlenih (IOPPD Obrazac)';
                }
            }
            // Za DOO i Ostalo (započinjanje - početna lista) – tekstovi prema Odluci
            elseif ($applicantType === 'doo' || $applicantType === 'ostalo') {
                if ($docType === 'licna_karta') {
                    $label = 'Ovjerenu kopiju lične karte';
                } elseif ($docType === 'crps_resenje') {
                    $label = 'Rješenje o upisu u CRPS (ukoliko ima registrovanu djelatnost)';
                } elseif ($docType === 'pib_resenje') {
                    $label = 'Rješenje o registraciji PJ Poreske uprave (ukoliko ima registrovanu djelatnost)';
                } elseif ($docType === 'pdv_resenje') {
                    $label = 'Rješenje o registraciji za PDV (ukoliko ima registrovanu djelatnost i ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)';
                } elseif ($docType === 'statut') {
                    $label = 'Važeći Statut društva (ukoliko ima registrovanu djelatnost)';
                } elseif ($docType === 'karton_potpisa') {
                    $label = 'Važeći karton deponovanih potpisa (ukoliko ima registrovanu djelatnost)';
                } elseif ($docType === 'potvrda_neosudjivanost') {
                    $label = 'Potvrda da se ne vodi krivični postupak na ime društva i na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) izdatu od strane Osnovnog suda';
                } elseif ($docType === 'uvjerenje_opstina_porezi') {
                    $label = 'Uvjerenje od organa lokalne uprave, ne starije od mjesec dana, o urednom izmirivanju poreza na ime preduzetnice po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada';
                } elseif ($docType === 'uvjerenje_opstina_nepokretnost') {
                    $label = 'Uvjerenje od organa lokalne uprave, ne starije od mjesec dana, o urednom izmirivanju poreza na nepokretnost na ime preduzetnice';
                } elseif ($docType === 'predracuni_nabavka') {
                    $label = 'Predračune za planiranu nabavku';
                } elseif ($docType === 'godisnji_racuni') {
                    $label = 'Komplet obrazaca za godišnje račune (Bilans stanja, Bilans uspjeha, Analitika kupaca i dobavljača) za prethodnu godinu';
                } elseif ($docType === 'potvrda_upc_porezi') {
                    $label = 'Potvrda Uprave prihoda i carina o urednom izmirivanju poreza i doprinosa ne stariju od 30 dana, na ime nosioca biznisa i na ime društva';
                } elseif ($docType === 'ioppd_obrazac') {
                    $label = 'Odgovarajući obrazac za posljednji mjesec uplate poreza i doprinosa za zaposlene ovjeren od Uprave prihoda i carina, kao dokaz o broju zaposlenih (IOPPD Obrazac)';
                }
            }
            
            return $label;
        }, $defaultDocuments);

        // Dodaj obavezne dokumente koje svi moraju imati
        if ($applicantType === 'preduzetnica' || $applicantType === 'fizicko_lice') {
            array_unshift($requiredDocuments, 'Popunjena forma za biznis plan (obrazac 2 — Forma za biznis plan)');
            array_unshift($requiredDocuments, 'Prijava na konkurs za podsticaj ženskog preduzetništva (obrazac 1a)');
        } else {
            array_unshift($requiredDocuments, 'Prijava na konkurs (Obrazac 1a ili 1b)');
            array_unshift($requiredDocuments, 'Popunjena forma za biznis plan (Obrazac 2)');
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
            'userApplication',
            'userType',
            'applicantType',
            'documentLabels'
        ));
    }
}