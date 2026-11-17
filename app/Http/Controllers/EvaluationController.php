<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\User;
use App\Models\YouthDecisionDocument;
use App\Services\ApplicationEliminatoryCheckService;
use App\Services\ApplicationPrigovorService;
use App\Services\ApplicationYouthAppealWindowService;
use App\Services\YouthPrigovorMappingCorrectionService;
use App\Services\CanonicalIndividualScoringService;
use App\Services\Competitions\Lifecycle\LegacyCommissionFlowGuard;
use App\Services\Competitions\ZenskoPreDeadlineContentGate;
use App\Services\Competitions\YouthAllocationDraftService;
use App\Services\Competitions\YouthDecisionDocumentStore;
use App\Services\Competitions\YouthDecisionDocumentStoreException;
use App\Services\Competitions\YouthDecisionProposalBuilder;
use App\Services\Competitions\YouthDecisionProposalGate;
use App\Services\Competitions\YouthDecisionDocxWriter;
use App\Services\Competitions\YouthDecisionRankingIdentity;
use App\Services\Competitions\YouthDecisionSessionDates;
use App\Services\Competitions\ZenskoCommissionDecisionWriter;
use App\Services\Competitions\YouthAllocationListConfirmationService;
use App\Services\Competitions\YouthEqualScoreVotingService;
use App\Services\Competitions\ZpEqualScoreAllocationGuard;
use App\Support\CommissionCanonicalSeat;
use App\Support\EliminatoryProfileConfig;
use App\Support\ScoringProfileConfig;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class EvaluationController extends Controller
{
    public const YOUTH_DECISION_GENERATED_MESSAGE = 'Odluka je uspješno generisana i sačuvana.';

    public function __construct(
        protected ApplicationEliminatoryCheckService $eliminatoryChecks,
        protected ApplicationPrigovorService $prigovors,
        protected CanonicalIndividualScoringService $canonicalScoring,
        protected ApplicationYouthAppealWindowService $youthAppealWindows,
        protected YouthPrigovorMappingCorrectionService $youthMappingCorrections,
        protected YouthAllocationDraftService $youthAllocationDrafts,
        protected YouthEqualScoreVotingService $youthEqualScoreVoting,
        protected YouthAllocationListConfirmationService $youthAllocationListConfirmation,
        protected ZenskoCommissionDecisionWriter $zenskoDecisions,
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

    /**
     * Tuđe ocjene ženskog konkursa čekaju završetak prigovornog postupka.
     * Ostali profili zadržavaju postojeći uslov završetka ciklusa.
     */
    protected function nonYouthCommissionDisclosureIsAllowed(?Competition $competition): bool
    {
        if (! $competition) {
            return false;
        }

        if ($competition->type === 'zensko') {
            return $this->canonicalScoring->zenskoCommissionDisclosureIsAllowed($competition);
        }

        return $competition->isIndividualScoringCycleComplete();
    }

    /**
     * Dok uvid nije otvoren, u odgovor ulazi samo ocjena trenutnog člana.
     *
     * @param  \Illuminate\Support\Collection<int, CommissionMember>  $members
     * @return \Illuminate\Support\Collection<int, EvaluationScore>
     */
    protected function commissionScoresForDisclosure(
        Application $application,
        $members,
        ?CommissionMember $viewer,
        bool $canViewOtherMembersScores
    ) {
        $memberIds = $canViewOtherMembersScores
            ? $members->pluck('id')
            : ($viewer ? collect([(int) $viewer->id]) : collect());

        if ($memberIds->isEmpty()) {
            return collect();
        }

        return EvaluationScore::query()
            ->where('application_id', $application->id)
            ->whereIn('commission_member_id', $memberIds)
            ->with('commissionMember')
            ->get()
            ->keyBy(fn (EvaluationScore $score) => (int) $score->commission_member_id);
    }

    protected function abortIfCommissionProcessingBlocked(?\App\Models\Competition $competition): void
    {
        if ($competition && $competition->isCommissionProcessingBlocked()) {
            abort(403, \App\Models\Competition::COMMISSION_PROCESSING_BLOCKED_MESSAGE);
        }
    }

    protected function abortUnlessOmladinskoEqualScoreVoting(\App\Models\Competition $competition): void
    {
        if (! $competition->isOmladinskoProfile()) {
            abort(403, YouthEqualScoreVotingService::NOT_YOUTH_MESSAGE);
        }
    }

    protected function abortUnlessOmladinskoListConfirmation(\App\Models\Competition $competition): void
    {
        if (! $competition->isOmladinskoProfile()) {
            abort(403, YouthAllocationListConfirmationService::NOT_YOUTH_MESSAGE);
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
            if (ZenskoPreDeadlineContentGate::individualContentIsLocked($c)) {
                return false;
            }

            if (! in_array($c->status, ['closed', 'completed'])
                && ! $c->isApplicationDeadlinePassed()
                && ! $c->isCanonicalTestPreview()) {
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
            if ($this->canonicalScoring->isCanonicalCompetition($assignedCompetition)) {
                if ($competitionIds->contains($assignedCompetition->id)) {
                    $this->canonicalScoring->persistCanonicalPreliminaryRankingIfReady($assignedCompetition);
                }

                continue;
            }

            if ($assignedCompetition->type === 'zensko') {
                if ($competitionIds->contains($assignedCompetition->id)) {
                    $this->canonicalScoring->persistApplicationAggregatesIfCycleComplete($assignedCompetition);
                }

                continue;
            }

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
            ->filter(fn ($c) => ! $c->isOmladinskoProfile()
                && ! $this->canonicalScoring->isCanonicalCompetition($c)
                && $c->isRankingFormed()
                && !in_array($c->status, ['closed', 'completed']))
            ->values();

        $canViewFinalScoresByCompetition = $competitions
            ->mapWithKeys(fn ($c) => [$c->id => $this->canonicalScoring->isCanonicalCompetition($c)
                ? $this->canonicalScoring->canonicalRankingIsFormed($c)
                : ($c->type === 'zensko'
                    ? $this->canonicalScoring->zenskoCommissionDisclosureIsAllowed($c)
                    : $c->isIndividualScoringCycleComplete())])
            ->toArray();

        $youthPreliminaryByCompetition = $competitions
            ->filter(fn ($c) => $c->isOmladinskoProfile())
            ->mapWithKeys(fn ($c) => [$c->id => $this->canonicalScoring->youthPreliminaryRankingView($c)])
            ->all();

        $youthAllocationCanEditByCompetition = $competitions
            ->filter(fn ($c) => $c->isOmladinskoProfile())
            ->mapWithKeys(fn ($c) => [$c->id => $this->youthAllocationDrafts->canEditDraft($c, $user)])
            ->all();

        $youthEqualScoreVotingByCompetition = $competitions
            ->filter(fn ($c) => $c->isOmladinskoProfile())
            ->mapWithKeys(fn ($c) => [$c->id => $this->youthEqualScoreVoting->board($c, $user)])
            ->all();

        $youthAllocationConfirmationByCompetition = $competitions
            ->filter(fn ($c) => $c->isOmladinskoProfile())
            ->mapWithKeys(fn ($c) => [$c->id => $this->youthAllocationListConfirmation->board($c, $user)])
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
            'youthEqualScoreVotingByCompetition',
            'youthAllocationConfirmationByCompetition',
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
        if ($this->canonicalScoring->isCanonicalCompetition($application->competition)) {
            return $this->createCanonical($application);
        }

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

        if ($commissionMember
            && $application->competition
            && ZenskoPreDeadlineContentGate::individualContentIsLocked($application->competition)) {
            abort(403, ScoringProfileConfig::for($application->competition->type)->evaluationOpensAfterApplicationDeadlineMessage());
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
            
            // Ocjenjivanje počinje tek kada istekne rok za prijave
            if ($competition && !$competition->isApplicationDeadlinePassed() && !in_array($competition->status, ['closed', 'completed'])) {
                abort(403, ScoringProfileConfig::for($competition->type)->evaluationOpensAfterApplicationDeadlineMessage());
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
            $youthMutualViewAllowed = $commissionMember && $competition
                && $this->canonicalScoring->youthMutualScoreViewIsAllowed($competition);
            $canViewOtherMembersScores = $commissionMember !== null && $youthMutualViewAllowed;
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
            $canViewOtherMembersScores = $this->nonYouthCommissionDisclosureIsAllowed($competition);
            $allScores = $this->commissionScoresForDisclosure($application, $allMembers, $commissionMember, $canViewOtherMembersScores);
        } else {
            $commission = $application->competition->commission;
            $allMembers = $commission->members()
                ->where('status', 'active')
                ->orderByRaw("CASE WHEN position = 'predsjednik' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->get();
            $canViewOtherMembersScores = $this->nonYouthCommissionDisclosureIsAllowed($competition);
            $allScores = $this->commissionScoresForDisclosure($application, $allMembers, null, $canViewOtherMembersScores);
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
            $canViewOtherMembersScores = $this->nonYouthCommissionDisclosureIsAllowed($competition);
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

        $application->load(['user', 'competition', 'businessPlan', 'documents', 'eliminatoryCheck', 'eliminatoryNotice', 'prigovor']);

        $eliminatoryCheck = $application->eliminatoryCheck;
        $eliminatoryProfile = EliminatoryProfileConfig::for($application->competition?->type);
        $scoringIsAllowed = $this->eliminatoryChecks->scoringIsAllowed($application);
        $eliminatoryIsConfirmedFail = $this->eliminatoryChecks->isConfirmedFail($application);
        $eliminatoryNotice = $application->eliminatoryNotice;
        $prigovor = $application->prigovor;
        $canDecidePrigovor = $this->chairmanCanDecidePrigovor($commissionMember, $application, $prigovor);
        $canCorrectYouth107Mapping = $this->youthMappingCorrections->chairmanCanCorrect($commissionMember, $application, $prigovor);
        $youth107MappingCorrection = $this->youthMappingCorrections->recordedCorrection($application);
        $scoringLockedMessage = $isOmladinskoScoring
            ? ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE
            : ApplicationEliminatoryCheckService::SCORING_LOCKED_MESSAGE;
        $youthPlannedRegistrationBonusEligible = $isOmladinskoScoring
            && $this->canonicalScoring->youthQualifiesForPlannedRegistrationBonus($application);
        $youthBonusesLocked = $isOmladinskoScoring && $application->bonuses_confirmed_at !== null;

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
            'canCorrectYouth107Mapping',
            'youth107MappingCorrection',
            'scoringProfile',
            'isOmladinskoScoring',
            'scoringLockedMessage',
            'youthPlannedRegistrationBonusEligible',
            'youthBonusesLocked',
            'youthFinalScoreDisplay',
        ));
    }

    /**
     * Snimanje ocjene
     */
    public function store(Request $request, Application $application): RedirectResponse
    {
        $user = Auth::user();
        
        $competition = $application->competition;
        if ($this->canonicalScoring->isCanonicalCompetition($competition)) {
            return $this->storeCanonicalScore($request, $application);
        }

        if ($competition && ZenskoPreDeadlineContentGate::individualContentIsLocked($competition)) {
            return redirect()->back()
                ->withErrors(['error' => ScoringProfileConfig::for($competition->type)->evaluationOpensAfterApplicationDeadlineMessage()]);
        }

        ScoringProfileConfig::for($competition?->type)->assertScoringFormulaActive();
        
        // Ocjenjivanje počinje tek kada istekne rok za prijave
        if ($competition && !$competition->isApplicationDeadlinePassed() && !in_array($competition->status, ['closed', 'completed'])) {
            return redirect()->back()
                ->withErrors(['error' => ScoringProfileConfig::for($competition->type)->evaluationOpensAfterApplicationDeadlineMessage()]);
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
            if ($request->boolean('save_as_draft')) {
                return $this->storeYouthScore($request, $application, $commissionMember, $alreadyFinal);
            }

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
        $isChairman = $commissionMember->position === 'predsjednik';
        $bonusesLocked = $application->bonuses_confirmed_at !== null;

        if ($alreadyFinal) {
            if ($isChairman && ! $bonusesLocked) {
                $this->canonicalScoring->saveYouthBonuses(
                    $application,
                    $commissionMember,
                    $this->youthBonusFlagsFromRequest($request),
                    true,
                );

                return redirect()->route('evaluation.index', ['filter' => 'evaluated'])
                    ->with('success', 'Dodatni bodovi su potvrđeni i zaključani.');
            }

            abort(403, CanonicalIndividualScoringService::SCORE_IMMUTABLE_MESSAGE);
        }

        if ($request->boolean('save_as_draft')) {
            throw ValidationException::withMessages([
                'save_as_draft' => CanonicalIndividualScoringService::YOUTH_INDIVIDUAL_DRAFT_NOT_ALLOWED_MESSAGE,
            ]);
        }

        $messages = [
            'scoring_confirmed.accepted' => CanonicalIndividualScoringService::CONFIRMATION_REQUIRED_MESSAGE,
        ];
        $rules = [
            'notes' => 'nullable|string|max:5000',
            'scoring_confirmed' => 'accepted',
        ];

        for ($i = 1; $i <= 10; $i++) {
            $messages["criterion_{$i}.min"] = "Kriterijum {$i} mora biti najmanje 1 poen.";
            $messages["criterion_{$i}.max"] = "Kriterijum {$i} može biti najviše 5 poena.";
            $messages["criterion_{$i}.required"] = "Kriterijum {$i} je obavezan.";
            $rules["criterion_{$i}"] = 'required|integer|min:1|max:5';
        }

        $validated = $request->validate($rules, $messages);

        if ($isChairman && ! $bonusesLocked) {
            $this->canonicalScoring->recordYouthChairmanFinalScoreAndBonuses(
                $application,
                $commissionMember,
                $validated,
                $validated['notes'] ?? null,
                $request->boolean('scoring_confirmed'),
                $this->youthBonusFlagsFromRequest($request),
            );
        } else {
            $this->canonicalScoring->recordYouthFinalScore(
                $application,
                $commissionMember,
                $validated,
                $validated['notes'] ?? null,
                $request->boolean('scoring_confirmed'),
            );
        }

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
            'bonus_info_and_training' => 'sometimes|boolean',
            'bonus_info_day' => 'sometimes|boolean',
            'bonus_training' => 'sometimes|boolean',
            'bonus_new_business' => 'sometimes|boolean',
            'bonus_green_innovative' => 'sometimes|boolean',
        ]);

        $flags = $this->youthBonusFlagsFromRequest($request);

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

    protected function createCanonical(Application $application): View
    {
        $user = Auth::user();
        $competition = $application->competition;
        $governance = app(\App\Services\Competitions\Commission\CanonicalCommissionGovernance::class);

        $commissionMember = $governance->activeMemberFor($user, $competition);
        if ($commissionMember === null) {
            abort(403, 'Niste član komisije.');
        }

        if ($application->status === 'draft') {
            abort(403, 'Prijava još nije podnesena. Članovi komisije mogu vidjeti prijavu tek nakon što korisnik klikne na "Podnesi prijavu".');
        }

        if (! $competition->isCanonicalTestPreview()
            && ! $competition->isApplicationDeadlinePassed()
            && ! in_array($competition->status, ['closed', 'completed'], true)) {
            abort(403, ScoringProfileConfig::for($competition->type)->evaluationOpensAfterApplicationDeadlineMessage());
        }

        $this->abortIfCommissionProcessingBlocked($competition);

        $this->canonicalScoring->persistCanonicalPreliminaryRankingIfReady($competition);

        $scoringProfile = ScoringProfileConfig::for($competition->type);
        $rankingFormed = $this->canonicalScoring->canonicalRankingIsFormed($competition);
        $visibleScores = $governance->visibleScores($user, $application, $rankingFormed)->keyBy('commission_member_id');
        $ownScore = $visibleScores->get($commissionMember->id);
        $scoreBlockReason = $ownScore === null
            ? $this->canonicalScoring->canonicalScoreWriteBlockReason($user, $application)
            : null;
        $aggregate = $rankingFormed ? $this->canonicalScoring->aggregateCanonicalApplication($application) : null;
        $members = $rankingFormed
            ? \App\Models\CommissionMember::query()->whereIn('id', $visibleScores->keys())->orderBy('canonical_seat_no')->get()
            : collect([$commissionMember]);
        $rankingView = $this->canonicalScoring->canonicalPreliminaryRankingView($competition, $user);

        $application->load(['user', 'competition']);

        return view('evaluation.canonical', compact(
            'application',
            'competition',
            'commissionMember',
            'scoringProfile',
            'rankingFormed',
            'visibleScores',
            'ownScore',
            'scoreBlockReason',
            'aggregate',
            'members',
            'rankingView',
        ));
    }

    protected function storeCanonicalScore(Request $request, Application $application): RedirectResponse
    {
        $application->loadMissing('competition');
        $competition = $application->competition;
        $profile = ScoringProfileConfig::for($competition?->type);
        $profile->assertScoringFormulaActive();
        if ($competition
            && ! $competition->isCanonicalTestPreview()
            && ! $competition->isApplicationDeadlinePassed()
            && ! in_array($competition->status, ['closed', 'completed'], true)) {
            abort(403, $profile->evaluationOpensAfterApplicationDeadlineMessage());
        }
        $this->abortIfCommissionProcessingBlocked($competition);

        $rules = [
            'notes' => 'nullable|string|max:5000',
            'scoring_confirmed' => 'accepted',
        ];
        $messages = [
            'scoring_confirmed.accepted' => CanonicalIndividualScoringService::CONFIRMATION_REQUIRED_MESSAGE,
        ];
        foreach (array_keys($profile->criteria) as $criterion) {
            $rules["criterion_{$criterion}"] = 'required|integer|min:1|max:5';
            $messages["criterion_{$criterion}.required"] = "Kriterijum {$criterion} je obavezan.";
            $messages["criterion_{$criterion}.min"] = "Kriterijum {$criterion} mora biti najmanje 1 poen.";
            $messages["criterion_{$criterion}.max"] = "Kriterijum {$criterion} može biti najviše 5 poena.";
        }

        $reason = $this->canonicalScoring->canonicalScoreWriteBlockReason(Auth::user(), $application);
        if ($reason !== null) {
            abort(403, $reason);
        }

        $validated = $request->validate($rules, $messages);

        $this->canonicalScoring->recordCanonicalFinalScore(
            $application,
            Auth::user(),
            $validated,
            $validated['notes'] ?? null,
            $request->boolean('scoring_confirmed'),
        );

        return redirect()->route('evaluation.create', $application)
            ->with('success', 'Ocjena je uspješno sačuvana i zaključana.');
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
     * @return array<string, bool>
     */
    protected function youthBonusFlagsFromRequest(Request $request): array
    {
        $flags = [];
        foreach (CanonicalIndividualScoringService::YOUTH_BONUS_FLAG_KEYS as $key) {
            $flags[$key] = $request->boolean($key);
        }

        if ($request->exists('bonus_info_and_training')) {
            $attendance = $request->boolean('bonus_info_and_training');
            $flags['bonus_info_day'] = $attendance;
            $flags['bonus_training'] = $attendance;
        }

        return $flags;
    }

    /**
     * Prikaz ocjene (read-only)
     */
    public function show(Application $application): View|\Illuminate\Http\RedirectResponse
    {
        if ($this->canonicalScoring->isCanonicalCompetition($application->competition)) {
            return redirect()->route('evaluation.create', $application);
        }

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
            $youthMutualViewAllowed = $commissionMember && $competition
                && $this->canonicalScoring->youthMutualScoreViewIsAllowed($competition);
            $canViewOtherMembersScores = $commissionMember !== null && $youthMutualViewAllowed;
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
            $canViewOtherMembersScores = $this->nonYouthCommissionDisclosureIsAllowed($competition);
            $allScores = $this->commissionScoresForDisclosure($application, $allMembers, $commissionMember, $canViewOtherMembersScores);
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
        $canCorrectYouth107Mapping = $this->youthMappingCorrections->chairmanCanCorrect($commissionMember, $application, $prigovor);
        $youth107MappingCorrection = $this->youthMappingCorrections->recordedCorrection($application);

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
            'canCorrectYouth107Mapping',
            'youth107MappingCorrection',
            'scoringProfile',
            'isOmladinskoScoring',
            'youthFinalScoreDisplay',
        ));
    }

    /**
     * Preliminarna rang-lista jednog omladinskog poziva.
     * Isti podaci i isti idempotentni upis pozicija kao na postojećem prikazu.
     */
    public function youthRanking(Competition $competition): View
    {
        if (! $competition->isOmladinskoProfile()) {
            abort(403, 'Preliminarna rang-lista mladih dostupna je samo za omladinski Poziv.');
        }

        $user = Auth::user();
        if (CommissionMember::activeForCompetition($user->id, $competition) === null) {
            abort(403, 'Niste član komisije tog Poziva.');
        }

        if (! $competition->isApplicationDeadlinePassed() && ! in_array($competition->status, ['closed', 'completed'], true)) {
            abort(403, ScoringProfileConfig::for($competition->type)->evaluationOpensAfterApplicationDeadlineMessage());
        }

        $this->abortIfCommissionProcessingBlocked($competition);

        $this->canonicalScoring->persistYouthPreliminaryRankingIfReady($competition);
        $competition->refresh();

        $decisionGate = app(YouthDecisionProposalGate::class);
        $canGenerateYouthDecision = $decisionGate->denyReason($competition, $user) === null;

        return view('evaluation.youth-ranking', [
            'youthRankingView' => $this->canonicalScoring->youthPreliminaryRankingView($competition),
            'youthRankingTitle' => $competition->title,
            'canEditYouthAllocationDraft' => $this->youthAllocationDrafts->canEditDraft($competition, $user),
            'youthEqualScoreVotingBoard' => $this->youthEqualScoreVoting->board($competition, $user),
            'youthEqualScoreCompetition' => $competition,
            'canGenerateYouthDecision' => $canGenerateYouthDecision,
            'youthRankingApplicantReadOnly' => false,
        ]);
    }

    /**
     * Čitanje konačne PM rang-liste za učesnika zatvorenog konkursa.
     * Ne upisuje rang i ne otvara komisijske radnje.
     */
    public function youthFinalRankingForApplicant(Competition $competition): View
    {
        $user = Auth::user();
        if (! $user instanceof User || ! $this->applicantMayReadYouthFinalRanking($competition, $user)) {
            abort(403, 'Konačna rang-lista Komisije nije dostupna.');
        }

        $application = $competition->applications()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->orderBy('id')
            ->first();

        return view('evaluation.youth-ranking', [
            'youthRankingView' => $this->canonicalScoring->youthPreliminaryRankingView($competition),
            'youthRankingTitle' => $competition->title,
            'canEditYouthAllocationDraft' => false,
            'youthEqualScoreVotingBoard' => null,
            'youthEqualScoreCompetition' => $competition,
            'canGenerateYouthDecision' => false,
            'youthRankingApplicantReadOnly' => true,
            'youthRankingReturnApplication' => $application,
        ]);
    }

    public function applicantMayReadYouthFinalRanking(Competition $competition, User $user): bool
    {
        if (! $competition->isOmladinskoProfile() || $competition->status !== 'completed') {
            return false;
        }

        $participates = $competition->applications()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->exists();
        if (! $participates || ! $competition->hasChairmanCompletedDecisions()) {
            return false;
        }

        try {
            return app(YouthDecisionDocumentStore::class)->latestValidDocument($competition) !== null;
        } catch (YouthDecisionDocumentStoreException) {
            return false;
        }
    }

    /**
     * PM stranica odluke i zatvaranja. Ista ovlašćenja kao rang-lista za uvid,
     * a Word i zatvaranje samo kada postojeći POST to već dozvoljava predsjedniku.
     */
    public function youthDecision(Competition $competition): View
    {
        if (! $competition->isOmladinskoProfile()) {
            abort(403, 'Odluka mladih dostupna je samo za omladinski Poziv.');
        }

        $user = Auth::user();
        $member = CommissionMember::activeForCompetition($user->id, $competition);
        if ($member === null) {
            abort(403, 'Niste član komisije tog Poziva.');
        }

        if (! $competition->isApplicationDeadlinePassed() && ! in_array($competition->status, ['closed', 'completed'], true)) {
            abort(403, ScoringProfileConfig::for($competition->type)->evaluationOpensAfterApplicationDeadlineMessage());
        }

        $this->abortIfCommissionProcessingBlocked($competition);

        $this->canonicalScoring->persistYouthPreliminaryRankingIfReady($competition);
        $competition->refresh();

        $decisionGate = app(YouthDecisionProposalGate::class);
        $canGenerateYouthDecision = $decisionGate->denyReason($competition, $user) === null;
        $isChairman = $member->position === 'predsjednik';
        $existingCloseConditions = $isChairman
            && ! in_array($competition->status, ['closed', 'completed'], true)
            && $competition->hasChairmanCompletedDecisions();
        $validYouthDecision = null;
        if ($isChairman) {
            try {
                $validYouthDecision = app(YouthDecisionDocumentStore::class)->latestValidDocument($competition);
            } catch (YouthDecisionDocumentStoreException) {
                $validYouthDecision = null;
            }
        }
        $canCloseYouthCompetition = $existingCloseConditions && $validYouthDecision !== null;
        $youthCloseBlockedByDocument = $existingCloseConditions && $validYouthDecision === null;
        $canDownloadYouthDecision = $isChairman && $validYouthDecision !== null;

        return view('evaluation.youth-decision', [
            'competition' => $competition,
            'youthAllocationConfirmationBoard' => $this->youthAllocationListConfirmation->board($competition, $user),
            'canGenerateYouthDecision' => $canGenerateYouthDecision,
            'canDownloadYouthDecision' => $canDownloadYouthDecision,
            'canCloseYouthCompetition' => $canCloseYouthCompetition,
            'youthCloseBlockedByDocument' => $youthCloseBlockedByDocument,
            'youthCloseDocumentMessage' => YouthDecisionDocumentStore::CLOSE_BLOCKED,
            'youthDecisionDates' => $canGenerateYouthDecision
                ? app(YouthDecisionSessionDates::class)->forCompetition($competition)
                : ['first' => null, 'second' => null, 'third' => null, 'oral' => null],
        ]);
    }

    /**
     * Preuzima posljednji ispravan trajno sačuvani PM Word dokument bez ponovnog generisanja.
     */
    public function downloadYouthDecision(Competition $competition, YouthDecisionDocumentStore $store): Response
    {
        $user = Auth::user();
        if (! $user instanceof User || ! $this->isActiveYouthDecisionChairman($competition, $user)) {
            abort(403, 'Samo aktivni predsjednik Komisije može preuzeti odluku.');
        }

        try {
            $document = $store->latestValidDocument($competition);
        } catch (YouthDecisionDocumentStoreException $exception) {
            if ($exception->getMessage() === YouthDecisionDocumentStore::HASH_MISMATCH) {
                abort(409, YouthDecisionDocumentStore::HASH_MISMATCH);
            }

            abort(404);
        }

        if ($document === null) {
            abort(404);
        }

        try {
            $absolute = $store->verifiedDownloadPath($competition, $document);
        } catch (YouthDecisionDocumentStoreException $exception) {
            if ($exception->getMessage() === YouthDecisionDocumentStore::HASH_MISMATCH) {
                abort(409, YouthDecisionDocumentStore::HASH_MISMATCH);
            }

            abort(404);
        }

        return response()->download($absolute, $this->youthDecisionDownloadName($competition), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    private function isActiveYouthDecisionChairman(Competition $competition, User $user): bool
    {
        if (! $competition->isOmladinskoProfile() || ! $competition->commission_id) {
            return false;
        }

        $member = CommissionMember::activeForCommission((int) $user->id, (int) $competition->commission_id);

        return $member !== null && $member->position === 'predsjednik' && $member->status === 'active';
    }

    private function youthDecisionDownloadName(Competition $competition): string
    {
        return 'Odluka-o-raspodjeli-sredstava-mladi-'.$competition->year.'-'.$competition->id.'.docx';
    }

    /**
     * Generiše PM Word odluku, trajno je čuva i vraća predsjednika na ekran odluke.
     * Ne upisuje datume, iznose ni zaključke i ne zatvara konkurs.
     * Preuzimanje je posebna radnja nad već sačuvanim dokumentom.
     */
    public function generateYouthDecision(
        Request $request,
        Competition $competition,
        YouthDecisionProposalGate $gate,
        YouthDecisionProposalBuilder $builder,
        YouthDecisionDocxWriter $writer,
        YouthDecisionDocumentStore $store,
        YouthDecisionRankingIdentity $ranking,
    ): RedirectResponse {
        $user = Auth::user();
        $reason = $gate->denyReason($competition, $user);
        if ($reason !== null || ! $user instanceof User) {
            abort(403, $reason ?? YouthDecisionProposalGate::CHAIRMAN_ONLY_MESSAGE);
        }

        $validated = $request->validate([
            'first_session_date' => ['required', 'date_format:Y-m-d'],
            'second_session_date' => ['required', 'date_format:Y-m-d'],
            'third_session_date' => ['required', 'date_format:Y-m-d'],
        ], [
            'first_session_date.required' => 'Unesite datum prve sjednice.',
            'second_session_date.required' => 'Unesite datum druge sjednice.',
            'third_session_date.required' => 'Unesite datum treće sjednice.',
            'first_session_date.date_format' => 'Datum prve sjednice nije ispravan.',
            'second_session_date.date_format' => 'Datum druge sjednice nije ispravan.',
            'third_session_date.date_format' => 'Datum treće sjednice nije ispravan.',
        ]);

        $temporaryPath = null;
        $storedPath = null;

        try {
            $document = DB::transaction(function () use (
                $competition,
                $user,
                $gate,
                $builder,
                $writer,
                $store,
                $ranking,
                $validated,
                &$temporaryPath,
                &$storedPath,
            ) {
                $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->first();
                if ($locked === null) {
                    abort(404);
                }

                $lockedReason = $gate->denyReason($locked, $user);
                if ($lockedReason !== null) {
                    abort(403, $lockedReason);
                }

                $before = $ranking->rankingSha256($locked);
                $proposal = $builder->build($locked, $validated, now());
                $temporaryPath = $writer->write($proposal);
                $after = $ranking->rankingSha256($locked);
                if (! hash_equals($before, $after)) {
                    throw ValidationException::withMessages([
                        'decision' => 'Rang-lista je izmijenjena tokom generisanja. Odluka nije sačuvana.',
                    ]);
                }

                $document = $store->store($locked, $user, $temporaryPath);
                $storedPath = $document->storage_path;
                if (! hash_equals($before, $document->ranking_sha256)) {
                    throw ValidationException::withMessages([
                        'decision' => 'Rang-lista je izmijenjena tokom generisanja. Odluka nije sačuvana.',
                    ]);
                }

                return $document;
            });
        } catch (HttpException|ValidationException $exception) {
            $this->discardFailedYouthDecisionFiles($temporaryPath, $storedPath);
            throw $exception;
        } catch (YouthDecisionDocumentStoreException $exception) {
            $this->discardFailedYouthDecisionFiles($temporaryPath, $storedPath);

            return back()->withErrors([
                'decision' => $this->publicYouthDecisionMessage($exception->getMessage()),
            ])->withInput();
        } catch (Throwable $exception) {
            $this->discardFailedYouthDecisionFiles($temporaryPath, $storedPath);

            return back()->withErrors([
                'decision' => 'Odluka nije generisana i nije trajno sačuvana.',
            ])->withInput();
        }

        if (is_string($temporaryPath) && is_file($temporaryPath)) {
            @unlink($temporaryPath);
        }

        $absolute = Storage::disk('local')->path($document->storage_path);
        if (! is_file($absolute)) {
            return back()->withErrors([
                'decision' => 'Odluka nije generisana i nije trajno sačuvana.',
            ])->withInput();
        }

        return redirect()
            ->route('evaluation.youth-decision', $competition)
            ->with('success', self::YOUTH_DECISION_GENERATED_MESSAGE);
    }

    private function discardFailedYouthDecisionFiles(?string $temporaryPath, ?string $storedPath): void
    {
        try {
            $this->removeTemporaryYouthDecisionFile($temporaryPath);
            $this->removeUnreferencedYouthDecisionFile($storedPath);
        } catch (Throwable) {
            $this->logYouthDecisionCleanup('cleanup_failed');
        }
    }

    private function removeTemporaryYouthDecisionFile(?string $temporaryPath): void
    {
        if (! is_string($temporaryPath) || $temporaryPath === '' || is_link($temporaryPath) || ! is_file($temporaryPath)) {
            return;
        }

        try {
            $deleted = unlink($temporaryPath);
        } catch (Throwable) {
            $deleted = false;
        }
        clearstatcache(true, $temporaryPath);
        if ($deleted !== true || is_file($temporaryPath)) {
            $this->logYouthDecisionCleanup('temporary_delete_failed');
        }
    }

    private function removeUnreferencedYouthDecisionFile(?string $storedPath): void
    {
        if (! is_string($storedPath) || $storedPath === '') {
            return;
        }

        if (preg_match(
            '#^youth-decision-documents/([0-9]+)/([0-9A-HJKMNP-TV-Z]{26})\.docx$#',
            $storedPath,
            $matches
        ) !== 1) {
            $this->logYouthDecisionCleanup('path_not_allowed');

            return;
        }

        $competitionId = (int) $matches[1];
        $documentId = $matches[2];

        try {
            $referenced = YouthDecisionDocument::query()->where('storage_path', $storedPath)->exists();
        } catch (Throwable) {
            $this->logYouthDecisionCleanup('reference_check_failed', $competitionId, $documentId);

            return;
        }

        if ($referenced) {
            return;
        }

        $absolute = Storage::disk('local')->path($storedPath);
        if (is_link($absolute)) {
            $this->logYouthDecisionCleanup('path_not_allowed', $competitionId, $documentId);

            return;
        }

        if (! is_file($absolute)) {
            return;
        }

        $real = realpath($absolute);
        $root = realpath(storage_path('app/private').DIRECTORY_SEPARATOR.'youth-decision-documents');
        if ($real === false || $root === false || ! $this->youthDecisionFileIsInsideRoot($real, $root)) {
            $this->logYouthDecisionCleanup('path_not_allowed', $competitionId, $documentId);

            return;
        }

        try {
            $deleted = unlink($real);
        } catch (Throwable) {
            $deleted = false;
        }
        clearstatcache(true, $real);
        if ($deleted !== true || is_file($real)) {
            $this->logYouthDecisionCleanup('delete_failed', $competitionId, $documentId);
        }
    }

    private function youthDecisionFileIsInsideRoot(string $real, string $root): bool
    {
        $realPath = strtolower(str_replace('\\', '/', $real));
        $rootPath = rtrim(strtolower(str_replace('\\', '/', $root)), '/');

        return $realPath !== $rootPath && str_starts_with($realPath, $rootPath.'/');
    }

    private function logYouthDecisionCleanup(string $outcome, ?int $competitionId = null, ?string $documentId = null): void
    {
        $context = [
            'operation' => 'youth_decision_cleanup',
            'outcome' => $outcome,
        ];
        if ($competitionId !== null) {
            $context['competition_id'] = $competitionId;
        }
        if (is_string($documentId) && preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $documentId) === 1) {
            $context['document_id'] = $documentId;
        }

        try {
            Log::warning('Čišćenje PM odluke nije završeno.', $context);
        } catch (Throwable) {
        }
    }

    private function publicYouthDecisionMessage(string $message): string
    {
        $allowed = [
            YouthDecisionDocumentStore::DISK_UNAVAILABLE,
            YouthDecisionDocumentStore::UNREADABLE,
            YouthDecisionDocumentStore::EMPTY_DOCUMENT,
            YouthDecisionDocumentStore::NOT_YOUTH,
            YouthDecisionDocumentStore::NOT_CHAIRMAN,
            YouthDecisionDocumentStore::WRITE_FAILED,
            YouthDecisionDocumentStore::NOT_OVERWRITTEN,
            YouthDecisionDocumentStore::HASH_MISMATCH,
            YouthDecisionDocumentStore::NOT_STORED,
        ];

        return in_array($message, $allowed, true)
            ? $message
            : 'Odluka nije generisana i nije trajno sačuvana.';
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
        ], [
            'commission_decision.required' => 'Morate odabrati zaključak komisije.',
        ]);

        $this->youthAllocationDrafts->saveDraft($application, Auth::user(), $validated);

        return redirect()->back()->with('success', 'Zaključak Komisije je uspješno sačuvan.');
    }

    /**
     * Elektronsko glasanje je ukinuto. Ruta ostaje radi odbijanja starog zahtjeva, bez upisa.
     */
    public function storeYouthEqualScoreRound(Request $request, \App\Models\Competition $competition): RedirectResponse
    {
        $this->abortUnlessOmladinskoEqualScoreVoting($competition);
        unset($request);

        abort(403, YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE);
    }

    /**
     * Elektronsko glasanje je ukinuto. Ruta ostaje radi odbijanja starog zahtjeva, bez upisa.
     */
    public function lockYouthEqualScoreRound(
        Request $request,
        \App\Models\Competition $competition,
        \App\Models\YouthEqualScoreRound $round,
    ): RedirectResponse {
        $this->abortUnlessOmladinskoEqualScoreVoting($competition);
        unset($request, $round);

        abort(403, YouthEqualScoreVotingService::VOTING_RETIRED_MESSAGE);
    }

    /**
     * Stara potvrda cijele liste je isključena. Ne upisuje odluke, iznose ni statuse.
     */
    public function confirmYouthAllocationList(Request $request, \App\Models\Competition $competition): RedirectResponse
    {
        $this->abortUnlessOmladinskoListConfirmation($competition);
        $this->abortIfCommissionProcessingBlocked($competition);

        $this->youthAllocationListConfirmation->confirm($competition, Auth::user(), $request->all());

        return redirect()->back();
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
        if ($this->canonicalScoring->isCanonicalCompetition($application->competition)) {
            abort(403, CanonicalIndividualScoringService::CANONICAL_OUTCOME_NOT_ACTIVE_MESSAGE);
        }
        ScoringProfileConfig::for($application->competition?->type)->assertScoringFormulaActive();

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
        if ($competition) {
            $resultsLock = $this->canonicalScoring->commissionPreliminaryResultsLockMessage($competition);
            if ($resultsLock !== null) {
                abort(403, $resultsLock);
            }
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
        if ($competition && ! $competition->isOmladinskoProfile() && $competition->isEvaluationDeadlinePassed()) {
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

        if ($competition && $competition->type === 'zensko') {
            $this->zenskoDecisions->store($application, [
                'commission_decision' => $decision,
                'commission_justification' => $justification,
                'commission_notes' => $validated['commission_notes'] ?? null,
                'approved_amount' => $approvedAmount,
            ]);

            return redirect()->route('admin.competitions.ranking', $application->competition)
                ->with('success', 'Zaključak komisije je uspješno sačuvan.');
        }

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

    public const YOUTH_SIGN_LOCKED_MESSAGE = 'Poziv je zaključen. Potpisivanje zaključka više nije dozvoljeno.';

    /**
     * Potpisivanje odluke od strane člana komisije
     */
    public function signDecision(Application $application): RedirectResponse
    {
        $application->loadMissing('competition');
        if ($this->canonicalScoring->isCanonicalCompetition($application->competition)) {
            abort(403, CanonicalIndividualScoringService::CANONICAL_OUTCOME_NOT_ACTIVE_MESSAGE);
        }

        $user = Auth::user();
        
        // Pronađi člana komisije
        $commissionMember = $this->commissionMemberForApplication($application, $user->id);

        if (!$commissionMember) {
            abort(403, 'Niste član komisije.');
        }

        $this->abortIfCommissionProcessingBlocked($application->competition);

        $competition = $application->competition;
        if ($this->youthSigningIsClosed($competition)) {
            abort(403, self::YOUTH_SIGN_LOCKED_MESSAGE);
        }
        if ($competition) {
            $resultsLock = $this->canonicalScoring->commissionPreliminaryResultsLockMessage($competition);
            if ($resultsLock !== null) {
                abort(403, $resultsLock);
            }
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

        DB::transaction(function () use ($application, $commissionMember) {
            $lockedCompetition = Competition::query()
                ->whereKey($application->competition_id)
                ->lockForUpdate()
                ->first();
            $lockedApplication = Application::query()
                ->whereKey($application->id)
                ->lockForUpdate()
                ->first();
            if ($lockedApplication === null) {
                abort(404);
            }
            if ($this->youthSigningIsClosed($lockedCompetition)) {
                abort(403, self::YOUTH_SIGN_LOCKED_MESSAGE);
            }
            if (! $lockedApplication->signed_by_chairman) {
                abort(403, 'Predsjednik komisije mora prvo donijeti zaključak.');
            }

            $signedMembers = $lockedApplication->signed_by_members ?? [];
            if (! in_array($commissionMember->id, $signedMembers)) {
                $signedMembers[] = $commissionMember->id;
                $lockedApplication->update(['signed_by_members' => $signedMembers]);
            }
        });

        return back()->with('success', 'Odluka je uspješno potpisana.');
    }

    private function youthSigningIsClosed(?Competition $competition): bool
    {
        return $competition?->isOmladinskoProfile() === true
            && $competition->status === 'completed';
    }

    public function storeEliminatory(Request $request, Application $application): RedirectResponse
    {
        $chairman = $this->chairmanForEliminatoryMutation($application);

        $answers = $this->validatedEliminatoryAnswers($request, $application, requireNotesIfFail: false);
        $this->eliminatoryChecks->saveDraft($application, $chairman, $answers);

        $formName = $application->competition?->isOmladinskoProfile() ? 'Obrazac M3' : 'Obrazac 3';

        return redirect()->route('evaluation.create', $application)
            ->with('success', 'Eliminatorna provjera je sačuvana. '.$formName.' još nije potvrđen.');
    }

    public function confirmEliminatory(Request $request, Application $application): RedirectResponse
    {
        $chairman = $this->chairmanForEliminatoryMutation($application);

        $answers = $this->validatedEliminatoryAnswers($request, $application, requireNotesIfFail: true);
        $acknowledgement = $request->boolean('confirmation_acknowledged');

        $this->eliminatoryChecks->confirm($application, $chairman, $answers, $acknowledgement);

        $formName = $application->competition?->isOmladinskoProfile() ? 'Obrazac M3' : 'Obrazac 3';

        return redirect()->route('evaluation.create', $application)
            ->with('success', $formName.' je potvrđen.');
    }

    public function decidePrigovor(Request $request, Application $application): RedirectResponse
    {
        $chairman = $this->chairmanForEliminatoryMutation($application);
        $application->loadMissing('competition');
        $isYouth = $application->competition?->isOmladinskoProfile() === true;

        $rules = [
            'decision_note' => 'required|string|max:5000',
            'odluka' => 'required|in:prihvacen,odbijen',
        ];
        if (! $isYouth) {
            $rules['criterion_outcomes'] = 'nullable|array';
            $rules['criterion_outcomes.1'] = 'nullable|in:otklonjen,ostaje';
            $rules['criterion_outcomes.2'] = 'nullable|in:otklonjen,ostaje';
            $rules['criterion_outcomes.3'] = 'nullable|in:otklonjen,ostaje';
        }

        $validated = $request->validate($rules);

        $prigovor = $this->prigovors->decide(
            $application,
            $chairman,
            $validated['odluka'] ?? '',
            $validated['decision_note'],
            $validated['criterion_outcomes'] ?? [],
        );

        if ($isYouth && $prigovor->isRejected()) {
            return redirect()->route('evaluation.index', [
                'competition_id' => $application->competition_id,
                'filter' => 'rejected',
            ])->with('success', 'Prigovor je odbijen. Odluka Komisije je evidentirana.');
        }

        if ($isYouth && $prigovor->isAccepted()) {
            return redirect()->route('evaluation.index', [
                'competition_id' => $application->competition_id,
            ])->with('success', 'Prigovor je prihvaćen. Prijava može nastaviti postupak.');
        }

        return redirect()->route('evaluation.create', $application)
            ->with('success', 'Odluka Komisije o Prigovoru je evidentirana.');
    }

    public function correctYouth107PrigovorMapping(Request $request, Application $application): RedirectResponse
    {
        $chairman = $this->chairmanForEliminatoryMutation($application);
        $validated = $request->validate([
            'correction_reason' => 'required|string|max:5000',
        ], [
            'correction_reason.required' => YouthPrigovorMappingCorrectionService::REASON_REQUIRED_MESSAGE,
        ]);

        $this->youthMappingCorrections->correct($application, $chairman, $validated['correction_reason']);

        return redirect()->route('evaluation.create', $application)
            ->with('success', 'Korekcija mapiranja Prigovora je evidentirana.');
    }

    protected function chairmanForEliminatoryMutation(Application $application): CommissionMember
    {
        $user = Auth::user();
        $competition = $application->competition;

        LegacyCommissionFlowGuard::abortUnlessAllowed($competition);

        if ($competition && ! $competition->isApplicationDeadlinePassed() && ! in_array($competition->status, ['closed', 'completed'], true)) {
            abort(403, ScoringProfileConfig::for($competition->type)->evaluationOpensAfterApplicationDeadlineMessage());
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

            if ($this->youthMappingCorrections->isPendingUncorrected($application)) {
                return false;
            }

            return $application->eliminatoryCheck->hasAnyFailCriterion();
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
                'note' => 'nullable|string|max:5000',
                'criterion_notes' => 'nullable|array',
                'criterion_notes.1' => 'nullable|string|max:2000',
                'criterion_notes.2' => 'nullable|string|max:2000',
                'criterion_notes.3' => 'nullable|string|max:2000',
            ]);

            $passedByNumber = [1 => $criterion1, 2 => $criterion2, 3 => $criterion3];

            if ($request->exists('note')) {
                $composedNote = EliminatoryProfileConfig::composeYouthNotesFromShared(
                    (string) $request->input('note', ''),
                    $passedByNumber
                );
            } else {
                $composedNote = EliminatoryProfileConfig::composeYouthNotes([
                    1 => trim((string) $request->input('criterion_notes.1', '')),
                    2 => trim((string) $request->input('criterion_notes.2', '')),
                    3 => trim((string) $request->input('criterion_notes.3', '')),
                ]);
            }

            if ($requireNotesIfFail && (! $criterion1 || ! $criterion2 || ! $criterion3)
                && EliminatoryProfileConfig::youthSharedNote($composedNote) === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'note' => EliminatoryProfileConfig::NOTE_REQUIRED_ON_FAIL_MESSAGE,
                ]);
            }

            return [
                'criterion_1' => $criterion1,
                'criterion_2' => $criterion2,
                'criterion_3' => $criterion3,
                'note' => $composedNote,
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
                'note' => EliminatoryProfileConfig::NOTE_REQUIRED_ON_FAIL_MESSAGE,
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
