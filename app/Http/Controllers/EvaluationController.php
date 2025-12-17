<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\EvaluationScore;
use App\Models\CommissionMember;
use App\Models\Commission;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    /**
     * Lista prijava za ocjenjivanje
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        // Pronađi člana komisije za trenutnog korisnika
        $commissionMember = CommissionMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$commissionMember) {
            abort(403, 'Niste član komisije.');
        }

        // Prijave koje treba ocjeniti (submitted ili evaluated status)
        $query = Application::with(['user', 'competition'])
            ->whereIn('status', ['submitted', 'evaluated']);

        // Filtriranje po konkursu
        if ($request->has('competition_id') && $request->competition_id !== '') {
            $query->where('competition_id', $request->competition_id);
        }

        // Prijave koje korisnik još nije ocjenio
        $evaluatedApplicationIds = EvaluationScore::where('commission_member_id', $commissionMember->id)
            ->pluck('application_id')
            ->toArray();

        if ($request->has('filter') && $request->filter === 'pending') {
            $query->whereNotIn('id', $evaluatedApplicationIds);
        } elseif ($request->has('filter') && $request->filter === 'evaluated') {
            $query->whereIn('id', $evaluatedApplicationIds);
        }

        $applications = $query->latest()->paginate(20);
        $competitions = \App\Models\Competition::where('status', 'published')
            ->orWhere('status', 'closed')
            ->get();

        return view('evaluation.index', compact('applications', 'competitions', 'commissionMember'));
    }

    /**
     * Forma za ocjenjivanje prijave
     */
    public function create(Application $application): View
    {
        $user = Auth::user();
        
        // Pronađi člana komisije
        $commissionMember = CommissionMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$commissionMember) {
            abort(403, 'Niste član komisije.');
        }

        // Proveri da li je već ocjenjeno
        $existingScore = EvaluationScore::where('application_id', $application->id)
            ->where('commission_member_id', $commissionMember->id)
            ->first();

        $application->load(['user', 'competition', 'businessPlan']);

        return view('evaluation.create', compact('application', 'commissionMember', 'existingScore'));
    }

    /**
     * Snimanje ocjene
     */
    public function store(Request $request, Application $application): RedirectResponse
    {
        $user = Auth::user();
        
        // Pronađi člana komisije
        $commissionMember = CommissionMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$commissionMember) {
            abort(403, 'Niste član komisije.');
        }

        // Validacija - svaki kriterijum 1-5 poena
        $rules = [];
        $messages = [];
        
        for ($i = 1; $i <= 10; $i++) {
            $rules["criterion_{$i}"] = 'required|integer|min:1|max:5';
            $messages["criterion_{$i}.required"] = "Kriterijum {$i} je obavezan.";
            $messages["criterion_{$i}.min"] = "Kriterijum {$i} mora biti najmanje 1 poen.";
            $messages["criterion_{$i}.max"] = "Kriterijum {$i} može biti najviše 5 poena.";
        }

        $rules['notes'] = 'nullable|string|max:5000';

        $validated = $request->validate($rules, $messages);

        // Izračunaj zbir ocjena
        $totalScore = 0;
        for ($i = 1; $i <= 10; $i++) {
            $totalScore += $validated["criterion_{$i}"];
        }

        // Kreiraj ili ažuriraj ocjenu
        $evaluationScore = EvaluationScore::updateOrCreate(
            [
                'application_id' => $application->id,
                'commission_member_id' => $commissionMember->id,
            ],
            [
                'criterion_1' => $validated['criterion_1'],
                'criterion_2' => $validated['criterion_2'],
                'criterion_3' => $validated['criterion_3'],
                'criterion_4' => $validated['criterion_4'],
                'criterion_5' => $validated['criterion_5'],
                'criterion_6' => $validated['criterion_6'],
                'criterion_7' => $validated['criterion_7'],
                'criterion_8' => $validated['criterion_8'],
                'criterion_9' => $validated['criterion_9'],
                'criterion_10' => $validated['criterion_10'],
                'final_score' => $totalScore,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        // Ažuriraj prosječnu ocjenu prijave (prosjek svih članova komisije)
        $this->updateApplicationScores($application);

        return redirect()->route('evaluation.index')
            ->with('success', 'Ocjena je uspješno sačuvana.');
    }

    /**
     * Ažurira prosječne ocjene prijave na osnovu ocjena svih članova komisije
     */
    private function updateApplicationScores(Application $application): void
    {
        $scores = EvaluationScore::where('application_id', $application->id)->get();

        if ($scores->isEmpty()) {
            return;
        }

        // Izračunaj prosjek za svaki kriterijum
        $averages = [];
        for ($i = 1; $i <= 10; $i++) {
            $criterionScores = $scores->pluck("criterion_{$i}")->filter()->toArray();
            if (!empty($criterionScores)) {
                $averages[$i] = round(array_sum($criterionScores) / count($criterionScores), 2);
            } else {
                $averages[$i] = 0;
            }
        }

        // Izračunaj konačnu ocjenu (zbir prosjeka)
        $finalScore = round(array_sum($averages), 2);

        // Ažuriraj prijavu
        $application->update([
            'final_score' => $finalScore,
            'status' => 'evaluated',
            'evaluated_at' => now(),
        ]);

        // Ažuriraj prosjek u svim ocjenama (za prikaz)
        foreach ($scores as $score) {
            $score->update(['average_score' => $finalScore]);
        }
    }

    /**
     * Prikaz ocjene (read-only)
     */
    public function show(Application $application): View
    {
        $user = Auth::user();
        
        $commissionMember = CommissionMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$commissionMember) {
            abort(403, 'Niste član komisije.');
        }

        $evaluationScore = EvaluationScore::where('application_id', $application->id)
            ->where('commission_member_id', $commissionMember->id)
            ->first();

        if (!$evaluationScore) {
            return redirect()->route('evaluation.create', $application);
        }

        $application->load(['user', 'competition', 'businessPlan']);

        return view('evaluation.show', compact('application', 'commissionMember', 'evaluationScore'));
    }
}
