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

        // Učitaj komisiju sa njenim konkursima
        $commission = $commissionMember->commission;
        $commission->load('competitions');

        // Prijave koje treba ocjeniti (submitted ili evaluated status)
        $query = Application::with(['user', 'competition'])
            ->whereIn('status', ['submitted', 'evaluated']);

        // Filtriranje prijava samo za konkurse dodijeljene komisiji člana
        $competitionIds = $commission->competitions->pluck('id');
        if ($competitionIds->isNotEmpty()) {
            $query->whereIn('competition_id', $competitionIds);
        } else {
            // Ako nema konkursa dodijeljenih komisiji, ne prikazuj ništa
            $query->whereRaw('1 = 0');
        }

        // Filtriranje po konkursu (ako je dodatno odabran u filteru)
        if ($request->filled('competition_id')) {
            $query->where('competition_id', $request->competition_id);
        }

        // Prijave koje član komisije još nije ocjenio
        $evaluatedApplicationIds = EvaluationScore::where('commission_member_id', $commissionMember->id)
            ->pluck('application_id')
            ->toArray();

        // Filtriranje po statusu ocjenjivanja
        if ($request->filled('filter')) {
            if ($request->filter === 'pending') {
                // Prijave koje član komisije još nije ocjenio
                if (!empty($evaluatedApplicationIds)) {
            $query->whereNotIn('id', $evaluatedApplicationIds);
                }
                // Ako nema ocjenjenih prijava, sve prijave su "pending"
            } elseif ($request->filter === 'evaluated') {
                // Prijave koje je član komisije već ocjenio
                if (!empty($evaluatedApplicationIds)) {
            $query->whereIn('id', $evaluatedApplicationIds);
                } else {
                    // Ako nema ocjenjenih prijava, ne prikazuj ništa
                    $query->whereRaw('1 = 0');
                }
            }
        }

        $applications = $query->latest()->paginate(20)->appends($request->query());
        
        // Filtriranje konkursa samo za konkurse dodijeljene komisiji člana
        $competitions = \App\Models\Competition::whereIn('id', $competitionIds->toArray())
            ->whereIn('status', ['published', 'closed', 'completed'])
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

        // Učitaj komisiju sa svim članovima
        $commission = $commissionMember->commission;
        $allMembers = $commission->members()
            ->where('status', 'active')
            ->orderByRaw("CASE WHEN position = 'predsjednik' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();

        // Učitaj sve postojeće ocjene za ovu prijavu
        $allScores = EvaluationScore::where('application_id', $application->id)
            ->whereIn('commission_member_id', $allMembers->pluck('id'))
            ->with('commissionMember')
            ->get()
            ->keyBy('commission_member_id');

        // Proveri da li je trenutni član već ocjenio
        $existingScore = $allScores->get($commissionMember->id);

        // Izračunaj prosječne ocjene za svaki kriterijum
        $averageScores = [];
        for ($i = 1; $i <= 10; $i++) {
            $scores = $allScores->pluck("criterion_{$i}")->filter()->values();
            if ($scores->count() > 0) {
                $averageScores[$i] = round($scores->sum() / $scores->count(), 2);
            } else {
                $averageScores[$i] = null;
            }
        }

        // Izračunaj konačnu ocjenu (zbir prosječnih ocjena)
        $finalScore = array_sum(array_filter($averageScores));

        // Provjeri da li su svi članovi komisije ocjenili prijavu
        $totalMembers = $commission->activeMembers()->count();
        $evaluatedMemberIds = EvaluationScore::where('application_id', $application->id)
            ->whereIn('commission_member_id', $commission->activeMembers()->pluck('id'))
            ->pluck('commission_member_id')
            ->unique()
            ->count();
        $allMembersEvaluated = $evaluatedMemberIds >= $totalMembers;

        $application->load(['user', 'competition', 'businessPlan']);

        return view('evaluation.create', compact(
            'application', 
            'commissionMember', 
            'existingScore',
            'allMembers',
            'allScores',
            'averageScores',
            'finalScore',
            'commission',
            'allMembersEvaluated',
            'evaluatedMemberIds',
            'totalMembers'
        ));
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

        // Provjeri da li su svi članovi komisije ocjenili prijavu
        $commission = $commissionMember->commission;
        $totalMembers = $commission->activeMembers()->count();
        $evaluatedMemberIds = EvaluationScore::where('application_id', $application->id)
            ->whereIn('commission_member_id', $commission->activeMembers()->pluck('id'))
            ->pluck('commission_member_id')
            ->unique()
            ->count();
        $allMembersEvaluated = $evaluatedMemberIds >= $totalMembers;
        
        // Osiguraj da samo predsjednik može slati zaključak, iznos odobrenih sredstava i documents_complete
        if ($commissionMember->position !== 'predsjednik') {
            // Ako nije predsjednik, ukloni te podatke iz requesta
            $request->merge([
                'commission_decision' => null,
                'approved_amount' => null,
                'decision_date' => null,
                'documents_complete' => null,
            ]);
        }

        // Validacija - provjera dokumentacije (samo za predsjednika)
        $rules = [];
        $messages = [];
        
        if ($commissionMember->position === 'predsjednik') {
            $rules['documents_complete'] = 'required|boolean';
            $messages['documents_complete.required'] = 'Morate odgovoriti da li su sva potrebna dokumenta dostavljena.';
        }

        // Validiraj samo documents_complete prvo (ako je predsjednik)
        if ($commissionMember->position === 'predsjednik' && !empty($rules)) {
            $request->validate($rules, $messages);
            
            // Ako dokumentacija nije kompletna, automatski odbiti prijavu
            if (!$request->boolean('documents_complete')) {
                $application->update([
                    'status' => 'rejected',
                    'rejection_reason' => 'Nedostaju potrebna dokumenta.',
                ]);
                
                return redirect()->route('evaluation.index', ['filter' => 'evaluated'])
                    ->with('error', 'Prijava je odbijena jer nisu dostavljena sva potrebna dokumenta.');
            }
        }

        // Validacija - svaki kriterijum 1-5 poena
        for ($i = 1; $i <= 10; $i++) {
            $rules["criterion_{$i}"] = 'required|integer|min:1|max:5';
            $messages["criterion_{$i}.required"] = "Kriterijum {$i} je obavezan.";
            $messages["criterion_{$i}.min"] = "Kriterijum {$i} mora biti najmanje 1 poen.";
            $messages["criterion_{$i}.max"] = "Kriterijum {$i} može biti najviše 5 poena.";
        }

        $rules['notes'] = 'nullable|string|max:5000';
        $rules['justification'] = 'nullable|string|max:5000';
        
        // Ako je predsjednik i svi članovi su ocjenili, može unijeti zaključak i iznos
        if ($commissionMember->position === 'predsjednik' && $allMembersEvaluated) {
            $rules['commission_decision'] = 'nullable|in:podrzava_potpuno,podrzava_djelimicno,odbija';
            $rules['approved_amount'] = 'nullable|numeric|min:0';
            $rules['decision_date'] = 'nullable|date';
        }

        $validated = $request->validate($rules, $messages);

        // Izračunaj zbir ocjena
        $totalScore = 0;
        for ($i = 1; $i <= 10; $i++) {
            $totalScore += $validated["criterion_{$i}"];
        }

        // Za ostale članove, koristi documents_complete od predsjednika
        $documentsCompleteValue = null;
        if ($commissionMember->position === 'predsjednik') {
            $documentsCompleteValue = $validated['documents_complete'] ?? null;
        } else {
            // Pronađi ocjenu predsjednika komisije
            $chairmanMember = $commission->activeMembers()->where('position', 'predsjednik')->first();
            if ($chairmanMember) {
                $chairmanScore = EvaluationScore::where('application_id', $application->id)
                    ->where('commission_member_id', $chairmanMember->id)
                    ->first();
                if ($chairmanScore) {
                    $documentsCompleteValue = $chairmanScore->documents_complete;
                }
            }
        }

        // Kreiraj ili ažuriraj ocjenu
        $evaluationScore = EvaluationScore::updateOrCreate(
            [
                'application_id' => $application->id,
                'commission_member_id' => $commissionMember->id,
            ],
            [
                'documents_complete' => $documentsCompleteValue,
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
                'justification' => $validated['justification'] ?? null,
            ]
        );

        // Ako je predsjednik i svi članovi su ocjenili, ažuriraj zaključak komisije i iznos odobrenih sredstava
        if ($commissionMember->position === 'predsjednik' && $allMembersEvaluated && isset($validated['commission_decision'])) {
            $application->update([
                'commission_decision' => $validated['commission_decision'],
                'approved_amount' => $validated['approved_amount'] ?? null,
                'commission_decision_date' => $validated['decision_date'] ?? now(),
            ]);
        }

        // Ažuriraj prosječnu ocjenu prijave (prosjek svih članova komisije)
        $this->updateApplicationScores($application);

        // Redirektuj sa filterom "evaluated" da se odmah vidi ocjenjena prijava
        return redirect()->route('evaluation.index', ['filter' => 'evaluated'])
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

        // Ako je ocjena ispod 30, automatski odbiti
        $status = 'evaluated';
        if ($finalScore < 30) {
            $status = 'rejected';
            $application->update([
                'rejection_reason' => 'Ukupna ocjena ispod 30 bodova (minimum za podršku).',
            ]);
        }

        // Ažuriraj prijavu
        $application->update([
            'final_score' => $finalScore,
            'status' => $status,
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
    public function show(Application $application): View|\Illuminate\Http\RedirectResponse
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

        // Učitaj komisiju sa svim članovima
        $commission = $commissionMember->commission;
        $allMembers = $commission->members()
            ->where('status', 'active')
            ->orderByRaw("CASE WHEN position = 'predsjednik' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();

        // Učitaj sve postojeće ocjene za ovu prijavu
        $allScores = EvaluationScore::where('application_id', $application->id)
            ->whereIn('commission_member_id', $allMembers->pluck('id'))
            ->with('commissionMember')
            ->get()
            ->keyBy('commission_member_id');

        // Izračunaj prosječne ocjene za svaki kriterijum
        $averageScores = [];
        for ($i = 1; $i <= 10; $i++) {
            $scores = $allScores->pluck("criterion_{$i}")->filter()->values();
            if ($scores->count() > 0) {
                $averageScores[$i] = round($scores->sum() / $scores->count(), 2);
            } else {
                $averageScores[$i] = null;
            }
        }

        // Izračunaj konačnu ocjenu (zbir prosječnih ocjena)
        $finalScore = array_sum(array_filter($averageScores));

        $application->load(['user', 'competition', 'businessPlan']);

        return view('evaluation.show', compact(
            'application', 
            'commissionMember', 
            'evaluationScore',
            'allMembers',
            'allScores',
            'averageScores',
            'finalScore',
            'commission'
        ));
    }

    /**
     * Pregled svih ocjena za predsjednika komisije
     */
    public function chairmanReview(Application $application): View
    {
        $user = Auth::user();
        
        // Pronađi člana komisije
        $commissionMember = CommissionMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$commissionMember) {
            abort(403, 'Niste član komisije.');
        }

        // Proveri da li je predsjednik
        if ($commissionMember->position !== 'predsjednik') {
            abort(403, 'Samo predsjednik komisije može donijeti zaključak.');
        }

        $application->load(['user', 'competition', 'businessPlan', 'evaluationScores.commissionMember']);
        
        // Učitaj sve ocjene
        $allScores = EvaluationScore::where('application_id', $application->id)
            ->with('commissionMember')
            ->get();

        // Izračunaj prosjeke po kriterijumima
        $criterionAverages = [];
        for ($i = 1; $i <= 10; $i++) {
            $scores = $allScores->pluck("criterion_{$i}")->filter()->toArray();
            if (!empty($scores)) {
                $criterionAverages[$i] = round(array_sum($scores) / count($scores), 2);
            } else {
                $criterionAverages[$i] = 0;
            }
        }

        return view('evaluation.chairman-review', compact('application', 'commissionMember', 'allScores', 'criterionAverages'));
    }

    /**
     * Snimanje zaključka komisije od strane predsjednika
     */
    public function storeDecision(Request $request, Application $application): RedirectResponse
    {
        $user = Auth::user();
        
        // Pronađi člana komisije
        $commissionMember = CommissionMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$commissionMember) {
            abort(403, 'Niste član komisije.');
        }

        // Proveri da li je predsjednik
        if ($commissionMember->position !== 'predsjednik') {
            abort(403, 'Samo predsjednik komisije može donijeti zaključak.');
        }

        $validated = $request->validate([
            'commission_decision' => 'required|in:podrzava_potpuno,podrzava_djelimicno,odbija',
            'commission_justification' => 'required|string|max:5000',
            'commission_notes' => 'nullable|string|max:5000',
            'approved_amount' => 'nullable|numeric|min:0',
        ], [
            'commission_decision.required' => 'Morate odabrati zaključak komisije.',
            'commission_justification.required' => 'Obrazloženje je obavezno.',
        ]);

        // Ažuriraj prijavu sa zaključkom
        $application->update([
            'commission_decision' => $validated['commission_decision'],
            'commission_justification' => $validated['commission_justification'],
            'commission_notes' => $validated['commission_notes'] ?? null,
            'approved_amount' => $validated['approved_amount'] ?? null,
            'commission_decision_date' => now(),
            'signed_by_chairman' => true,
        ]);

        // Ažuriraj status prijave na osnovu zaključka
        if ($validated['commission_decision'] === 'odbija') {
            $application->update(['status' => 'rejected']);
        } elseif ($validated['commission_decision'] === 'podrzava_potpuno' || $validated['commission_decision'] === 'podrzava_djelimicno') {
            $application->update(['status' => 'approved']);
        }

        return redirect()->route('evaluation.chairman-review', $application)
            ->with('success', 'Zaključak komisije je uspješno sačuvan.');
    }

    /**
     * Potpisivanje odluke od strane člana komisije
     */
    public function signDecision(Application $application): RedirectResponse
    {
        $user = Auth::user();
        
        // Pronađi člana komisije
        $commissionMember = CommissionMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$commissionMember) {
            abort(403, 'Niste član komisije.');
        }

        // Proveri da li je predsjednik već potpisao
        if (!$application->signed_by_chairman) {
            return back()->with('error', 'Predsjednik komisije mora prvo donijeti zaključak.');
        }

        // Dodaj člana u listu potpisanih
        $signedMembers = $application->signed_by_members ?? [];
        if (!in_array($commissionMember->id, $signedMembers)) {
            $signedMembers[] = $commissionMember->id;
            $application->update(['signed_by_members' => $signedMembers]);
        }

        return back()->with('success', 'Odluka je uspješno potpisana.');
    }
}
