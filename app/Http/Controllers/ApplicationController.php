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
    public function create(Competition $competition): View
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
            return redirect()->route('applications.show', $existingApplication)
                ->with('info', 'Već ste podneli prijavu na ovaj konkurs.');
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
        $rules = [
            'business_plan_name' => 'required|string|max:255',
            'applicant_type' => 'required|in:preduzetnica,doo,fizicko_lice,ostalo',
            'business_stage' => 'required|in:započinjanje,razvoj',
            'business_area' => 'required|string|max:255',
            'requested_amount' => 'required|numeric|min:0',
            'total_budget_needed' => 'required|numeric|min:0|gte:requested_amount',
            'website' => 'nullable|url|max:255',
            'bank_account' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'de_minimis_declaration' => 'required|accepted',
        ];

        // Izjava o tačnosti je obavezna za sve tipove podnosioca
        $rules['accuracy_declaration'] = 'required|accepted';

        // Dodatna polja za DOO i Ostalo (ista polja)
        if ($request->applicant_type === 'doo' || $request->applicant_type === 'ostalo') {
            $rules['founder_name'] = 'required|string|max:255';
            $rules['director_name'] = 'required|string|max:255';
            $rules['company_seat'] = 'required|string|max:255';
        }

        // Dodatna polja za fizičko lice BEZ registrovane djelatnosti
        // Ova polja se koriste samo za 'fizicko_lice' tip, ne za 'preduzetnica' (koja je takođe fizičko lice ali SA registrovanom djelatnošću)
        if ($request->applicant_type === 'fizicko_lice') {
            $rules['physical_person_name'] = 'required|string|max:255';
            $rules['physical_person_jmbg'] = 'required|string|regex:/^[0-9]{13}$/';
            $rules['physical_person_phone'] = 'required|string|max:50';
            $rules['physical_person_email'] = 'required|email|max:255';
        }

        // Polja za CRPS broj (opciono za sve tipove)
        $rules['crps_number'] = 'nullable|string|max:50';

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
            'physical_person_name.required' => 'Ime i prezime je obavezno za fizičko lice.',
            'physical_person_jmbg.required' => 'JMBG je obavezan za fizičko lice.',
            'physical_person_jmbg.regex' => 'JMBG mora imati tačno 13 cifara.',
            'physical_person_phone.required' => 'Kontakt telefon je obavezan za fizičko lice.',
            'physical_person_email.required' => 'E-mail je obavezan za fizičko lice.',
            'physical_person_email.email' => 'E-mail mora biti validan.',
        ]);

        // Proveri maksimalnu podršku (30% budžeta)
        $maxSupport = ($competition->budget ?? 0) * (($competition->max_support_percentage ?? 30) / 100);
        if ($validated['requested_amount'] > $maxSupport) {
            return back()->withErrors([
                'requested_amount' => "Maksimalna podrška po biznis planu je {$maxSupport} € (30% budžeta)."
            ])->withInput();
        }

        // Kreiraj prijavu
        // VAŽNO: Automatsko postavljanje is_registered na osnovu tipa podnosioca:
        // - 'fizicko_lice' → is_registered = false (nema registrovanu djelatnost)
        // - 'preduzetnica' → is_registered = true (preduzetnik ima registrovanu djelatnost)
        // - 'doo' → is_registered = true (DOO ima registrovanu djelatnost)
        // - 'ostalo' → is_registered = true (ostali pravni subjekti imaju registrovanu djelatnost)
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => Auth::id(),
            'business_plan_name' => $validated['business_plan_name'],
            'applicant_type' => $validated['applicant_type'],
            'business_stage' => $validated['business_stage'],
            'founder_name' => $validated['founder_name'] ?? null,
            'director_name' => $validated['director_name'] ?? null,
            'company_seat' => $validated['company_seat'] ?? null,
            // Polja za fizičko lice BEZ registrovane djelatnosti (samo za 'fizicko_lice' tip)
            'physical_person_name' => $validated['physical_person_name'] ?? null,
            'physical_person_jmbg' => $validated['physical_person_jmbg'] ?? null,
            'physical_person_phone' => $validated['physical_person_phone'] ?? null,
            'physical_person_email' => $validated['physical_person_email'] ?? null,
            'requested_amount' => $validated['requested_amount'],
            'total_budget_needed' => $validated['total_budget_needed'],
            'business_area' => $validated['business_area'],
            'website' => $validated['website'] ?? null,
            'bank_account' => $validated['bank_account'] ?? null,
            'vat_number' => $validated['vat_number'] ?? null,
            'crps_number' => $validated['crps_number'] ?? null,
            // Automatsko postavljanje is_registered na osnovu tipa
            'is_registered' => $validated['applicant_type'] !== 'fizicko_lice',
            'accuracy_declaration' => $request->has('accuracy_declaration') && ($request->accuracy_declaration == '1' || $request->accuracy_declaration === true),
            'de_minimis_declaration' => true,
            'previous_support_declaration' => $request->has('previous_support_declaration'),
            'status' => 'draft', // Draft dok se ne prilože svi dokumenti
        ]);

        return redirect()->route('applications.business-plan.create', $application)
            ->with('success', 'Osnovni podaci prijave su sačuvani. Sada popunite biznis plan.');
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
     * Download dokumenta prijave
     */
    public function downloadDocument(Application $application, ApplicationDocument $document)
    {
        // ... (existing code) ...
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

        // Proveri da li korisnik ima pravo da briše dokument
        $user = Auth::user();
        $isOwner = $application->user_id === $user->id;
        $isAdmin = $user->role && in_array($user->role->name, ['admin', 'superadmin', 'konkurs_admin']);

        if (!$isOwner && !$isAdmin) {
            abort(403, 'Nemate pravo da brišete ovaj dokument.');
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