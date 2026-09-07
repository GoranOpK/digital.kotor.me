<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\EvaluationScore;
use App\Models\CommissionMember;
use App\Models\Commission;
use App\Services\ApplicationEliminatoryCheckService;
use App\Services\ApplicationPrigovorService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function __construct(
        protected ApplicationEliminatoryCheckService $eliminatoryChecks,
        protected ApplicationPrigovorService $prigovors,
    ) {}

    /**
     * Aktivan član komisije za konkurs na koji se odnosi prijava.
     */
    protected function commissionMemberForApplication(Application $application, int $userId): ?CommissionMember
    {
        $application->loadMissing('competition');
        $commissionId = $application->competition?->commission_id;

        if (!$commissionId) {
            return null;
        }

        return CommissionMember::activeForCommission($userId, $commissionId);
    }

    protected function abortIfCommissionProcessingBlocked(?\App\Models\Competition $competition): void
    {
        if ($competition && $competition->isCommissionProcessingBlocked()) {
            abort(403, \App\Models\Competition::COMMISSION_PROCESSING_BLOCKED_MESSAGE);
        }
    }

    /**
     * Lista prijava za ocjenjivanje
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        // Pronađi člana komisije za trenutnog korisnika
        $commissionMember = CommissionMember::activeMembershipForUser($user->id);

        if (!$commissionMember) {
            abort(403, 'Niste član komisije.');
        }

        // Učitaj komisiju sa njenim konkursima
        $commission = $commissionMember->commission;
        $commission->load('competitions');

        // Prijave su komisiji vidljive i na ocjenjivanje tek nakon isteka roka za prijavljivanje (20 dana)
        $competitionIds = $commission->competitions->filter(function ($c) {
            if (! in_array($c->status, ['closed', 'completed']) && ! $c->isApplicationDeadlinePassed()) {
                return false;
            }

            return ! $c->isCommissionProcessingBlocked();
        })->pluck('id');
        
        // Prijave koje treba ocjeniti (submitted, evaluated ili rejected status)
        // Statusi se određuju na osnovu filtera
        $query = Application::with(['user', 'competition']);
        
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
            ->whereNotNull('criterion_1')
            ->pluck('application_id')
            ->toArray();

        // Filtriranje po statusu ocjenjivanja
        if ($request->filled('filter')) {
            if ($request->filter === 'pending') {
                $query->whereIn('status', ['submitted', 'evaluated']);
                $query->whereEliminatoryScoringNotBlocked();
                if (!empty($evaluatedApplicationIds)) {
                    $query->whereNotIn('id', $evaluatedApplicationIds);
                }
            } elseif ($request->filter === 'evaluated') {
                $query->where(function ($outer) use ($evaluatedApplicationIds) {
                    $outer->where(function ($q) use ($evaluatedApplicationIds) {
                        $q->whereIn('status', ['submitted', 'evaluated', 'rejected']);
                        if (!empty($evaluatedApplicationIds)) {
                            $q->whereIn('id', $evaluatedApplicationIds);
                        } else {
                            $q->whereRaw('1 = 0');
                        }
                    });
                    $outer->orWhere(function ($q) {
                        $q->whereEliminatoryScoringBlocked();
                    });
                });
            } elseif ($request->filter === 'rejected') {
                // Odbijene prijave
                $query->where('status', 'rejected');
            }
        } else {
            // Ako nema filtera, prikaži sve prijave (submitted, evaluated i rejected)
            $query->whereIn('status', ['submitted', 'evaluated', 'rejected']);
        }

        $applications = $query->latest()->paginate(20)->appends($request->query());
        
        // Filtriranje konkursa samo za konkurse dodijeljene komisiji člana
        $competitions = \App\Models\Competition::whereIn('id', $competitionIds->toArray())
            ->whereIn('status', ['draft', 'published', 'closed', 'completed'])
            ->get();

        // Link na rang listu na ekranu za ocjenjivanje:
        // prikaži samo za konkurse koji imaju formiranu rang listu (isRankingFormed)
        // i koji nijesu arhivirani (status nije 'closed' ili 'completed')
        $competitionsWithAllEvaluated = $competitions
            ->filter(fn ($c) => $c->isRankingFormed() && !in_array($c->status, ['closed', 'completed']))
            ->values();

        // Kontrola vidljivosti konačne ocjene u listi prijava:
        // konačna ocjena je vidljiva tek kada je rang lista formirana za čitav konkurs
        $canViewFinalScoresByCompetition = $competitions
            ->mapWithKeys(fn ($c) => [$c->id => $c->isRankingFormed()])
            ->toArray();

        $isChairman = $commissionMember->position === 'predsjednik';

        return view('evaluation.index', compact(
            'applications',
            'competitions',
            'commissionMember',
            'competitionsWithAllEvaluated',
            'canViewFinalScoresByCompetition',
            'isChairman'
        ));
    }

    /**
     * Forma za ocjenjivanje prijave
     */
    public function create(Application $application): View|RedirectResponse
    {
        $user = Auth::user();
        
        // Provjeri da li je korisnik podnosilac prijave
        $isApplicant = $application->user_id === $user->id;
        
        // Pronađi člana komisije
        $commissionMember = $this->commissionMemberForApplication($application, $user->id);

        // Ako nije član komisije, provjeri da li je podnosilac prijave i da li je prijava odbijena
        if (!$commissionMember) {
            if ($isApplicant && $application->status === 'rejected') {
                // Podnosilac prijave može pristupiti formi samo ako je prijava odbijena (read-only)
                $commissionMember = null; // Postavimo na null da znamo da nije član komisije
            } else {
                abort(403, 'Niste član komisije.');
            }
        } else {
            // Članovi komisije mogu vidjeti samo prijave koje su podnesene (status 'submitted' ili viši)
            // Ne mogu vidjeti draft prijave
            if ($application->status === 'draft') {
                abort(403, 'Prijava još nije podnesena. Članovi komisije mogu vidjeti prijavu tek nakon što korisnik klikne na "Podnesi prijavu".');
            }
            
            $competition = $application->competition;
            
            // Ocjenjivanje počinje tek kada istekne rok od 20 dana za prijave
            if ($competition && !$competition->isApplicationDeadlinePassed() && !in_array($competition->status, ['closed', 'completed'])) {
                abort(403, 'Ocjenjivanje počinje tek kada istekne rok od 20 dana za prijave na konkurs. Nakon toga počinje rok od 45 dana za donošenje odluke od strane komisije.');
            }

            $this->abortIfCommissionProcessingBlocked($competition);
            
            // Provjeri da li je prošao rok od 45 dana za ocjenjivanje
            if ($competition && $competition->isEvaluationDeadlinePassed()) {
                abort(403, 'Rok za ocjenjivanje je istekao. Komisija je dužna donijeti odluku u roku od 45 dana od dana zatvaranja prijava na konkurs.');
            }
        }

        // Provjeri da li je prijava već odbijena
        // Svi članovi komisije mogu pristupiti odbijenim prijavama, ali forma će biti read-only (provjera se vrši u view-u)
        // Podnosilac prijave takođe može pristupiti odbijenim prijavama u read-only modu

        // Učitaj komisiju sa svim članovima
        // Ako je podnosilac prijave, učitaj komisiju preko konkursa
        if ($commissionMember) {
            $commission = $commissionMember->commission;
        } else {
            // Podnosilac prijave - učitaj komisiju preko konkursa
            $commission = $application->competition->commission;
        }
        
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
        $existingScore = $commissionMember ? $allScores->get($commissionMember->id) : null;
        // Rezerva: direktan upit ako nije u allScores (npr. druga komisija)
        if (!$existingScore && $commissionMember) {
            $existingScore = EvaluationScore::where('application_id', $application->id)
                ->where('commission_member_id', $commissionMember->id)
                ->first();
            if ($existingScore) {
                $allScores->put($commissionMember->id, $existingScore);
            }
        }
        
        // Član je završio ocjenjivanje kada su uneseni svi kriterijumi (ne samo prazan red nakon odbijanja zbog dokumentacije)
        $hasCompletedEvaluation = $existingScore && $existingScore->criterion_1 !== null;

        // Provjeri da li su svi članovi komisije ocjenili prijavu (samo zapisi sa popunjenim kriterijumima)
        $totalMembers = $commission->activeMembers()->count();
        $evaluatedMemberIds = EvaluationScore::where('application_id', $application->id)
            ->whereIn('commission_member_id', $commission->activeMembers()->pluck('id'))
            ->whereNotNull('criterion_1')
            ->pluck('commission_member_id')
            ->unique()
            ->count();
        $allMembersEvaluated = $evaluatedMemberIds >= $totalMembers;
        
        // Provjeri da li je predsjednik zaključio prijavu
        $isDecisionMade = $application->commission_decision !== null;
        
        // Ako je već ocjenio, zabrani izmjenu - OSIM ako je predsjednik (predsjednik može pristupiti bilo kada)
        // ILI ako su svi članovi ocjenili (tada svi članovi mogu vidjeti formu u read-only modu)
        // ILI ako nije zaključena prijava (tada članovi mogu mijenjati napomene)
        // Podnosilac prijave uvijek vidi formu u read-only modu
        $isChairman = $commissionMember && $commissionMember->position === 'predsjednik';
        
        if ($commissionMember && $hasCompletedEvaluation && !$isChairman && !$allMembersEvaluated && $isDecisionMade) {
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

        // Izračunaj konačnu ocjenu (zbir prosječnih ocjena, BEZ dodatnih bodova;
        // dodatni bodovi se vizuelno dodaju u tabeli preko getBonusScore())
        $finalScore = array_sum(array_filter($averageScores));

        // Ocjene ostalih članova i zbirne ocjene (prosjeci, konačna) vidljive su tek kada su
        // SVE prijave na ovom konkursu ocijenjene od SVIH članova - logika je centralizovana
        // u Competition::isRankingFormed(). Oslanjamo se direktno na taj metod.
        $competition = $application->competition;
        $canViewOtherMembersScores = $competition ? $competition->isRankingFormed() : false;
        if ($this->eliminatoryChecks->isConfirmedFail($application)) {
            $canViewOtherMembersScores = true;
        }

        $application->load(['user', 'competition', 'businessPlan', 'documents', 'eliminatoryCheck', 'eliminatoryNotice', 'prigovor']);

        $eliminatoryCheck = $application->eliminatoryCheck;
        $scoringIsAllowed = $this->eliminatoryChecks->scoringIsAllowed($application);
        $eliminatoryIsConfirmedFail = $this->eliminatoryChecks->isConfirmedFail($application);
        $eliminatoryNotice = $application->eliminatoryNotice;
        $prigovor = $application->prigovor;
        $canDecidePrigovor = $commissionMember
            && $commissionMember->position === 'predsjednik'
            && $commissionMember->status === 'active'
            && $prigovor?->isPodnesen();

        // Provjeri da li je korisnik podnosilac prijave
        $isApplicant = $application->user_id === $user->id;

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
            'canViewOtherMembersScores',
            'evaluatedMemberIds',
            'totalMembers',
            'hasCompletedEvaluation',
            'isChairman',
            'isApplicant',
            'eliminatoryCheck',
            'scoringIsAllowed',
            'eliminatoryIsConfirmedFail',
            'eliminatoryNotice',
            'prigovor',
            'canDecidePrigovor',
        ));
    }

    /**
     * Snimanje ocjene
     */
    public function store(Request $request, Application $application): RedirectResponse
    {
        $user = Auth::user();
        
        $competition = $application->competition;
        
        // Ocjenjivanje počinje tek kada istekne rok od 20 dana za prijave
        if ($competition && !$competition->isApplicationDeadlinePassed() && !in_array($competition->status, ['closed', 'completed'])) {
            return redirect()->back()
                ->withErrors(['error' => 'Ocjenjivanje počinje tek kada istekne rok od 20 dana za prijave na konkurs. Nakon toga počinje rok od 45 dana za donošenje odluke od strane komisije.']);
        }

        $this->abortIfCommissionProcessingBlocked($competition);
        
        // Provjeri da li je prošao rok od 45 dana za ocjenjivanje
        if ($competition && $competition->isEvaluationDeadlinePassed()) {
            return redirect()->back()
                ->withErrors(['error' => 'Rok za ocjenjivanje je istekao. Komisija je dužna donijeti odluku u roku od 45 dana od dana zatvaranja prijava na konkurs.']);
        }
        
        // Pronađi člana komisije
        $commissionMember = $this->commissionMemberForApplication($application, $user->id);

        if (!$commissionMember) {
            abort(403, 'Niste član komisije.');
        }

        if (! $this->eliminatoryChecks->scoringIsAllowed($application)) {
            abort(403, $this->eliminatoryChecks->isConfirmedFail($application)
                ? ApplicationEliminatoryCheckService::CONFIRMED_FAIL_SCORING_MESSAGE
                : ApplicationEliminatoryCheckService::SCORING_LOCKED_MESSAGE);
        }

        // Provjeri da li je prijava već odbijena - ako jeste, ne dozvoli izmjene
        if ($application->status === 'rejected') {
            return redirect()->route('evaluation.index', ['filter' => 'rejected'])
                ->with('error', 'Prijava je već odbijena i ne može se editovati.');
        }

        $isChairman = $commissionMember->position === 'predsjednik';

        // Provjeri da li je trenutni član završio ocjenjivanje (ima sve kriterijume popunjene)
        $existingScore = EvaluationScore::where('application_id', $application->id)
            ->where('commission_member_id', $commissionMember->id)
            ->first();
        
        $hasCompletedEvaluation = $existingScore && $existingScore->criterion_1 !== null;
        
        // Provjeri da li je prijava zaključena
        $isDecisionMade = $application->commission_decision !== null;
        
        // Provjeri da li su svi članovi komisije ocjenili prijavu
        $commission = $commissionMember->commission;
        $totalMembers = $commission->activeMembers()->count();
        $evaluatedMemberIds = EvaluationScore::where('application_id', $application->id)
            ->whereIn('commission_member_id', $commission->activeMembers()->pluck('id'))
            ->whereNotNull('criterion_1')
            ->pluck('commission_member_id')
            ->unique()
            ->count();
        $allMembersEvaluated = $evaluatedMemberIds >= $totalMembers;
        
        // Osiguraj da samo predsjednik može slati zaključak, iznos odobrenih sredstava i bonus kriterijume
        if ($commissionMember->position !== 'predsjednik') {
            $request->merge([
                'commission_decision' => null,
                'approved_amount' => null,
                'decision_date' => null,
                'bonus_info_day' => null,
                'bonus_new_business' => null,
                'bonus_zavod_nezaposleni' => null,
                'bonus_green_innovative' => null,
            ]);
        }

        // Validacija - svaki kriterijum 1-5 poena (samo ako nije predsjednik koji mijenja samo sekciju 2)
        // Ako je predsjednik i već je ocjenio, ne validiraj kriterijume (može mijenjati samo sekciju 2)
        // Takođe, ako član već ocjenio i prijava nije zaključena, ne validiraj kriterijume (može mijenjati samo napomene)
        $rules = [];
        $messages = [];

        if (!($isChairman && $hasCompletedEvaluation) && !($hasCompletedEvaluation && !$isDecisionMade && !$isChairman)) {
            for ($i = 1; $i <= 10; $i++) {
                $rules["criterion_{$i}"] = 'required|integer|min:1|max:5';
                $messages["criterion_{$i}.required"] = "Kriterijum {$i} je obavezan.";
                $messages["criterion_{$i}.min"] = "Kriterijum {$i} mora biti najmanje 1 poen.";
                $messages["criterion_{$i}.max"] = "Kriterijum {$i} može biti najviše 5 poena.";
            }
        }

        $rules['notes'] = 'nullable|string|max:5000';
        // Obrazloženje unosi samo predsjednik komisije
        if ($commissionMember->position === 'predsjednik') {
            $rules['justification'] = 'nullable|string|max:5000';
        } else {
            // Za ostale članove zanemari eventualno poslato obrazloženje
            $request->merge([
                'justification' => null,
            ]);
        }
        
        // Ako je predsjednik i svi članovi su ocjenili, može unijeti zaključak i iznos
        if ($commissionMember->position === 'predsjednik' && $allMembersEvaluated) {
            $rules['commission_decision'] = 'nullable|in:podrzava_potpuno,odbija';
            $rules['approved_amount'] = 'nullable|numeric|min:0';
            $rules['decision_date'] = 'nullable|date';
        }

        if (!empty($rules)) {
            try {
                $validated = $request->validate($rules, $messages);
            } catch (\Illuminate\Validation\ValidationException $e) {
                // Ako validacija ne prođe, vrati grešku
                return redirect()->back()
                    ->withErrors($e->errors())
                    ->withInput();
            }
        } else {
            $validated = $request->all();
        }

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

        // Sačuvaj dodatne kriterijume (bonus bodovi) – označava ih samo predsjednik
        if ($commissionMember->position === 'predsjednik') {
            $application->bonus_info_day = $request->boolean('bonus_info_day');
            $application->bonus_new_business = $request->boolean('bonus_new_business');
            $application->bonus_zavod_nezaposleni = $request->boolean('bonus_zavod_nezaposleni');
            $application->bonus_green_innovative = $request->boolean('bonus_green_innovative');
            $application->save();
        }

        // Kreiraj ili ažuriraj ocjenu
        if ($isChairman && $allMembersEvaluated) {
            $existingScore = EvaluationScore::where('application_id', $application->id)
                ->where('commission_member_id', $commissionMember->id)
                ->first();
        } else {
            $evaluationScore = EvaluationScore::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'commission_member_id' => $commissionMember->id,
                ],
                [
                    'criterion_1' => $validated['criterion_1'] ?? $existingScore?->criterion_1 ?? null,
                    'criterion_2' => $validated['criterion_2'] ?? $existingScore?->criterion_2 ?? null,
                    'criterion_3' => $validated['criterion_3'] ?? $existingScore?->criterion_3 ?? null,
                    'criterion_4' => $validated['criterion_4'] ?? $existingScore?->criterion_4 ?? null,
                    'criterion_5' => $validated['criterion_5'] ?? $existingScore?->criterion_5 ?? null,
                    'criterion_6' => $validated['criterion_6'] ?? $existingScore?->criterion_6 ?? null,
                    'criterion_7' => $validated['criterion_7'] ?? $existingScore?->criterion_7 ?? null,
                    'criterion_8' => $validated['criterion_8'] ?? $existingScore?->criterion_8 ?? null,
                    'criterion_9' => $validated['criterion_9'] ?? $existingScore?->criterion_9 ?? null,
                    'criterion_10' => $validated['criterion_10'] ?? $existingScore?->criterion_10 ?? null,
                    'final_score' => $totalScore,
                    'notes' => $validated['notes'] ?? null,
                    'justification' => $validated['justification'] ?? null,
                ]
            );
        }

        // Kreiraj ili ažuriraj ocjenu
        if ($isChairman && $hasCompletedEvaluation) {
            // Ako je predsjednik i već je ocjenio, ažuriraj samo notes (dok ne zaključi prijavu)
            $existingScore = EvaluationScore::where('application_id', $application->id)
                ->where('commission_member_id', $commissionMember->id)
                ->first();
            
            // Provjeri da li je predsjednik zaključio prijavu
            $isDecisionMade = $application->commission_decision !== null;
            
            if ($existingScore && !$isDecisionMade) {
                $notesValue = $request->has('notes') ? ($request->input('notes') ?? '') : $existingScore->notes;

                $existingScore->update([
                    'notes' => $notesValue,
                ]);
            }
            
            // Redirectuj na listu sa porukom
            return redirect()->route('evaluation.index', ['filter' => 'evaluated'])
                ->with('success', 'Izmjene su uspješno sačuvane.');
        } else {
            $isDecisionMadeForUpdate = $application->commission_decision !== null;
            
            $existingScoreForNotes = EvaluationScore::where('application_id', $application->id)
                ->where('commission_member_id', $commissionMember->id)
                ->first();
            
            if ($existingScoreForNotes && $hasCompletedEvaluation && !$isDecisionMadeForUpdate && !$isChairman) {
                $notesValue = $existingScoreForNotes->notes;
                
                if ($request->has('notes')) {
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
                    'criterion_1' => $validated['criterion_1'] ?? $existingScoreForNotes?->criterion_1 ?? null,
                    'criterion_2' => $validated['criterion_2'] ?? $existingScoreForNotes?->criterion_2 ?? null,
                    'criterion_3' => $validated['criterion_3'] ?? $existingScoreForNotes?->criterion_3 ?? null,
                    'criterion_4' => $validated['criterion_4'] ?? $existingScoreForNotes?->criterion_4 ?? null,
                    'criterion_5' => $validated['criterion_5'] ?? $existingScoreForNotes?->criterion_5 ?? null,
                    'criterion_6' => $validated['criterion_6'] ?? $existingScoreForNotes?->criterion_6 ?? null,
                    'criterion_7' => $validated['criterion_7'] ?? $existingScoreForNotes?->criterion_7 ?? null,
                    'criterion_8' => $validated['criterion_8'] ?? $existingScoreForNotes?->criterion_8 ?? null,
                    'criterion_9' => $validated['criterion_9'] ?? $existingScoreForNotes?->criterion_9 ?? null,
                    'criterion_10' => $validated['criterion_10'] ?? $existingScoreForNotes?->criterion_10 ?? null,
                    'final_score' => $totalScore,
                    'notes' => $validated['notes'] ?? null,
                    'justification' => $validated['justification'] ?? null,
                ]
            );
            
            // Ažuriraj prosječnu ocjenu prijave (prosjek svih članova komisije)
            $this->updateApplicationScores($application);

            // Ako je predsjednik i svi članovi su ocjenili, može ažurirati zaključak komisije i iznos odobrenih sredstava
            if ($commissionMember->position === 'predsjednik' && $allMembersEvaluated && isset($validated['commission_decision'])) {
                $updateData = [
                    'commission_decision' => $validated['commission_decision'],
                    'approved_amount' => $validated['approved_amount'] ?? null,
                    'commission_decision_date' => $validated['decision_date'] ?? now(),
                ];
                
                // Ako je zaključak "odbija", postavi status na rejected
                // Napomena: Obrazloženje se unosi samo u rang listi kroz storeDecision metodu
                if ($validated['commission_decision'] === 'odbija') {
                    $updateData['status'] = 'rejected';
                    // Ako postoji obrazloženje u requestu, postavi ga kao razlog odbijanja
                    if (isset($validated['commission_justification']) && !empty($validated['commission_justification'])) {
                        $updateData['rejection_reason'] = $validated['commission_justification'];
                    }
                } elseif ($validated['commission_decision'] === 'podrzava_potpuno') {
                    $updateData['status'] = 'approved';
                }
                
                $application->update($updateData);
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
        $application->refresh();

        if ($application->isEliminatedFromScoring()) {
            return;
        }

        $scores = EvaluationScore::where('application_id', $application->id)->get();

        if ($scores->isEmpty()) {
            return;
        }

        // Provjeri da li su svi članovi komisije ocjenili prijavu
        $competition = $application->competition;
        if (!$competition || !$competition->commission_id) {
            return;
        }
        
        $commission = Commission::find($competition->commission_id);
        if (!$commission) {
            return;
        }
        
        $activeMemberIds = $commission->activeMembers()->pluck('id');
        $totalMembers = $activeMemberIds->count();
        
        // Provjeri da li su svi članovi ocijenili (imaju sve kriterijume popunjene)
        $evaluatedMemberIds = EvaluationScore::where('application_id', $application->id)
            ->whereIn('commission_member_id', $activeMemberIds)
            ->whereNotNull('criterion_1') // Ako ima criterion_1, znači da je završio ocjenjivanje
            ->pluck('commission_member_id')
            ->unique()
            ->count();
        
        $allMembersEvaluated = $evaluatedMemberIds >= $totalMembers;

        // Izračunaj prosjek za svaki kriterijum (samo za članove koji su završili ocjenjivanje)
        $averages = [];
        for ($i = 1; $i <= 10; $i++) {
            $criterionScores = $scores->whereNotNull("criterion_{$i}")->pluck("criterion_{$i}")->toArray();
            if (!empty($criterionScores)) {
                $averages[$i] = round(array_sum($criterionScores) / count($criterionScores), 2);
            } else {
                $averages[$i] = 0;
            }
        }

        // Izračunaj konačnu ocjenu (zbir prosjeka + eventualni bonus bodovi)
        $finalScore = round(array_sum($averages) + $application->getBonusScore(), 2);

        // Ažuriraj final_score i evaluated_at
        $updateData = [
            'final_score' => $finalScore,
            'evaluated_at' => now(),
        ];

        // Ako su svi članovi ocijenili i ocjena je ispod 30, odbiti prijavu
        // Ako nisu svi ocijenili, ne odbijati prijavu (ostavi status 'evaluated' ili 'submitted')
        if ($allMembersEvaluated) {
            if ($finalScore < 30) {
                $updateData['status'] = 'rejected';
                $updateData['rejection_reason'] = 'Ukupna ocjena ispod 30 bodova (minimum za podršku).';
            } else {
                // Ako je ocjena 30 ili više, postavi status na 'evaluated'
                $updateData['status'] = 'evaluated';
            }
        } else {
            // Ako nisu svi ocijenili, ne mijenjaj status (ostavi postojeći)
            // Samo ažuriraj final_score i evaluated_at
        }

        // Ažuriraj prijavu
        $application->update($updateData);

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
        
        $commissionMember = $this->commissionMemberForApplication($application, $user->id);

        if (!$commissionMember) {
            abort(403, 'Niste član komisije.');
        }

        $this->abortIfCommissionProcessingBlocked($application->competition);

        $evaluationScore = EvaluationScore::where('application_id', $application->id)
            ->where('commission_member_id', $commissionMember->id)
            ->first();

        if (!$evaluationScore) {
            return redirect()->route('evaluation.create', $application);
        }

        // Učitaj komisiju sa svim članovima
        // Ako je podnosilac prijave, učitaj komisiju preko konkursa
        if ($commissionMember) {
            $commission = $commissionMember->commission;
        } else {
            // Podnosilac prijave - učitaj komisiju preko konkursa
            $commission = $application->competition->commission;
        }
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

        // Ocjene ostalih članova vidljive su tek kada su SVE prijave na ovom konkursu ocijenjene od SVIH članova
        $competition = $application->competition;
        $applicationIdsOnCompetition = Application::where('competition_id', $competition->id)
            ->whereIn('status', ['submitted', 'evaluated', 'rejected', 'approved'])
            ->pluck('id');
        $activeMemberIds = $commission->activeMembers()->pluck('id');
        $totalMembers = $activeMemberIds->count();
        $canViewOtherMembersScores = true;
        if ($applicationIdsOnCompetition->isNotEmpty() && $totalMembers > 0) {
            $evaluatedPerApplication = EvaluationScore::whereIn('application_id', $applicationIdsOnCompetition)
                ->whereIn('commission_member_id', $activeMemberIds)
                ->whereNotNull('criterion_1')
                ->selectRaw('application_id, count(distinct commission_member_id) as cnt')
                ->groupBy('application_id')
                ->pluck('cnt', 'application_id');
            $canViewOtherMembersScores = $applicationIdsOnCompetition->every(function ($appId) use ($evaluatedPerApplication, $totalMembers) {
                return ($evaluatedPerApplication->get($appId, 0)) >= $totalMembers;
            });
        }

        $application->load(['user', 'competition', 'businessPlan', 'eliminatoryCheck', 'eliminatoryNotice', 'prigovor']);

        $eliminatoryCheck = $application->eliminatoryCheck;
        $eliminatoryNotice = $application->eliminatoryNotice;
        $prigovor = $application->prigovor;
        $canDecidePrigovor = $commissionMember
            && $commissionMember->position === 'predsjednik'
            && $commissionMember->status === 'active'
            && $prigovor?->isPodnesen();

        return view('evaluation.show', compact(
            'application', 
            'commissionMember', 
            'evaluationScore',
            'allMembers',
            'allScores',
            'averageScores',
            'finalScore',
            'commission',
            'canViewOtherMembersScores',
            'eliminatoryCheck',
            'eliminatoryNotice',
            'prigovor',
            'canDecidePrigovor',
        ));
    }

    /**
     * Snimanje zaključka komisije od strane predsjednika
     */
    public function storeDecision(Request $request, Application $application): RedirectResponse
    {
        $user = Auth::user();
        
        // Pronađi člana komisije
        $commissionMember = $this->commissionMemberForApplication($application, $user->id);

        if (!$commissionMember) {
            abort(403, 'Niste član komisije.');
        }

        $this->abortIfCommissionProcessingBlocked($application->competition);

        // Proveri da li je predsjednik
        if ($commissionMember->position !== 'predsjednik') {
            abort(403, 'Samo predsjednik komisije može donijeti zaključak.');
        }

        if (! $this->eliminatoryChecks->scoringIsAllowed($application)) {
            abort(403, $this->eliminatoryChecks->isConfirmedFail($application)
                ? ApplicationEliminatoryCheckService::CONFIRMED_FAIL_SCORING_MESSAGE
                : ApplicationEliminatoryCheckService::SCORING_LOCKED_MESSAGE);
        }
        
        // Provjeri da li je prošao rok od 45 dana za donošenje odluke
        $competition = $application->competition;
        if ($competition && $competition->status === 'completed') {
            return redirect()->back()
                ->withErrors(['error' => 'Rang lista je zaključena. Nakon završetka konkursa izmjene nijesu dozvoljene.']);
        }
        if ($competition && $competition->isEvaluationDeadlinePassed()) {
            return redirect()->back()
                ->withErrors(['error' => 'Rok za donošenje odluke je istekao. Komisija je dužna donijeti odluku u roku od 45 dana od dana zatvaranja prijava na konkurs.']);
        }

        $validated = $request->validate([
            'commission_decision' => 'required|in:podrzava_potpuno,odbija',
            'commission_justification' => 'nullable|string|max:5000',
            'commission_notes' => 'nullable|string|max:5000',
            'approved_amount' => 'nullable|numeric|min:0',
        ], [
            'commission_decision.required' => 'Morate odabrati zaključak komisije.',
        ]);

        if (
            isset($validated['approved_amount']) &&
            $validated['approved_amount'] !== null &&
            $application->requested_amount !== null &&
            (float) $validated['approved_amount'] > (float) $application->requested_amount
        ) {
            return redirect()->back()
                ->withErrors([
                    'approved_amount' => 'Odobreni iznos ne može biti veći od traženog iznosa.',
                ])
                ->withInput();
        }

        if (
            isset($validated['approved_amount']) &&
            $validated['approved_amount'] !== null &&
            (float) $validated['approved_amount'] > 0 &&
            empty(trim((string)($validated['commission_justification'] ?? '')))
        ) {
            return redirect()->back()
                ->withErrors([
                    'commission_justification' => 'Obrazloženje je obavezno kada unosite odobreni iznos.',
                ])
                ->withInput();
        }

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
            // Postavi status na rejected i postavi obrazloženje kao razlog odbijanja
            $application->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['commission_justification'],
            ]);
        } elseif ($validated['commission_decision'] === 'podrzava_potpuno') {
            $application->update(['status' => 'approved']);
        }

        return redirect()->route('admin.competitions.ranking', $application->competition)
            ->with('success', 'Zaključak komisije je uspješno sačuvan.');
    }

    /**
     * Potpisivanje odluke od strane člana komisije
     */
    public function signDecision(Application $application): RedirectResponse
    {
        $user = Auth::user();
        
        // Pronađi člana komisije
        $commissionMember = $this->commissionMemberForApplication($application, $user->id);

        if (!$commissionMember) {
            abort(403, 'Niste član komisije.');
        }

        $this->abortIfCommissionProcessingBlocked($application->competition);

        if (! $this->eliminatoryChecks->scoringIsAllowed($application)) {
            abort(403, $this->eliminatoryChecks->isConfirmedFail($application)
                ? ApplicationEliminatoryCheckService::CONFIRMED_FAIL_SCORING_MESSAGE
                : ApplicationEliminatoryCheckService::SCORING_LOCKED_MESSAGE);
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

    public function storeEliminatory(Request $request, Application $application): RedirectResponse
    {
        $chairman = $this->chairmanForEliminatoryMutation($application);

        $answers = $this->validatedEliminatoryAnswers($request, requireNoteIfFail: false);
        $this->eliminatoryChecks->saveDraft($application, $chairman, $answers);

        return redirect()->route('evaluation.create', $application)
            ->with('success', 'Eliminatorna provjera je sačuvana. Obrazac 3 još nije potvrđen.');
    }

    public function confirmEliminatory(Request $request, Application $application): RedirectResponse
    {
        $chairman = $this->chairmanForEliminatoryMutation($application);

        $answers = $this->validatedEliminatoryAnswers($request, requireNoteIfFail: true);
        $acknowledgement = $request->boolean('confirmation_acknowledged');

        $this->eliminatoryChecks->confirm($application, $chairman, $answers, $acknowledgement);

        return redirect()->route('evaluation.create', $application)
            ->with('success', 'Obrazac 3 je potvrđen.');
    }

    public function decidePrigovor(Request $request, Application $application): RedirectResponse
    {
        $chairman = $this->chairmanForEliminatoryMutation($application);

        $validated = $request->validate([
            'odluka' => 'required|in:prihvacen,odbijen',
            'decision_note' => 'nullable|string|max:5000',
        ]);

        $this->prigovors->decide(
            $application,
            $chairman,
            $validated['odluka'],
            $validated['decision_note'] ?? null,
        );

        return redirect()->route('evaluation.create', $application)
            ->with('success', 'Odluka Komisije o Prigovoru je evidentirana.');
    }

    protected function chairmanForEliminatoryMutation(Application $application): CommissionMember
    {
        $user = Auth::user();
        $competition = $application->competition;

        if ($competition && ! $competition->isApplicationDeadlinePassed() && ! in_array($competition->status, ['closed', 'completed'], true)) {
            abort(403, 'Ocjenjivanje počinje tek kada istekne rok od 20 dana za prijave na konkurs. Nakon toga počinje rok od 45 dana za donošenje odluke od strane komisije.');
        }

        $this->abortIfCommissionProcessingBlocked($competition);

        $commissionMember = $this->commissionMemberForApplication($application, $user->id);
        if (! $commissionMember) {
            abort(403, 'Niste član komisije.');
        }

        if ($application->status === 'draft') {
            abort(403, 'Prijava još nije podnesena. Članovi komisije mogu vidjeti prijavu tek nakon što korisnik klikne na "Podnesi prijavu".');
        }

        if ($commissionMember->position !== 'predsjednik' || $commissionMember->status !== 'active') {
            abort(403, 'Samo predsjednik Komisije može uređivati Obrazac 3.');
        }

        return $commissionMember;
    }

    /**
     * @return array{criterion_1: bool, criterion_2: bool, criterion_3: bool, note: ?string}
     */
    protected function validatedEliminatoryAnswers(Request $request, bool $requireNoteIfFail): array
    {
        $validated = $request->validate([
            'criterion_1' => 'required|boolean',
            'criterion_2' => 'required|boolean',
            'criterion_3' => 'required|boolean',
            'note' => 'nullable|string|max:5000',
        ]);

        $criterion1 = $request->boolean('criterion_1');
        $criterion2 = $request->boolean('criterion_2');
        $criterion3 = $request->boolean('criterion_3');
        $note = isset($validated['note']) ? trim((string) $validated['note']) : '';
        $note = $note === '' ? null : $note;

        if ($requireNoteIfFail && (! $criterion1 || ! $criterion2 || ! $criterion3) && $note === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'note' => 'Napomena je obavezna kada postoji najmanje jedan odgovor Ne*.',
            ]);
        }

        return [
            'criterion_1' => $criterion1,
            'criterion_2' => $criterion2,
            'criterion_3' => $criterion3,
            'note' => $note,
        ];
    }
}
