<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\EvaluationScore;
use App\Models\CommissionMember;
use App\Services\ApplicationEliminatoryCheckService;
use App\Services\ApplicationPrigovorService;
use App\Services\CanonicalIndividualScoringService;
use App\Support\CommissionCanonicalSeat;
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
        protected CanonicalIndividualScoringService $canonicalScoring,
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
            ->whereCompletedFinal()
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
            ->filter(fn ($c) => $c->isIndividualScoringCycleComplete() && !in_array($c->status, ['closed', 'completed']))
            ->values();

        $canViewFinalScoresByCompetition = $competitions
            ->mapWithKeys(fn ($c) => [$c->id => $c->isIndividualScoringCycleComplete()])
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
        $hasCompletedEvaluation = $this->canonicalScoring->isFinalCompleted($existingScore);

        $totalMembers = count(CommissionCanonicalSeat::SEATS);
        $completedBySeat = $this->canonicalScoring->completedEvaluationsBySeat($application);
        $evaluatedMemberIds = count($completedBySeat);
        $allMembersEvaluated = $this->canonicalScoring->applicationHasFiveCanonicalSeats($application);
        
        $isDecisionMade = $application->commission_decision !== null;
        
        $isChairman = $commissionMember && $commissionMember->position === 'predsjednik';
        
        if ($commissionMember && $hasCompletedEvaluation && !$isChairman && !$allMembersEvaluated && $isDecisionMade) {
            return redirect()->route('evaluation.index', ['filter' => 'evaluated'])
                ->with('error', 'Već ste ocjenili ovu prijavu. Ocjene se ne mogu mijenjati.');
        }

        $competition = $application->competition;
        $canViewOtherMembersScores = $competition ? $competition->isIndividualScoringCycleComplete() : false;
        if (! $canViewOtherMembersScores && $commissionMember) {
            $allScores = $allScores->only([$commissionMember->id]);
        }

        $aggregate = $canViewOtherMembersScores
            ? $this->canonicalScoring->aggregateApplication($application)
            : null;
        $averageScores = $aggregate['criterion_averages'] ?? [];
        $finalScore = $aggregate['base_score'] ?? 0;

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
        $existingScore = EvaluationScore::where('application_id', $application->id)
            ->where('commission_member_id', $commissionMember->id)
            ->first();
        $alreadyFinal = $this->canonicalScoring->isFinalCompleted($existingScore);
        $cycleCompleteBefore = $competition
            ? $this->canonicalScoring->isIndividualScoringCycleComplete($competition)
            : false;

        if (!$isChairman) {
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

        if ($alreadyFinal) {
            if ($request->filled('notes') && trim((string) $request->input('notes')) !== trim((string) ($existingScore->notes ?? ''))) {
                abort(403, CanonicalIndividualScoringService::SCORE_IMMUTABLE_MESSAGE);
            }

            for ($i = 1; $i <= 10; $i++) {
                if ($request->has("criterion_{$i}") && (int) $request->input("criterion_{$i}") !== (int) $existingScore->{"criterion_{$i}"}) {
                    abort(403, CanonicalIndividualScoringService::SCORE_IMMUTABLE_MESSAGE);
                }
            }

            if ($isChairman) {
                $this->canonicalScoring->saveChairmanBonusWhileCycleOpen(
                    $application,
                    $this->chairmanBonusFlagsFromRequest($request),
                    $cycleCompleteBefore,
                );
                if ($competition) {
                    $this->canonicalScoring->persistApplicationAggregatesIfCycleComplete($competition);
                }

                return redirect()->route('evaluation.index', ['filter' => 'evaluated'])
                    ->with('success', 'Izmjene su uspješno sačuvane.');
            }

            abort(403, CanonicalIndividualScoringService::SCORE_IMMUTABLE_MESSAGE);
        }

        $rules = [];
        $messages = [
            'scoring_confirmed.accepted' => CanonicalIndividualScoringService::CONFIRMATION_REQUIRED_MESSAGE,
        ];
        for ($i = 1; $i <= 10; $i++) {
            $rules["criterion_{$i}"] = 'required|integer|min:1|max:5';
            $messages["criterion_{$i}.required"] = "Kriterijum {$i} je obavezan.";
            $messages["criterion_{$i}.min"] = "Kriterijum {$i} mora biti najmanje 1 poen.";
            $messages["criterion_{$i}.max"] = "Kriterijum {$i} može biti najviše 5 poena.";
        }
        $rules['notes'] = 'nullable|string|max:5000';
        $rules['scoring_confirmed'] = 'accepted';

        $validated = $request->validate($rules, $messages);

        $this->canonicalScoring->recordFinalScore(
            $application,
            $commissionMember,
            $validated,
            $validated['notes'] ?? null,
            $request->boolean('scoring_confirmed'),
        );

        if ($isChairman) {
            $this->canonicalScoring->saveChairmanBonusWhileCycleOpen(
                $application,
                $this->chairmanBonusFlagsFromRequest($request),
                $cycleCompleteBefore,
            );
        }

        if ($competition) {
            $this->canonicalScoring->persistApplicationAggregatesIfCycleComplete($competition->fresh());
        }

        return redirect()->route('evaluation.index', ['filter' => 'evaluated'])
            ->with('success', 'Ocjena je uspješno sačuvana.');
    }

    /**
     * @return array<string, bool>
     */
    protected function chairmanBonusFlagsFromRequest(Request $request): array
    {
        $flags = [];
        foreach (CanonicalIndividualScoringService::BONUS_FLAG_KEYS as $key) {
            $flags[$key] = $request->boolean($key);
        }

        return $flags;
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

        $canViewOtherMembersScores = $application->competition
            ? $application->competition->isIndividualScoringCycleComplete()
            : false;
        if (! $canViewOtherMembersScores && $commissionMember) {
            $allScores = $allScores->only([$commissionMember->id]);
        }

        $aggregate = $canViewOtherMembersScores
            ? $this->canonicalScoring->aggregateApplication($application)
            : null;
        $averageScores = $aggregate['criterion_averages'] ?? [];
        $finalScore = $aggregate['base_score'] ?? 0;

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

        $competition = $application->competition;
        if ($competition && ! $competition->isIndividualScoringCycleComplete()) {
            abort(403, CanonicalIndividualScoringService::RANKING_LOCKED_MESSAGE);
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

        $decision = $validated['commission_decision'];
        $justification = trim((string) ($validated['commission_justification'] ?? ''));
        $approvedAmount = array_key_exists('approved_amount', $validated) ? $validated['approved_amount'] : null;

        if ($decision === 'podrzava_potpuno') {
            if ($approvedAmount === null || $approvedAmount === '' || (float) $approvedAmount <= 0) {
                return redirect()->back()
                    ->withErrors([
                        'approved_amount' => 'Za zaključak Podržava predloženi iznos podrške je obavezan i mora biti veći od nule.',
                    ])
                    ->withInput();
            }

            if (
                $application->requested_amount !== null &&
                (float) $approvedAmount > (float) $application->requested_amount
            ) {
                return redirect()->back()
                    ->withErrors([
                        'approved_amount' => 'Odobreni iznos ne može biti veći od traženog iznosa.',
                    ])
                    ->withInput();
            }

            $approvedAmount = (float) $approvedAmount;
            $justification = $justification !== '' ? $justification : null;
        } else {
            // Odbija: obrazloženje obavezno; odobreni iznos se ne čuva.
            if ($justification === '') {
                return redirect()->back()
                    ->withErrors([
                        'commission_justification' => 'Za zaključak Odbija obrazloženje je obavezno.',
                    ])
                    ->withInput();
            }

            $approvedAmount = null;
        }

        // Ažuriraj prijavu sa zaključkom
        $application->update([
            'commission_decision' => $decision,
            'commission_justification' => $justification,
            'commission_notes' => $validated['commission_notes'] ?? null,
            'approved_amount' => $approvedAmount,
            'commission_decision_date' => now(),
            'signed_by_chairman' => true,
        ]);

        // Ažuriraj status prijave na osnovu zaključka
        if ($decision === 'odbija') {
            // Postavi status na rejected i postavi obrazloženje kao razlog odbijanja
            $application->update([
                'status' => 'rejected',
                'rejection_reason' => $justification,
            ]);
        } elseif ($decision === 'podrzava_potpuno') {
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

        $competition = $application->competition;
        if ($competition && ! $competition->isIndividualScoringCycleComplete()) {
            abort(403, CanonicalIndividualScoringService::RANKING_LOCKED_MESSAGE);
        }

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
            'decision_note' => 'required|string|max:5000',
            'criterion_outcomes' => 'nullable|array',
            'criterion_outcomes.1' => 'nullable|in:otklonjen,ostaje',
            'criterion_outcomes.2' => 'nullable|in:otklonjen,ostaje',
            'criterion_outcomes.3' => 'nullable|in:otklonjen,ostaje',
        ]);

        $this->prigovors->decide(
            $application,
            $chairman,
            $validated['odluka'],
            $validated['decision_note'],
            $validated['criterion_outcomes'] ?? [],
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

    public function score(): never
    {
        abort(404);
    }

    public function comment(): never
    {
        abort(404);
    }
}
