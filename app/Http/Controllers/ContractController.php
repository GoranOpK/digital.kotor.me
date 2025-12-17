<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Generisanje ugovora za prijavu
     */
    public function generate(Application $application): View
    {
        // Proveri da li je prijava odobrena
        if ($application->status !== 'approved') {
            abort(403, 'Ugovor se može generisati samo za odobrene prijave.');
        }

        // Proveri pristup (vlasnik ili admin)
        $user = Auth::user();
        $isOwner = $application->user_id === $user->id;
        $isAdmin = $user->role && ($user->role->name === 'admin' || $user->role->name === 'superadmin');
        
        if (!$isOwner && !$isAdmin) {
            abort(403, 'Nemate pristup ovom ugovoru.');
        }

        // Proveri da li već postoji ugovor
        $contract = $application->contract;

        return view('contracts.generate', compact('application', 'contract'));
    }

    /**
     * Kreiranje ugovora
     */
    public function store(Request $request, Application $application): RedirectResponse
    {
        // Proveri da li je prijava odobrena
        if ($application->status !== 'approved') {
            abort(403, 'Ugovor se može kreirati samo za odobrene prijave.');
        }

        // Proveri pristup
        $user = Auth::user();
        $isAdmin = $user->role && ($user->role->name === 'admin' || $user->role->name === 'superadmin');
        
        if (!$isAdmin) {
            abort(403, 'Samo administratori mogu kreirati ugovore.');
        }

        // Kreiraj ugovor
        $contract = Contract::updateOrCreate(
            ['application_id' => $application->id],
            [
                'status' => 'draft',
            ]
        );

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Ugovor je uspješno kreiran.');
    }

    /**
     * Prikaz ugovora
     */
    public function show(Contract $contract): View
    {
        $contract->load(['application.user', 'application.competition']);
        
        // Proveri pristup
        $user = Auth::user();
        $isOwner = $contract->application->user_id === $user->id;
        $isAdmin = $user->role && ($user->role->name === 'admin' || $user->role->name === 'superadmin');
        
        if (!$isOwner && !$isAdmin) {
            abort(403, 'Nemate pristup ovom ugovoru.');
        }

        return view('contracts.show', compact('contract'));
    }

    /**
     * Download ugovora
     */
    public function download(Contract $contract)
    {
        // Proveri pristup
        $user = Auth::user();
        $isOwner = $contract->application->user_id === $user->id;
        $isAdmin = $user->role && ($user->role->name === 'admin' || $user->role->name === 'superadmin');
        
        if (!$isOwner && !$isAdmin) {
            abort(403, 'Nemate pristup ovom ugovoru.');
        }

        if (!$contract->contract_file || !Storage::disk('local')->exists($contract->contract_file)) {
            abort(404, 'Fajl ugovora nije pronađen.');
        }

        return Storage::disk('local')->download($contract->contract_file, 'ugovor_' . $contract->id . '.pdf');
    }

    /**
     * Upload potpisanog ugovora
     */
    public function upload(Request $request, Contract $contract): RedirectResponse
    {
        // Proveri da li je vlasnik prijave
        if ($contract->application->user_id !== Auth::id()) {
            abort(403, 'Samo vlasnik prijave može upload-ovati potpisani ugovor.');
        }

        $validated = $request->validate([
            'signed_contract' => 'required|file|mimes:pdf|max:10240', // 10MB max
        ], [
            'signed_contract.required' => 'Potpisani ugovor je obavezan.',
            'signed_contract.mimes' => 'Ugovor mora biti PDF fajl.',
            'signed_contract.max' => 'Ugovor ne može biti veći od 10MB.',
        ]);

        // Obriši stari fajl ako postoji
        if ($contract->contract_file && Storage::disk('local')->exists($contract->contract_file)) {
            Storage::disk('local')->delete($contract->contract_file);
        }

        // Upload novog fajla
        $file = $request->file('signed_contract');
        $path = $file->store('contracts/signed', 'local');

        $contract->update([
            'contract_file' => $path,
            'status' => 'signed',
            'signed_at' => now(),
        ]);

        return back()->with('success', 'Potpisani ugovor je uspješno upload-ovan.');
    }

    /**
     * Potvrda ugovora (admin)
     */
    public function approve(Request $request, Contract $contract): RedirectResponse
    {
        // Proveri da li je admin
        $user = Auth::user();
        $isAdmin = $user->role && ($user->role->name === 'admin' || $user->role->name === 'superadmin');
        
        if (!$isAdmin) {
            abort(403, 'Samo administratori mogu potvrditi ugovor.');
        }

        $contract->update([
            'status' => 'approved',
        ]);

        return back()->with('success', 'Ugovor je uspješno potvrđen.');
    }
}
