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

        // Proveri da li već postoji biznis plan
        $businessPlan = $application->businessPlan;

        return view('business-plans.create', compact('application', 'businessPlan'));
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
            'applicant_data' => 'required|string',
            'registered_activity_data' => 'required|string',
            'summary' => 'required|string',
            
            // II. MARKETING
            'product_service' => 'required|string',
            'location' => 'required|string',
            'pricing' => 'required|string',
            'promotion' => 'required|string',
            'people_marketing' => 'required|string',
            
            // III. POSLOVANJE
            'business_analysis' => 'required|string',
            'supply_market' => 'required|string',
            
            // IV. FINANSIJE
            'required_funds' => 'required|string',
            'revenue_expense_projection' => 'required|string',
            
            // V. LJUDI
            'entrepreneur_data' => 'required|string',
            'job_schedule' => 'required|string',
            
            // VI. RIZICI
            'risk_matrix' => 'required|string',
        ], [
            'business_idea_name.required' => 'Naziv biznis ideje je obavezan.',
            'applicant_data.required' => 'Podaci o podnosiocu su obavezni.',
            'registered_activity_data.required' => 'Podaci o registrovanoj djelatnosti su obavezni.',
            'summary.required' => 'Rezime je obavezno.',
            'product_service.required' => 'Proizvod/Usluga je obavezan.',
            'location.required' => 'Lokacija je obavezna.',
            'pricing.required' => 'Cijena je obavezna.',
            'promotion.required' => 'Promocija je obavezna.',
            'people_marketing.required' => 'Ljudi (marketing) je obavezno.',
            'business_analysis.required' => 'Analiza dosadašnjeg poslovanja je obavezna.',
            'supply_market.required' => 'Nabavno tržište je obavezno.',
            'required_funds.required' => 'Potrebna sredstva i izvori finansiranja su obavezni.',
            'revenue_expense_projection.required' => 'Projekcija prihoda i rashoda je obavezna.',
            'entrepreneur_data.required' => 'Podaci o preduzetnici su obavezni.',
            'job_schedule.required' => 'Raspored poslova je obavezan.',
            'risk_matrix.required' => 'Matrica upravljanja rizicima je obavezna.',
        ]);

        // Kreiraj ili ažuriraj biznis plan
        $businessPlan = BusinessPlan::updateOrCreate(
            ['application_id' => $application->id],
            [
                // I. OSNOVNI PODACI
                'business_idea_name' => $validated['business_idea_name'],
                'applicant_data' => $validated['applicant_data'],
                'registered_activity_data' => $validated['registered_activity_data'],
                'summary' => $validated['summary'],
                
                // II. MARKETING
                'product_service' => $validated['product_service'],
                'location' => $validated['location'],
                'pricing' => $validated['pricing'],
                'promotion' => $validated['promotion'],
                'people_marketing' => $validated['people_marketing'],
                
                // III. POSLOVANJE
                'business_analysis' => $validated['business_analysis'],
                'supply_market' => $validated['supply_market'],
                
                // IV. FINANSIJE
                'required_funds' => $validated['required_funds'],
                'revenue_expense_projection' => $validated['revenue_expense_projection'],
                
                // V. LJUDI
                'entrepreneur_data' => $validated['entrepreneur_data'],
                'job_schedule' => $validated['job_schedule'],
                
                // VI. RIZICI
                'risk_matrix' => $validated['risk_matrix'],
            ]
        );

        return redirect()->route('applications.show', $application)
            ->with('success', 'Biznis plan je uspješno sačuvan. Sada možete pregledati prijavu i priložiti dokumente.');
    }
}

