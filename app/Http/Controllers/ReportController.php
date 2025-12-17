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
    public function __construct()
    {
        $this->middleware('auth');
    }

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

        $validated = $request->validate([
            'description' => 'required|string|max:10000',
            'document_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240', // 10MB
        ], [
            'description.required' => 'Opis realizacije je obavezan.',
            'document_file.mimes' => 'Dokument mora biti PDF, DOC ili DOCX.',
            'document_file.max' => 'Dokument ne može biti veći od 10MB.',
        ]);

        $filePath = null;
        if ($request->hasFile('document_file')) {
            $file = $request->file('document_file');
            $filePath = $file->store('reports/realization', 'local');
        }

        $report = Report::updateOrCreate(
            [
                'application_id' => $application->id,
                'type' => 'realization',
            ],
            [
                'description' => $validated['description'],
                'document_file' => $filePath,
                'status' => 'submitted',
            ]
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

        $validated = $request->validate([
            'description' => 'required|string|max:10000',
            'document_file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
        ], [
            'description.required' => 'Opis finansijskog izvještaja je obavezan.',
            'document_file.required' => 'Finansijski izvještaj je obavezan.',
            'document_file.mimes' => 'Dokument mora biti PDF, DOC, DOCX, XLS ili XLSX.',
            'document_file.max' => 'Dokument ne može biti veći od 10MB.',
        ]);

        $file = $request->file('document_file');
        $filePath = $file->store('reports/financial', 'local');

        $report = Report::updateOrCreate(
            [
                'application_id' => $application->id,
                'type' => 'financial',
            ],
            [
                'description' => $validated['description'],
                'document_file' => $filePath,
                'status' => 'submitted',
            ]
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
