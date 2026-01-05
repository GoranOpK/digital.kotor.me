<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Forma za kreiranje izvještaja o realizaciji (Obrazac 4)
     */
    public function create(Application $application): View
    {
        // Proveri da li je prijava odobrena i ima potpisan ugovor
        if ($application->status !== 'approved') {
            abort(403, 'Izvještaj se može kreirati samo za odobrene prijave.');
        }

        $contract = $application->contract;
        if (!$contract || $contract->status !== 'approved') {
            abort(403, 'Ugovor mora biti potpisan i potvrđen pre kreiranja izvještaja.');
        }

        // Proveri da li je vlasnik prijave
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Nemate pristup ovoj prijavi.');
        }

        // Proveri da li već postoji izvještaj
        $report = $application->reports()->where('type', 'realization')->first();
        
        // Učitaj podatke iz aplikacije i biznis plana ako nema izvještaja
        if (!$report) {
            $report = new Report();
            $report->entrepreneur_name = $application->user->name ?? '';
            $report->business_plan_name = $application->business_plan_name ?? '';
            $report->approved_amount = $application->approved_amount ?? 0;
            if ($application->contract) {
                $report->contract_number = 'Ugovor #' . $application->contract->id;
            }
        }

        return view('reports.create', compact('application', 'report'));
    }

    /**
     * Snimanje izvještaja o realizaciji
     */
    public function store(Request $request, Application $application): RedirectResponse
    {
        // Proveri pristup
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Nemate pristup ovoj prijavi.');
        }

        // Proveri da li već postoji izvještaj sa fajlovima
        $existingReport = $application->reports()->where('type', 'realization')->first();
        $hasExistingFiles = $existingReport && ($existingReport->financial_report_file || $existingReport->invoices_file || $existingReport->bank_statement_file);

        $validated = $request->validate([
            // Osnovni podaci
            'entrepreneur_name' => 'required|string|max:255',
            'legal_status' => 'required|string|max:255',
            'business_plan_name' => 'required|string|max:255',
            'approved_amount' => 'required|numeric|min:0',
            'contract_number' => 'nullable|string|max:255',
            'report_period_start' => 'required|date',
            'report_period_end' => 'required|date|after_or_equal:report_period_start',
            
            // Pitanja
            'activities_description' => 'required|string',
            'problems_description' => 'nullable|string',
            'successes_description' => 'nullable|string',
            'new_employees' => 'nullable|string',
            'new_product_service' => 'nullable|string',
            'purchases_description' => 'nullable|string',
            'deviations_description' => 'nullable|string',
            'satisfaction_with_cooperation' => 'required|in:da,ne',
            'recommendations' => 'nullable|string',
            'will_apply_again' => 'required|in:da,ne',
            
            // Prilozi - obavezni samo ako ne postoje već upload-ovani fajlovi
            'financial_report_file' => $hasExistingFiles && $existingReport->financial_report_file ? 'nullable' : 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
            'invoices_file' => $hasExistingFiles && $existingReport->invoices_file ? 'nullable' : 'required|file|mimes:pdf,jpg,jpeg,png,zip|max:10240',
            'bank_statement_file' => $hasExistingFiles && $existingReport->bank_statement_file ? 'nullable' : 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'entrepreneur_name.required' => 'Ime i prezime preduzetnice je obavezno.',
            'legal_status.required' => 'Pravni status i naziv biznisa je obavezan.',
            'business_plan_name.required' => 'Naziv biznis plana je obavezan.',
            'approved_amount.required' => 'Iznos odobrenih sredstava je obavezan.',
            'report_period_start.required' => 'Početak izvještajnog perioda je obavezan.',
            'report_period_end.required' => 'Kraj izvještajnog perioda je obavezan.',
            'report_period_end.after_or_equal' => 'Kraj perioda mora biti posle ili jednak početku perioda.',
            'activities_description.required' => 'Opis aktivnosti je obavezan.',
            'satisfaction_with_cooperation.required' => 'Morate odgovoriti da li ste zadovoljni saradnjom.',
            'will_apply_again.required' => 'Morate odgovoriti da li ćete aplicirati ponovo.',
            'financial_report_file.required' => 'Finansijski izvještaj je obavezan.',
            'invoices_file.required' => 'Fakture su obavezne.',
            'bank_statement_file.required' => 'Izvod sa banke je obavezan.',
        ]);

        // Upload fajlova - obriši stare ako se upload-uju novi
        $financialReportPath = $existingReport->financial_report_file ?? null;
        if ($request->hasFile('financial_report_file')) {
            // Obriši stari fajl ako postoji
            if ($financialReportPath && Storage::disk('local')->exists($financialReportPath)) {
                Storage::disk('local')->delete($financialReportPath);
            }
            $file = $request->file('financial_report_file');
            $financialReportPath = $file->store('reports/financial', 'local');
        }

        $invoicesPath = $existingReport->invoices_file ?? null;
        if ($request->hasFile('invoices_file')) {
            // Obriši stari fajl ako postoji
            if ($invoicesPath && Storage::disk('local')->exists($invoicesPath)) {
                Storage::disk('local')->delete($invoicesPath);
            }
            $file = $request->file('invoices_file');
            $invoicesPath = $file->store('reports/invoices', 'local');
        }

        $bankStatementPath = $existingReport->bank_statement_file ?? null;
        if ($request->hasFile('bank_statement_file')) {
            // Obriši stari fajl ako postoji
            if ($bankStatementPath && Storage::disk('local')->exists($bankStatementPath)) {
                Storage::disk('local')->delete($bankStatementPath);
            }
            $file = $request->file('bank_statement_file');
            $bankStatementPath = $file->store('reports/bank-statements', 'local');
        }

        $report = Report::updateOrCreate(
            [
                'application_id' => $application->id,
                'type' => 'realization',
            ],
            array_merge(
                $validated,
                [
                    'financial_report_file' => $financialReportPath,
                    'invoices_file' => $invoicesPath,
                    'bank_statement_file' => $bankStatementPath,
                    'status' => 'submitted',
                ]
            )
        );

        return redirect()->route('applications.show', $application)
            ->with('success', 'Izvještaj o realizaciji je uspješno podnesen.');
    }

    /**
     * Forma za finansijski izvještaj (Obrazac 4a)
     */
    public function createFinancial(Application $application): View
    {
        // Proveri pristup
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Nemate pristup ovoj prijavi.');
        }

        $report = $application->reports()->where('type', 'financial')->first();
        
        // Učitaj podatke iz aplikacije ako nema izvještaja
        if (!$report) {
            $report = new Report();
            $report->entrepreneur_name = $application->user->name ?? '';
            $report->business_plan_name = $application->business_plan_name ?? '';
            $report->approved_amount = $application->approved_amount ?? 0;
        }

        return view('reports.create-financial', compact('application', 'report'));
    }

    /**
     * Snimanje finansijskog izvještaja
     */
    public function storeFinancial(Request $request, Application $application): RedirectResponse
    {
        // Proveri pristup
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Nemate pristup ovoj prijavi.');
        }

        // Proveri da li već postoji izvještaj
        $existingReport = $application->reports()->where('type', 'financial')->first();

        $validated = $request->validate([
            // Osnovni podaci
            'entrepreneur_name' => 'required|string|max:255',
            'legal_status' => 'required|string|max:255',
            'business_plan_name' => 'required|string|max:255',
            'approved_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'report_date' => 'required|date',
            
            // Tabela sa nabavkama
            'purchases_table' => 'required|array|min:1',
            'purchases_table.*.purchase_type' => 'nullable|string|max:255',
            'purchases_table.*.amount' => 'nullable|numeric|min:0',
            'purchases_table.*.supplier' => 'nullable|string|max:255',
            'purchases_table.*.invoice_number' => 'nullable|string|max:255',
            'purchases_table.*.invoice_date' => 'nullable|date',
            'purchases_table.*.payment_info' => 'nullable|string|max:255',
        ], [
            'entrepreneur_name.required' => 'Ime i prezime preduzetnice je obavezno.',
            'legal_status.required' => 'Pravni status i naziv biznisa je obavezan.',
            'business_plan_name.required' => 'Naziv biznis plana je obavezan.',
            'approved_amount.required' => 'Iznos odobrenih sredstava je obavezan.',
            'total_amount.required' => 'Iznos ukupnih sredstava je obavezan.',
            'report_date.required' => 'Datum popunjavanja izvještaja je obavezan.',
            'purchases_table.required' => 'Morate dodati najmanje jednu nabavku.',
            'purchases_table.min' => 'Morate dodati najmanje jednu nabavku.',
        ]);

        $report = Report::updateOrCreate(
            [
                'application_id' => $application->id,
                'type' => 'financial',
            ],
            array_merge(
                $validated,
                [
                    'status' => 'submitted',
                ]
            )
        );

        return redirect()->route('applications.show', $application)
            ->with('success', 'Finansijski izvještaj je uspješno podnesen.');
    }

    /**
     * Upload dokaza realizacije
     */
    public function upload(Request $request, Report $report): RedirectResponse
    {
        // Proveri pristup
        if ($report->application->user_id !== Auth::id()) {
            abort(403, 'Nemate pristup ovom izvještaju.');
        }

        $validated = $request->validate([
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'document_file.required' => 'Dokument je obavezan.',
            'document_file.mimes' => 'Dokument mora biti PDF, JPG, JPEG ili PNG.',
            'document_file.max' => 'Dokument ne može biti veći od 10MB.',
        ]);

        // Obriši stari fajl ako postoji
        if ($report->document_file && Storage::disk('local')->exists($report->document_file)) {
            Storage::disk('local')->delete($report->document_file);
        }

        $file = $request->file('document_file');
        $filePath = $file->store('reports/evidence', 'local');

        $report->update([
            'document_file' => $filePath,
        ]);

        return back()->with('success', 'Dokument je uspješno upload-ovan.');
    }

    /**
     * Ocjena izvještaja (admin)
     */
    public function evaluate(Request $request, Report $report): RedirectResponse
    {
        // Proveri da li je admin
        $user = Auth::user();
        $isAdmin = $user->role && ($user->role->name === 'admin' || $user->role->name === 'superadmin');
        
        if (!$isAdmin) {
            abort(403, 'Samo administratori mogu ocjenjivati izvještaje.');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string|max:5000',
        ]);

        $report->update([
            'status' => $validated['status'],
            'evaluation_notes' => $validated['notes'] ?? null,
            'evaluated_at' => now(),
        ]);

        return back()->with('success', 'Izvještaj je uspješno ocjenjen.');
    }

    /**
     * Download izvještaja
     */
    public function download(Report $report)
    {
        // Proveri pristup
        $user = Auth::user();
        $isOwner = $report->application->user_id === $user->id;
        $isAdmin = $user->role && ($user->role->name === 'admin' || $user->role->name === 'superadmin');
        
        if (!$isOwner && !$isAdmin) {
            abort(403, 'Nemate pristup ovom izvještaju.');
        }

        if (!$report->document_file || !Storage::disk('local')->exists($report->document_file)) {
            abort(404, 'Fajl izvještaja nije pronađen.');
        }

        return Storage::disk('local')->download($report->document_file, 'izvjestaj_' . $report->id . '.pdf');
    }
}
