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
        // Proveri da li prijava pripada korisniku
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Nemate pristup ovoj prijavi.');
        }

        // Proveri da li je Obrazac 1a/1b kompletno popunjen
        $isComplete = $application->business_plan_name && 
                     $application->applicant_type && 
                     $application->business_stage && 
                     $application->business_area && 
                     $application->requested_amount && 
                     $application->total_budget_needed &&
                     ($application->applicant_type === 'fizicko_lice' ? 
                        ($application->physical_person_name && $application->physical_person_jmbg && $application->physical_person_phone && $application->physical_person_email) :
                        ($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo' ?
                            ($application->founder_name && $application->director_name && $application->company_seat) :
                            true
                        )
                     ) &&
                     ($application->applicant_type !== 'fizicko_lice' ? $application->registration_form : true);

        if (!$isComplete) {
            return redirect()->route('applications.create', $application->competition_id)
                ->withErrors(['error' => 'Molimo popunite kompletan Obrazac 1a ili 1b pre nego što nastavite na biznis plan.'])
                ->withInput();
        }

        // Proveri da li već postoji biznis plan
        $businessPlan = $application->businessPlan;

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

        return view('business-plans.create', compact('application', 'businessPlan', 'defaultData'));
    }

    /**
     * Snimi biznis plan
     */
    public function store(Request $request, Application $application): RedirectResponse
    {
        // Proveri da li prijava pripada korisniku
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Nemate pristup ovoj prijavi.');
        }

        // Validacija
        $validated = $request->validate([
            // I. OSNOVNI PODACI
            'business_idea_name' => 'required|string|max:255',
            'applicant_name' => 'required|string|max:255',
            'applicant_jmbg' => 'required|string|max:13',
            'applicant_address' => 'required|string',
            'applicant_phone' => 'required|string|max:50',
            'applicant_email' => 'required|email|max:255',
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
            'summary' => 'required|string',
            
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

        // Kreiraj ili ažuriraj biznis plan
        $businessPlan = BusinessPlan::updateOrCreate(
            ['application_id' => $application->id],
            array_merge(
                $validated,
                [
                    'has_registered_business' => $request->has('has_registered_business') ? (bool)$request->has_registered_business : null,
                    'has_seasonal_workers' => $request->has('has_seasonal_workers') ? (bool)$request->has_seasonal_workers : null,
                ]
            )
        );

        return redirect()->route('applications.show', $application)
            ->with('success', 'Biznis plan je uspješno sačuvan. Sada možete pregledati prijavu i priložiti dokumente.');
    }
}

