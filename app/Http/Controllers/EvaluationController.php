<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\EvaluationScore;
use App\Models\CommissionMember;
use App\Models\Commission;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
    public function create(Application $application): View|RedirectResponse
    {
        $user = Auth::user();
        
        // Pronađi člana komisije
        $commissionMember = CommissionMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$commissionMember) {
            abort(403, 'Niste član komisije.');
        }

        // Provjeri da li je prijava već odbijena zbog nedostajućih dokumenata
        if ($application->status === 'rejected' && $application->rejection_reason === 'Nedostaju potrebna dokumenta.') {
            abort(403, 'Prijava je već odbijena zbog nedostajućih dokumenata.');
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
        
        // Provjeri da li je trenutni član završio ocjenjivanje (ima sve kriterijume popunjene)
        $hasCompletedEvaluation = $existingScore && $existingScore->criterion_1 !== null;
        
        // Provjeri da li su svi članovi komisije ocjenili prijavu
        $totalMembers = $commission->activeMembers()->count();
        $evaluatedMemberIds = EvaluationScore::where('application_id', $application->id)
            ->whereIn('commission_member_id', $commission->activeMembers()->pluck('id'))
            ->pluck('commission_member_id')
            ->unique()
            ->count();
        $allMembersEvaluated = $evaluatedMemberIds >= $totalMembers;
        
        // Provjeri da li je predsjednik zaključio prijavu
        $isDecisionMade = $application->commission_decision !== null;
        
        // Ako je već ocjenio, zabrani izmjenu - OSIM ako je predsjednik (predsjednik može pristupiti bilo kada)
        // ILI ako su svi članovi ocjenili (tada svi članovi mogu vidjeti formu u read-only modu)
        // ILI ako nije zaključena prijava (tada članovi mogu mijenjati napomene)
        $isChairman = $commissionMember->position === 'predsjednik';
        
        if ($hasCompletedEvaluation && !$isChairman && !$allMembersEvaluated && $isDecisionMade) {
            return redirect()->route('evaluation.index', ['filter' => 'evaluated'])
                ->with('error', 'Već ste ocjenili ovu prijavu. Ocjene se ne mogu mijenjati.');
        }

        // Izračunaj prosječne ocjene za svaki kriterijum (samo za članove koji su završili ocjenjivanje)
        $averageScores = [];
        for ($i = 1; $i <= 10; $i++) {
            // Uzmi samo ocjene članova koji su završili ocjenjivanje (imaju sve kriterijume popunjene)
            $scores = $allScores->filter(function($score) {
                return $score->criterion_1 !== null; // Ako ima criterion_1, znači da je završio ocjenjivanje
            })->pluck("criterion_{$i}")->filter()->values();
            
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
            'totalMembers',
            'hasCompletedEvaluation',
            'isChairman'
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

        // Provjeri da li je trenutni član završio ocjenjivanje (ima sve kriterijume popunjene)
        $existingScore = EvaluationScore::where('application_id', $application->id)
            ->where('commission_member_id', $commissionMember->id)
            ->first();
        
        $hasCompletedEvaluation = $existingScore && $existingScore->criterion_1 !== null;
        
        // Provjeri da li je prijava zaključena
        $isDecisionMade = $application->commission_decision !== null;
        $isChairman = $commissionMember->position === 'predsjednik';
        
        // Ako je već ocjenio, zabrani izmjenu - OSIM ako je predsjednik (može mijenjati sekciju 2)
        // ILI ako nije zaključena prijava (tada članovi mogu mijenjati napomene)
        if ($hasCompletedEvaluation && !$isChairman && $isDecisionMade) {
            return redirect()->route('evaluation.index', ['filter' => 'evaluated'])
                ->with('error', 'Već ste ocjenili ovu prijavu. Ocjene se ne mogu mijenjati.');
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
            
            // Ako dokumentacija nije kompletna, automatski odbiti prijavu i ne dozvoli dalje ocjenjivanje
            if (!$request->boolean('documents_complete')) {
                // Sačuvaj ocjenu predsjednika samo sa documents_complete = false
                EvaluationScore::updateOrCreate(
                    [
                        'application_id' => $application->id,
                        'commission_member_id' => $commissionMember->id,
                    ],
                    [
                        'documents_complete' => false,
                        'criterion_1' => null,
                        'criterion_2' => null,
                        'criterion_3' => null,
                        'criterion_4' => null,
                        'criterion_5' => null,
                        'criterion_6' => null,
                        'criterion_7' => null,
                        'criterion_8' => null,
                        'criterion_9' => null,
                        'criterion_10' => null,
                        'final_score' => 0,
                        'notes' => null,
                        'justification' => null,
                    ]
                );
                
                $application->update([
                    'status' => 'rejected',
                    'rejection_reason' => 'Nedostaju potrebna dokumenta.',
                ]);
                
                return redirect()->route('evaluation.index', ['filter' => 'evaluated'])
                    ->with('error', 'Prijava je odbijena jer nisu dostavljena sva potrebna dokumenta.');
            }
        }

        // Validacija - svaki kriterijum 1-5 poena (samo ako nije predsjednik koji mijenja samo sekciju 2)
        $isChairman = $commissionMember->position === 'predsjednik';
        $totalMembers = $commission->activeMembers()->count();
        $evaluatedMemberIds = EvaluationScore::where('application_id', $application->id)
            ->whereIn('commission_member_id', $commission->activeMembers()->pluck('id'))
            ->pluck('commission_member_id')
            ->unique()
            ->count();
        $allMembersEvaluated = $evaluatedMemberIds >= $totalMembers;
        
        // Ako je predsjednik i već je ocjenio, ne validiraj kriterijume (može mijenjati samo sekciju 2)
        // Takođe, ako član već ocjenio i prijava nije zaključena, ne validiraj kriterijume (može mijenjati samo napomene)
        $isDecisionMade = $application->commission_decision !== null;
        
        if (!($isChairman && $hasCompletedEvaluation) && !($hasCompletedEvaluation && !$isDecisionMade && !$isChairman)) {
            for ($i = 1; $i <= 10; $i++) {
                $rules["criterion_{$i}"] = 'required|integer|min:1|max:5';
                $messages["criterion_{$i}.required"] = "Kriterijum {$i} je obavezan.";
                $messages["criterion_{$i}.min"] = "Kriterijum {$i} mora biti najmanje 1 poen.";
                $messages["criterion_{$i}.max"] = "Kriterijum {$i} može biti najviše 5 poena.";
            }
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

        // Izračunaj zbir ocjena (samo ako nije predsjednik koji mijenja samo sekciju 2 ili član koji mijenja samo napomene)
        $totalScore = 0;
        if (!($isChairman && $hasCompletedEvaluation) && !($hasCompletedEvaluation && !$isDecisionMade && !$isChairman)) {
            for ($i = 1; $i <= 10; $i++) {
                $totalScore += $validated["criterion_{$i}"] ?? 0;
            }
        } else {
            // Ako je predsjednik ili član koji već ocjenio, koristi postojeću ocjenu
            $existingScore = EvaluationScore::where('application_id', $application->id)
                ->where('commission_member_id', $commissionMember->id)
                ->first();
            if ($existingScore) {
                $totalScore = $existingScore->final_score ?? 0;
            }
        }

        // Provjeri da li član već ocjenio i da li je prijava zaključena
        // Koristimo postojeći $existingScore umjesto ponovnog traženja
        $isDecisionMadeForUpdate = $application->commission_decision !== null;
        
        if ($existingScore && $hasCompletedEvaluation && !$isDecisionMadeForUpdate && !$isChairman) {
            // Ako je već ocjenio ali prijava nije zaključena i nije predsjednik, može ažurirati samo notes
            // Provjeri da li je notes poslan u request-u (čak i ako je null)
            // Ako jeste poslan (čak i kao null), koristimo tu vrijednost (konvertujemo null u prazan string)
            // Ako nije poslan, koristimo postojeću vrijednost
            $notesValue = $existingScore->notes; // Default: postojeća vrijednost
            
            if ($request->has('notes')) {
                // Notes je poslan u request-u (može biti null ili prazan string)
                $inputNotes = $request->input('notes');
                $notesValue = $inputNotes !== null ? $inputNotes : '';
            }
            
            $existingScore->update([
                'notes' => $notesValue,
            ]);
            
            return redirect()->route('evaluation.index', ['filter' => 'evaluated'])
                ->with('success', 'Napomene su uspješno ažurirane.');
        }

        // Kreiraj ili ažuriraj ocjenu
        if ($isChairman && $allMembersEvaluated) {
            // Ako je predsjednik i svi su ocjenili, ažuriraj samo documents_complete
            $existingScore = EvaluationScore::where('application_id', $application->id)
                ->where('commission_member_id', $commissionMember->id)
                ->first();
            
            if ($existingScore) {
                $existingScore->update([
                    'documents_complete' => $validated['documents_complete'] ?? $existingScore->documents_complete,
                ]);
            }
        } else {
            // Normalno spremanje ocjene
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

            $evaluationScore = EvaluationScore::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'commission_member_id' => $commissionMember->id,
                ],
                [
                    'documents_complete' => $documentsCompleteValue,
                    'criterion_1' => $validated['criterion_1'] ?? $existingScore->criterion_1 ?? null,
                    'criterion_2' => $validated['criterion_2'] ?? $existingScore->criterion_2 ?? null,
                    'criterion_3' => $validated['criterion_3'] ?? $existingScore->criterion_3 ?? null,
                    'criterion_4' => $validated['criterion_4'] ?? $existingScore->criterion_4 ?? null,
                    'criterion_5' => $validated['criterion_5'] ?? $existingScore->criterion_5 ?? null,
                    'criterion_6' => $validated['criterion_6'] ?? $existingScore->criterion_6 ?? null,
                    'criterion_7' => $validated['criterion_7'] ?? $existingScore->criterion_7 ?? null,
                    'criterion_8' => $validated['criterion_8'] ?? $existingScore->criterion_8 ?? null,
                    'criterion_9' => $validated['criterion_9'] ?? $existingScore->criterion_9 ?? null,
                    'criterion_10' => $validated['criterion_10'] ?? $existingScore->criterion_10 ?? null,
                    'final_score' => $totalScore,
                    'notes' => $validated['notes'] ?? null,
                    'justification' => $validated['justification'] ?? null,
                ]
            );
        }

        // Kreiraj ili ažuriraj ocjenu
        if ($isChairman && $hasCompletedEvaluation) {
            // Ako je predsjednik i već je ocjenio, ažuriraj samo documents_complete i notes (dok ne zaključi prijavu)
            $existingScore = EvaluationScore::where('application_id', $application->id)
                ->where('commission_member_id', $commissionMember->id)
                ->first();
            
            // Provjeri da li je predsjednik zaključio prijavu
            $isDecisionMade = $application->commission_decision !== null;
            
            if ($existingScore && !$isDecisionMade) {
                // Ako nije zaključio prijavu, može mijenjati documents_complete i notes
                // Provjeri da li je notes poslan u request-u (čak i ako je prazan string)
                // Koristimo $request->input() direktno jer $validated možda ne uključuje prazan string
                $notesValue = $request->has('notes') ? ($request->input('notes') ?? '') : $existingScore->notes;
                $existingScore->update([
                    'documents_complete' => $validated['documents_complete'] ?? $existingScore->documents_complete,
                    'notes' => $notesValue,
                ]);
            }
            
            // Redirectuj na listu sa porukom
            return redirect()->route('evaluation.index', ['filter' => 'evaluated'])
                ->with('success', 'Izmjene su uspješno sačuvane.');
        } else {
            // Normalno spremanje ocjene
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

            // Provjeri da li član već ocjenio i da li je prijava zaključena
            // Koristimo postojeći $existingScore umjesto ponovnog traženja
            $isDecisionMadeForUpdate = $application->commission_decision !== null;
            
            // Provjeri da li postoji postojeća ocjena za ovog člana
            $existingScoreForNotes = EvaluationScore::where('application_id', $application->id)
                ->where('commission_member_id', $commissionMember->id)
                ->first();
            
            if ($existingScoreForNotes && $hasCompletedEvaluation && !$isDecisionMadeForUpdate && !$isChairman) {
                // Ako je već ocjenio ali prijava nije zaključena i nije predsjednik, može ažurirati samo notes
                // Provjeri da li je notes poslan u request-u (čak i ako je null)
                // Ako jeste poslan (čak i kao null), koristimo tu vrijednost (konvertujemo null u prazan string)
                // Ako nije poslan, koristimo postojeću vrijednost
                $notesValue = $existingScoreForNotes->notes; // Default: postojeća vrijednost
                
                if ($request->has('notes')) {
                    // Notes je poslan u request-u (može biti null ili prazan string)
                    $inputNotes = $request->input('notes');
                    $notesValue = $inputNotes !== null ? $inputNotes : '';
                }
                
                $existingScoreForNotes->update([
                    'notes' => $notesValue,
                ]);
                
                return redirect()->route('evaluation.index', ['filter' => 'evaluated'])
                    ->with('success', 'Napomene su uspješno ažurirane.');
            }

            $evaluationScore = EvaluationScore::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'commission_member_id' => $commissionMember->id,
                ],
                [
                    'documents_complete' => $documentsCompleteValue,
                    'criterion_1' => $validated['criterion_1'] ?? $existingScoreForNotes->criterion_1 ?? null,
                    'criterion_2' => $validated['criterion_2'] ?? $existingScoreForNotes->criterion_2 ?? null,
                    'criterion_3' => $validated['criterion_3'] ?? $existingScoreForNotes->criterion_3 ?? null,
                    'criterion_4' => $validated['criterion_4'] ?? $existingScoreForNotes->criterion_4 ?? null,
                    'criterion_5' => $validated['criterion_5'] ?? $existingScoreForNotes->criterion_5 ?? null,
                    'criterion_6' => $validated['criterion_6'] ?? $existingScoreForNotes->criterion_6 ?? null,
                    'criterion_7' => $validated['criterion_7'] ?? $existingScoreForNotes->criterion_7 ?? null,
                    'criterion_8' => $validated['criterion_8'] ?? $existingScoreForNotes->criterion_8 ?? null,
                    'criterion_9' => $validated['criterion_9'] ?? $existingScoreForNotes->criterion_9 ?? null,
                    'criterion_10' => $validated['criterion_10'] ?? $existingScoreForNotes->criterion_10 ?? null,
                    'final_score' => $totalScore,
                    'notes' => $validated['notes'] ?? null,
                    'justification' => $validated['justification'] ?? null,
                ]
            );
            
            // Ažuriraj prosječnu ocjenu prijave (prosjek svih članova komisije)
            $this->updateApplicationScores($application);

            // Ako je predsjednik i svi članovi su ocjenili, može ažurirati zaključak komisije i iznos odobrenih sredstava
            if ($commissionMember->position === 'predsjednik' && $allMembersEvaluated && isset($validated['commission_decision'])) {
                $application->update([
                    'commission_decision' => $validated['commission_decision'],
                    'approved_amount' => $validated['approved_amount'] ?? null,
                    'commission_decision_date' => $validated['decision_date'] ?? now(),
                ]);
            }

            // Redirektuj sa filterom "evaluated" da se odmah vidi ocjenjena prijava
            return redirect()->route('evaluation.index', ['filter' => 'evaluated'])
                ->with('success', 'Ocjena je uspješno sačuvana.');
        }
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
