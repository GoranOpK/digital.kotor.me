<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\UserDocument;
use App\Rules\KotorMunicipalityAddress;
use App\Support\KotorAddress;
use App\Identity\Runtime\CurrentIdentityResolver;
use App\Identity\Runtime\ExistingSubjectIdentityEligibility;
use App\Identity\Runtime\ExistingSubjectIdentityReturnTo;
use App\Identity\Runtime\IdentityUseGateException;
use App\Security\JmbDualWrite;
use App\Security\JmbEncryptedReadException;
use App\Support\KnApplicationClassification;
use App\Support\KnApplicationStartContext;
use App\Support\Pib;
use App\Support\SensitiveIdentifierLogSanitizer;
use App\Services\KnApplicationStartContextFactory;
use App\Services\KnApplicationStartContextStore;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    /**
     * Autoritativni start-context za novu prijavu. Query parametar nije authority.
     */
    public function start(Request $request, Competition $competition): RedirectResponse
    {
        $user = Auth::user();
        $roleName = $user->role ? $user->role->name : null;
        $identityResolver = app(CurrentIdentityResolver::class);

        if ($identityResolver->gatesSubjectFlows()) {
            try {
                $identityResolver->requireCurrentSubject($user);
            } catch (IdentityUseGateException $e) {
                if (app(ExistingSubjectIdentityEligibility::class)->isEligible($user)) {
                    app(ExistingSubjectIdentityReturnTo::class)->rememberFromRequest($request);

                    return redirect()->route('identity.completion.create');
                }

                return redirect()->route('competitions.show', $competition)
                    ->withErrors(['error' => $e->getMessage()]);
            }
        }

        if ($roleName === 'komisija' && $competition->commission_id) {
            $commissionMember = \App\Models\CommissionMember::activeForCommission(
                $user->id,
                $competition->commission_id
            );
            if ($commissionMember) {
                abort(403, 'Članovi komisije ne mogu se prijaviti na konkurse za koje su imenovani kao članovi komisije.');
            }
        }

        if ($roleName === 'konkurs_admin') {
            abort(403, 'Administrator konkursa ne može se prijaviti na konkurse.');
        }

        if ($competition->status !== 'published') {
            abort(404, 'Konkurs nije pronađen ili nije objavljen.');
        }

        if (! $competition->is_open) {
            $message = $competition->is_upcoming
                ? 'Konkurs još nije počeo. Prijave će biti moguće od datuma početka konkursa.'
                : 'Rok za prijave je istekao.';

            return redirect()->route('competitions.show', $competition)
                ->withErrors(['error' => $message]);
        }

        $existingApplication = Application::query()
            ->where('competition_id', $competition->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingApplication) {
            if ($existingApplication->status === 'draft') {
                return redirect()->to($this->applicationCreateUrl($competition, $existingApplication));
            }

            return redirect()->route('applications.show', $existingApplication)
                ->with('info', 'Već ste podneli prijavu na ovaj konkurs.');
        }

        $context = app(KnApplicationStartContextFactory::class)->fromShowRequest($user, $competition, $request);
        $token = app(KnApplicationStartContextStore::class)->put($context);

        return redirect()->route('applications.create', [
            'competition' => $competition,
            'start_token' => $token,
        ]);
    }

    /**
     * Prikaz forme za prijavu na konkurs (Obrazac 1a/1b)
     */
    public function create(Competition $competition, Request $request): View|RedirectResponse
    {
        $competition->load('upNumber');
        $user = Auth::user();
        $roleName = $user->role ? $user->role->name : null;
        $identityResolver = app(CurrentIdentityResolver::class);

        if (! $request->has('application_id') && $identityResolver->gatesSubjectFlows()) {
            try {
                $identityResolver->requireCurrentSubject($user);
            } catch (IdentityUseGateException $e) {
                if (app(ExistingSubjectIdentityEligibility::class)->isEligible($user)) {
                    app(ExistingSubjectIdentityReturnTo::class)->rememberFromRequest($request);

                    return redirect()->route('identity.completion.create');
                }

                return redirect()->route('competitions.show', $competition)
                    ->withErrors(['error' => $e->getMessage()]);
            }
        }
        
        // Provjeri da li je ovo read-only pristup za člana komisije
        $readOnly = false;
        $existingApplication = null;
        
        if ($request->has('application_id')) {
            // Ako je proslijeđen application_id, provjeri da li je član komisije
            $existingApplication = Application::with(['user', 'competition'])->find($request->application_id);
            
            if ($existingApplication && $roleName === 'komisija') {
                // Provjeri da li je član komisije za ovu prijavu
                $isCommissionMemberForThisCompetition = false;
                $appCompetition = $existingApplication->competition;
                if ($appCompetition && $appCompetition->commission_id) {
                    $commissionMember = \App\Models\CommissionMember::activeForCommission(
                        $user->id,
                        $appCompetition->commission_id
                    );

                    if ($commissionMember) {
                        $isCommissionMemberForThisCompetition = true;
                        $readOnly = true;
                    }
                }
                
                if (!$isCommissionMemberForThisCompetition) {
                    abort(403, 'Nemate pristup ovoj prijavi.');
                }

                if ($existingApplication->status === 'draft') {
                    abort(403, 'Prijava još nije podnesena. Članovi komisije mogu vidjeti prijavu tek nakon što korisnik klikne na "Podnesi prijavu".');
                }

                if ($appCompetition && !in_array($appCompetition->status, ['closed', 'completed']) && !$appCompetition->isApplicationDeadlinePassed()) {
                    abort(403, 'Prijave su komisiji vidljive tek nakon isteka roka za prijavljivanje na konkurs.');
                }

                if ($appCompetition && $appCompetition->isCommissionProcessingBlocked()) {
                    abort(403, \App\Models\Competition::COMMISSION_PROCESSING_BLOCKED_MESSAGE);
                }
            }

            // Administrator konkursa: read-only pristup obrascu samo u arhivi (closed/completed)
            if ($existingApplication && $roleName === 'konkurs_admin') {
                $appCompetition = $existingApplication->competition;
                if ($appCompetition && in_array($appCompetition->status, ['closed', 'completed'], true)) {
                    $readOnly = true;
                } else {
                    abort(403, 'Administrator konkursa može pregledati obrasce samo za arhivirane konkurse.');
                }
            }
        }
        
        // Blokiraj članove komisije od prijavljivanja NA KONKURSE za koje su imenovani kao članovi,
        // ali im dozvoli prijavu na druge konkurse
        if ($roleName === 'komisija' && !$readOnly) {
            $isCommissionMemberForThisCompetition = false;
            if ($competition->commission_id) {
                $commissionMember = \App\Models\CommissionMember::activeForCommission(
                    $user->id,
                    $competition->commission_id
                );
                if ($commissionMember) {
                    $isCommissionMemberForThisCompetition = true;
                }
            }
            if ($isCommissionMemberForThisCompetition) {
                abort(403, 'Članovi komisije ne mogu se prijaviti na konkurse za koje su imenovani kao članovi komisije.');
            }
        }
        
        // Administrator konkursa ne može se prijaviti na konkurse
        if ($roleName === 'konkurs_admin' && !$readOnly) {
            abort(403, 'Administrator konkursa ne može se prijaviti na konkurse.');
        }
        
        // Query applicant_type nije authority. Start-context token ili sačuvana prijava jeste.
        $preferredApplicantType = null;

        // Proveri da li je konkurs otvoren (samo ako nije read-only)
        if (!$readOnly && $competition->status !== 'published') {
            abort(404, 'Konkurs nije pronađen ili nije objavljen.');
        }

        // Isti kriterijum kao na stranici konkursa (start_date + deadline_days, ne published_at)
        if (!$readOnly && !$competition->is_open) {
            $message = $competition->is_upcoming
                ? 'Konkurs još nije počeo. Prijave će biti moguće od datuma početka konkursa.'
                : 'Rok za prijave je istekao.';

            return redirect()->route('competitions.show', $competition)
                ->withErrors(['error' => $message]);
        }

        // Ako nije read-only, provjeri da li korisnik već ima prijavu
        if (!$readOnly && !$existingApplication) {
            // Prvo provjeri da li je proslijeđen application_id kao query parametar
            if ($request->has('application_id')) {
                $existingApplication = Application::with('user')->find($request->application_id);
                
                // Provjeri da li prijava pripada trenutnom korisniku
                if ($existingApplication && $existingApplication->user_id !== Auth::id()) {
                    abort(403, 'Nemate pristup ovoj prijavi.');
                }
            }
            
            // Ako još uvijek nema existingApplication, provjeri da li korisnik već ima prijavu za ovaj konkurs
            if (!$existingApplication) {
                $existingApplication = Application::with('user')->where('competition_id', $competition->id)
                    ->where('user_id', Auth::id())
                    ->first();
            }

            if ($existingApplication) {
                // Ako je proslijeđen application_id query parametar, prikaži formu bez obzira na status
                if ($request->has('application_id')) {
                    return $this->applicationCreateView($request, $competition, $user, $existingApplication, $readOnly);
                }
                
                // Ako postoji draft prijava, omogući nastavak popunjavanja
                if ($existingApplication->status === 'draft') {
                    return $this->applicationCreateView($request, $competition, $user, $existingApplication, $readOnly)
                        ->with('info', 'Već imate započetu prijavu. Možete je nastaviti popunjavati.');
                } else {
                    // Ako je prijava već podnesena, preusmeri na detalje
                    return redirect()->route('applications.show', $existingApplication)
                        ->with('info', 'Već ste podneli prijavu na ovaj konkurs.');
                }
            }
        }

        if ($readOnly && $existingApplication?->user) {
            $user = $existingApplication->user;
        }

        return $this->applicationCreateView($request, $competition, $user, $existingApplication, $readOnly);
    }

    /**
     * Snimi prijavu (Obrazac 1a/1b)
     */
    public function store(Request $request, Competition $competition): RedirectResponse
    {
        $user = Auth::user();
        $roleName = $user->role ? $user->role->name : null;
        if (app(CurrentIdentityResolver::class)->gatesSubjectFlows()) {
            try {
                app(CurrentIdentityResolver::class)->requireCurrentSubject($user);
            } catch (IdentityUseGateException $e) {
                if (app(ExistingSubjectIdentityEligibility::class)->isEligible($user)) {
                    app(ExistingSubjectIdentityReturnTo::class)->rememberFromRequest($request);

                    return redirect()->route('identity.completion.create');
                }

                return back()->withErrors(['error' => $e->getMessage()])->withInput();
            }
        }
        // Blokiraj članove komisije od podnošenja prijave NA KONKURSE za koje su imenovani kao članovi
        if ($roleName === 'komisija') {
            $isCommissionMemberForThisCompetition = false;
            if ($competition->commission_id) {
                $commissionMember = \App\Models\CommissionMember::activeForCommission(
                    $user->id,
                    $competition->commission_id
                );
                if ($commissionMember) {
                    $isCommissionMemberForThisCompetition = true;
                }
            }
            if ($isCommissionMemberForThisCompetition) {
                abort(403, 'Članovi komisije ne mogu podnositi prijave na konkurse za koje su imenovani kao članovi komisije.');
            }
        }
        
        // Proveri da li je konkurs otvoren (isti kriterijum kao Competition::is_open)
        if (!$competition->is_open) {
            return back()->withErrors(['error' => 'Rok za prijave je istekao ili konkurs nije otvoren za prijave.'])->withInput();
        }

        // Validacija osnovnih podataka
        // applicant_type je oblik Obrasca 1 (1a/1b), ne dokaz registrovanosti i ne kanonski identitet.
        // is_registered se određuje iz kanonskog stanja korisnika, ne iz applicant_type.

        // Proveri da li je ovo draft ili finalno čuvanje
        $isDraft = $request->has('save_as_draft') && $request->save_as_draft === '1';

        $existingApplication = $this->findExistingApplicationForStore($request, $competition);
        $startContext = $this->authoritativeStartContext($request, $user, $competition, $existingApplication);
        $this->rejectIfRequestContradictsStartContext($request, $startContext);

        $knContext = $this->knStoreContext($user);
        $kn = $knContext['classification'];
        $resolvedIsRegistered = $knContext['is_registered'];
        if ($resolvedIsRegistered !== $startContext->isRegistered) {
            throw ValidationException::withMessages([
                'is_registered' => 'Registrovanost biznisa se ne može mijenjati.',
            ]);
        }

        $requestedStage = $request->input('business_stage');
        if (is_string($requestedStage) && $requestedStage !== '' && ! $kn->allowsStage($requestedStage)) {
            throw ValidationException::withMessages([
                'business_stage' => 'Neregistrovani biznis može biti samo u fazi Započinjanje.',
            ]);
        }

        $resolvedApplicantType = $startContext->applicantType;
        $resolvedBusinessStage = $startContext->businessStage;
        $resolvedRegistrationForm = $startContext->registrationForm;

        $request->merge([
            'applicant_type' => $resolvedApplicantType,
            'business_stage' => $resolvedBusinessStage,
            'registration_form' => $resolvedRegistrationForm ?? $request->input('registration_form'),
        ]);

        $physicalJmbgFromForm = $request->filled('physical_person_jmbg');
        $applicantJmbgFromForm = $request->filled('applicant_jmbg')
            || $request->filled('preduzetnik_jmbg')
            || $request->filled('doo_jmbg');

        try {
            $this->mergeApplicantJmbgIntoRequest($request);
        } catch (JmbEncryptedReadException $e) {
            return back()->withErrors(['error' => JmbEncryptedReadException::USER_MESSAGE])->withInput();
        }

        if (! $resolvedIsRegistered) {
            $request->merge([
                'bank_account' => null,
                'vat_number' => null,
                'website' => null,
                'crps_number' => null,
                'pib' => null,
                'company_seat' => null,
                'founder_name' => null,
                'director_name' => null,
            ]);
        }

        if (!$isDraft && in_array($request->applicant_type, ['preduzetnica', 'doo', 'ostalo', 'fizicko_lice'], true)) {
            $profileAddressError = $this->profileAddressErrorForUser($request->user());
            if ($profileAddressError !== null) {
                return back()->withErrors(['preduzetnik_address' => $profileAddressError])->withInput();
            }
        }

        if (!$isDraft && $resolvedIsRegistered && $request->applicant_type === 'preduzetnica' && !$request->filled('registration_form')) {
            $request->merge(['registration_form' => 'Preduzetnik']);
        }
        
        $liveApplicantTypes = $resolvedApplicantType;
        $liveStages = $kn->isRegisteredBusiness
            ? 'započinjanje,razvoj'
            : 'započinjanje';

        $rules = [
            'business_plan_name' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
            'applicant_type' => $isDraft ? 'nullable|in:'.$liveApplicantTypes : 'required|in:'.$liveApplicantTypes,
            'business_stage' => $isDraft ? 'nullable|in:'.$liveStages : 'required|in:'.$liveStages,
            'business_area' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
            'requested_amount' => 'nullable|numeric|min:0',
            'total_budget_needed' => 'nullable|numeric|min:0',
            'website' => 'nullable|url|max:255',
            'bank_account' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'pib' => ($resolvedIsRegistered && !$isDraft)
                ? 'required|string|regex:'.Pib::REGEX
                : 'nullable|string|regex:'.Pib::REGEX,
        ];

        // Izjava o tačnosti je obavezna za sve tipove prijave
        if (in_array($request->applicant_type, ['preduzetnica', 'doo', 'ostalo', 'fizicko_lice'], true) && !$isDraft) {
            $rules['accuracy_declaration'] = 'required|accepted';
        }

        // Dodatna pravila za fizičko lice BEZ registrovane djelatnosti
        if ($request->applicant_type === 'fizicko_lice' && !$isDraft) {
            $rules['business_stage'] = 'required|in:'.$liveStages;
        }

        // Company-block (osnivač / direktor / sjedište) je obavezan samo za registrovani 1b.
        // KN-FS-003 §7.8: neregistrovani 1b može biti Popunjen bez podataka koji postoje tek nakon registracije.
        $isCompanyForm = $request->applicant_type === 'doo' || $request->applicant_type === 'ostalo';
        if ($isCompanyForm && $resolvedIsRegistered && !$isDraft) {
            $rules['founder_name'] = 'required|string|max:255';
            $rules['director_name'] = 'required|string|max:255';
            $rules['company_seat'] = ['required', 'string', 'max:255', new KotorMunicipalityAddress()];
        } elseif ($isCompanyForm || $isDraft) {
            $rules['founder_name'] = 'nullable|string|max:255';
            $rules['director_name'] = 'nullable|string|max:255';
            $rules['company_seat'] = ['nullable', 'string', 'max:255', new KotorMunicipalityAddress()];
        }

        if (in_array($request->applicant_type, ['preduzetnica', 'doo', 'ostalo'], true)) {
            $rules['applicant_jmbg'] = $isDraft
                ? 'nullable|string|regex:/^[0-9]{13}$/'
                : 'required|string|regex:/^[0-9]{13}$/';
        }

        $contactRequired = $isDraft ? 'nullable' : 'required';
        if ($request->applicant_type === 'preduzetnica') {
            $rules['preduzetnik_name'] = $contactRequired.'|string|max:255';
            $rules['preduzetnik_phone'] = $contactRequired.'|string|max:50';
            $rules['preduzetnik_email'] = $contactRequired.'|email|max:255';
            $rules['preduzetnik_address'] = $contactRequired.'|string|max:500';
        }

        if ($isCompanyForm) {
            $rules['doo_name'] = $contactRequired.'|string|max:255';
            $rules['doo_phone'] = $contactRequired.'|string|max:50';
            $rules['doo_email'] = $contactRequired.'|email|max:255';
            $rules['doo_address'] = $contactRequired.'|string|max:500';
        }

        // Dodatna polja za fizičko lice BEZ registrovane djelatnosti
        // Ova polja se koriste samo za 'fizicko_lice' tip, ne za 'preduzetnica' (koja je takođe fizičko lice ali SA registrovanom djelatnošću)
        if ($request->applicant_type === 'fizicko_lice' && !$isDraft) {
            $rules['physical_person_name'] = 'required|string|max:255';
            $rules['physical_person_jmbg'] = 'required|string|regex:/^[0-9]{13}$/';
            $rules['physical_person_phone'] = 'required|string|max:50';
            $rules['physical_person_email'] = 'required|email|max:255';
            $rules['physical_person_address'] = 'required|string|max:500';
        } elseif ($isDraft && $request->applicant_type === 'fizicko_lice') {
            // Za draft, ova polja su opciona
            $rules['physical_person_name'] = 'nullable|string|max:255';
            $rules['physical_person_jmbg'] = 'nullable|string|regex:/^[0-9]{13}$/';
            $rules['physical_person_phone'] = 'nullable|string|max:50';
            $rules['physical_person_email'] = 'nullable|email|max:255';
            $rules['physical_person_address'] = 'nullable|string|max:500';
        }

        // Oblik registracije, CRPS i PIB su obavezni samo kada je biznis registrovan
        if ($resolvedIsRegistered && !$isDraft) {
            $rules['registration_form'] = 'required|in:Preduzetnik,Ortačko društvo,Komanditno društvo,Društvo sa ograničenom odgovornošću,Akcionarsko društvo,Dio stranog društva (predstavništvo ili poslovna jedinica),Udruženje (nvo, fondacije, sportske organizacije),Ustanova (državne i privatne),Druge organizacije (Političke partije, Vjerske zajednice, Komore, Sindikati)';
            $rules['crps_number'] = 'required|string|max:50';
        } else {
            $rules['registration_form'] = 'nullable|in:Preduzetnik,Ortačko društvo,Komanditno društvo,Društvo sa ograničenom odgovornošću,Akcionarsko društvo,Dio stranog društva (predstavništvo ili poslovna jedinica),Udruženje (nvo, fondacije, sportske organizacije),Ustanova (državne i privatne),Druge organizacije (Političke partije, Vjerske zajednice, Komore, Sindikati)';
            $rules['crps_number'] = 'nullable|string|max:50';
        }

        try {
            $validated = $request->validate($rules, [
            'business_plan_name.required' => 'Naziv biznis plana je obavezan.',
            'applicant_type.required' => 'Tip podnosioca je obavezan.',
            'business_stage.required' => 'Faza biznisa je obavezna.',
            'business_area.required' => 'Oblast biznisa je obavezna.',
            'accuracy_declaration.required' => 'Morate potvrditi izjavu o tačnosti podataka.',
            'accuracy_declaration.accepted' => 'Morate potvrditi izjavu o tačnosti podataka.',
            'founder_name.required' => 'Ime osnivača/ice je obavezno.',
            'director_name.required' => 'Ime izvršnog direktora/ice je obavezno.',
            'company_seat.required' => 'Sjedište društva je obavezno.',
            'preduzetnik_name.required' => 'Ime i prezime je obavezno.',
            'preduzetnik_phone.required' => 'Kontakt telefon je obavezan.',
            'preduzetnik_email.required' => 'E-mail je obavezan.',
            'preduzetnik_address.required' => 'Adresa je obavezna.',
            'doo_name.required' => 'Ime i prezime nositeljke biznisa je obavezno.',
            'doo_phone.required' => 'Kontakt telefon je obavezan.',
            'doo_email.required' => 'E-mail je obavezan.',
            'doo_address.required' => 'Adresa je obavezna.',
            'registration_form.required' => 'Oblik registracije je obavezan.',
            'registration_form.in' => 'Izabrani oblik registracije nije validan.',
            'crps_number.required' => 'Broj registracije u CRPS je obavezan.',
            'pib.required' => 'PIB je obavezan.',
            'pib.regex' => Pib::VALIDATION_MESSAGE,
            'physical_person_name.required' => 'Ime i prezime je obavezno za fizičko lice.',
            'physical_person_jmbg.required' => 'JMBG je obavezan za fizičko lice.',
            'physical_person_jmbg.regex' => 'JMBG mora imati tačno 13 cifara.',
            'applicant_jmbg.required' => 'JMBG je obavezan.',
            'applicant_jmbg.regex' => 'JMBG mora imati tačno 13 cifara.',
            'physical_person_phone.required' => 'Kontakt telefon je obavezan za fizičko lice.',
            'physical_person_email.required' => 'E-mail je obavezan za fizičko lice.',
            'physical_person_email.email' => 'E-mail mora biti validan.',
            'physical_person_address.required' => 'Adresa je obavezna za fizičko lice.',
        ]);
            $safeValidated = SensitiveIdentifierLogSanitizer::redact($validated);
            if (! is_array($safeValidated)) {
                $safeValidated = [];
            }
            Log::info(
                'Validation passed! Validated data: '.json_encode($safeValidated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        if (!$isDraft && $request->applicant_type === 'fizicko_lice') {
            $profileAddressError = $this->profileAddressErrorForUser($request->user());
            if ($profileAddressError !== null) {
                return back()
                    ->withErrors(['error' => $profileAddressError . ' Ažurirajte adresu u profilu.'])
                    ->withInput();
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

        // Jedna prijava po korisniku po konkursu (Odluka): editovanje Obrazaca 1a/1b ili Forme biznis plana ažurira postojeću prijavu, ne kreira novu
        if (! $existingApplication && $request->filled('application_id')) {
            $existingApplication = Application::where('id', $request->application_id)
                ->where('competition_id', $competition->id)
                ->where('user_id', Auth::id())
                ->first();
        }
        if (!$existingApplication) {
            $existingApplication = Application::where('competition_id', $competition->id)
                ->where('user_id', Auth::id())
                ->first(); // bilo koji status (draft, submitted, evaluated, itd.)
        }

        try {
            if ($existingApplication) {
            // Ažuriraj postojeću prijavu (draft ili već podnesenu) – jedna prijava po korisniku po konkursu, status se ne mijenja
            // VAŽNO: Koristimo direktno iz request-a, ne iz $validated, jer $validated može biti prazan za neka polja
            $updateData = [
                'business_plan_name' => $request->filled('business_plan_name') ? $request->business_plan_name : $existingApplication->business_plan_name,
                'applicant_type' => $resolvedApplicantType,
                'business_stage' => $resolvedBusinessStage,
                'founder_name' => $resolvedIsRegistered
                    ? ($request->filled('founder_name') ? $request->founder_name : $existingApplication->founder_name)
                    : null,
                'director_name' => $resolvedIsRegistered
                    ? ($request->filled('director_name') ? $request->director_name : $existingApplication->director_name)
                    : null,
                'company_seat' => $resolvedIsRegistered
                    ? ($request->filled('company_seat') ? $request->company_seat : $existingApplication->company_seat)
                    : null,
                'physical_person_name' => $request->filled('physical_person_name') ? $request->physical_person_name : $existingApplication->physical_person_name,
                'physical_person_phone' => $request->filled('physical_person_phone') ? \App\Support\PhoneNumber::normalize($request->physical_person_phone) : $existingApplication->physical_person_phone,
                'physical_person_email' => $request->filled('physical_person_email') ? $request->physical_person_email : $existingApplication->physical_person_email,
                'physical_person_address' => $request->has('physical_person_address')
                    ? ($request->filled('physical_person_address') ? $request->physical_person_address : null)
                    : $existingApplication->physical_person_address,
                'preduzetnik_name' => $request->filled('preduzetnik_name') ? $request->preduzetnik_name : $existingApplication->preduzetnik_name,
                'preduzetnik_phone' => $request->filled('preduzetnik_phone') ? \App\Support\PhoneNumber::normalize($request->preduzetnik_phone) : $existingApplication->preduzetnik_phone,
                'preduzetnik_email' => $request->filled('preduzetnik_email') ? $request->preduzetnik_email : $existingApplication->preduzetnik_email,
                'preduzetnik_address' => $request->has('preduzetnik_address')
                    ? ($request->filled('preduzetnik_address') ? $request->preduzetnik_address : null)
                    : $existingApplication->preduzetnik_address,
                'doo_name' => $request->filled('doo_name') ? $request->doo_name : $existingApplication->doo_name,
                'doo_phone' => $request->filled('doo_phone') ? \App\Support\PhoneNumber::normalize($request->doo_phone) : $existingApplication->doo_phone,
                'doo_email' => $request->filled('doo_email') ? $request->doo_email : $existingApplication->doo_email,
                'doo_address' => $request->has('doo_address')
                    ? ($request->filled('doo_address') ? $request->doo_address : null)
                    : $existingApplication->doo_address,
                'requested_amount' => $request->filled('requested_amount') ? $request->requested_amount : $existingApplication->requested_amount,
                'total_budget_needed' => $request->filled('total_budget_needed') ? $request->total_budget_needed : $existingApplication->total_budget_needed,
                'business_area' => $request->filled('business_area') ? $request->business_area : $existingApplication->business_area,
                'website' => $request->filled('website') ? $request->website : $existingApplication->website,
                'bank_account' => $request->filled('bank_account') ? $request->bank_account : $existingApplication->bank_account,
                'vat_number' => $request->filled('vat_number') ? $request->vat_number : $existingApplication->vat_number,
                'pib' => $resolvedIsRegistered
                    ? ($request->has('pib') ? ($request->pib ?: null) : $existingApplication->pib)
                    : null,
                'crps_number' => $resolvedIsRegistered
                    ? ($request->filled('crps_number') ? $request->crps_number : $existingApplication->crps_number)
                    : null,
                'registration_form' => $resolvedRegistrationForm ?? ($request->filled('registration_form') ? $request->registration_form : $existingApplication->registration_form),
                'is_registered' => $resolvedIsRegistered,
                'accuracy_declaration' => $request->has('accuracy_declaration') && ($request->accuracy_declaration == '1' || $request->accuracy_declaration === true),
                'previous_support_declaration' => $request->has('previous_support_declaration'),
            ];

            if ($physicalJmbgFromForm) {
                JmbDualWrite::assignLogical(
                    $existingApplication,
                    'physical_person_jmbg',
                    $request->filled('physical_person_jmbg') ? (string) $request->physical_person_jmbg : null
                );
            }
            if ($applicantJmbgFromForm) {
                JmbDualWrite::assignLogical(
                    $existingApplication,
                    'applicant_jmbg',
                    $request->filled('applicant_jmbg') ? (string) $request->applicant_jmbg : null
                );
            }

            $existingApplication->update($updateData);

                $application = $existingApplication;
            } else {
                // Kreiraj novu prijavu
                // VAŽNO: Koristimo direktno iz request-a, ne iz $validated, jer $validated može biti prazan za neka polja
                // is_registered je snapshot registrovanosti iz kanonskog identiteta, ne iz applicant_type.
                $application = new Application([
                    'competition_id' => $competition->id,
                    'user_id' => Auth::id(),
                    'business_plan_name' => $request->filled('business_plan_name') ? $request->business_plan_name : null,
                    'applicant_type' => $resolvedApplicantType,
                    'business_stage' => $resolvedBusinessStage,
                    'founder_name' => ($resolvedIsRegistered && $request->filled('founder_name')) ? $request->founder_name : null,
                    'director_name' => ($resolvedIsRegistered && $request->filled('director_name')) ? $request->director_name : null,
                    'company_seat' => ($resolvedIsRegistered && $request->filled('company_seat')) ? $request->company_seat : null,
                    'physical_person_name' => $request->filled('physical_person_name') ? $request->physical_person_name : null,
                    'physical_person_phone' => $request->filled('physical_person_phone') ? \App\Support\PhoneNumber::normalize($request->physical_person_phone) : null,
                    'physical_person_email' => $request->filled('physical_person_email') ? $request->physical_person_email : null,
                    'physical_person_address' => $request->filled('physical_person_address') ? $request->physical_person_address : null,
                    'preduzetnik_name' => $request->filled('preduzetnik_name') ? $request->preduzetnik_name : null,
                    'preduzetnik_phone' => $request->filled('preduzetnik_phone') ? \App\Support\PhoneNumber::normalize($request->preduzetnik_phone) : null,
                    'preduzetnik_email' => $request->filled('preduzetnik_email') ? $request->preduzetnik_email : null,
                    'preduzetnik_address' => $request->filled('preduzetnik_address') ? $request->preduzetnik_address : null,
                    'doo_name' => $request->filled('doo_name') ? $request->doo_name : null,
                    'doo_phone' => $request->filled('doo_phone') ? \App\Support\PhoneNumber::normalize($request->doo_phone) : null,
                    'doo_email' => $request->filled('doo_email') ? $request->doo_email : null,
                    'doo_address' => $request->filled('doo_address') ? $request->doo_address : null,
                    'requested_amount' => $request->filled('requested_amount') ? $request->requested_amount : null,
                    'total_budget_needed' => $request->filled('total_budget_needed') ? $request->total_budget_needed : null,
                    'business_area' => $request->filled('business_area') ? $request->business_area : null,
                    'website' => $request->filled('website') ? $request->website : null,
                    'bank_account' => $request->filled('bank_account') ? $request->bank_account : null,
                    'vat_number' => $request->filled('vat_number') ? $request->vat_number : null,
                    'pib' => ($resolvedIsRegistered && $request->filled('pib')) ? $request->pib : null,
                    'crps_number' => ($resolvedIsRegistered && $request->filled('crps_number')) ? $request->crps_number : null,
                    'registration_form' => $resolvedRegistrationForm ?? ($request->filled('registration_form') ? $request->registration_form : null),
                    'is_registered' => $resolvedIsRegistered,
                    'accuracy_declaration' => $request->has('accuracy_declaration') && ($request->accuracy_declaration == '1' || $request->accuracy_declaration === true),
                    'previous_support_declaration' => $request->has('previous_support_declaration'),
                    'status' => 'draft', // Draft dok se ne prilože svi dokumenti
                ]);
                if ($request->filled('physical_person_jmbg')) {
                    JmbDualWrite::assignLogical($application, 'physical_person_jmbg', (string) $request->physical_person_jmbg);
                }
                if (in_array($request->applicant_type, ['preduzetnica', 'doo', 'ostalo'], true) && $request->filled('applicant_jmbg')) {
                    JmbDualWrite::assignLogical($application, 'applicant_jmbg', (string) $request->applicant_jmbg);
                }
                $application->save();
            }
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Greška pri čuvanju prijave: ' . $e->getMessage()])->withInput();
        }

        app(KnApplicationStartContextStore::class)->forget(
            $request->input('start_context_token') ?: $request->query('start_token')
        );

        $application->refresh();

        // Proveri da li je Obrazac 1a/1b kompletno popunjen (sva polja + checkbox-ovi)
        $isObrazacComplete = $application->isObrazacComplete();

        // VAŽNO: Prvo proveri da li je obrazac kompletan, pa tek onda proveri da li je draft
        // Ako je obrazac kompletan, preusmeri na formu za biznis plan (bez obzira na $isDraft)
        if ($isObrazacComplete) {
            return redirect()->route('applications.business-plan.create', $application)
                ->with('success', 'Obrazac 1a/1b je kompletno popunjen. Sada popunite biznis plan.');
        }

        if ($isDraft) {
            return redirect()->route('dashboard')
                ->with('success', 'Prijava je sačuvana kao nacrt. U bilo kom trenutku je možete nastaviti iz sekcije \"Moje prijave\".');
        }

        return redirect()->to($this->applicationCreateUrl($competition, $application))
            ->with('warning', 'Prijava je sačuvana, ali još nisu popunjena sva obavezna polja. Provjerite formu i pokušajte ponovo.')
            ->withInput();
    }

    /**
     * Prikaz detalja prijave
     */
    public function show(Application $application): View
    {
        // Prikaz prijave:
        // - vlasnik prijave uvijek može da vidi
        // - član komisije može da vidi samo prijave za konkurse dodijeljene njegovoj komisiji
        $user = Auth::user();
        $isOwner = $application->user_id === $user->id;
        $roleName = $user->role ? $user->role->name : null;

        $isCommissionMemberForThisCompetition = false;
        if ($roleName === 'komisija') {
            $competition = $application->competition;
            if ($competition && $competition->commission_id) {
                $commissionMember = \App\Models\CommissionMember::activeForCommission(
                    $user->id,
                    $competition->commission_id
                );

                if ($commissionMember) {
                    $isCommissionMemberForThisCompetition = true;
                }
            }
        }

        if (!$isOwner && !$isCommissionMemberForThisCompetition) {
            abort(403, 'Nemate pristup ovoj prijavi.');
        }

        // Članovi komisije mogu vidjeti samo prijave koje su podnesene (status 'submitted' ili viši)
        // Ne mogu vidjeti draft prijave
        if ($isCommissionMemberForThisCompetition && $application->status === 'draft') {
            abort(403, 'Prijava još nije podnesena. Članovi komisije mogu vidjeti prijavu tek nakon što korisnik klikne na "Podnesi prijavu".');
        }

        // Članovi komisije ne mogu vidjeti prijave dok ne istekne rok od 20 dana za prijavljivanje na konkurs
        if ($isCommissionMemberForThisCompetition) {
            $competition = $application->competition;
            if ($competition && !in_array($competition->status, ['closed', 'completed']) && !$competition->isApplicationDeadlinePassed()) {
                abort(403, 'Prijave su komisiji vidljive tek nakon isteka roka za prijavljivanje na konkurs (20 dana). Do tada prijave nisu dostupne za pregled ni ocjenjivanje.');
            }

            if ($competition && $competition->isCommissionProcessingBlocked()) {
                abort(403, \App\Models\Competition::COMMISSION_PROCESSING_BLOCKED_MESSAGE);
            }
        }

        $application->load(['competition', 'businessPlan', 'documents', 'evaluationScores.commissionMember', 'contract', 'reports', 'eliminatoryCheck', 'eliminatoryNotice', 'prigovor']);

        // Provjeri da li je prijava spremna za podnošenje
        // Napomena: Dozvoljavamo prijavu čak i ako nisu sva dokumenta uploadovana
        // Predsjednik komisije će odbiti prijavu ako nedostaju dokumenti kroz formu za ocjenjivanje
        $requiredDocs = $application->getStrictlyRequiredDocuments();
        $uploadedDocs = $application->documents->pluck('document_type')->toArray();
        $missingDocs = array_diff($requiredDocs, $uploadedDocs);
        
        $isReadyToSubmit = $application->status === 'draft' && 
                           $application->businessPlan !== null;

        // Samo vlasnik može da mijenja (uploaduje/briše dokumente, podnosi prijavu, uređuje biznis plan)
        $canManage = $isOwner;
        $canSubmitPrigovor = $isOwner
            && app(\App\Services\ApplicationPrigovorService::class)->applicantCanSubmit($application, $user);

        return view('applications.show', compact('application', 'isReadyToSubmit', 'canManage', 'missingDocs', 'isCommissionMemberForThisCompetition', 'canSubmitPrigovor'));
    }

    /**
     * Brisanje prijave od strane korisnika
     *
     * Korisnik može obrisati svoju prijavu samo do isteka roka za prijavu na konkurs.
     */
    public function destroy(Application $application): RedirectResponse
    {
        $user = Auth::user();

        // Dozvoli brisanje samo vlasniku prijave
        if ($application->user_id !== $user->id) {
            abort(403, 'Nemate pravo da obrišete ovu prijavu.');
        }

        $competition = $application->competition;

        if (!$competition || !$competition->published_at) {
            return redirect()->route('dashboard')
                ->withErrors(['error' => 'Ova prijava nije povezana sa važećim konkursom. Brisanje nije moguće.']);
        }

        // Kandidat ne može brisati prijavu nakon isteka roka od 20 dana ili ako je konkurs zatvoren
        if ($competition->status === 'closed' || $competition->isApplicationDeadlinePassed()) {
            return redirect()->route('applications.show', $application)
                ->withErrors(['error' => 'Rok za prijave je istekao ili je konkurs zatvoren. Prijavu više nije moguće obrisati.']);
        }

        // Obriši prijavu (i kaskadno povezane podatke prema definisanim relacionim pravilima)
        $application->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Prijava je uspješno obrisana.');
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

        $kotorAddressError = $this->kotorAddressErrorForApplication($application);
        if ($kotorAddressError !== null) {
            return back()->withErrors(['error' => $kotorAddressError]);
        }

        // Napomena: Provjera dokumenata je uklonjena - korisnici mogu podnijeti prijavu i bez svih dokumenata.
        // Predsjednik komisije će odbiti prijavu ako nedostaju dokumenti kroz formu za ocjenjivanje.

        // Isti kriterijum kao create/store: podnošenje dozvoljeno samo dok je konkurs otvoren za prijave
        $competition = $application->competition;
        if (!$competition || !$competition->is_open) {
            return back()->withErrors(['error' => 'Rok za prijave je istekao ili konkurs nije otvoren za prijave.']);
        }

        // Dodeli redni broj prijave (1, 2, 3, ...) po konkursu
        $maxRedni = Application::where('competition_id', $application->competition_id)->max('redni_broj');
        $application->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'redni_broj' => ($maxRedni ?? 0) + 1,
        ]);

        return redirect()->route('applications.show', $application)
            ->with('success', 'Poštovani, vaša prijava će biti proslijeđena komisiji na razmatranje, nakon isteka roka za prijavljivanje!');
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
            'document_type' => 'required|string|in:licna_karta,crps_resenje,pib_resenje,pdv_resenje,statut,karton_potpisa,potvrda_neosudjivanost,uvjerenje_opstina_porezi,uvjerenje_opstina_nepokretnost,potvrda_upc_porezi,ioppd_obrazac,godisnji_racuni,izvjestaj_realizacija,finansijski_izvjestaj,izvjestaj_registar_kase,dokaz_ziro_racun,predracuni_nabavka,potvrda_zavod_nezaposleni,ostalo',
            'files' => 'required_without:user_document_id|array|min:1',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:20480', // 20MB max po fajlu
            'user_document_id' => 'nullable|required_without:files',
            'document_name' => 'nullable|string|max:255',
        ], [
            'document_type.required' => 'Tip dokumenta je obavezan.',
            'files.required_without' => 'Morate priložiti fajl ili izabrati dokument iz biblioteke.',
            'user_document_id.required_without' => 'Morate priložiti fajl ili izabrati dokument iz biblioteke.',
            'files.*.max' => 'Fajl ne može biti veći od 20MB.',
            'document_name.required' => 'Unesite naziv spojenog dokumenta.',
            'document_name.max' => 'Naziv dokumenta može imati najviše 255 karaktera.',
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

            // Kreiraj vezu sa prijavom (kopiraj i MEGA podatke ako postoje)
            ApplicationDocument::create([
                'application_id' => $application->id,
                'name' => $userDocument->name,
                'file_path' => $userDocument->file_path,
                'cloud_path' => $userDocument->cloud_path,
                'mega_node_id' => $userDocument->mega_node_id,
                'mega_file_name' => $userDocument->mega_file_name,
                'document_type' => $validated['document_type'],
                'is_required' => in_array($validated['document_type'], $application->getStrictlyRequiredDocuments()),
                'user_document_id' => $userDocument->id,
            ]);

            return back()->with('success', 'Dokument je uspješno priložen iz biblioteke.');
        }

        // Ako je upload-ovan novi fajl (jedan ili više)
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            $documentProcessor = app(\App\Services\DocumentProcessor::class);

            if (count($files) > 1) {
                $request->validate([
                    'document_name' => 'required|string|max:255',
                ], [
                    'document_name.required' => 'Unesite naziv spojenog dokumenta.',
                    'document_name.max' => 'Naziv dokumenta može imati najviše 255 karaktera.',
                ]);
            }

            if (count($files) > 1) {
                $result = $documentProcessor->mergeDocuments($files, Auth::id());
            } else {
                $result = $documentProcessor->processDocument($files[0], Auth::id());
            }

            if (!$result['success']) {
                return back()->withErrors(['files' => $result['error'] ?? 'Greška pri obradi fajla.'])->withInput();
            }

            $filePath = $result['file_path'];
            $megaFileName = basename($filePath);
            $cloudPath = null;
            $megaNodeId = null;

            // Upload na MEGA (dokument ostaje i lokalno, i na MEGA)
            $fullPath = Storage::disk('local')->path($filePath);
            $folderPath = 'digital.kotor/applications/user_' . Auth::id();
            $nodeScript = base_path('scripts/upload-to-mega.js');
            $nodeBinary = env('NODE_BINARY', 'node');

            $processResult = Process::path(base_path())->run([
                $nodeBinary,
                $nodeScript,
                $fullPath,
                $folderPath,
                $megaFileName,
            ]);

            if ($processResult->successful()) {
                try {
                    $megaData = json_decode(trim($processResult->output()), true);
                    if (is_array($megaData) && !empty($megaData['mega_link'])) {
                        $cloudPath = $megaData['mega_link'];
                        $megaNodeId = $megaData['mega_node_id'] ?? null;
                    }
                } catch (\Throwable $e) {
                    Log::warning('MEGA upload result parse failed', ['output' => $processResult->output(), 'error' => $e->getMessage()]);
                }
            } else {
                Log::warning('MEGA upload failed for application document', [
                    'file_path' => $filePath,
                    'error' => $processResult->errorOutput(),
                ]);
            }

            $documentName = count($files) === 1
                ? $files[0]->getClientOriginalName()
                : $this->normalizeDocumentDisplayName($request->input('document_name'));

            $fileSize = $result['file_size'] ?? (file_exists(Storage::disk('local')->path($filePath)) ? filesize(Storage::disk('local')->path($filePath)) : 0);
            $userDocumentId = null;

            // Ako je štrikirana opcija "Sačuvaj u biblioteku", kreiraj UserDocument
            if ($request->boolean('save_to_library')) {
                $userDocument = UserDocument::create([
                    'user_id' => Auth::id(),
                    'category' => 'Ostali dokumenti',
                    'name' => $documentName,
                    'file_path' => $filePath,
                    'cloud_path' => $cloudPath,
                    'mega_node_id' => $megaNodeId,
                    'mega_file_name' => $cloudPath ? $megaFileName : null,
                    'original_filename' => $documentName,
                    'file_size' => $fileSize,
                    'status' => 'active',
                    'processed_at' => now(),
                ]);
                $userDocumentId = $userDocument->id;
            }

            ApplicationDocument::create([
                'application_id' => $application->id,
                'name' => $documentName,
                'file_path' => $filePath,
                'cloud_path' => $cloudPath,
                'mega_node_id' => $megaNodeId,
                'mega_file_name' => $cloudPath ? $megaFileName : null,
                'document_type' => $validated['document_type'],
                'is_required' => in_array($validated['document_type'], $application->getStrictlyRequiredDocuments()),
                'user_document_id' => $userDocumentId,
            ]);

            $successMessage = 'Dokument je uspješno upload-ovan, priložen i podignut na MEGA.';
            if ($userDocumentId) {
                $successMessage .= ' Dokument je takođe sačuvan u vašu biblioteku dokumenata.';
            }
            return back()->with('success', $successMessage);
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
        // - član komisije (role: komisija) za konkurs dodijeljen njegovoj komisiji, ili
        // - administrator konkursa za arhivirane konkurse
        // može da pregleda dokument
        $isCommissionMemberForThisCompetition = false;
        $isCompetitionAdminArchiveAccess = false;

        if ($roleName === 'komisija') {
            // Učitaj konkurs povezano sa prijavom
            $competition = $application->competition;

            if ($competition && $competition->commission_id) {
                $commissionMember = \App\Models\CommissionMember::activeForCommission(
                    $user->id,
                    $competition->commission_id
                );

                if ($commissionMember) {
                    $isCommissionMemberForThisCompetition = true;
                }
            }
        }

        if ($roleName === 'konkurs_admin') {
            $competition = $application->competition;
            if ($competition && in_array($competition->status, ['closed', 'completed'], true)) {
                $isCompetitionAdminArchiveAccess = true;
            }
        }

        if (!$isOwner && !$isCommissionMemberForThisCompetition && !$isCompetitionAdminArchiveAccess) {
            abort(403, 'Nemate pravo pristupa ovom dokumentu.');
        }

        // Iste vidljivosti kao ApplicationController::show — PRIJE lokalnog fajla / MEGA
        if ($isCommissionMemberForThisCompetition && $application->status === 'draft') {
            abort(403, 'Prijava još nije podnesena. Članovi komisije mogu vidjeti prijavu tek nakon što korisnik klikne na "Podnesi prijavu".');
        }

        if ($isCommissionMemberForThisCompetition) {
            $competition = $application->competition;
            if ($competition && !in_array($competition->status, ['closed', 'completed']) && !$competition->isApplicationDeadlinePassed()) {
                abort(403, 'Prijave su komisiji vidljive tek nakon isteka roka za prijavljivanje na konkurs (20 dana). Do tada prijave nisu dostupne za pregled ni ocjenjivanje.');
            }

            if ($competition && $competition->isCommissionProcessingBlocked()) {
                abort(403, \App\Models\Competition::COMMISSION_PROCESSING_BLOCKED_MESSAGE);
            }
        }

        // Ako lokalni fajl ne postoji, a ima MEGA link – preuzmi sa MEGA-e
        if (!$document->file_path || !Storage::disk('local')->exists($document->file_path)) {
            if ($document->cloud_path && str_contains((string) $document->cloud_path, 'mega.nz')) {
                return $this->serveFromMega($document->cloud_path, $document->name ?? 'document.pdf', true);
            }
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

        // Ako lokalni fajl ne postoji, a ima MEGA link – preuzmi sa MEGA-e
        if (!$document->file_path || !Storage::disk('local')->exists($document->file_path)) {
            if ($document->cloud_path && str_contains((string) $document->cloud_path, 'mega.nz')) {
                return $this->serveFromMega($document->cloud_path, $document->name ?? 'document.pdf', false);
            }
            abort(404, 'Fajl dokumenta nije pronađen na serveru.');
        }

        $downloadName = $document->name ?? basename($document->file_path);
        return Storage::disk('local')->download($document->file_path, $downloadName);
    }

    /**
     * Preuzima fajl sa MEGA-e i servira ga (view ili download).
     */
    private function serveFromMega(string $cloudPath, string $downloadName, bool $inline): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_\-\.\s]/', '_', $downloadName) ?: 'document';
        $ext = pathinfo($downloadName, PATHINFO_EXTENSION) ?: 'pdf';
        $tempRel = 'temp_downloads/app_' . uniqid('dl_', true) . '_' . $safeName . '.' . $ext;
        $tempFull = Storage::disk('local')->path($tempRel);

        Storage::disk('local')->makeDirectory(dirname($tempRel));
        $nodeScript = base_path('scripts/download-from-mega.js');
        $nodeBinary = env('NODE_BINARY', 'node');

        $result = Process::path(base_path())->run([$nodeBinary, $nodeScript, $cloudPath, $tempFull]);

        if (!$result->successful()) {
            Log::error('serveFromMega failed', ['output' => $result->output(), 'error' => $result->errorOutput()]);
            return redirect()->away($cloudPath);
        }

        $disposition = $inline ? 'inline' : 'attachment';
        return response()->download($tempFull, $downloadName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . basename($downloadName) . '"',
        ])->deleteFileAfterSend(true);
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

    protected function kotorAddressErrorForApplication(Application $application): ?string
    {
        $application->loadMissing(['businessPlan', 'user']);

        if (in_array($application->applicant_type, ['doo', 'ostalo'], true)) {
            if (!KotorAddress::isInKotorMunicipality($application->company_seat)) {
                return 'Sjedište društva mora biti na teritoriji Opštine Kotor.';
            }
        } else {
            $identity = $application->user
                ? app(CurrentIdentityResolver::class)->viewFor($application->user)
                : null;
            $identityAddress = $identity
                ? KotorAddress::formatStreetAndCity($identity->address, $identity->city)
                : '';
            $address = $application->businessPlan?->applicant_address ?: $identityAddress;
            if (!KotorAddress::isInKotorMunicipality($address)) {
                return KotorAddress::validationMessage();
            }
        }

        $companyAddress = trim((string) ($application->businessPlan?->company_address ?? ''));
        if ($companyAddress !== '' && !KotorAddress::isInKotorMunicipality($companyAddress)) {
            return 'Adresa/sjedište registrovane djelatnosti mora biti na teritoriji Opštine Kotor.';
        }

        return null;
    }

    protected function normalizeDocumentDisplayName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/[\\\\\/:*?"<>|]+/u', '-', $name) ?? $name;
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        if ($name === '') {
            return 'Dokument.pdf';
        }

        if (!str_ends_with(strtolower($name), '.pdf')) {
            $name .= '.pdf';
        }

        return $name;
    }

    /**
     * JMBG u Obrascu 1a/1b: iz polja forme, ranije sačuvane prijave ili profila korisnika.
     */
    protected function mergeApplicantJmbgIntoRequest(Request $request): void
    {
        $applicantType = $request->input('applicant_type');
        if ($applicantType === 'fizicko_lice') {
            if (filled($request->input('physical_person_jmbg'))) {
                return;
            }

            if ($request->filled('application_id')) {
                $existingApplication = Application::where('id', $request->application_id)
                    ->where('user_id', $request->user()->id)
                    ->first();

                if ($existingApplication && $existingApplication->hasPhysicalPersonJmbgSnapshot()) {
                    $request->merge(['physical_person_jmbg' => $existingApplication->physicalPersonJmbgForRead()]);

                    return;
                }
            }

            $userJmb = app(CurrentIdentityResolver::class)->viewFor($request->user())->jmb;
            if (filled($userJmb)) {
                $request->merge(['physical_person_jmbg' => trim((string) $userJmb)]);
            }

            return;
        }

        if (!in_array($applicantType, ['preduzetnica', 'doo', 'ostalo'], true)) {
            return;
        }

        $fromForm = $applicantType === 'preduzetnica'
            ? $request->input('preduzetnik_jmbg')
            : $request->input('doo_jmbg');

        if (filled($fromForm)) {
            $request->merge(['applicant_jmbg' => trim((string) $fromForm)]);

            return;
        }

        if ($request->filled('application_id')) {
            $existingApplication = Application::where('id', $request->application_id)
                ->where('user_id', $request->user()->id)
                ->first();

            if ($existingApplication && $existingApplication->hasApplicantJmbgSnapshot()) {
                $request->merge(['applicant_jmbg' => $existingApplication->applicantJmbgForRead()]);

                return;
            }
        }

        $userJmb = app(CurrentIdentityResolver::class)->viewFor($request->user())->jmb;
        if (filled($userJmb)) {
            $request->merge(['applicant_jmbg' => trim((string) $userJmb)]);
        }
    }

    protected function profileAddressErrorForUser(?\App\Models\User $user): ?string
    {
        if (!$user) {
            return 'Nije moguće učitati adresu iz profila.';
        }

        $identity = app(CurrentIdentityResolver::class)->viewFor($user);
        $profileAddress = KotorAddress::formatStreetAndCity($identity->address, $identity->city);
        if ($profileAddress === '') {
            return 'Popunite ulicu i grad u svom profilu prije nastavka prijave.';
        }

        if (!KotorAddress::isInKotorMunicipality($profileAddress)) {
            return KotorAddress::validationMessage();
        }

        return null;
    }

    /**
     * @return array{classification: KnApplicationClassification, is_registered: bool}
     */
    protected function knStoreContext(\App\Models\User $user): array
    {
        $identity = app(CurrentIdentityResolver::class)->viewFor($user);
        $classification = KnApplicationClassification::fromUserType($identity->userType);

        return [
            'classification' => $classification,
            'is_registered' => $classification->isRegisteredBusiness,
        ];
    }

    protected function applicationCreateUrl(Competition $competition, Application $application): string
    {
        return route('applications.create', $competition) . '?application_id=' . $application->id;
    }

    protected function applicationCreateView(
        Request $request,
        Competition $competition,
        \App\Models\User $user,
        ?Application $existingApplication,
        bool $readOnly
    ): View|RedirectResponse {
        $userDocuments = collect();
        if (! $readOnly && $user) {
            $userDocuments = UserDocument::where('user_id', $user->id)
                ->where('status', 'active')
                ->get()
                ->groupBy('category');
        }

        $presentation = $this->resolveCreateStartPresentation($request, $user, $competition, $existingApplication, $readOnly);
        if ($presentation instanceof RedirectResponse) {
            return $presentation;
        }

        $physicalPersonJmbgForForm = null;
        $applicantJmbgForForm = null;
        try {
            if ($existingApplication) {
                if ($existingApplication->hasPhysicalPersonJmbgSnapshot()) {
                    $physicalPersonJmbgForForm = $existingApplication->physicalPersonJmbgForRead();
                }
                if ($existingApplication->hasApplicantJmbgSnapshot()) {
                    $applicantJmbgForForm = $existingApplication->applicantJmbgForRead();
                }
            }
        } catch (JmbEncryptedReadException $e) {
            return redirect()->route('competitions.show', $competition)
                ->withErrors(['error' => JmbEncryptedReadException::USER_MESSAGE]);
        }

        $preferredApplicantType = $presentation['preferredApplicantType'];
        $preselectedBusinessStage = $presentation['preselectedBusinessStage'];
        $startContextToken = $presentation['startContextToken'];
        $startContext = $presentation['startContext'];
        $lockedApplicantType = $presentation['lockedApplicantType'];
        $lockedRegistrationForm = $presentation['lockedRegistrationForm'];
        $lockedCommercialForm = $presentation['lockedCommercialForm'];
        $knStartTargetForm = $presentation['knStartTargetForm'];
        $applicantTypeLocked = true;

        return view('applications.create', compact(
            'competition',
            'user',
            'userDocuments',
            'existingApplication',
            'physicalPersonJmbgForForm',
            'applicantJmbgForForm',
            'readOnly',
            'preselectedBusinessStage',
            'preferredApplicantType',
            'startContextToken',
            'startContext',
            'lockedApplicantType',
            'lockedRegistrationForm',
            'lockedCommercialForm',
            'knStartTargetForm',
            'applicantTypeLocked'
        ));
    }

    /**
     * @return array{
     *     preferredApplicantType: string|null,
     *     preselectedBusinessStage: string|null,
     *     startContextToken: string|null,
     *     startContext: KnApplicationStartContext|null,
     *     lockedApplicantType: string|null,
     *     lockedRegistrationForm: string|null,
     *     lockedCommercialForm: string|null,
     *     knStartTargetForm: string|null
     * }|RedirectResponse
     */
    protected function resolveCreateStartPresentation(
        Request $request,
        \App\Models\User $user,
        Competition $competition,
        ?Application $existingApplication,
        bool $readOnly
    ): array|RedirectResponse {
        $factory = app(KnApplicationStartContextFactory::class);

        if ($existingApplication) {
            $context = $factory->fromSavedApplication($existingApplication, $existingApplication->user ?? $user);

            return [
                'preferredApplicantType' => $context->applicantType,
                'preselectedBusinessStage' => $context->businessStage,
                'startContextToken' => null,
                'startContext' => $context,
                'lockedApplicantType' => $context->applicantType,
                'lockedRegistrationForm' => $context->registrationForm,
                'lockedCommercialForm' => $context->commercialForm,
                'knStartTargetForm' => $context->targetForm,
            ];
        }

        if ($readOnly) {
            return [
                'preferredApplicantType' => null,
                'preselectedBusinessStage' => null,
                'startContextToken' => null,
                'startContext' => null,
                'lockedApplicantType' => null,
                'lockedRegistrationForm' => null,
                'lockedCommercialForm' => null,
                'knStartTargetForm' => null,
            ];
        }

        $token = $request->query('start_token');
        $context = app(KnApplicationStartContextStore::class)->get(is_string($token) ? $token : null);
        if ($context === null || $context->userId !== (int) $user->id || $context->competitionId !== (int) $competition->id) {
            return redirect()->route('competitions.show', $competition)
                ->withErrors(['error' => 'Izaberite tip prijave na stranici konkursa prije ulaska u obrazac.']);
        }

        return [
            'preferredApplicantType' => $context->applicantType,
            'preselectedBusinessStage' => $context->businessStage,
            'startContextToken' => is_string($token) ? $token : null,
            'startContext' => $context,
            'lockedApplicantType' => $context->applicantType,
            'lockedRegistrationForm' => $context->registrationForm,
            'lockedCommercialForm' => $context->commercialForm,
            'knStartTargetForm' => $context->targetForm,
        ];
    }

    protected function findExistingApplicationForStore(Request $request, Competition $competition): ?Application
    {
        $existingApplication = null;
        if ($request->filled('application_id')) {
            $existingApplication = Application::where('id', $request->application_id)
                ->where('competition_id', $competition->id)
                ->where('user_id', Auth::id())
                ->first();
        }
        if (! $existingApplication) {
            $existingApplication = Application::where('competition_id', $competition->id)
                ->where('user_id', Auth::id())
                ->first();
        }

        return $existingApplication;
    }

    protected function authoritativeStartContext(
        Request $request,
        \App\Models\User $user,
        Competition $competition,
        ?Application $existingApplication
    ): KnApplicationStartContext {
        if ($existingApplication) {
            return app(KnApplicationStartContextFactory::class)->fromSavedApplication($existingApplication, $user);
        }

        $token = $request->input('start_context_token') ?: $request->query('start_token');
        $context = app(KnApplicationStartContextStore::class)->get(is_string($token) ? $token : null);
        if ($context === null || $context->userId !== (int) $user->id || $context->competitionId !== (int) $competition->id) {
            throw ValidationException::withMessages([
                'start_context_token' => 'Nedostaje ili je istekao kontekst početka prijave. Vratite se na konkurs i ponovo izaberite tip prijave.',
            ]);
        }

        return $context;
    }

    protected function rejectIfRequestContradictsStartContext(Request $request, KnApplicationStartContext $context): void
    {
        $messages = [];

        if ($this->requestHasPresentValue($request, 'applicant_type')
            && $request->input('applicant_type') !== $context->applicantType) {
            $messages['applicant_type'] = 'Tip prijave se ne može mijenjati.';
        }

        if ($this->requestHasPresentValue($request, 'business_stage')
            && $request->input('business_stage') !== $context->businessStage) {
            $messages['business_stage'] = 'Faza biznisa se ne može mijenjati.';
        }

        if ($context->registrationForm !== null
            && $this->requestHasPresentValue($request, 'registration_form')
            && $request->input('registration_form') !== $context->registrationForm) {
            $messages['registration_form'] = 'Pravni oblik se ne može mijenjati.';
        }

        foreach (['planned_company_form', 'commercial_form'] as $field) {
            if (! $this->requestHasPresentValue($request, $field)) {
                continue;
            }
            $sent = (string) $request->input($field);
            if ($context->commercialForm === null || $sent !== $context->commercialForm) {
                $messages[$field] = 'Pravni oblik se ne može mijenjati.';
            }
        }

        if ($request->exists('is_registered') && $request->input('is_registered') !== null && $request->input('is_registered') !== '') {
            if ($this->requestBoolean($request->input('is_registered')) !== $context->isRegistered) {
                $messages['is_registered'] = 'Registrovanost biznisa se ne može mijenjati.';
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    protected function requestHasPresentValue(Request $request, string $key): bool
    {
        if (! $request->exists($key)) {
            return false;
        }

        $value = $request->input($key);
        if (is_string($value)) {
            return trim($value) !== '';
        }

        return $value !== null;
    }

    protected function requestBoolean(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on'], true);
    }

}