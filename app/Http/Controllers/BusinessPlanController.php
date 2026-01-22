<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\BusinessPlan;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BusinessPlanController extends Controller
{
    /**
     * Prikaz forme za popunjavanje biznis plana (Obrazac 2)
     */
    public function create(Application $application): View
    {
        // Prikaz biznis plana:
        // - vlasnik prijave uvijek može da vidi/uređuje
        // - član komisije može da vidi samo biznis plan za konkurse dodijeljene njegovoj komisiji (read-only)
        $user = Auth::user();
        $isOwner = $application->user_id === $user->id;
        $roleName = $user->role ? $user->role->name : null;

        $isCommissionMemberForThisCompetition = false;
        if ($roleName === 'komisija') {
            $competition = $application->competition;
            if ($competition && $competition->commission_id) {
                $commissionMember = \App\Models\CommissionMember::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->first();

                if ($commissionMember && $commissionMember->commission_id === $competition->commission_id) {
                    $isCommissionMemberForThisCompetition = true;
                }
            }
        }

        if (!$isOwner && !$isCommissionMemberForThisCompetition) {
            abort(403, 'Nemate pristup ovoj prijavi.');
        }

        // Proveri da li je Obrazac 1a/1b kompletno popunjen
        if (!$application->isObrazacComplete()) {
            return redirect()->route('applications.create', $application->competition_id)
                ->withErrors(['error' => 'Molimo popunite kompletan Obrazac 1a ili 1b (sva obavezna polja i potvrdite sve obavezne izjave) pre nego što nastavite na biznis plan.'])
                ->withInput();
        }

        // Proveri da li već postoji biznis plan
        $businessPlan = $application->businessPlan;
        
        // Debug: Provjeri kako se podaci učitavaju iz baze
        if ($businessPlan) {
            \Log::info("Business plan found, ID: " . $businessPlan->id);
            \Log::info("Employment structure raw: " . json_encode($businessPlan->getRawOriginal('employment_structure')));
            \Log::info("Employment structure casted: " . json_encode($businessPlan->employment_structure));
        } else {
            \Log::info("No business plan found for application ID: " . $application->id);
        }

        // Pripremi podatke za automatsko popunjavanje iz prijave
        $user = Auth::user();
        $defaultData = [];
        
        // Podaci o podnosiocu - uzmi iz prijave ili korisničkog profila
        if ($application->applicant_type === 'fizicko_lice') {
            // Za fizičko lice, podaci su u prijavi
            $defaultData['applicant_name'] = $application->physical_person_name ?? $user->name ?? '';
            $defaultData['applicant_jmbg'] = $application->physical_person_jmbg ?? $user->jmb ?? '';
            $defaultData['applicant_phone'] = $application->physical_person_phone ?? $user->phone ?? '';
            $defaultData['applicant_email'] = $application->physical_person_email ?? $user->email ?? '';
            $defaultData['applicant_address'] = $user->address ?? '';
        } elseif ($application->applicant_type === 'preduzetnica') {
            // Za preduzetnicu, podaci su u korisničkom profilu
            $defaultData['applicant_name'] = $user->name ?? '';
            $defaultData['applicant_jmbg'] = $user->jmb ?? '';
            $defaultData['applicant_phone'] = $user->phone ?? '';
            $defaultData['applicant_email'] = $user->email ?? '';
            $defaultData['applicant_address'] = $user->address ?? '';
        } elseif ($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') {
            // Za DOO/Ostalo, podaci su u korisničkom profilu
            $defaultData['applicant_name'] = $user->name ?? '';
            $defaultData['applicant_jmbg'] = $user->jmb ?? '';
            $defaultData['applicant_phone'] = $user->phone ?? '';
            $defaultData['applicant_email'] = $user->email ?? '';
            $defaultData['applicant_address'] = $user->address ?? '';
        }

        // Podaci o registrovanom biznisu - uzmi iz prijave
        $defaultData['has_registered_business'] = $application->is_registered ?? false;
        $defaultData['registration_form'] = $application->registration_form ?? '';
        $defaultData['pib'] = $application->pib ?? $user->pib ?? '';
        $defaultData['vat_number'] = $application->vat_number ?? '';
        $defaultData['bank_account'] = $application->bank_account ?? '';
        $defaultData['company_website'] = $application->website ?? '';

        // Ako već postoji biznis plan, koristi njegove podatke, inače koristi default podatke
        if ($businessPlan) {
            // Ako biznis plan već ima podatke, koristi ih
            $defaultData = array_merge($defaultData, [
                'applicant_name' => $businessPlan->applicant_name ?? $defaultData['applicant_name'],
                'applicant_jmbg' => $businessPlan->applicant_jmbg ?? $defaultData['applicant_jmbg'],
                'applicant_phone' => $businessPlan->applicant_phone ?? $defaultData['applicant_phone'],
                'applicant_email' => $businessPlan->applicant_email ?? $defaultData['applicant_email'],
                'applicant_address' => $businessPlan->applicant_address ?? $defaultData['applicant_address'],
                'has_registered_business' => $businessPlan->has_registered_business ?? $defaultData['has_registered_business'],
                'registration_form' => $businessPlan->registration_form ?? $defaultData['registration_form'],
                'pib' => $businessPlan->pib ?? $defaultData['pib'],
                'vat_number' => $businessPlan->vat_number ?? $defaultData['vat_number'],
                'bank_account' => $businessPlan->bank_account ?? $defaultData['bank_account'],
                'company_website' => $businessPlan->company_website ?? $defaultData['company_website'],
            ]);
        }

        // Član komisije može samo da pregleda (read-only), bez izmjena
        $readOnly = !$isOwner;
        
        // Ako je član komisije, osiguraj da je readOnly = true
        if ($roleName === 'komisija' && !$readOnly) {
            abort(403, 'Članovi komisije mogu samo pregledati biznis planove u read-only modu.');
        }

        return view('business-plans.create', compact('application', 'businessPlan', 'defaultData', 'readOnly'));
    }

    /**
     * Snimi biznis plan
     */
    public function store(Request $request, Application $application): RedirectResponse
    {
        // Blokiraj članove komisije od čuvanja izmjena
        $user = Auth::user();
        $roleName = $user->role ? $user->role->name : null;
        if ($roleName === 'komisija') {
            abort(403, 'Članovi komisije mogu samo pregledati biznis planove, ne mogu ih mijenjati.');
        }
        
        // Proveri da li prijava pripada korisniku
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Nemate pristup ovoj prijavi.');
        }

        // Proveri da li se čuva kao nacrt
        $isDraft = $request->has('save_as_draft') && $request->save_as_draft === '1';

        // Validacija
        $validated = $request->validate([
            // I. OSNOVNI PODACI
            'business_idea_name' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
            'applicant_name' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
            'applicant_jmbg' => $isDraft ? 'nullable|string|max:13' : 'required|string|max:13',
            'applicant_address' => $isDraft ? 'nullable|string' : 'required|string',
            'applicant_phone' => $isDraft ? 'nullable|string|max:50' : 'required|string|max:50',
            'applicant_email' => $isDraft ? 'nullable|email|max:255' : 'required|email|max:255',
            'has_registered_business' => 'nullable|boolean',
            'registration_form' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'pib' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'company_address' => 'nullable|string',
            'company_phone' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:255',
            'company_website' => 'nullable|string|max:255',
            'bank_account' => 'nullable|string|max:255',
            'summary' => $isDraft ? 'nullable|string' : 'required|string',
            
            // II. MARKETING
            'products_services_table' => 'nullable|array',
            'realization_type' => 'nullable|string|max:255',
            'target_customers' => 'nullable|array',
            'sales_locations' => 'nullable|array',
            'has_business_space' => 'nullable|string|max:50',
            'pricing_table' => 'nullable|array',
            'annual_sales_volume' => 'nullable|numeric|min:0',
            'revenue_share_table' => 'nullable|array',
            'promotion' => 'nullable|string',
            'employment_structure' => 'nullable|array',
            'has_seasonal_workers' => 'nullable|boolean',
            'competition_analysis' => 'nullable|string',
            
            // III. POSLOVANJE
            'business_analysis' => 'nullable|string',
            'business_history' => 'nullable|array',
            'required_resources' => 'nullable|string',
            'suppliers_table' => 'nullable|array',
            'annual_purchases_volume' => 'nullable|numeric|min:0',
            
            // IV. FINANSIJE
            'required_amount' => 'nullable|numeric|min:0',
            'requested_amount' => 'nullable|numeric|min:0',
            'funding_sources_table' => 'nullable|array',
            'funding_alternative' => 'nullable|string|max:50',
            'revenue_projection' => 'nullable|array',
            'expense_projection' => 'nullable|array',
            
            // V. LJUDI
            'work_experience' => 'nullable|string',
            'personal_strengths_weaknesses' => 'nullable|string',
            'biggest_support' => 'nullable|string|max:255',
            'job_schedule' => 'nullable|array',
            
            // VI. RIZICI
            'risk_matrix' => 'nullable|array',
        ], [
            'business_idea_name.required' => 'Naziv biznis ideje je obavezan.',
            'applicant_name.required' => 'Ime i prezime podnosioca je obavezno.',
            'applicant_jmbg.required' => 'JMBG je obavezan.',
            'applicant_address.required' => 'Adresa je obavezna.',
            'applicant_phone.required' => 'Kontakt telefon je obavezan.',
            'applicant_email.required' => 'E-mail je obavezan.',
            'summary.required' => 'Rezime je obavezno.',
        ]);

        // Očisti i pripremi podatke iz tabela - osiguraj da se čuvaju svi redovi
        // Koristi $request->all() umjesto $validated za tabela polja jer $validated možda ne sadrži sve podatke
        $tableFields = [
            'products_services_table',
            'target_customers',
            'sales_locations',
            'pricing_table',
            'revenue_share_table',
            'employment_structure',
            'business_history',
            'suppliers_table',
            'funding_sources_table',
            'revenue_projection',
            'expense_projection',
            'job_schedule',
            'risk_matrix',
        ];

        $cleanedData = $validated;
        foreach ($tableFields as $field) {
            // Koristi podatke direktno iz request-a, ne iz validated
            if ($request->has($field) && is_array($request->input($field))) {
                $tableData = $request->input($field);
                
                // Debug log
                \Log::info("Processing table field: {$field}");
                \Log::info("Raw data count: " . count($tableData));
                \Log::info("Raw data: " . json_encode($tableData));
                
                // Filtriraj prazne redove - zadrži samo redove gdje je barem jedno polje popunjeno
                $cleanedData[$field] = array_filter($tableData, function($row) {
                    if (!is_array($row)) return false;
                    // Provjeri da li je barem jedno polje u redu popunjeno
                    foreach ($row as $value) {
                        if (!empty($value) && trim($value) !== '') {
                            return true;
                        }
                    }
                    return false;
                });
                // Resetuj array ključeve da budu sekvencijalni (0, 1, 2, ...)
                $cleanedData[$field] = array_values($cleanedData[$field]);
                
                // Debug log
                \Log::info("Cleaned data count: " . count($cleanedData[$field]));
                \Log::info("Cleaned data: " . json_encode($cleanedData[$field]));
            } elseif (isset($cleanedData[$field]) && is_array($cleanedData[$field])) {
                // Ako nema u request-u, ali ima u validated, koristi validated
                $cleanedData[$field] = array_filter($cleanedData[$field], function($row) {
                    if (!is_array($row)) return false;
                    foreach ($row as $value) {
                        if (!empty($value) && trim($value) !== '') {
                            return true;
                        }
                    }
                    return false;
                });
                $cleanedData[$field] = array_values($cleanedData[$field]);
            }
        }

        // Kreiraj ili ažuriraj biznis plan
        $businessPlan = BusinessPlan::updateOrCreate(
            ['application_id' => $application->id],
            array_merge(
                $cleanedData,
                [
                    'has_registered_business' => $request->has('has_registered_business') ? (bool)$request->has_registered_business : null,
                    'has_seasonal_workers' => $request->has('has_seasonal_workers') ? (bool)$request->has_seasonal_workers : null,
                ]
            )
        );

        // Ako je sačuvano kao nacrt, vrati korisnika na Moj Panel
        if ($isDraft) {
            return redirect()->route('dashboard')
                ->with('success', 'Biznis plan je sačuvan kao nacrt. Možete ga nastaviti popunjavati iz sekcije \"Moje prijave\".');
        }

        // U suprotnom, vrati na prikaz prijave
        return redirect()->route('applications.show', $application)
            ->with('success', 'Biznis plan je uspješno sačuvan. Sada možete pregledati prijavu i priložiti dokumente.');
    }
}

