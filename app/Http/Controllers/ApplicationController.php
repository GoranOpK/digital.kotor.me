<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\UserDocument;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    /**
     * Prikaz forme za prijavu na konkurs (Obrazac 1a/1b)
     */
    public function create(Competition $competition): View|RedirectResponse
    {
        // Proveri da li je konkurs otvoren
        if ($competition->status !== 'published') {
            abort(404, 'Konkurs nije pronađen ili nije objavljen.');
        }

        // Proveri da li je konkurs još otvoren
        $deadline = $competition->published_at 
            ? $competition->published_at->copy()->addDays($competition->deadline_days ?? 20)
            : null;
        
        if ($deadline && $deadline->isPast()) {
            return redirect()->route('competitions.show', $competition)
                ->withErrors(['error' => 'Rok za prijave je istekao.']);
        }

        // Proveri da li korisnik već ima prijavu
        $existingApplication = Application::where('competition_id', $competition->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingApplication) {
            // Ako postoji draft prijava, omogući nastavak popunjavanja
            if ($existingApplication->status === 'draft') {
                // Preuzmi dokumente iz biblioteke korisnika
                $user = Auth::user();
                $userDocuments = UserDocument::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->get()
                    ->groupBy('category');
                
                return view('applications.create', compact('competition', 'user', 'userDocuments', 'existingApplication'))
                    ->with('info', 'Već imate započetu prijavu. Možete je nastaviti popunjavati.');
            } else {
                // Ako je prijava već podnesena, preusmeri na detalje
                return redirect()->route('applications.show', $existingApplication)
                    ->with('info', 'Već ste podneli prijavu na ovaj konkurs.');
            }
        }

        $user = Auth::user();
        
        // Preuzmi dokumente iz biblioteke korisnika
        $userDocuments = UserDocument::where('user_id', $user->id)
            ->where('status', 'active')
            ->get()
            ->groupBy('category');

        return view('applications.create', compact('competition', 'user', 'userDocuments'));
    }

    /**
     * Snimi prijavu (Obrazac 1a/1b)
     */
    public function store(Request $request, Competition $competition): RedirectResponse
    {
        // Proveri da li je konkurs otvoren
        $deadline = $competition->published_at 
            ? $competition->published_at->copy()->addDays($competition->deadline_days ?? 20)
            : null;
        
        if ($deadline && $deadline->isPast()) {
            return back()->withErrors(['error' => 'Rok za prijave je istekao.'])->withInput();
        }

        // Validacija osnovnih podataka
        // VAŽNO: applicant_type vrednosti:
        // - 'fizicko_lice' = Fizičko lice BEZ registrovane djelatnosti (nema registrovanu djelatnost u skladu sa Zakonom o privrednim društvima)
        // - 'preduzetnica' = Fizičko lice SA registrovanom djelatnošću (preduzetnik) - automatski ima registrovanu djelatnost
        // - 'doo' = Društvo sa ograničenom odgovornošću - automatski ima registrovanu djelatnost
        // - 'ostalo' = Ostali pravni subjekti - automatski ima registrovanu djelatnost
        
        // Proveri da li je ovo draft ili finalno čuvanje
        $isDraft = $request->has('save_as_draft') && $request->save_as_draft === '1';
        
        // Debug log
        \Log::info('=== Application Store ===');
        \Log::info('isDraft: ' . ($isDraft ? 'true' : 'false'));
        \Log::info('save_as_draft value: ' . ($request->input('save_as_draft') ?? 'null'));
        \Log::info('business_plan_name: ' . ($request->input('business_plan_name') ?? 'null'));
        \Log::info('All request data: ' . json_encode($request->all()));
        
        $rules = [
            'business_plan_name' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
            'applicant_type' => $isDraft ? 'nullable|in:preduzetnica,doo,fizicko_lice,ostalo' : 'required|in:preduzetnica,doo,fizicko_lice,ostalo',
            'business_stage' => $isDraft ? 'nullable|in:započinjanje,razvoj' : 'required|in:započinjanje,razvoj',
            'business_area' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
            'requested_amount' => $isDraft ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'total_budget_needed' => $isDraft ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'website' => 'nullable|url|max:255',
            'bank_account' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'pib' => 'nullable|string|regex:/^[0-9]{8}$/',
            'de_minimis_declaration' => $isDraft ? 'nullable|boolean' : 'required|accepted',
        ];

        // Izjava o tačnosti je obavezna samo za fizičko lice BEZ registrovane djelatnosti
        // Preduzetnica, DOO i Ostalo automatski imaju registrovanu djelatnost
        if ($request->applicant_type === 'fizicko_lice' && !$isDraft) {
            $rules['accuracy_declaration'] = 'required|accepted';
        }

        // Dodatna polja za DOO i Ostalo (ista polja)
        if (($request->applicant_type === 'doo' || $request->applicant_type === 'ostalo') && !$isDraft) {
            $rules['founder_name'] = 'required|string|max:255';
            $rules['director_name'] = 'required|string|max:255';
            $rules['company_seat'] = 'required|string|max:255';
        } elseif ($isDraft) {
            // Za draft, ova polja su opciona
            $rules['founder_name'] = 'nullable|string|max:255';
            $rules['director_name'] = 'nullable|string|max:255';
            $rules['company_seat'] = 'nullable|string|max:255';
        }

        // Dodatna polja za fizičko lice BEZ registrovane djelatnosti
        // Ova polja se koriste samo za 'fizicko_lice' tip, ne za 'preduzetnica' (koja je takođe fizičko lice ali SA registrovanom djelatnošću)
        if ($request->applicant_type === 'fizicko_lice' && !$isDraft) {
            $rules['physical_person_name'] = 'required|string|max:255';
            $rules['physical_person_jmbg'] = 'required|string|regex:/^[0-9]{13}$/';
            $rules['physical_person_phone'] = 'required|string|max:50';
            $rules['physical_person_email'] = 'required|email|max:255';
        } elseif ($isDraft && $request->applicant_type === 'fizicko_lice') {
            // Za draft, ova polja su opciona
            $rules['physical_person_name'] = 'nullable|string|max:255';
            $rules['physical_person_jmbg'] = 'nullable|string|regex:/^[0-9]{13}$/';
            $rules['physical_person_phone'] = 'nullable|string|max:50';
            $rules['physical_person_email'] = 'nullable|email|max:255';
        }

        // Polja za CRPS broj (opciono za sve tipove)
        // Oblik registracije je obavezan za sve tipove osim fizičkog lica bez registrovane djelatnosti
        if ($request->applicant_type !== 'fizicko_lice' && !$isDraft) {
            $rules['registration_form'] = 'required|in:Preduzetnik,Ortačko društvo,Komanditno društvo,Društvo sa ograničenom odgovornošću,Akcionarsko društvo,Dio stranog društva (predstavništvo ili poslovna jedinica),Udruženje (nvo, fondacije, sportske organizacije),Ustanova (državne i privatne),Druge organizacije (Političke partije, Verske zajednice, Komore, Sindikati)';
        } elseif ($isDraft) {
            $rules['registration_form'] = 'nullable|in:Preduzetnik,Ortačko društvo,Komanditno društvo,Društvo sa ograničenom odgovornošću,Akcionarsko društvo,Dio stranog društva (predstavništvo ili poslovna jedinica),Udruženje (nvo, fondacije, sportske organizacije),Ustanova (državne i privatne),Druge organizacije (Političke partije, Verske zajednice, Komore, Sindikati)';
        }
        $rules['crps_number'] = 'nullable|string|max:50';

        \Log::info('About to validate with rules: ' . json_encode($rules));
        \Log::info('Request data before validation: ' . json_encode($request->all()));
        
        try {
            $validated = $request->validate($rules, [
            'business_plan_name.required' => 'Naziv biznis plana je obavezan.',
            'applicant_type.required' => 'Tip podnosioca je obavezan.',
            'business_stage.required' => 'Faza biznisa je obavezna.',
            'business_area.required' => 'Oblast biznisa je obavezna.',
            'accuracy_declaration.required' => 'Morate potvrditi izjavu o tačnosti podataka.',
            'accuracy_declaration.accepted' => 'Morate potvrditi izjavu o tačnosti podataka.',
            'requested_amount.required' => 'Traženi iznos je obavezan.',
            'total_budget_needed.required' => 'Ukupan budžet je obavezan.',
            'total_budget_needed.gte' => 'Ukupan budžet mora biti veći ili jednak traženom iznosu.',
            'de_minimis_declaration.required' => 'Morate potvrditi de minimis izjavu.',
            'founder_name.required' => 'Ime osnivača/ice je obavezno.',
            'director_name.required' => 'Ime izvršnog direktora/ice je obavezno.',
            'company_seat.required' => 'Sjedište društva je obavezno.',
            'registration_form.required' => 'Oblik registracije je obavezan.',
            'registration_form.in' => 'Izabrani oblik registracije nije validan.',
            'pib.regex' => 'PIB mora imati tačno 8 cifara.',
            'physical_person_name.required' => 'Ime i prezime je obavezno za fizičko lice.',
            'physical_person_jmbg.required' => 'JMBG je obavezan za fizičko lice.',
            'physical_person_jmbg.regex' => 'JMBG mora imati tačno 13 cifara.',
            'physical_person_phone.required' => 'Kontakt telefon je obavezan za fizičko lice.',
            'physical_person_email.required' => 'E-mail je obavezan za fizičko lice.',
            'physical_person_email.email' => 'E-mail mora biti validan.',
        ]);
            \Log::info('Validation passed! Validated data: ' . json_encode($validated));
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed! Errors: ' . json_encode($e->errors()));
            \Log::error('Request data: ' . json_encode($request->all()));
            throw $e;
        }

        // Proveri maksimalnu podršku (30% budžeta) - samo ako nije draft
        if (!$isDraft && isset($validated['requested_amount'])) {
            $maxSupport = ($competition->budget ?? 0) * (($competition->max_support_percentage ?? 30) / 100);
            if ($validated['requested_amount'] > $maxSupport) {
                return back()->withErrors([
                    'requested_amount' => "Maksimalna podrška po biznis planu je {$maxSupport} € (30% budžeta)."
                ])->withInput();
            }
        }
        
        // Proveri da li je total_budget_needed >= requested_amount - samo ako nije draft i oba su popunjena
        if (!$isDraft && isset($validated['requested_amount']) && isset($validated['total_budget_needed'])) {
            if ($validated['total_budget_needed'] < $validated['requested_amount']) {
                return back()->withErrors([
                    'total_budget_needed' => 'Ukupan budžet mora biti veći ili jednak traženom iznosu.'
                ])->withInput();
            }
        }

        // Proveri da li već postoji draft prijava za ovaj konkurs
        // Proveri SVE aplikacije (draft i submitted) da ne bi pravio duplikate
        $existingApplication = Application::where('competition_id', $competition->id)
            ->where('user_id', Auth::id())
            ->where('status', 'draft')
            ->first();

        \Log::info('Existing application check: ' . ($existingApplication ? 'found ID: ' . $existingApplication->id : 'not found'));

        try {
            if ($existingApplication) {
            // Ažuriraj postojeću draft prijavu
            // VAŽNO: Koristimo direktno iz request-a, ne iz $validated, jer $validated može biti prazan za neka polja
            \Log::info('Before update - PIB in request: ' . ($request->input('pib') ?? 'null'));
            \Log::info('Before update - PIB filled check: ' . ($request->filled('pib') ? 'true' : 'false'));
            \Log::info('Before update - PIB in existing: ' . ($existingApplication->pib ?? 'null'));
            
            $updateData = [
                'business_plan_name' => $request->filled('business_plan_name') ? $request->business_plan_name : $existingApplication->business_plan_name,
                'applicant_type' => $request->filled('applicant_type') ? $request->applicant_type : $existingApplication->applicant_type,
                'business_stage' => $request->filled('business_stage') ? $request->business_stage : $existingApplication->business_stage,
                'founder_name' => $request->filled('founder_name') ? $request->founder_name : $existingApplication->founder_name,
                'director_name' => $request->filled('director_name') ? $request->director_name : $existingApplication->director_name,
                'company_seat' => $request->filled('company_seat') ? $request->company_seat : $existingApplication->company_seat,
                'physical_person_name' => $request->filled('physical_person_name') ? $request->physical_person_name : $existingApplication->physical_person_name,
                'physical_person_jmbg' => $request->filled('physical_person_jmbg') ? $request->physical_person_jmbg : $existingApplication->physical_person_jmbg,
                'physical_person_phone' => $request->filled('physical_person_phone') ? $request->physical_person_phone : $existingApplication->physical_person_phone,
                'physical_person_email' => $request->filled('physical_person_email') ? $request->physical_person_email : $existingApplication->physical_person_email,
                'requested_amount' => $request->filled('requested_amount') ? $request->requested_amount : $existingApplication->requested_amount,
                'total_budget_needed' => $request->filled('total_budget_needed') ? $request->total_budget_needed : $existingApplication->total_budget_needed,
                'business_area' => $request->filled('business_area') ? $request->business_area : $existingApplication->business_area,
                'website' => $request->filled('website') ? $request->website : $existingApplication->website,
                'bank_account' => $request->filled('bank_account') ? $request->bank_account : $existingApplication->bank_account,
                'vat_number' => $request->filled('vat_number') ? $request->vat_number : $existingApplication->vat_number,
                'pib' => $request->has('pib') ? ($request->pib ?: null) : $existingApplication->pib,
                'crps_number' => $request->filled('crps_number') ? $request->crps_number : $existingApplication->crps_number,
                'registration_form' => $request->filled('registration_form') ? $request->registration_form : $existingApplication->registration_form,
                'is_registered' => $request->filled('applicant_type') ? ($request->applicant_type !== 'fizicko_lice') : $existingApplication->is_registered,
                'accuracy_declaration' => $request->has('accuracy_declaration') && ($request->accuracy_declaration == '1' || $request->accuracy_declaration === true),
                'de_minimis_declaration' => $request->has('de_minimis_declaration') && ($request->de_minimis_declaration == '1' || $request->de_minimis_declaration === true),
                'previous_support_declaration' => $request->has('previous_support_declaration'),
            ];
            
            \Log::info('Update data PIB value: ' . ($updateData['pib'] ?? 'null'));
            
            $existingApplication->update($updateData);

                $application = $existingApplication;
                \Log::info('Updated existing application ID: ' . $application->id);
            } else {
                // Kreiraj novu prijavu
                \Log::info('Creating new application...');
                // VAŽNO: Koristimo direktno iz request-a, ne iz $validated, jer $validated može biti prazan za neka polja
                // VAŽNO: Automatsko postavljanje is_registered na osnovu tipa podnosioca:
                // - 'fizicko_lice' → is_registered = false (nema registrovanu djelatnost)
                // - 'preduzetnica' → is_registered = true (preduzetnik ima registrovanu djelatnost)
                // - 'doo' → is_registered = true (DOO ima registrovanu djelatnost)
                // - 'ostalo' → is_registered = true (ostali pravni subjekti imaju registrovanu djelatnost)
                $application = Application::create([
                    'competition_id' => $competition->id,
                    'user_id' => Auth::id(),
                    'business_plan_name' => $request->filled('business_plan_name') ? $request->business_plan_name : null,
                    'applicant_type' => $request->filled('applicant_type') ? $request->applicant_type : null,
                    'business_stage' => $request->filled('business_stage') ? $request->business_stage : null,
                    'founder_name' => $request->filled('founder_name') ? $request->founder_name : null,
                    'director_name' => $request->filled('director_name') ? $request->director_name : null,
                    'company_seat' => $request->filled('company_seat') ? $request->company_seat : null,
                    // Polja za fizičko lice BEZ registrovane djelatnosti (samo za 'fizicko_lice' tip)
                    'physical_person_name' => $request->filled('physical_person_name') ? $request->physical_person_name : null,
                    'physical_person_jmbg' => $request->filled('physical_person_jmbg') ? $request->physical_person_jmbg : null,
                    'physical_person_phone' => $request->filled('physical_person_phone') ? $request->physical_person_phone : null,
                    'physical_person_email' => $request->filled('physical_person_email') ? $request->physical_person_email : null,
                    'requested_amount' => $request->filled('requested_amount') ? $request->requested_amount : null,
                    'total_budget_needed' => $request->filled('total_budget_needed') ? $request->total_budget_needed : null,
                    'business_area' => $request->filled('business_area') ? $request->business_area : null,
                    'website' => $request->filled('website') ? $request->website : null,
                    'bank_account' => $request->filled('bank_account') ? $request->bank_account : null,
                    'vat_number' => $request->filled('vat_number') ? $request->vat_number : null,
                    'crps_number' => $request->filled('crps_number') ? $request->crps_number : null,
                    'registration_form' => $request->filled('registration_form') ? $request->registration_form : null,
                    // Automatsko postavljanje is_registered na osnovu tipa
                    'is_registered' => $request->filled('applicant_type') ? ($request->applicant_type !== 'fizicko_lice') : false,
                    'accuracy_declaration' => $request->has('accuracy_declaration') && ($request->accuracy_declaration == '1' || $request->accuracy_declaration === true),
                    'de_minimis_declaration' => $request->has('de_minimis_declaration') && ($request->de_minimis_declaration == '1' || $request->de_minimis_declaration === true),
                    'previous_support_declaration' => $request->has('previous_support_declaration'),
                    'status' => 'draft', // Draft dok se ne prilože svi dokumenti
                ]);
                \Log::info('Created new application ID: ' . $application->id);
            }
        } catch (\Exception $e) {
            \Log::error('Error creating/updating application: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->withErrors(['error' => 'Greška pri čuvanju prijave: ' . $e->getMessage()])->withInput();
        }

        // Refresh aplikaciju da dobijemo ažurirane podatke (posebno važno za existingApplication)
        try {
            $application->refresh();
        } catch (\Exception $e) {
            \Log::error('Error refreshing application: ' . $e->getMessage());
        }
        
        // Debug: Loguj stanje
        \Log::info('=== Application Store Debug ===');
        \Log::info('Application ID: ' . ($application->id ?? 'null'));
        \Log::info('isDraft: ' . ($isDraft ? 'true' : 'false'));
        \Log::info('business_stage from request: ' . ($request->business_stage ?? 'null'));
        \Log::info('business_stage in application: ' . ($application->business_stage ?? 'null'));
        \Log::info('pib from request: ' . ($request->pib ?? 'null'));
        \Log::info('pib in application: ' . ($application->pib ?? 'null'));
        \Log::info('business_plan_name: ' . ($application->business_plan_name ?? 'null'));
        \Log::info('applicant_type: ' . ($application->applicant_type ?? 'null'));
        \Log::info('business_area: ' . ($application->business_area ?? 'null'));
        \Log::info('requested_amount: ' . ($application->requested_amount ?? 'null'));
        \Log::info('total_budget_needed: ' . ($application->total_budget_needed ?? 'null'));
        \Log::info('de_minimis_declaration: ' . ($application->de_minimis_declaration ? 'true' : 'false'));
        \Log::info('registration_form: ' . ($application->registration_form ?? 'null'));
        \Log::info('status: ' . ($application->status ?? 'null'));
        
        // Proveri da li je Obrazac 1a/1b kompletno popunjen (sva polja + checkbox-ovi)
        $isObrazacComplete = $application->isObrazacComplete();
        
        \Log::info('isObrazacComplete: ' . ($isObrazacComplete ? 'true' : 'false'));
        \Log::info('=== End Debug ===');

        // VAŽNO: Prvo proveri da li je obrazac kompletan, pa tek onda proveri da li je draft
        // Ako je obrazac kompletan, preusmeri na formu za biznis plan (bez obzira na $isDraft)
        if ($isObrazacComplete) {
            // Ako je obrazac kompletno popunjen, preusmeri na formu za biznis plan
            return redirect()->route('applications.business-plan.create', $application)
                ->with('success', 'Obrazac 1a/1b je kompletno popunjen. Sada popunite biznis plan.');
        } elseif ($isDraft) {
            // Ako je eksplicitno kliknuto "Sačuvaj kao nacrt", uvek čuvaj kao draft
            return redirect()->route('applications.create', $competition)
                ->with('success', 'Prijava je sačuvana kao nacrt. Možete je nastaviti popunjavati.')
                ->withInput();
        } else {
            // Ako nije kompletna (kliknuo "Sačuvaj prijavu" ali nisu sva polja popunjena), sačuvaj kao draft i vrati na formu
            return redirect()->route('applications.create', $competition)
                ->with('warning', 'Prijava je sačuvana kao nacrt jer još uvek nisu popunjena sva obavezna polja. Molimo popunite sva obavezna polja i potvrdite sve obavezne izjave.')
                ->withInput();
        }
    }

    /**
     * Prikaz detalja prijave
     */
    public function show(Application $application): View
    {
        // Proveri da li prijava pripada korisniku ili je admin/superadmin
        $user = Auth::user();
        $isOwner = $application->user_id === $user->id;
        $isAdmin = $user->role && ($user->role->name === 'admin' || $user->role->name === 'superadmin' || $user->role->name === 'konkurs_admin');
        
        if (!$isOwner && !$isAdmin) {
            abort(403, 'Nemate pristup ovoj prijavi.');
        }

        $application->load(['competition', 'businessPlan', 'documents', 'evaluationScores.commissionMember', 'contract', 'reports']);

        // Provjeri da li je prijava spremna za podnošenje
        $requiredDocs = $application->getRequiredDocuments();
        $uploadedDocs = $application->documents->pluck('document_type')->toArray();
        $missingDocs = array_diff($requiredDocs, $uploadedDocs);
        
        $isReadyToSubmit = $application->status === 'draft' && 
                           $application->businessPlan !== null && 
                           empty($missingDocs);

        return view('applications.show', compact('application', 'isReadyToSubmit'));
    }

    /**
     * Konačno podnošenje prijave
     */
    public function submit(Application $application): RedirectResponse
    {
        // Proveri da li prijava pripada korisniku
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Nemate pristup ovoj prijavi.');
        }

        if ($application->status !== 'draft') {
            return back()->withErrors(['error' => 'Prijava je već podnesena ili je u obradi.']);
        }

        // Provjera biznis plana
        if (!$application->businessPlan) {
            return back()->withErrors(['error' => 'Morate popuniti biznis plan prije podnošenja prijave.']);
        }

        // Provjera dokumenata
        $requiredDocs = $application->getRequiredDocuments();
        $uploadedDocs = $application->documents->pluck('document_type')->toArray();
        $missingDocs = array_diff($requiredDocs, $uploadedDocs);

        if (!empty($missingDocs)) {
            return back()->withErrors(['error' => 'Niste priložili sve obavezne dokumente.']);
        }

        // Sve je u redu, podnesi prijavu
        $application->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return redirect()->route('applications.show', $application)
            ->with('success', 'Vaša prijava je uspješno podnesena i proslijeđena komisiji na razmatranje.');
    }

    /**
     * Upload dokumenata za prijavu
     */
    public function uploadDocument(Request $request, Application $application): RedirectResponse
    {
        // Proveri da li prijava pripada korisniku
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Nemate pristup ovoj prijavi.');
        }

        // Proveri da li dokument iz biblioteke pripada korisniku (pre validacije)
        if ($request->filled('user_document_id')) {
            $userDocument = UserDocument::where('id', $request->user_document_id)
                ->where('user_id', Auth::id())
                ->where('status', 'active')
                ->first();
            
            if (!$userDocument) {
                return back()->withErrors(['user_document_id' => 'Izabrani dokument nije validan ili ne pripada vašoj biblioteci.'])->withInput();
            }
        }

        $validated = $request->validate([
            'document_type' => 'required|string|in:licna_karta,crps_resenje,pib_resenje,pdv_resenje,statut,karton_potpisa,potvrda_neosudjivanost,uvjerenje_opstina_porezi,uvjerenje_opstina_nepokretnost,potvrda_upc_porezi,ioppd_obrazac,godisnji_racuni,biznis_plan_usb,izvjestaj_realizacija,finansijski_izvjestaj,ostalo',
            'file' => 'required_without:user_document_id|file|mimes:pdf,jpg,jpeg,png|max:20480', // 20MB max
            'user_document_id' => 'nullable|required_without:file',
        ], [
            'document_type.required' => 'Tip dokumenta je obavezan.',
            'file.required_without' => 'Morate priložiti fajl ili izabrati dokument iz biblioteke.',
            'user_document_id.required_without' => 'Morate priložiti fajl ili izabrati dokument iz biblioteke.',
            'file.max' => 'Fajl ne može biti veći od 20MB.',
        ]);

        // Proveri da li je dokument već priložen
        $existingDoc = $application->documents()
            ->where('document_type', $validated['document_type'])
            ->first();

        if ($existingDoc) {
            return back()->withErrors(['document_type' => 'Ovaj dokument je već priložen.'])->withInput();
        }

        // Ako je izabran dokument iz biblioteke
        if (!empty($validated['user_document_id'])) {
            $userDocument = UserDocument::where('id', $validated['user_document_id'])
                ->where('user_id', Auth::id())
                ->where('status', 'active')
                ->first();

            if (!$userDocument) {
                return back()->withErrors(['user_document_id' => 'Izabrani dokument nije validan ili ne pripada vašoj biblioteci.'])->withInput();
            }

            // Kreiraj vezu sa prijavom
            ApplicationDocument::create([
                'application_id' => $application->id,
                'name' => $userDocument->name,
                'file_path' => $userDocument->file_path,
                'document_type' => $validated['document_type'],
                'is_required' => in_array($validated['document_type'], $application->getRequiredDocuments()),
                'user_document_id' => $userDocument->id,
            ]);

            return back()->with('success', 'Dokument je uspješno priložen iz biblioteke.');
        }

        // Ako je upload-ovan novi fajl
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $documentProcessor = app(\App\Services\DocumentProcessor::class);
            
            $result = $documentProcessor->processDocument($file, Auth::id());

            if (!$result['success']) {
                return back()->withErrors(['file' => $result['error'] ?? 'Greška pri obradi fajla.'])->withInput();
            }

            // Kreiraj zapis o dokumentu
            ApplicationDocument::create([
                'application_id' => $application->id,
                'name' => $file->getClientOriginalName(),
                'file_path' => $result['file_path'],
                'document_type' => $validated['document_type'],
                'is_required' => in_array($validated['document_type'], $application->getRequiredDocuments()),
            ]);

            return back()->with('success', 'Dokument je uspješno upload-ovan i priložen.');
        }

        return back()->withErrors(['error' => 'Greška pri priložavanju dokumenta.'])->withInput();
    }

    /**
     * Pregled dokumenta prijave u pregledaču
     */
    public function viewDocument(Application $application, ApplicationDocument $document)
    {
        // Proveri da li dokument pripada ovoj prijavi
        if ($document->application_id !== $application->id) {
            abort(404, 'Dokument nije pronađen.');
        }

        // Proveri da li korisnik ima pravo pristupa dokumentu
        $user = Auth::user();
        $isOwner = $application->user_id === $user->id;
        $roleName = $user->role ? $user->role->name : null;

        // Samo:
        // - vlasnik prijave, ili
        // - član komisije (role: komisija) za konkurs dodijeljen njegovoj komisiji
        // može da pregleda dokument
        $isCommissionMemberForThisCompetition = false;

        if ($roleName === 'komisija') {
            // Učitaj konkurs povezano sa prijavom
            $competition = $application->competition;

            if ($competition && $competition->commission_id) {
                // Provjeri da li je korisnik aktivni član iste komisije
                $commissionMember = \App\Models\CommissionMember::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->first();

                if ($commissionMember && $commissionMember->commission_id === $competition->commission_id) {
                    $isCommissionMemberForThisCompetition = true;
                }
            }
        }

        if (!$isOwner && !$isCommissionMemberForThisCompetition) {
            abort(403, 'Nemate pravo pristupa ovom dokumentu.');
        }

        // Proveri da li fajl postoji na disku
        if (!$document->file_path || !Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Fajl dokumenta nije pronađen na serveru.');
        }

        $path = Storage::disk('local')->path($document->file_path);
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
        ]);
    }

    /**
     * Download dokumenta prijave
     */
    public function downloadDocument(Application $application, ApplicationDocument $document)
    {
        // Proveri da li dokument pripada ovoj prijavi
        if ($document->application_id !== $application->id) {
            abort(404, 'Dokument nije pronađen.');
        }

        $user = Auth::user();
        $isOwner = $application->user_id === $user->id;

        // Samo vlasnik prijave može da preuzme svoje dokumente
        if (!$isOwner) {
            abort(403, 'Samo podnosilac prijave može da preuzme ovaj dokument.');
        }

        // Proveri da li fajl postoji na disku
        if (!$document->file_path || !Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Fajl dokumenta nije pronađen na serveru.');
        }

        // Vrati fajl kao download sa originalnim imenom
        $downloadName = $document->name ?? basename($document->file_path);
        return Storage::disk('local')->download($document->file_path, $downloadName);
    }

    /**
     * Brisanje pojedinačnog dokumenta iz prijave
     */
    public function destroyDocument(Application $application, ApplicationDocument $document): RedirectResponse
    {
        // Proveri da li dokument pripada ovoj prijavi
        if ($document->application_id !== $application->id) {
            abort(404, 'Dokument nije pronađen.');
        }

        $user = Auth::user();
        $isOwner = $application->user_id === $user->id;

        // Samo vlasnik prijave može da briše svoje dokumente iz prijave
        if (!$isOwner) {
            abort(403, 'Samo podnosilac prijave može da briše svoje dokumente.');
        }

        // Ako dokument nije iz korisničke biblioteke, obriši fizički fajl
        if (!$document->user_document_id && $document->file_path) {
            if (Storage::disk('local')->exists($document->file_path)) {
                Storage::disk('local')->delete($document->file_path);
            }
        }

        // Obriši zapis iz baze
        $document->delete();

        return redirect()->route('applications.show', $application)
            ->with('success', 'Dokument je uspješno obrisan.');
    }

    /**
     * Brisanje prijave
     */
    public function destroy(Application $application): RedirectResponse
    {
        // Proveri da li prijava pripada korisniku ili je administrator
        $user = Auth::user();
        $isOwner = $application->user_id === $user->id;
        $isAdmin = $user->role && in_array($user->role->name, ['admin', 'superadmin', 'konkurs_admin']);

        if (!$isOwner && !$isAdmin) {
            abort(403, 'Nemate pristup ovoj prijavi.');
        }

        // Korisnik ili admin mogu obrisati prijavu u bilo kom momentu
        // (Uklonjen uslov za 'draft' status na zahtjev korisnika)

        // Obriši biznis plan ako postoji
        if ($application->businessPlan) {
            $application->businessPlan->delete();
        }

        // Obriši dokumente (ali ne i fizičke fajlove ako su iz biblioteke!)
        $application->documents()->delete();

        // Obriši samu prijavu
        $application->delete();

        if ($isAdmin) {
            return redirect()->route('admin.applications.index')->with('success', 'Prijava je uspješno obrisana.');
        }

        return redirect()->route('dashboard')->with('success', 'Prijava je uspješno obrisana.');
    }
}