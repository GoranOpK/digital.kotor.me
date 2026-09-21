<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\EvaluationScore;
use App\Models\CommissionMember;
use App\Services\ApplicationEliminatoryCheckService;
use App\Services\ApplicationPrigovorService;
use App\Services\ApplicationYouthAppealWindowService;
use App\Services\CanonicalIndividualScoringService;
use App\Services\Competitions\YouthAllocationDraftService;
use App\Services\Competitions\ZpEqualScoreAllocationGuard;
use App\Support\CommissionCanonicalSeat;
use App\Support\EliminatoryProfileConfig;
use App\Support\ScoringProfileConfig;
use App\Services\YouthSecondSessionGate;
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
        protected ApplicationYouthAppealWindowService $youthAppealWindows,
        protected YouthSecondSessionGate $youthSecondSessionGate,
        protected YouthAllocationDraftService $youthAllocationDrafts,
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

        $memberships = CommissionMember::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        if ($memberships->isEmpty()) {
            abort(403, 'Niste član komisije.');
        }

        $commissionIds = $memberships->pluck('commission_id')->unique()->filter()->values();
        $assignedCompetitions = \App\Models\Competition::query()
            ->whereIn('commission_id', $commissionIds)
            ->get();

        $competitionIds = $assignedCompetitions->filter(function ($c) {
            if (! in_array($c->status, ['closed', 'completed']) && ! $c->isApplicationDeadlinePassed()) {
                return false;
            }

            return ! $c->isCommissionProcessingBlocked();
        })->pluck('id');

        $query = Application::with(['user', 'competition']);

        if ($competitionIds->isNotEmpty()) {
            $query->whereIn('competition_id', $competitionIds);
            $this->youthAppealWindows->finalizeExpiredWithoutPrigovorForCompetitionIds($competitionIds->all());
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('competition_id')) {
            $requestedId = (int) $request->competition_id;
            if (! $assignedCompetitions->pluck('id')->contains($requestedId)) {
                abort(403, 'Niste član komisije tog Poziva.');
            }
            $query->where('competition_id', $requestedId);
        }

        foreach ($assignedCompetitions as $assignedCompetition) {
            if (! $assignedCompetition->isOmladinskoProfile()) {
                continue;
            }
            $membership = $memberships->first(
                fn (CommissionMember $member) => (int) $member->commission_id === (int) $assignedCompetition->commission_id
                    && $member->status === 'active'
            );
            if ($membership === null) {
                continue;
            }
            $this->canonicalScoring->persistYouthPreliminaryRankingIfReady($assignedCompetition);
        }

        $viewerMembershipIds = $memberships->pluck('id')->all();
        $evaluatedApplicationIds = EvaluationScore::whereIn('commission_member_id', $viewerMembershipIds)
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
        
        $competitions = $assignedCompetitions
            ->whereIn('id', $competitionIds->all())
            ->whereIn('status', ['draft', 'published', 'closed', 'completed'])
            ->values();

        // Link na rang listu na ekranu za ocjenjivanje:
        // prikaži samo za konkurse koji imaju formiranu rang listu (isRankingFormed)
        // i koji nijesu arhivirani (status nije 'closed' ili 'completed')
        $competitionsWithAllEvaluated = $competitions
            ->filter(fn ($c) => ! $c->isOmladinskoProfile() && $c->isIndividualScoringCycleComplete() && !in_array($c->status, ['closed', 'completed']))
            ->values();

        $canViewFinalScoresByCompetition = $competitions
            ->mapWithKeys(fn ($c) => [$c->id => $c->isIndividualScoringCycleComplete()])
            ->toArray();

        $youthPreliminaryByCompetition = $competitions
            ->filter(fn ($c) => $c->isOmladinskoProfile())
            ->mapWithKeys(fn ($c) => [$c->id => $this->canonicalScoring->youthPreliminaryRankingView($c)])
            ->all();

        $youthAllocationCanEditByCompetition = $competitions
            ->filter(fn ($c) => $c->isOmladinskoProfile())
            ->mapWithKeys(fn ($c) => [$c->id => $this->youthAllocationDrafts->canEditDraft($c, $user)])
            ->all();

        $isChairman = $memberships->contains(fn (CommissionMember $member) => $member->position === 'predsjednik');
        $commissionMember = $memberships->firstWhere('position', 'predsjednik') ?? $memberships->first();
        $membershipByCommissionId = $memberships->keyBy('commission_id');

        return view('evaluation.index', compact(
            'applications',
            'competitions',
            'commissionMember',
            'competitionsWithAllEvaluated',
            'canViewFinalScoresByCompetition',
            'youthPreliminaryByCompetition',
            'youthAllocationCanEditByCompetition',
            'isChairman',
            'viewerMembershipIds',
            'membershipByCommissionId',
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
        }

        $this->youthAppealWindows->finalizeExpiredWithoutPrigovor($application);
        $application->refresh();

        if ($commissionMember) {
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
            if ($competition && ! $competition->isOmladinskoProfile() && $competition->isEvaluationDeadlinePassed()) {
                abort(403, 'Rok za ocjenjivanje je istekao. Komisija je dužna donijeti odluku u roku od 45 dana od dana zatvaranja prijava na konkurs.');
            }

            if ($competition?->isOmladinskoProfile()) {
                $this->canonicalScoring->persistYouthPreliminaryRankingIfReady($competition);
            }
        }

        // Provjeri da li je prijava već odbijena
        // Svi članovi komisije mogu pristupiti odbijenim prijavama, ali forma će biti read-only (provjera se vrši u view-u)
        // Podnosilac prijave takođe može pristupiti odbijenim prijavama u read-only modu

        // Učitaj komisiju sa svim članovima
        // Ako je podnosilac prijave, učitaj komisiju preko konkursa
        $competition = $application->competition;
        $scoringProfile = ScoringProfileConfig::for($competition?->type);
        $isOmladinskoScoring = $scoringProfile->isOmladinsko();
        $canViewOtherMembersScores = false;
        $youthRankingView = null;

        if ($isOmladinskoScoring) {
            $commissionId = (int) ($competition?->commission_id ?? 0);
            $commission = $commissionId > 0
                ? \App\Models\Commission::query()->find($commissionId)
                : null;
            $allMembers = $commission
                ? $commission->members()
                    ->where('status', 'active')
                    ->orderByRaw("CASE WHEN position = 'predsjednik' THEN 0 ELSE 1 END")
                    ->orderBy('id')
                    ->get()
                : collect();
            $youthRankingView = ($commissionMember && $competition)
                ? $this->canonicalScoring->youthPreliminaryRankingView($competition)
                : null;
            $youthCycleComplete = (bool) ($youthRankingView['individual_cycle_complete'] ?? false);
            $canViewOtherMembersScores = $commissionMember !== null && $youthCycleComplete;
            $application->unsetRelation('evaluationScores');
            $allScores = $canViewOtherMembersScores
                ? $application->evaluationScores()
                    ->whereNotNull('completed_at')
                    ->whereIn('canonical_seat_no', [1, 2, 3])
                    ->orderBy('canonical_seat_no')
                    ->with('commissionMember')
                    ->get()
                    ->keyBy('commission_member_id')
                : ($commissionMember
                    ? $application->evaluationScores()
                        ->where('commission_member_id', $commissionMember->id)
                        ->get()
                        ->keyBy('commission_member_id')
                    : collect());
        } elseif ($commissionMember) {
            $commission = $commissionMember->commission;
            $allMembers = $commission->members()
                ->where('status', 'active')
                ->orderByRaw("CASE WHEN position = 'predsjednik' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->get();
            $allScores = EvaluationScore::where('application_id', $application->id)
                ->whereIn('commission_member_id', $allMembers->pluck('id'))
                ->with('commissionMember')
                ->get()
                ->keyBy('commission_member_id');
        } else {
            $commission = $application->competition->commission;
            $allMembers = $commission->members()
                ->where('status', 'active')
                ->orderByRaw("CASE WHEN position = 'predsjednik' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->get();
            $allScores = EvaluationScore::where('application_id', $application->id)
                ->whereIn('commission_member_id', $allMembers->pluck('id'))
                ->with('commissionMember')
                ->get()
                ->keyBy('commission_member_id');
        }

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

        $totalMembers = $isOmladinskoScoring
            ? $scoringProfile->requiredFinalCount
            : count(CommissionCanonicalSeat::SEATS);
        $completedBySeat = $this->canonicalScoring->completedEvaluationsBySeat($application);
        $evaluatedMemberIds = count($completedBySeat);
        $allMembersEvaluated = $isOmladinskoScoring
            ? $this->canonicalScoring->applicationHasYouthCanonicalSeats($application)
            : $this->canonicalScoring->applicationHasFiveCanonicalSeats($application);
        
        $isDecisionMade = $application->commission_decision !== null;
        
        $isChairman = $commissionMember && $commissionMember->position === 'predsjednik';
        
        if ($commissionMember && $hasCompletedEvaluation && !$isChairman && !$allMembersEvaluated && $isDecisionMade) {
            return redirect()->route('evaluation.index', ['filter' => 'evaluated'])
                ->with('error', 'Već ste ocjenili ovu prijavu. Ocjene se ne mogu mijenjati.');
        }

        if (! $isOmladinskoScoring) {
            $canViewOtherMembersScores = $competition ? $competition->isIndividualScoringCycleComplete() : false;
            if (! $canViewOtherMembersScores && $commissionMember) {
                $allScores = $allScores->only([$commissionMember->id]);
            }
        }

        $aggregate = null;
        $youthFinalScoreDisplay = null;
        if ($canViewOtherMembersScores) {
            if ($isOmladinskoScoring) {
                $aggregate = $this->canonicalScoring->aggregateYouthApplication($application);
                if ($application->bonuses_confirmed_at !== null) {
                    $youthFinalScoreDisplay = $aggregate['final_score_display'] ?? null;
                }
            } else {
                $aggregate = $this->canonicalScoring->aggregateApplication($application);
            }
        }
        $averageScores = $aggregate['criterion_averages'] ?? [];
        $finalScore = $isOmladinskoScoring
            ? 0
            : ($aggregate['base_score'] ?? 0);

        $application->load(['user', 'competition', 'businessPlan', 'documents', 'eliminatoryCheck', 'eliminatoryNotice', 'prigovor', 'oralPresentation']);

        $eliminatoryCheck = $application->eliminatoryCheck;
        $eliminatoryProfile = EliminatoryProfileConfig::for($application->competition?->type);
        $scoringIsAllowed = $this->eliminatoryChecks->scoringIsAllowed($application);
        $eliminatoryIsConfirmedFail = $this->eliminatoryChecks->isConfirmedFail($application);
        $eliminatoryNotice = $application->eliminatoryNotice;
        $prigovor = $application->prigovor;
        $canDecidePrigovor = $this->chairmanCanDecidePrigovor($commissionMember, $application, $prigovor);
        $youthOralPresentation = $isOmladinskoScoring ? $application->oralPresentation : null;
        $youthLockEvidenceReady = $isOmladinskoScoring
            && $this->youthSecondSessionGate->youthLockEvidenceIsComplete($application);
        $scoringLockedMessage = $isOmladinskoScoring
            ? ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE
            : ApplicationEliminatoryCheckService::SCORING_LOCKED_MESSAGE;
        $youthPlannedRegistrationBonusEligible = $isOmladinskoScoring
            && $this->canonicalScoring->youthQualifiesForPlannedRegistrationBonus($application);
        $youthBonusesLocked = $isOmladinskoScoring && $application->bonuses_confirmed_at !== null;
        $canEditYouthAllocationDraft = $isOmladinskoScoring
            && $competition
            && $this->youthAllocationDrafts->canEditDraft($competition, $user);

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
            'eliminatoryProfile',
            'scoringIsAllowed',
            'eliminatoryIsConfirmedFail',
            'eliminatoryNotice',
            'prigovor',
            'canDecidePrigovor',
            'scoringProfile',
            'isOmladinskoScoring',
            'youthOralPresentation',
            'youthLockEvidenceReady',
            'scoringLockedMessage',
            'youthPlannedRegistrationBonusEligible',
            'youthBonusesLocked',
            'youthFinalScoreDisplay',
            'youthRankingView',
            'canEditYouthAllocationDraft',
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
        if ($competition && ! $competition->isOmladinskoProfile() && $competition->isEvaluationDeadlinePassed()) {
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
                : ($competition?->isOmladinskoProfile()
                    ? ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE
                    : ApplicationEliminatoryCheckService::SCORING_LOCKED_MESSAGE));
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

        if ($competition?->isOmladinskoProfile()) {
            if ($request->filled('youth_bonus_action')) {
                return $this->storeYouthBonuses($request, $application, $commissionMember);
            }

            return $this->storeYouthScore($request, $application, $commissionMember, $alreadyFinal);
        }

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

    protected function storeYouthScore(
        Request $request,
        Application $application,
        CommissionMember $commissionMember,
        bool $alreadyFinal,
    ): RedirectResponse {
        if ($alreadyFinal) {
            abort(403, CanonicalIndividualScoringService::SCORE_IMMUTABLE_MESSAGE);
        }

        $saveAsDraft = $request->boolean('save_as_draft');
        $messages = [
            'scoring_confirmed.accepted' => CanonicalIndividualScoringService::CONFIRMATION_REQUIRED_MESSAGE,
        ];
        $rules = [
            'notes' => 'nullable|string|max:5000',
        ];

        for ($i = 1; $i <= 10; $i++) {
            $messages["criterion_{$i}.min"] = "Kriterijum {$i} mora biti najmanje 1 poen.";
            $messages["criterion_{$i}.max"] = "Kriterijum {$i} može biti najviše 5 poena.";
            $messages["criterion_{$i}.required"] = "Kriterijum {$i} je obavezan.";
            $rules["criterion_{$i}"] = $saveAsDraft
                ? 'nullable|integer|min:1|max:5'
                : 'required|integer|min:1|max:5';
        }

        if (! $saveAsDraft) {
            $rules['scoring_confirmed'] = 'accepted';
        }

        $validated = $request->validate($rules, $messages);

        if ($saveAsDraft) {
            $this->canonicalScoring->recordYouthDraftScore(
                $application,
                $commissionMember,
                $validated,
                $validated['notes'] ?? null,
            );

            return redirect()->route('evaluation.create', $application)
                ->with('success', 'Nacrt ocjene je sačuvan.');
        }

        $this->canonicalScoring->recordYouthFinalScore(
            $application,
            $commissionMember,
            $validated,
            $validated['notes'] ?? null,
            $request->boolean('scoring_confirmed'),
        );

        return redirect()->route('evaluation.index', ['filter' => 'evaluated'])
            ->with('success', 'Ocjena je uspješno sačuvana.');
    }

    protected function storeYouthBonuses(
        Request $request,
        Application $application,
        CommissionMember $commissionMember,
    ): RedirectResponse {
        $action = (string) $request->input('youth_bonus_action');
        if (! in_array($action, ['draft', 'confirm'], true)) {
            abort(403, CanonicalIndividualScoringService::YOUTH_BONUS_CHAIRMAN_REQUIRED_MESSAGE);
        }

        $validated = $request->validate([
            'youth_bonus_action' => 'required|in:draft,confirm',
            'bonus_info_day' => 'sometimes|boolean',
            'bonus_training' => 'sometimes|boolean',
            'bonus_new_business' => 'sometimes|boolean',
            'bonus_green_innovative' => 'sometimes|boolean',
        ]);

        $flags = [];
        foreach (CanonicalIndividualScoringService::YOUTH_BONUS_FLAG_KEYS as $key) {
            $flags[$key] = $request->boolean($key);
        }

        $this->canonicalScoring->saveYouthBonuses(
            $application,
            $commissionMember,
            $flags,
            $action === 'confirm',
        );

        return redirect()->route('evaluation.create', $application)
            ->with('success', $action === 'confirm'
                ? 'Dodatni bodovi su potvrđeni i zaključani.'
                : 'Nacrt dodatnih bodova je sačuvan.');
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

        $this->youthAppealWindows->finalizeExpiredWithoutPrigovor($application);
        $application->refresh();

        $this->abortIfCommissionProcessingBlocked($application->competition);

        if ($application->competition?->isOmladinskoProfile()) {
            $this->canonicalScoring->persistYouthPreliminaryRankingIfReady($application->competition);
        }

        $evaluationScore = EvaluationScore::where('application_id', $application->id)
            ->where('commission_member_id', $commissionMember->id)
            ->first();

        if (!$evaluationScore) {
            return redirect()->route('evaluation.create', $application);
        }

        $scoringProfile = ScoringProfileConfig::for($application->competition?->type);
        $isOmladinskoScoring = $scoringProfile->isOmladinsko();
        $competition = $application->competition;
        $canViewOtherMembersScores = false;
        $youthRankingView = null;

        if ($isOmladinskoScoring) {
            $commissionId = (int) ($competition?->commission_id ?? 0);
            $commission = $commissionId > 0
                ? \App\Models\Commission::query()->find($commissionId)
                : null;
            $allMembers = $commission
                ? $commission->members()
                    ->where('status', 'active')
                    ->orderByRaw("CASE WHEN position = 'predsjednik' THEN 0 ELSE 1 END")
                    ->orderBy('id')
                    ->get()
                : collect();
            $youthRankingView = ($commissionMember && $competition)
                ? $this->canonicalScoring->youthPreliminaryRankingView($competition)
                : null;
            $youthCycleComplete = (bool) ($youthRankingView['individual_cycle_complete'] ?? false);
            $canViewOtherMembersScores = $commissionMember !== null && $youthCycleComplete;
            $application->unsetRelation('evaluationScores');
            $allScores = $canViewOtherMembersScores
                ? $application->evaluationScores()
                    ->whereNotNull('completed_at')
                    ->whereIn('canonical_seat_no', [1, 2, 3])
                    ->orderBy('canonical_seat_no')
                    ->with('commissionMember')
                    ->get()
                    ->keyBy('commission_member_id')
                : $application->evaluationScores()
                    ->where('commission_member_id', $commissionMember->id)
                    ->get()
                    ->keyBy('commission_member_id');
        } else {
            if ($commissionMember) {
                $commission = $commissionMember->commission;
            } else {
                $commission = $application->competition->commission;
            }
            $allMembers = $commission->members()
                ->where('status', 'active')
                ->orderByRaw("CASE WHEN position = 'predsjednik' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->get();
            $allScores = EvaluationScore::where('application_id', $application->id)
                ->whereIn('commission_member_id', $allMembers->pluck('id'))
                ->with('commissionMember')
                ->get()
                ->keyBy('commission_member_id');
            $canViewOtherMembersScores = $competition
                ? $competition->isIndividualScoringCycleComplete()
                : false;
            if (! $canViewOtherMembersScores && $commissionMember) {
                $allScores = $allScores->only([$commissionMember->id]);
            }
        }

        $aggregate = null;
        $youthFinalScoreDisplay = null;
        if ($canViewOtherMembersScores) {
            if ($isOmladinskoScoring) {
                $aggregate = $this->canonicalScoring->aggregateYouthApplication($application);
                if ($application->bonuses_confirmed_at !== null) {
                    $youthFinalScoreDisplay = $aggregate['final_score_display'] ?? null;
                }
            } else {
                $aggregate = $this->canonicalScoring->aggregateApplication($application);
            }
        }
        $averageScores = $aggregate['criterion_averages'] ?? [];
        $finalScore = $isOmladinskoScoring
            ? 0
            : ($aggregate['base_score'] ?? 0);

        $application->load(['user', 'competition', 'businessPlan', 'eliminatoryCheck', 'eliminatoryNotice', 'prigovor']);

        $eliminatoryCheck = $application->eliminatoryCheck;
        $eliminatoryProfile = EliminatoryProfileConfig::for($application->competition?->type);
        $eliminatoryNotice = $application->eliminatoryNotice;
        $prigovor = $application->prigovor;
        $canDecidePrigovor = $this->chairmanCanDecidePrigovor($commissionMember, $application, $prigovor);
        $canEditYouthAllocationDraft = $isOmladinskoScoring
            && $competition
            && $this->youthAllocationDrafts->canEditDraft($competition, $user);

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
            'eliminatoryProfile',
            'eliminatoryNotice',
            'prigovor',
            'canDecidePrigovor',
            'scoringProfile',
            'isOmladinskoScoring',
            'youthFinalScoreDisplay',
            'youthRankingView',
            'canEditYouthAllocationDraft',
        ));
    }

    /**
     * Nacrt raspodjele mladih — predsjednik, samo nakon trajnog preliminarnog ranga.
     */
    public function storeYouthAllocationDraft(Request $request, Application $application): RedirectResponse
    {
        $application->loadMissing('competition');
        $this->abortIfCommissionProcessingBlocked($application->competition);

        $validated = $request->validate([
            'commission_decision' => 'required|in:podrzava_potpuno,odbija',
            'commission_justification' => 'nullable|string|max:5000',
            'approved_amount' => 'nullable|numeric|min:0',
            'youth_innovative_tech_startup' => 'nullable|boolean',
            'youth_prior_municipal_youth_funding' => 'nullable|boolean',
        ], [
            'commission_decision.required' => 'Morate odabrati zaključak komisije.',
        ]);

        $this->youthAllocationDrafts->saveDraft($application, Auth::user(), $validated);

        return redirect()->back()->with('success', 'Nacrt raspodjele je sačuvan.');
    }

    /**
     * Snimanje zaključka komisije od strane predsjednika
     */
    public function storeDecision(Request $request, Application $application): RedirectResponse
    {
        $application->loadMissing('competition');
        if ($application->competition?->isOmladinskoProfile()) {
            abort(403, YouthAllocationDraftService::WOMEN_STORE_DECISION_CLOSED_MESSAGE);
        }

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

        // §13.6 same-stage: require obrazloženje only when equal-score / same-stage budget choice is active.
        // Does not block mid-flow for mixed-stage priority (that is enforced at Predlog).
        if (
            $competition
            && $competition->type === 'zensko'
            && $justification === ''
            && app(ZpEqualScoreAllocationGuard::class)->requiresCommissionJustification(
                $competition,
                $application,
                ['commission_decision' => $decision]
            )
        ) {
            return redirect()->back()
                ->withErrors([
                    'commission_justification' => ZpEqualScoreAllocationGuard::SAME_STAGE_JUSTIFICATION_MESSAGE,
                ])
                ->withInput();
        }

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

            $requiredFunds = $application->displayRequiredFunds();
            if (
                $requiredFunds !== null &&
                $requiredFunds > 0 &&
                (float) $approvedAmount > $requiredFunds
            ) {
                return redirect()->back()
                    ->withErrors([
                        'approved_amount' => 'Odobreni iznos ne može biti veći od ukupno potrebnih sredstava za realizaciju biznis plana.',
                    ])
                    ->withInput();
            }

            $competition = $application->competition;
            $budget = (float) ($competition->budget ?? 0);
            $usedByOthers = (float) Application::query()
                ->where('competition_id', $competition->id)
                ->where('id', '!=', $application->id)
                ->where('commission_decision', 'podrzava_potpuno')
                ->whereNotNull('approved_amount')
                ->where('approved_amount', '>', 0)
                ->sum('approved_amount');
            $remainingForThisApplication = $budget - $usedByOthers;

            if ((float) $approvedAmount > $remainingForThisApplication) {
                return redirect()->back()
                    ->withErrors([
                        'approved_amount' => 'Odobreni iznos ne može biti veći od preostalih sredstava konkursa.',
                    ])
                    ->withInput();
            }

            // KN-BM-003 §13.4 / čl. 18: +3 green/innovative bonus je pouzdan dokaz za maksimum 20% budžeta.
            // bonus=false ne isključuje 20% (Komisija može utvrditi van aplikacije) — ne nameće se 10/5.
            if ((bool) $application->bonus_green_innovative) {
                $twentyPercentCap = round($budget * 0.20, 2);
                if (round((float) $approvedAmount, 2) > $twentyPercentCap) {
                    return redirect()->back()
                        ->withErrors([
                            'approved_amount' => 'Odobreni iznos ne može biti veći od 20% ukupnog budžeta konkursa.',
                        ])
                        ->withInput();
                }
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

        $answers = $this->validatedEliminatoryAnswers($request, $application, requireNotesIfFail: false);
        $this->eliminatoryChecks->saveDraft($application, $chairman, $answers);

        return redirect()->route('evaluation.create', $application)
            ->with('success', 'Eliminatorna provjera je sačuvana. Obrazac 3 još nije potvrđen.');
    }

    public function confirmEliminatory(Request $request, Application $application): RedirectResponse
    {
        $chairman = $this->chairmanForEliminatoryMutation($application);

        $answers = $this->validatedEliminatoryAnswers($request, $application, requireNotesIfFail: true);
        $acknowledgement = $request->boolean('confirmation_acknowledged');

        $this->eliminatoryChecks->confirm($application, $chairman, $answers, $acknowledgement);

        return redirect()->route('evaluation.create', $application)
            ->with('success', 'Obrazac 3 je potvrđen.');
    }

    public function decidePrigovor(Request $request, Application $application): RedirectResponse
    {
        $chairman = $this->chairmanForEliminatoryMutation($application);
        $application->loadMissing('competition');
        $isYouth = $application->competition?->isOmladinskoProfile() === true;

        $rules = [
            'decision_note' => 'required|string|max:5000',
            'criterion_outcomes' => 'nullable|array',
            'criterion_outcomes.1' => 'nullable|in:otklonjen,ostaje',
            'criterion_outcomes.2' => 'nullable|in:otklonjen,ostaje',
            'criterion_outcomes.3' => 'nullable|in:otklonjen,ostaje',
        ];
        if (! $isYouth) {
            $rules['odluka'] = 'required|in:prihvacen,odbijen';
        }

        $validated = $request->validate($rules);

        $this->prigovors->decide(
            $application,
            $chairman,
            $validated['odluka'] ?? '',
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

    protected function chairmanCanDecidePrigovor(?CommissionMember $commissionMember, Application $application, $prigovor): bool
    {
        if (! $commissionMember
            || $commissionMember->position !== 'predsjednik'
            || $commissionMember->status !== 'active'
            || $prigovor?->isPodnesen() !== true
        ) {
            return false;
        }

        $application->loadMissing(['competition.commission.activeMembers', 'eliminatoryCheck']);

        if ($application->competition?->isOmladinskoProfile()) {
            if ($application->eliminatoryCheck?->isConfirmedFail() !== true) {
                return false;
            }

            if ($application->competition?->hasCompleteValidCommission() !== true) {
                return false;
            }

            return $prigovor->hasReadableContestedData();
        }

        return true;
    }

    /**
     * @return array{criterion_1: bool, criterion_2: bool, criterion_3: bool, note: ?string}
     */
    protected function validatedEliminatoryAnswers(Request $request, Application $application, bool $requireNotesIfFail): array
    {
        $application->loadMissing('competition');
        $profile = EliminatoryProfileConfig::for($application->competition?->type);

        $criterion1 = $request->boolean('criterion_1');
        $criterion2 = $request->boolean('criterion_2');
        $criterion3 = $request->boolean('criterion_3');

        if ($profile->usesStructuredNotes) {
            $request->validate([
                'criterion_1' => 'required|boolean',
                'criterion_2' => 'required|boolean',
                'criterion_3' => 'required|boolean',
                'criterion_notes' => 'nullable|array',
                'criterion_notes.1' => 'nullable|string|max:2000',
                'criterion_notes.2' => 'nullable|string|max:2000',
                'criterion_notes.3' => 'nullable|string|max:2000',
            ]);

            $explanations = [
                1 => trim((string) $request->input('criterion_notes.1', '')),
                2 => trim((string) $request->input('criterion_notes.2', '')),
                3 => trim((string) $request->input('criterion_notes.3', '')),
            ];

            $passedByNumber = [1 => $criterion1, 2 => $criterion2, 3 => $criterion3];
            $errors = [];
            foreach ($passedByNumber as $number => $passed) {
                if ($requireNotesIfFail && ! $passed && $explanations[$number] === '') {
                    $errors['criterion_notes.'.$number] = EliminatoryProfileConfig::YOUTH_EXPLANATION_REQUIRED_MESSAGE;
                }
            }
            if ($errors !== []) {
                throw \Illuminate\Validation\ValidationException::withMessages($errors);
            }

            return [
                'criterion_1' => $criterion1,
                'criterion_2' => $criterion2,
                'criterion_3' => $criterion3,
                'note' => EliminatoryProfileConfig::composeYouthNotes($explanations),
            ];
        }

        $validated = $request->validate([
            'criterion_1' => 'required|boolean',
            'criterion_2' => 'required|boolean',
            'criterion_3' => 'required|boolean',
            'note' => 'nullable|string|max:5000',
        ]);

        $note = isset($validated['note']) ? trim((string) $validated['note']) : '';
        $note = $note === '' ? null : $note;

        if ($requireNotesIfFail && (! $criterion1 || ! $criterion2 || ! $criterion3) && $note === null) {
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
