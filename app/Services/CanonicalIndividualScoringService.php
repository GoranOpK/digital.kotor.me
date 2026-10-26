<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationCompletenessAssessment;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\User;
use App\Services\Competitions\CanonicalReviewState;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Services\Competitions\YouthAllocationListConfirmationService;
use App\Support\CommissionCanonicalSeat;
use App\Support\CompetitionRanking;
use App\Support\NamedMysqlUniqueViolation;
use App\Support\ScoringProfileConfig;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CanonicalIndividualScoringService
{
    public const SEAT_ALREADY_FINAL_MESSAGE = 'Kanonsko mjesto Komisije već ima konačnu ocjenu za ovu prijavu. Ponovno bodovanje nije dozvoljeno.';

    public const SCORE_IMMUTABLE_MESSAGE = 'Individualno bodovanje je završeno i ne može se mijenjati.';

    public const CONFIRMATION_REQUIRED_MESSAGE = 'Prije završetka bodovanja morate potvrditi da je ocjena konačna.';

    public const YOUTH_INDIVIDUAL_DRAFT_NOT_ALLOWED_MESSAGE = 'Privremeno čuvanje nacrta individualne ocjene nije dozvoljeno. Unesite svih 10 kriterijuma i sačuvajte konačne ocjene.';

    public const INVALID_SEAT_MESSAGE = 'Ne može se utvrditi kanonsko mjesto Komisije za bodovanje.';

    public const RANKING_LOCKED_MESSAGE = 'Rang lista i zbirni rezultati dostupni su tek kada svih pet kanonskih mjesta Komisije završi individualno bodovanje svih prijava koje su ušle u bodovanje.';

    public const YOUTH_ADMIN_RANKING_ROUTE_MESSAGE = 'Preliminarna rang-lista mladih dostupna je Komisiji na ekranu ocjenjivanja, ne na administratorskoj ruti.';

    public const YOUTH_CYCLE_INCOMPLETE_MESSAGE = 'Nijesu završene sve individualne ocjene prijava u ciklusu.';

    public const YOUTH_RANKING_APPEAL_WINDOW_MESSAGE = 'Rok za prigovor je otvoren.';

    public const YOUTH_RANKING_PRIGOVOR_PODNESEN_MESSAGE = 'Postoji neriješen prigovor.';

    public const YOUTH_RANKING_BONUSES_UNCONFIRMED_MESSAGE = 'Bonusi nijesu potvrđeni.';

    public const YOUTH_RANKING_FINAL_SCORE_MISSING_MESSAGE = 'Konačni rezultat nije obračunat.';

    public const YOUTH_CLOSE_RANKING_NOT_READY_MESSAGE = 'Poziv mladih se ne može zatvoriti dok preliminarna rang-lista nije trajno formirana.';

    public const YOUTH_CLOSE_LIST_NOT_CONFIRMED_MESSAGE = 'Poziv mladih se ne može zatvoriti dok konačna lista raspodjele nije potvrđena.';

    public const YOUTH_CLOSE_POSITIONS_MISMATCH_MESSAGE = 'Poziv mladih se ne može zatvoriti dok rang-pozicije nijesu usklađene sa potvrđenom listom.';

    public const PARTIAL_ROW_MESSAGE = 'Pronađen je nepotpun istorijski red ocjena za ovo kanonsko mjesto. Konačno bodovanje je zaustavljeno do pregleda. Red se ne smije prepisati niti dopuniti.';

    public const BONUS_LOCKED_MESSAGE = 'Dodatni bodovi su trajno zaključani. Izmjena nije dozvoljena nakon završetka cjelokupnog ciklusa individualnog bodovanja.';

    public const YOUTH_BONUS_LOCKED_MESSAGE = 'Dodatni bodovi mladih su trajno zaključani. Izmjena nije dozvoljena.';

    public const YOUTH_BONUS_CHAIRMAN_REQUIRED_MESSAGE = 'Dodatne bodove mladih evidentira samo aktivni predsjednik Komisije konkretnog Poziva.';

    public const YOUTH_BONUS_NEW_BUSINESS_INVALID_MESSAGE = 'Dodatni bod za planiranu registraciju može se evidentirati samo za fizičko lice koje planira registraciju.';

    public const YOUTH_BONUS_SCALE = 10;

    public const CANONICAL_SUPPORT_THRESHOLD = '30';

    public const CANONICAL_CYCLE_INCOMPLETE_MESSAGE = 'Bodovanje kanonskog Poziva nije završeno: sva 3 člana Komisije moraju konačno ocijeniti sve prijave koje su ušle u bodovanje.';

    public const CANONICAL_REVIEW_UNRESOLVED_MESSAGE = 'Preliminarna rang-lista se ne formira dok potpunost prijava i prigovori nijesu riješeni.';

    public const CANONICAL_CORRUPT_ROW_MESSAGE = 'Pronađen je neispravan red ocjena kanonskog Poziva. Bodovanje i rang-lista su zaustavljeni do pregleda. Red se ne smije prepisati niti dopuniti.';

    public const CANONICAL_RANKING_FROZEN_MESSAGE = 'Preliminarna rang-lista kanonskog Poziva je formirana i zaključana. Bodovanje više nije dozvoljeno.';

    public const CANONICAL_OUTCOME_NOT_ACTIVE_MESSAGE = 'Ishod kanonskog Poziva (podržava/odbija, iznos, Predlog) utvrđuje se na trećoj sjednici i nije dio preliminarne rang-liste.';

    public const CANONICAL_ADMIN_RANKING_ROUTE_MESSAGE = 'Preliminarna rang-lista kanonskog Poziva dostupna je Komisiji na ekranu ocjenjivanja, ne na administratorskoj ruti.';

    /** @var list<string> */
    public const CANONICAL_UNRESOLVED_REVIEW_STATES = [
        CanonicalReviewState::PENDING_REVIEW,
        CanonicalReviewState::COMPLAINT_ELIGIBLE,
        CanonicalReviewState::COMPLAINT_PENDING,
    ];

    /** @var list<string> */
    public const BONUS_FLAG_KEYS = [
        'bonus_info_day',
        'bonus_new_business',
        'bonus_zavod_nezaposleni',
        'bonus_green_innovative',
    ];

    /** @var list<string> */
    public const YOUTH_BONUS_FLAG_KEYS = [
        'bonus_info_day',
        'bonus_training',
        'bonus_new_business',
        'bonus_green_innovative',
    ];

    public function __construct(
        protected ApplicationEliminatoryCheckService $eliminatoryChecks,
        protected YouthSecondSessionGate $youthSecondSessionGate,
    ) {}

    public function isFinalCompleted(?EvaluationScore $score): bool
    {
        if ($score === null) {
            return false;
        }

        $score->loadMissing('application.competition');
        if ($score->application?->competition?->isOmladinskoProfile()) {
            return $score->completed_at !== null;
        }

        if ($this->isCanonicalCompetition($score->application?->competition)) {
            return $this->canonicalRowIsFinal($score);
        }

        return CommissionCanonicalSeat::isHistoricallyCompleted($score);
    }

    public function resolveSeat(CommissionMember $member): int
    {
        $seat = CommissionCanonicalSeat::resolvePersistedOrInfer($member);
        if ($seat === null) {
            abort(403, self::INVALID_SEAT_MESSAGE);
        }

        if ($member->canonical_seat_no === null) {
            $member->canonical_seat_no = $seat;
            $member->save();
        }

        return $seat;
    }

    /**
     * @return array<int, EvaluationScore> seat 1–5 => completed evaluation
     */
    public function completedEvaluationsBySeat(Application $application): array
    {
        $scores = EvaluationScore::query()
            ->where('application_id', $application->id)
            ->with('commissionMember.commission.members')
            ->get();

        $bySeat = [];

        foreach ($scores as $score) {
            if (! $this->isFinalCompleted($score)) {
                continue;
            }

            $seat = $score->canonical_seat_no !== null
                ? (int) $score->canonical_seat_no
                : CommissionCanonicalSeat::resolvePersistedOrInfer($score->commissionMember);

            if ($seat === null || $seat < 1 || $seat > 5) {
                return [];
            }

            if (isset($bySeat[$seat])) {
                return [];
            }

            $bySeat[$seat] = $score;
        }

        return $bySeat;
    }

    public function applicationHasFiveCanonicalSeats(Application $application): bool
    {
        if ($this->hasBlockingPartialRow($application)) {
            return false;
        }

        $bySeat = $this->completedEvaluationsBySeat($application);

        foreach (CommissionCanonicalSeat::SEATS as $seat) {
            if (! isset($bySeat[$seat])) {
                return false;
            }
        }

        return count($bySeat) === 5;
    }

    public function applicationHasYouthCanonicalSeats(Application $application): bool
    {
        $bySeat = $this->completedEvaluationsBySeat($application);
        $profile = ScoringProfileConfig::for('omladinsko');

        foreach ($profile->allowedSeats as $seat) {
            if (! isset($bySeat[$seat])) {
                return false;
            }
        }

        foreach (array_keys($bySeat) as $seat) {
            if (! $profile->allowsSeat((int) $seat)) {
                return false;
            }
        }

        return count($bySeat) === $profile->requiredFinalCount;
    }

    /**
     * @return Collection<int, Application>
     */
    public function scoringEligibleApplications(Competition $competition): Collection
    {
        return $this->cycleApplications($competition)
            ->filter(fn (Application $application) => $this->eliminatoryChecks->scoringIsAllowed($application))
            ->values();
    }

    public function isIndividualScoringCycleComplete(Competition $competition): bool
    {
        if ($competition->isOmladinskoProfile()) {
            return $this->isYouthIndividualScoringCycleComplete($competition);
        }

        if ($this->isCanonicalCompetition($competition)) {
            return $this->canonicalCycleBlockReason($competition) === null;
        }

        if ($competition->status !== 'closed' && $competition->status !== 'completed' && ! $competition->isApplicationDeadlinePassed()) {
            return false;
        }

        if (! $competition->commission_id) {
            return false;
        }

        $applications = $this->cycleApplications($competition);
        if ($applications->isEmpty()) {
            return false;
        }

        $isArchived = in_array($competition->status, ['closed', 'completed'], true);
        $hasAnyEliminatoryRecord = $applications->contains(
            fn (Application $application) => $application->eliminatoryCheck !== null
        );

        $pendingEliminatory = $applications->filter(function (Application $application) {
            return ! $application->isEliminatedFromScoring()
                && ! $this->eliminatoryChecks->scoringIsAllowed($application);
        });

        if ($pendingEliminatory->isNotEmpty()) {
            if ($isArchived && ! $hasAnyEliminatoryRecord) {
                return $this->legacyArchivedCycleIsComplete($applications);
            }

            return false;
        }

        $eligible = $applications->filter(
            fn (Application $application) => $this->eliminatoryChecks->scoringIsAllowed($application)
        );

        if ($eligible->isEmpty()) {
            return $isArchived;
        }

        foreach ($eligible as $application) {
            if (! $this->applicationHasFiveCanonicalSeats($application)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Operativni scoring skup: unos novih ocjena.
     * Samo submitted/evaluated i aktivne youth scoring kapije.
     *
     * @return Collection<int, Application>
     */
    public function youthPositiveScoringApplications(Competition $competition): Collection
    {
        return $competition->applications()
            ->whereIn('status', ['submitted', 'evaluated'])
            ->with(['user', 'eliminatoryCheck', 'eliminatoryNotice', 'prigovor', 'evaluationScores.commissionMember.commission.members', 'competition'])
            ->orderBy('id')
            ->get()
            ->filter(fn (Application $application) => $this->youthSecondSessionGate->youthDraftScoringIsAllowed($application))
            ->values();
    }

    /**
     * Istorijski scoring skup: završetak ciklusa i trajnost formiranog ranga.
     * Uključuje approved/rejected samo ako prijava ima pozitivne scoring činjenice.
     *
     * @return Collection<int, Application>
     */
    public function youthScoringCycleApplications(Competition $competition): Collection
    {
        if (! $competition->isOmladinskoProfile()) {
            return collect();
        }

        return $competition->applications()
            ->whereIn('status', ['submitted', 'evaluated', 'approved', 'rejected'])
            ->with(['user', 'eliminatoryCheck', 'eliminatoryNotice', 'prigovor', 'evaluationScores.commissionMember.commission.members', 'competition'])
            ->orderBy('id')
            ->get()
            ->filter(fn (Application $application) => $this->applicationBelongsToYouthScoringCycle($application))
            ->values();
    }

    public function isYouthIndividualScoringCycleComplete(Competition $competition): bool
    {
        if (! $competition->isOmladinskoProfile()) {
            return false;
        }

        if ($competition->status !== 'closed' && $competition->status !== 'completed' && ! $competition->isApplicationDeadlinePassed()) {
            return false;
        }

        if (! $competition->commission_id) {
            return false;
        }

        $historical = $this->youthScoringCycleApplications($competition);
        if ($historical->isEmpty()) {
            return false;
        }

        foreach ($this->youthPositiveScoringApplications($competition) as $application) {
            if (! $this->applicationHasYouthCanonicalSeats($application)) {
                return false;
            }
        }

        return true;
    }

    public function isYouthPreliminaryRankingReady(Competition $competition): bool
    {
        return $this->youthPreliminaryRankingBlockReason($competition) === null;
    }

    public function youthPreliminaryRankingBlockReason(Competition $competition): ?string
    {
        if (! $competition->isOmladinskoProfile()) {
            return self::YOUTH_CYCLE_INCOMPLETE_MESSAGE;
        }

        if (! $this->isYouthIndividualScoringCycleComplete($competition)) {
            return self::YOUTH_CYCLE_INCOMPLETE_MESSAGE;
        }

        $historical = $this->youthScoringCycleApplications($competition);
        if ($historical->isEmpty()) {
            return self::YOUTH_CYCLE_INCOMPLETE_MESSAGE;
        }

        $appealRelevant = $competition->applications()
            ->whereIn('status', ['submitted', 'evaluated'])
            ->with(['eliminatoryCheck', 'eliminatoryNotice', 'prigovor', 'competition'])
            ->orderBy('id')
            ->get();

        $hasUnresolvedPrigovor = $appealRelevant->contains(
            fn (Application $application) => $application->prigovor?->isPodnesen() === true
        ) || $this->youthSecondSessionGate->competitionHasUnresolvedPrigovor($competition);
        if ($hasUnresolvedPrigovor) {
            return self::YOUTH_RANKING_PRIGOVOR_PODNESEN_MESSAGE;
        }

        foreach ($appealRelevant as $application) {
            if ($this->youthSecondSessionGate->appealWindowIsOpen($application)) {
                return self::YOUTH_RANKING_APPEAL_WINDOW_MESSAGE;
            }
        }

        foreach ($historical as $application) {
            if ($application->bonuses_confirmed_at === null) {
                return self::YOUTH_RANKING_BONUSES_UNCONFIRMED_MESSAGE;
            }
            if ($application->final_score === null || $this->aggregateYouthApplication($application) === null) {
                return self::YOUTH_RANKING_FINAL_SCORE_MISSING_MESSAGE;
            }
        }

        return null;
    }

    /**
     * Idempotentno formiranje preliminarne rang-liste mladih.
     *
     * Redoslijed katanaca: competitions → applications (id ASC) → evaluation_scores.
     * Ne smije se zvati dok pozivalac drži application lock izvan ovog redoslijeda.
     */
    public function persistYouthPreliminaryRankingIfReady(Competition $competition): void
    {
        if (! $competition->isOmladinskoProfile()) {
            return;
        }

        DB::transaction(function () use ($competition) {
            /** @var Competition|null $lockedCompetition */
            $lockedCompetition = Competition::query()
                ->whereKey($competition->id)
                ->lockForUpdate()
                ->first();
            if ($lockedCompetition === null || ! $lockedCompetition->isOmladinskoProfile()) {
                return;
            }

            $lockedApplications = Application::query()
                ->where('competition_id', $lockedCompetition->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lockedApplications->isNotEmpty()) {
                EvaluationScore::query()
                    ->whereIn('application_id', $lockedApplications->pluck('id')->all())
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
            }

            $lockedCompetition->unsetRelation('applications');
            foreach ($lockedApplications as $lockedApplication) {
                $lockedApplication->unsetRelation('evaluationScores');
                $lockedApplication->setRelation('competition', $lockedCompetition);
            }

            if ($lockedCompetition->youth_allocation_list_confirmed_at !== null) {
                return;
            }

            if ($this->youthPreliminaryRankingBlockReason($lockedCompetition) !== null) {
                return;
            }

            $eligible = $this->youthScoringCycleApplications($lockedCompetition);
            $this->assignYouthPreliminaryRankingPositions($eligible);

            $keepIds = $eligible
                ->filter(fn (Application $application) => $application->ranking_position !== null)
                ->pluck('id')
                ->all();
            $stale = Application::query()
                ->where('competition_id', $lockedCompetition->id)
                ->whereNotNull('ranking_position');
            if ($keepIds !== []) {
                $stale->whereNotIn('id', $keepIds);
            }
            $stale->update(['ranking_position' => null]);
        });
    }

    /**
     * @return array{
     *     cycle_complete: bool,
     *     individual_cycle_complete: bool,
     *     ranking_ready: bool,
     *     block_reason: ?string,
     *     above: Collection<int, Application>,
     *     below: Collection<int, Application>
     * }
     */
    public function youthPreliminaryRankingView(Competition $competition): array
    {
        $cycleComplete = $this->isYouthIndividualScoringCycleComplete($competition);
        $blockReason = $this->youthPreliminaryRankingBlockReason($competition);
        $rankingReady = $blockReason === null;
        $above = collect();
        $below = collect();

        if ($rankingReady) {
            $scored = $this->youthScoringCycleApplications($competition)
                ->sortBy('id')
                ->values();
            $above = $scored
                ->filter(fn (Application $application) => $this->youthMeetsMinimumScore($application))
                ->sort(function (Application $a, Application $b) {
                    $aPos = $a->ranking_position ?? PHP_INT_MAX;
                    $bPos = $b->ranking_position ?? PHP_INT_MAX;
                    if ($aPos !== $bPos) {
                        return $aPos <=> $bPos;
                    }

                    return $a->id <=> $b->id;
                })
                ->values();
            $below = $scored
                ->filter(fn (Application $application) => ! $this->youthMeetsMinimumScore($application))
                ->sort(function (Application $a, Application $b) {
                    $aScore = $this->youthFullPrecisionFinalScore($a) ?? '0';
                    $bScore = $this->youthFullPrecisionFinalScore($b) ?? '0';
                    $compared = bccomp($bScore, $aScore, self::YOUTH_BONUS_SCALE);
                    if ($compared !== 0) {
                        return $compared;
                    }

                    return $a->id <=> $b->id;
                })
                ->values();
        }

        return [
            'cycle_complete' => $cycleComplete,
            'individual_cycle_complete' => $cycleComplete,
            'ranking_ready' => $rankingReady,
            'block_reason' => $cycleComplete ? $blockReason : self::YOUTH_CYCLE_INCOMPLETE_MESSAGE,
            'above' => $above,
            'below' => $below,
        ];
    }

    /**
     * Read-only close gate after the omladinski list is confirmed.
     * Does not persist ranking, rewrite scores, or change confirmation audit.
     */
    public function youthCloseFreezeBlockReason(Competition $competition): ?string
    {
        if (! $competition->isOmladinskoProfile()) {
            return null;
        }

        if ($this->youthPreliminaryRankingBlockReason($competition) !== null) {
            return self::YOUTH_CLOSE_RANKING_NOT_READY_MESSAGE;
        }

        if (! app(YouthAllocationListConfirmationService::class)->confirmedListIsIntact($competition)) {
            return self::YOUTH_CLOSE_LIST_NOT_CONFIRMED_MESSAGE;
        }

        if (! $competition->hasChairmanCompletedDecisions()) {
            return self::YOUTH_CLOSE_LIST_NOT_CONFIRMED_MESSAGE;
        }

        $eligible = $this->youthScoringCycleApplications($competition);
        $expected = $this->expectedYouthRankingPositions($eligible);
        $applications = Application::query()
            ->where('competition_id', $competition->id)
            ->orderBy('id')
            ->get();

        foreach ($applications as $application) {
            $want = $expected[$application->id] ?? null;
            $stored = $application->ranking_position;
            if ($want === null) {
                if ($stored !== null) {
                    return self::YOUTH_CLOSE_POSITIONS_MISMATCH_MESSAGE;
                }

                continue;
            }

            if ($stored === null || (int) $stored !== $want) {
                return self::YOUTH_CLOSE_POSITIONS_MISMATCH_MESSAGE;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, Application>  $eligible
     * @return array<int, int>
     */
    public function expectedYouthRankingPositions(Collection $eligible): array
    {
        $rows = [];
        foreach ($eligible as $application) {
            $aggregate = $this->aggregateYouthApplication($application);
            if ($aggregate === null) {
                continue;
            }

            $rows[] = [
                'application' => $application,
                'full' => $aggregate['final_score_full'],
            ];
        }

        usort($rows, function (array $a, array $b) {
            $cmp = bccomp($b['full'], $a['full'], self::YOUTH_BONUS_SCALE);
            if ($cmp !== 0) {
                return $cmp;
            }

            return $a['application']->id <=> $b['application']->id;
        });

        $above = [];
        foreach ($rows as $row) {
            if (bccomp($row['full'], '30', self::YOUTH_BONUS_SCALE) >= 0) {
                $above[] = $row;
            }
        }

        $previousFull = null;
        $previousRank = 0;
        $expected = [];
        foreach ($above as $index => $row) {
            $rank = ($previousFull !== null && bccomp($row['full'], $previousFull, self::YOUTH_BONUS_SCALE) === 0)
                ? $previousRank
                : $index + 1;
            $previousRank = $rank;
            $previousFull = $row['full'];
            $expected[$row['application']->id] = $rank;
        }

        return $expected;
    }

    /**
     * @param  Collection<int, Application>  $eligible
     */
    private function assignYouthPreliminaryRankingPositions(Collection $eligible): void
    {
        $expected = $this->expectedYouthRankingPositions($eligible);

        foreach ($eligible as $application) {
            $rank = $expected[$application->id] ?? null;
            if ($rank === null) {
                if ($application->ranking_position !== null) {
                    $application->forceFill(['ranking_position' => null])->save();
                }

                continue;
            }

            if ((int) $application->ranking_position !== $rank) {
                $application->forceFill(['ranking_position' => $rank])->save();
            }
        }
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function recordFinalScore(
        Application $application,
        CommissionMember $member,
        array $criteria,
        ?string $notes,
        bool $confirmed,
    ): EvaluationScore {
        ScoringProfileConfig::for($application->competition()->value('type'))->assertScoringFormulaActive();

        if (! $this->eliminatoryChecks->scoringIsAllowed($application)) {
            abort(403, $this->eliminatoryChecks->isConfirmedFail($application)
                ? ApplicationEliminatoryCheckService::CONFIRMED_FAIL_SCORING_MESSAGE
                : ApplicationEliminatoryCheckService::SCORING_LOCKED_MESSAGE);
        }

        if (! $confirmed) {
            throw ValidationException::withMessages([
                'scoring_confirmed' => self::CONFIRMATION_REQUIRED_MESSAGE,
            ]);
        }

        $seat = $this->resolveSeat($member);

        try {
            $recorded = DB::transaction(function () use ($application, $member, $criteria, $notes, $seat) {
                Application::query()->whereKey($application->id)->lockForUpdate()->first();

                CommissionMember::query()
                    ->where('commission_id', $member->commission_id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $lockedScores = EvaluationScore::query()
                    ->where('application_id', $application->id)
                    ->lockForUpdate()
                    ->get();

                $seatAlreadyFinal = $lockedScores->first(function (EvaluationScore $score) use ($seat) {
                    return $this->isFinalCompleted($score)
                        && (int) ($score->canonical_seat_no ?? 0) === $seat;
                });

                if ($seatAlreadyFinal && (int) $seatAlreadyFinal->commission_member_id !== (int) $member->id) {
                    abort(403, self::SEAT_ALREADY_FINAL_MESSAGE);
                }

                $own = $lockedScores->firstWhere('commission_member_id', $member->id);

                if ($this->isFinalCompleted($own)) {
                    abort(403, self::SCORE_IMMUTABLE_MESSAGE);
                }

                if ($own && CommissionCanonicalSeat::isPartial($own)) {
                    abort(403, self::PARTIAL_ROW_MESSAGE);
                }

                $blockingPartial = $lockedScores->first(function (EvaluationScore $score) use ($seat, $member) {
                    if (! CommissionCanonicalSeat::isPartial($score)) {
                        return false;
                    }

                    if ((int) $score->commission_member_id === (int) $member->id) {
                        return true;
                    }

                    return $this->resolvedSeatOf($score) === $seat;
                });

                if ($blockingPartial) {
                    abort(403, self::PARTIAL_ROW_MESSAGE);
                }

                $inferredSeatFinal = $lockedScores->first(function (EvaluationScore $score) use ($seat, $member) {
                    if (! $this->isFinalCompleted($score) || (int) $score->commission_member_id === (int) $member->id) {
                        return false;
                    }

                    return $this->resolvedSeatOf($score) === $seat;
                });

                if ($inferredSeatFinal) {
                    abort(403, self::SEAT_ALREADY_FINAL_MESSAGE);
                }

                $payload = $this->finalScorePayload($application, $member, $criteria, $notes, $seat);

                if ($own && CommissionCanonicalSeat::isPlaceholder($own)) {
                    $own->fill($payload);
                    $own->save();

                    return $own->fresh();
                }

                if ($own) {
                    abort(403, self::PARTIAL_ROW_MESSAGE);
                }

                return EvaluationScore::query()->create($payload);
            });
        } catch (UniqueConstraintViolationException $e) {
            abort(403, self::SEAT_ALREADY_FINAL_MESSAGE);
        }

        return $recorded;
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function recordYouthFinalScore(
        Application $application,
        CommissionMember $member,
        array $criteria,
        ?string $notes,
        bool $confirmed,
    ): EvaluationScore {
        $this->assertYouthDraftGates($application, $member);

        if ($application->status !== 'submitted') {
            abort(403, ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE);
        }

        if (! $confirmed) {
            throw ValidationException::withMessages([
                'scoring_confirmed' => self::CONFIRMATION_REQUIRED_MESSAGE,
            ]);
        }

        $seat = $this->resolveYouthSeat($member);

        try {
            $recorded = DB::transaction(function () use ($application, $member, $criteria, $notes, $seat) {
                $lockedApplication = Application::query()->whereKey($application->id)->lockForUpdate()->first();
                if ($lockedApplication === null || $lockedApplication->status !== 'submitted') {
                    abort(403, ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE);
                }

                CommissionMember::query()
                    ->whereKey($member->id)
                    ->where('commission_id', $member->commission_id)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedApplication->load(['competition', 'eliminatoryCheck', 'prigovor']);
                $this->assertYouthDraftGates($lockedApplication, $member);

                $lockedScores = EvaluationScore::query()
                    ->where('application_id', $application->id)
                    ->lockForUpdate()
                    ->get();

                $seatAlreadyFinal = $lockedScores->first(function (EvaluationScore $score) use ($seat) {
                    return $this->isFinalCompleted($score)
                        && (int) ($score->canonical_seat_no ?? 0) === $seat;
                });

                if ($seatAlreadyFinal && (int) $seatAlreadyFinal->commission_member_id !== (int) $member->id) {
                    abort(403, self::SEAT_ALREADY_FINAL_MESSAGE);
                }

                $own = $lockedScores->firstWhere('commission_member_id', $member->id);

                if ($this->isFinalCompleted($own)) {
                    abort(403, self::SCORE_IMMUTABLE_MESSAGE);
                }

                $payload = $this->youthFinalPayload($application, $member, $criteria, $notes, $seat);

                if ($own) {
                    $own->fill($payload);
                    $own->save();
                    $fresh = $own->fresh();
                } else {
                    $fresh = EvaluationScore::query()->create($payload);
                }

                $this->markYouthEvaluatedIfThreeSeatsLocked($lockedApplication);
                $this->persistYouthAggregateIfReady($lockedApplication);

                return $fresh;
            });
        } catch (UniqueConstraintViolationException $e) {
            $message = NamedMysqlUniqueViolation::matches($e, 'eval_scores_app_seat_unique')
                || NamedMysqlUniqueViolation::matches($e, 'evaluation_scores_application_id_commission_member_id_unique')
                ? ScoringProfileConfig::YOUTH_SCORE_CONFLICT_MESSAGE
                : self::SEAT_ALREADY_FINAL_MESSAGE;
            abort(403, $message);
        }

        $application->loadMissing('competition');
        if ($application->competition) {
            $this->persistYouthPreliminaryRankingIfReady($application->competition);
        }

        return $recorded;
    }

    public function isCanonicalCompetition(?Competition $competition): bool
    {
        return $competition?->type === CompetitionProfileResolver::TYPE_CANONICAL;
    }

    /**
     * Governance gate (active valid member, persisted seat, interview 3/3, FINAL + complete +
     * complaint resolved, ranking not formed, seat not scored) plus fail-closed row integrity.
     */
    public function canonicalScoreWriteBlockReason(User $user, Application $application): ?string
    {
        $application->loadMissing('competition');
        if (! $this->isCanonicalCompetition($application->competition)) {
            return CanonicalCommissionGovernance::NOT_CANONICAL_MESSAGE;
        }

        if ($this->canonicalRankingIsFormed($application->competition)) {
            return self::CANONICAL_RANKING_FROZEN_MESSAGE;
        }

        $deadlineBlock = $this->canonicalIndividualScoreDeadlineBlockReason($application->competition);
        if ($deadlineBlock !== null) {
            return $deadlineBlock;
        }

        $reason = $this->canonicalGovernance()->scoreWriteBlockReason($user, $application);
        if ($reason !== null) {
            return $reason;
        }

        return $this->canonicalRowsAreCorrupt($application, $this->canonicalScoreRows($application))
            ? self::CANONICAL_CORRUPT_ROW_MESSAGE
            : null;
    }

    /**
     * Individual scoring before the application deadline is allowed only for an MMSP draft
     * with the test preview flag. Ranking formation keeps its own deadline gate, except when
     * that same test preview has irreversibly closed its application phase.
     */
    private function canonicalIndividualScoreDeadlineBlockReason(?Competition $competition): ?string
    {
        if ($competition === null
            || $competition->isCanonicalTestPreview()
            || in_array($competition->status, ['closed', 'completed'], true)
            || $competition->isApplicationDeadlinePassed()) {
            return null;
        }

        return CanonicalCommissionGovernance::APPLICATION_DEADLINE_OPEN_MESSAGE;
    }

    /**
     * One immutable final score per canonical seat. No drafts, no overwrite, no seat inference.
     *
     * @param  array<string, mixed>  $criteria
     */
    public function recordCanonicalFinalScore(
        Application $application,
        User $user,
        array $criteria,
        ?string $notes,
        bool $confirmed,
    ): EvaluationScore {
        $application->loadMissing('competition');
        if (! $this->isCanonicalCompetition($application->competition)) {
            abort(403, CanonicalCommissionGovernance::NOT_CANONICAL_MESSAGE);
        }

        $profile = ScoringProfileConfig::for($application->competition->type);
        $profile->assertScoringFormulaActive();

        $reason = $this->canonicalScoreWriteBlockReason($user, $application);
        if ($reason !== null) {
            abort(403, $reason);
        }

        if (! $confirmed) {
            throw ValidationException::withMessages([
                'scoring_confirmed' => self::CONFIRMATION_REQUIRED_MESSAGE,
            ]);
        }

        $normalized = $this->normalizedCanonicalCriteria($profile, $criteria);

        try {
            $recorded = DB::transaction(function () use ($application, $user, $normalized, $notes) {
                $lockedCompetition = Competition::query()
                    ->whereKey($application->competition_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $lockedApplication = Application::query()
                    ->whereKey($application->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                CommissionMember::query()
                    ->where('commission_id', $lockedCompetition->commission_id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                $lockedScores = EvaluationScore::query()
                    ->where('application_id', $lockedApplication->id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                $lockedApplication->setRelation('competition', $lockedCompetition);

                $reason = $this->canonicalScoreWriteBlockReason($user, $lockedApplication);
                if ($reason !== null) {
                    abort(403, $reason);
                }

                $member = $this->canonicalGovernance()->activeMemberFor($user, $lockedCompetition);
                $seat = (int) $member->canonical_seat_no;

                if ($lockedScores->contains(fn (EvaluationScore $score) => (int) $score->commission_member_id === (int) $member->id)) {
                    abort(403, self::SCORE_IMMUTABLE_MESSAGE);
                }

                if ($lockedScores->contains(fn (EvaluationScore $score) => (int) $score->canonical_seat_no === $seat)) {
                    abort(403, CanonicalCommissionGovernance::SEAT_SCORE_COMPLETED_MESSAGE);
                }

                return EvaluationScore::query()->create(
                    $this->finalScorePayload($lockedApplication, $member, $normalized, $notes, $seat)
                );
            });
        } catch (UniqueConstraintViolationException $e) {
            abort(403, CanonicalCommissionGovernance::SEAT_SCORE_COMPLETED_MESSAGE);
        }

        $this->persistCanonicalPreliminaryRankingIfReady($application->competition);

        return $recorded;
    }

    /**
     * @return Collection<int, Application> FINAL canonical applications that entered Commission review
     */
    public function canonicalCycleApplications(Competition $competition): Collection
    {
        if (! $this->isCanonicalCompetition($competition)) {
            return collect();
        }

        return $competition->applications()
            ->whereIn('status', ['submitted', 'evaluated', 'approved', 'rejected'])
            ->whereHas('finalClaim')
            ->with(['finalClaim', 'competition', 'evaluationScores.commissionMember'])
            ->orderBy('id')
            ->get();
    }

    public function canonicalApplicationIsScoringEligible(Application $application): bool
    {
        $application->loadMissing(['competition', 'finalClaim']);
        if (! $this->isCanonicalCompetition($application->competition) || ! $application->finalClaim || $application->status === 'draft') {
            return false;
        }

        if (! in_array(CanonicalReviewState::resolve($application), [CanonicalReviewState::COMPLETE, CanonicalReviewState::COMPLAINT_RESOLVED], true)) {
            return false;
        }

        return ApplicationCompletenessAssessment::query()
            ->where('application_id', $application->id)
            ->latest('id')
            ->value('outcome') === ApplicationCompletenessAssessment::COMPLETE;
    }

    /**
     * @return Collection<int, Application>
     */
    public function canonicalScoringEligibleApplications(Competition $competition): Collection
    {
        return $this->canonicalCycleApplications($competition)
            ->filter(fn (Application $application) => $this->canonicalApplicationIsScoringEligible($application))
            ->values();
    }

    /**
     * Čl.21: the cycle is complete only when all 3 seats scored every eligible plan and no
     * application is still awaiting completeness review or a complaint outcome.
     */
    public function canonicalCycleBlockReason(Competition $competition): ?string
    {
        if (! $this->isCanonicalCompetition($competition)) {
            return CanonicalCommissionGovernance::NOT_CANONICAL_MESSAGE;
        }

        if (! ScoringProfileConfig::for($competition->type)->scoringFormulaActive) {
            return ScoringProfileConfig::CANONICAL_SCORING_DISABLED_MESSAGE;
        }

        if (! $competition->commission_id) {
            return CanonicalCommissionGovernance::NO_COMMISSION_MESSAGE;
        }

        if (! in_array($competition->status, ['closed', 'completed'], true)
            && ! $competition->isApplicationDeadlinePassed()
            && ! $competition->isCanonicalTestApplicationPhaseClosed()) {
            return self::CANONICAL_CYCLE_INCOMPLETE_MESSAGE;
        }

        $applications = $this->canonicalCycleApplications($competition);
        if ($applications->isEmpty()) {
            return self::CANONICAL_CYCLE_INCOMPLETE_MESSAGE;
        }

        foreach ($applications as $application) {
            if (in_array(CanonicalReviewState::resolve($application), self::CANONICAL_UNRESOLVED_REVIEW_STATES, true)) {
                return self::CANONICAL_REVIEW_UNRESOLVED_MESSAGE;
            }
        }

        $eligible = $applications
            ->filter(fn (Application $application) => $this->canonicalApplicationIsScoringEligible($application))
            ->values();
        if ($eligible->isEmpty()) {
            return self::CANONICAL_CYCLE_INCOMPLETE_MESSAGE;
        }

        foreach ($eligible as $application) {
            $seats = $this->canonicalCompletedSeats($application);
            if ($seats === null) {
                return self::CANONICAL_CORRUPT_ROW_MESSAGE;
            }

            if (array_keys($seats) !== ScoringProfileConfig::for($competition->type)->allowedSeats) {
                return self::CANONICAL_CYCLE_INCOMPLETE_MESSAGE;
            }
        }

        return null;
    }

    public function isCanonicalIndividualScoringCycleComplete(Competition $competition): bool
    {
        return $this->canonicalCycleBlockReason($competition) === null;
    }

    /**
     * @return array<int, EvaluationScore>|null seat => final score, ordered by seat; null when any row is corrupt
     */
    public function canonicalCompletedSeats(Application $application): ?array
    {
        $rows = $this->canonicalScoreRows($application);
        if ($this->canonicalRowsAreCorrupt($application, $rows)) {
            return null;
        }

        $bySeat = [];
        foreach ($rows as $row) {
            $bySeat[(int) $row->canonical_seat_no] = $row;
        }
        ksort($bySeat);

        return $bySeat;
    }

    /**
     * Čl.21 / KN-BR-036/037: average per criterion = sum of the 3 member scores / 3;
     * final CALCULATION VALUE = sum of the 10 averages, computed as one exact division
     * (Σ all member scores / 3) so equal plans never diverge through per-criterion truncation.
     * No bonuses. DISPLAY VALUE = 2 decimals, half-up.
     *
     * @return array{
     *     criterion_sums: array<int, int>,
     *     criterion_averages: array<int, string>,
     *     total_points: int,
     *     final_score_full: string,
     *     final_score_display: string,
     *     meets_support_threshold: bool
     * }|null
     */
    public function aggregateCanonicalApplication(Application $application): ?array
    {
        $application->loadMissing('competition');
        if (! $this->isCanonicalCompetition($application->competition)) {
            return null;
        }

        $profile = ScoringProfileConfig::for($application->competition->type);
        $seats = $this->canonicalCompletedSeats($application);
        if ($seats === null || array_keys($seats) !== $profile->allowedSeats) {
            return null;
        }

        $divisor = (string) $profile->requiredFinalCount;
        $sums = [];
        $averages = [];
        $total = 0;
        foreach (array_keys($profile->criteria) as $criterion) {
            $sum = 0;
            foreach ($seats as $score) {
                $sum += (int) $score->{"criterion_{$criterion}"};
            }
            $sums[$criterion] = $sum;
            $averages[$criterion] = bcdiv((string) $sum, $divisor, CompetitionRanking::SCALE);
            $total += $sum;
        }

        $full = bcdiv((string) $total, $divisor, CompetitionRanking::SCALE);

        return [
            'criterion_sums' => $sums,
            'criterion_averages' => $averages,
            'total_points' => $total,
            'final_score_full' => $full,
            'final_score_display' => CompetitionRanking::roundHalfUp($full, 2),
            'meets_support_threshold' => bccomp($full, self::CANONICAL_SUPPORT_THRESHOLD, CompetitionRanking::SCALE) >= 0,
        ];
    }

    public function canonicalRankingIsFormed(?Competition $competition): bool
    {
        return $this->canonicalGovernance()->canonicalRankingFormed($competition);
    }

    /**
     * Idempotent, frozen preliminary ranking (čl.21, KN-BR-038): all scored eligible plans,
     * including those below 30, ranked by full-precision CALCULATION VALUE with shared ranks.
     * Writes only final_score (display), final_score_calc and ranking_position; never scores,
     * statuses, outcomes or amounts. Once formed it is never rewritten here.
     *
     * Lock order: competitions → applications (id ASC) → evaluation_scores.
     */
    public function persistCanonicalPreliminaryRankingIfReady(?Competition $competition): bool
    {
        if (! $this->isCanonicalCompetition($competition)) {
            return false;
        }

        return DB::transaction(function () use ($competition) {
            $lockedCompetition = Competition::query()->whereKey($competition->id)->lockForUpdate()->first();
            if ($lockedCompetition === null || ! $this->isCanonicalCompetition($lockedCompetition)) {
                return false;
            }

            if ($lockedCompetition->canonical_ranking_formed_at !== null) {
                return true;
            }

            $lockedApplications = Application::query()
                ->where('competition_id', $lockedCompetition->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            if ($lockedApplications->isNotEmpty()) {
                EvaluationScore::query()
                    ->whereIn('application_id', $lockedApplications->pluck('id')->all())
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
            }

            if ($this->canonicalCycleBlockReason($lockedCompetition) !== null) {
                return false;
            }

            $eligible = $this->canonicalScoringEligibleApplications($lockedCompetition);
            $aggregates = [];
            foreach ($eligible as $application) {
                $aggregate = $this->aggregateCanonicalApplication($application);
                if ($aggregate === null) {
                    throw new \RuntimeException(
                        "Fail-closed canonical ranking: eligible application {$application->id} is not exactly seats 1–3."
                    );
                }
                $aggregates[$application->id] = $aggregate;
            }

            $positions = CompetitionRanking::positions(
                array_map(fn (array $aggregate) => $aggregate['final_score_full'], $aggregates)
            );

            foreach ($eligible as $application) {
                $aggregate = $aggregates[$application->id];
                $application->forceFill([
                    'final_score' => $aggregate['final_score_display'],
                    'final_score_calc' => $aggregate['final_score_full'],
                    'ranking_position' => $positions[$application->id],
                ])->save();
            }

            Application::query()
                ->where('competition_id', $lockedCompetition->id)
                ->whereNotIn('id', array_keys($aggregates))
                ->whereNotNull('ranking_position')
                ->update(['ranking_position' => null]);

            $lockedCompetition->forceFill(['canonical_ranking_formed_at' => now()])->save();

            return true;
        });
    }

    /**
     * Commission view of the formed preliminary ranking (no amounts, no outcome).
     *
     * @return array{
     *     formed: bool,
     *     visible: bool,
     *     block_reason: ?string,
     *     rows: Collection<int, array{application: Application, rank: int, shared: bool, display: string, calc: string, meets_support_threshold: bool}>
     * }
     */
    public function canonicalPreliminaryRankingView(Competition $competition, User $viewer): array
    {
        $formed = $this->canonicalRankingIsFormed($competition);
        $isMember = CommissionMember::query()
            ->where('commission_id', $competition->commission_id)
            ->where('user_id', $viewer->id)
            ->exists();
        $isAdmin = in_array($viewer->role?->name, ['admin', 'superadmin'], true);
        $visible = $formed && ($isMember || $isAdmin);

        $rows = collect();
        if ($visible) {
            $ranked = Application::query()
                ->where('competition_id', $competition->id)
                ->whereNotNull('ranking_position')
                ->orderBy('ranking_position')
                ->orderBy('id')
                ->get();
            $rankCounts = $ranked->countBy(fn (Application $application) => (int) $application->ranking_position);
            $rows = $ranked->map(fn (Application $application) => [
                'application' => $application,
                'rank' => (int) $application->ranking_position,
                'shared' => $rankCounts[(int) $application->ranking_position] > 1,
                'display' => (string) $application->final_score,
                'calc' => (string) $application->final_score_calc,
                'meets_support_threshold' => bccomp((string) $application->final_score_calc, self::CANONICAL_SUPPORT_THRESHOLD, CompetitionRanking::SCALE) >= 0,
            ])->values();
        }

        return [
            'formed' => $formed,
            'visible' => $visible,
            'block_reason' => $formed ? null : $this->canonicalCycleBlockReason($competition),
            'rows' => $rows,
        ];
    }

    public function persistApplicationAggregatesIfCycleComplete(Competition $competition): void
    {
        if ($competition->isOmladinskoProfile() || $this->isCanonicalCompetition($competition)) {
            return;
        }

        $this->persistZenskoApplicationAggregatesIfCycleComplete($competition);
    }

    /**
     * Chairman bonus may be written only while the global scoring cycle is still open.
     * The completing POST uses the pre-write cycle flag so legitimate bonus on that
     * request is stored before aggregate finalization.
     *
     * @param  array<string, mixed>  $flags
     */
    public function saveChairmanBonusWhileCycleOpen(Application $application, array $flags, bool $cycleWasAlreadyComplete): void
    {
        if ($cycleWasAlreadyComplete) {
            abort(403, self::BONUS_LOCKED_MESSAGE);
        }

        $wantsNewBusinessBonus = (bool) ($flags['bonus_new_business'] ?? false);
        $bonusNewBusiness = $wantsNewBusinessBonus && $application->isEligibleForNewBusinessBonus();

        $application->forceFill([
            'bonus_info_day' => (bool) ($flags['bonus_info_day'] ?? false),
            'bonus_new_business' => $bonusNewBusiness,
            'bonus_zavod_nezaposleni' => (bool) ($flags['bonus_zavod_nezaposleni'] ?? false),
            'bonus_green_innovative' => (bool) ($flags['bonus_green_innovative'] ?? false),
        ])->save();
    }

    /**
     * Chairman "Sačuvaj ocjene" locks the personal score and youth bonuses together.
     * Bonus confirmation/audit are written from the server member record, not the request.
     *
     * @param  array<string, mixed>  $criteria
     * @param  array<string, mixed>  $flags
     */
    public function recordYouthChairmanFinalScoreAndBonuses(
        Application $application,
        CommissionMember $member,
        array $criteria,
        ?string $notes,
        bool $confirmed,
        array $flags,
    ): EvaluationScore {
        return DB::transaction(function () use ($application, $member, $criteria, $notes, $confirmed, $flags) {
            $recorded = $this->recordYouthFinalScore(
                $application,
                $member,
                $criteria,
                $notes,
                $confirmed,
            );

            $locked = Application::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
            if ($locked->bonuses_confirmed_at === null) {
                $this->saveYouthBonuses($locked, $member, $flags, true);
            }

            return $recorded;
        });
    }

    /**
     * @param  array<string, mixed>  $flags
     */
    public function saveYouthBonuses(
        Application $application,
        CommissionMember $member,
        array $flags,
        bool $confirm,
    ): void {
        DB::transaction(function () use ($application, $member, $flags, $confirm) {
            $locked = Application::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
            $locked->load(['competition', 'eliminatoryCheck', 'prigovor']);

            $this->assertYouthBonusGates($locked, $member);

            $normalized = $this->normalizedYouthBonusFlags($locked, $flags);

            $payload = [
                'bonus_info_day' => $normalized['bonus_info_day'],
                'bonus_training' => $normalized['bonus_training'],
                'bonus_new_business' => $normalized['bonus_new_business'],
                'bonus_green_innovative' => $normalized['bonus_green_innovative'],
            ];

            if ($confirm) {
                $payload['bonuses_confirmed_at'] = now();
                $payload['bonuses_confirmed_by_user_id'] = $member->user_id;
                $payload['bonuses_confirmed_by_commission_member_id'] = $member->id;
                $payload['bonuses_confirmed_by_name'] = $member->name;
            }

            $locked->forceFill($payload)->save();
            if ($confirm) {
                $this->persistYouthAggregateIfReady($locked);
            }
        });

        $application->loadMissing('competition');
        if ($application->competition) {
            $this->persistYouthPreliminaryRankingIfReady($application->competition);
        }
    }

    public function youthQualifiesForPlannedRegistrationBonus(Application $application): bool
    {
        if ($application->applicant_type === \App\Support\KnApplicationClassification::FORM_FIZICKO_LICE) {
            return true;
        }

        return $application->applicant_type === \App\Support\KnApplicationClassification::FORM_PRIVREDNO_DRUSTVO
            && ! (bool) $application->is_registered;
    }

    public function youthBonusScore(Application $application): int
    {
        $bonus = 0;
        if ((bool) $application->bonus_info_day && (bool) $application->bonus_training) {
            $bonus += 1;
        }
        if ((bool) $application->bonus_new_business && $this->youthQualifiesForPlannedRegistrationBonus($application)) {
            $bonus += 2;
        }
        if ((bool) $application->bonus_green_innovative) {
            $bonus += 3;
        }

        return min($bonus, 6);
    }

    public function youthMeetsMinimumScore(Application $application): bool
    {
        $full = $this->youthFullPrecisionFinalScore($application);
        if ($full === null) {
            return false;
        }

        return bccomp($full, '30', self::YOUTH_BONUS_SCALE) >= 0;
    }

    public function persistYouthAggregateIfReady(Application $application): void
    {
        $run = function () use ($application) {
            $locked = Application::query()->whereKey($application->id)->lockForUpdate()->first();
            if ($locked === null) {
                return;
            }

            $locked->loadMissing('competition');
            if (! $locked->competition?->isOmladinskoProfile()) {
                return;
            }

            if ($locked->bonuses_confirmed_at === null) {
                return;
            }

            EvaluationScore::query()
                ->where('application_id', $locked->id)
                ->lockForUpdate()
                ->get();
            $locked->unsetRelation('evaluationScores');

            $aggregate = $this->aggregateYouthApplication($locked);
            if ($aggregate === null) {
                return;
            }

            $display = $aggregate['final_score_display'];
            if ($locked->final_score !== null && bccomp((string) $locked->final_score, $display, 2) === 0) {
                return;
            }

            $locked->forceFill(['final_score' => $display])->save();
        };

        if (DB::transactionLevel() > 0) {
            $run();

            return;
        }

        DB::transaction($run);
    }

    /**
     * @return array{
     *     criterion_averages: array<int, string>,
     *     base_score: string,
     *     bonus: int,
     *     final_score_full: string,
     *     final_score_display: string
     * }|null
     */
    public function aggregateYouthApplication(Application $application): ?array
    {
        if (! $application->competition?->isOmladinskoProfile() && ! $application->isOmladinskoProfile()) {
            return null;
        }

        if (! $this->applicationHasYouthCanonicalSeats($application)) {
            return null;
        }

        $bySeat = $this->completedEvaluationsBySeat($application);
        $averages = [];
        $base = '0';

        for ($i = 1; $i <= 10; $i++) {
            $sum = '0';
            foreach (ScoringProfileConfig::for('omladinsko')->allowedSeats as $seat) {
                $sum = bcadd($sum, (string) (int) $bySeat[$seat]->{"criterion_{$i}"}, self::YOUTH_BONUS_SCALE);
            }
            $average = bcdiv($sum, '3', self::YOUTH_BONUS_SCALE);
            $averages[$i] = $average;
            $base = bcadd($base, $average, self::YOUTH_BONUS_SCALE);
        }

        $bonus = $this->youthBonusScore($application);
        $full = bcadd($base, (string) $bonus, self::YOUTH_BONUS_SCALE);

        return [
            'criterion_averages' => $averages,
            'base_score' => $base,
            'bonus' => $bonus,
            'final_score_full' => $full,
            'final_score_display' => $this->bcRound($full, 2),
        ];
    }

    /**
     * @return array{
     *     criterion_averages: array<int, float>,
     *     base_score: float,
     *     bonus: int,
     *     final_score: float
     * }|null
     */
    public function aggregateApplication(Application $application): ?array
    {
        if (! $this->applicationHasFiveCanonicalSeats($application)) {
            return null;
        }

        $bySeat = $this->completedEvaluationsBySeat($application);
        $averages = [];
        $base = 0.0;

        for ($i = 1; $i <= 10; $i++) {
            $sum = 0;
            foreach (CommissionCanonicalSeat::SEATS as $seat) {
                $sum += (int) $bySeat[$seat]->{"criterion_{$i}"};
            }
            $average = round($sum / 5, 2);
            $averages[$i] = $average;
            $base += $average;
        }

        $base = round($base, 2);
        $bonus = $application->getBonusScore();

        return [
            'criterion_averages' => $averages,
            'base_score' => $base,
            'bonus' => $bonus,
            'final_score' => round($base + $bonus, 2),
        ];
    }

    public function persistZenskoApplicationAggregatesIfCycleComplete(Competition $competition): void
    {
        $competition = $competition->fresh(['commission']);
        if (! $this->isIndividualScoringCycleComplete($competition)) {
            return;
        }

        DB::transaction(function () use ($competition) {
            $eligible = $this->scoringEligibleApplications($competition);
            $aggregates = [];

            foreach ($eligible as $application) {
                $aggregate = $this->aggregateApplication($application);
                if ($aggregate === null) {
                    throw new \RuntimeException(
                        "Fail-closed scoring cycle finalization: eligible application {$application->id} is not exactly seats 1–5."
                    );
                }
                $aggregates[$application->id] = $aggregate;
            }

            foreach ($eligible as $application) {
                $aggregate = $aggregates[$application->id];
                $updates = [];
                if ($application->final_score === null
                    || (float) $application->final_score !== (float) $aggregate['final_score']) {
                    $updates['final_score'] = $aggregate['final_score'];
                }
                if ($application->evaluated_at === null) {
                    $updates['evaluated_at'] = now();
                }
                if ($updates !== []) {
                    $application->forceFill($updates)->save();
                }
            }

            if (! in_array($competition->status, ['closed', 'completed'], true)) {
                $this->assignAboveLineRankingPositions($eligible);
            }
        });
    }

    /**
     * Competition ranking (KN-FS-003 §14.6): equal final_score → shared rank,
     * next distinct score skips places (e.g. 1, 2, 2, 4).
     * Sort by id is only for deterministic order within a tied score group;
     * it must not produce distinct ranking_position values.
     *
     * @param  Collection<int, Application>  $eligible
     */
    public function assignAboveLineRankingPositions(Collection $eligible): void
    {
        $aboveLine = $eligible
            ->filter(function (Application $application) {
                return ! $application->isEliminatedFromScoring()
                    && $application->meetsMinimumScore();
            })
            ->sort(function (Application $a, Application $b) {
                $aScore = (float) ($a->final_score ?? 0);
                $bScore = (float) ($b->final_score ?? 0);
                if ($aScore !== $bScore) {
                    return $bScore <=> $aScore;
                }

                return $a->id <=> $b->id;
            })
            ->values();

        $previousScore = null;
        $rank = 1;

        foreach ($aboveLine as $index => $application) {
            $score = (float) ($application->final_score ?? 0);

            if ($previousScore === null) {
                $rank = 1;
            } elseif ($score !== $previousScore) {
                $rank = $index + 1;
            }

            if ((int) $application->ranking_position !== $rank) {
                $application->forceFill(['ranking_position' => $rank])->save();
            }

            $previousScore = $score;
        }
    }

    private function canonicalGovernance(): CanonicalCommissionGovernance
    {
        return app(CanonicalCommissionGovernance::class);
    }

    /**
     * @return Collection<int, EvaluationScore>
     */
    private function canonicalScoreRows(Application $application): Collection
    {
        return EvaluationScore::query()
            ->where('application_id', $application->id)
            ->with('commissionMember')
            ->orderBy('id')
            ->get();
    }

    private function canonicalRowIsFinal(EvaluationScore $score): bool
    {
        $profile = ScoringProfileConfig::for(CompetitionProfileResolver::TYPE_CANONICAL);
        if ($score->completed_at === null || ! $profile->allowsSeat((int) $score->canonical_seat_no)) {
            return false;
        }

        foreach (array_keys($profile->criteria) as $criterion) {
            $value = $score->{"criterion_{$criterion}"};
            if ($value === null || (int) $value < 1 || (int) $value > 5) {
                return false;
            }
        }

        return true;
    }

    /**
     * Canonical has no drafts: every row must be a complete final score of a seat 1–3 whose
     * persisted identity matches the member of this Commission. Anything else fails closed.
     *
     * @param  Collection<int, EvaluationScore>  $rows
     */
    private function canonicalRowsAreCorrupt(Application $application, Collection $rows): bool
    {
        $application->loadMissing('competition');
        $commissionId = (int) ($application->competition?->commission_id ?? 0);
        $seen = [];

        foreach ($rows as $row) {
            if (! $this->canonicalRowIsFinal($row)) {
                return true;
            }

            $seat = (int) $row->canonical_seat_no;
            $member = $row->commissionMember;
            if (isset($seen[$seat])
                || $member === null
                || (int) $member->commission_id !== $commissionId
                || (int) $member->canonical_seat_no !== $seat) {
                return true;
            }
            $seen[$seat] = true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<string, int>
     */
    private function normalizedCanonicalCriteria(ScoringProfileConfig $profile, array $criteria): array
    {
        $normalized = [];
        $errors = [];
        foreach (array_keys($profile->criteria) as $criterion) {
            $key = "criterion_{$criterion}";
            $raw = $criteria[$key] ?? null;
            $valid = (is_int($raw) || (is_string($raw) && ctype_digit($raw))) && (int) $raw >= 1 && (int) $raw <= 5;
            if (! $valid) {
                $errors[$key] = "Kriterijum {$criterion} mora biti cijeli broj od 1 do 5.";

                continue;
            }
            $normalized[$key] = (int) $raw;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $normalized;
    }

    private function hasBlockingPartialRow(Application $application): bool
    {
        $scores = $application->relationLoaded('evaluationScores')
            ? $application->evaluationScores
            : EvaluationScore::query()
                ->where('application_id', $application->id)
                ->with('commissionMember.commission.members')
                ->get();

        foreach ($scores as $score) {
            if (! CommissionCanonicalSeat::isPartial($score)) {
                continue;
            }

            $seat = $this->resolvedSeatOf($score);
            if ($seat !== null && $seat >= 1 && $seat <= 5) {
                return true;
            }

            return true;
        }

        return false;
    }

    private function resolvedSeatOf(EvaluationScore $score): ?int
    {
        if ($score->canonical_seat_no !== null) {
            $seat = (int) $score->canonical_seat_no;

            return ($seat >= 1 && $seat <= 5) ? $seat : null;
        }

        $score->loadMissing('commissionMember.commission.members');

        return CommissionCanonicalSeat::resolvePersistedOrInfer($score->commissionMember);
    }

    /**
     * @param  Collection<int, Application>  $applications
     */
    private function legacyArchivedCycleIsComplete(Collection $applications): bool
    {
        $historicallyScored = $applications->filter(function (Application $application) {
            return count($this->completedEvaluationsBySeat($application)) > 0;
        });

        if ($historicallyScored->isEmpty()) {
            return true;
        }

        foreach ($historicallyScored as $application) {
            if (! $this->applicationHasFiveCanonicalSeats($application)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return Collection<int, Application>
     */
    private function cycleApplications(Competition $competition): Collection
    {
        return $competition->applications()
            ->whereIn('status', ['submitted', 'evaluated', 'rejected', 'approved'])
            ->with(['eliminatoryCheck', 'prigovor', 'evaluationScores.commissionMember.commission.members'])
            ->get();
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>
     */
    private function finalScorePayload(
        Application $application,
        CommissionMember $member,
        array $criteria,
        ?string $notes,
        int $seat,
    ): array {
        $total = 0;
        $payload = [
            'application_id' => $application->id,
            'commission_member_id' => $member->id,
            'canonical_seat_no' => $seat,
            'notes' => $notes,
            'completed_at' => now(),
        ];

        for ($i = 1; $i <= 10; $i++) {
            $value = (int) $criteria["criterion_{$i}"];
            $payload["criterion_{$i}"] = $value;
            $total += $value;
        }

        $payload['final_score'] = $total;

        return $payload;
    }

    private function assertYouthDraftGates(Application $application, CommissionMember $member): void
    {
        if (! $application->competition?->isOmladinskoProfile()) {
            abort(403, ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE);
        }

        if (! $this->eliminatoryChecks->scoringIsAllowed($application)) {
            abort(403, $this->eliminatoryChecks->isConfirmedFail($application)
                ? ApplicationEliminatoryCheckService::CONFIRMED_FAIL_SCORING_MESSAGE
                : ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE);
        }

        $expectedCommissionId = (int) ($application->competition?->commission_id ?? 0);
        if ($expectedCommissionId === 0 || (int) $member->commission_id !== $expectedCommissionId || $member->status !== 'active') {
            abort(403, 'Niste član komisije.');
        }

        $seat = $member->canonical_seat_no !== null ? (int) $member->canonical_seat_no : 0;
        if (! ScoringProfileConfig::for('omladinsko')->allowsSeat($seat)) {
            abort(403, self::INVALID_SEAT_MESSAGE);
        }
    }

    private function resolveYouthSeat(CommissionMember $member): int
    {
        $seat = $member->canonical_seat_no !== null ? (int) $member->canonical_seat_no : 0;
        if (! ScoringProfileConfig::for('omladinsko')->allowsSeat($seat)) {
            abort(403, self::INVALID_SEAT_MESSAGE);
        }

        return $seat;
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>
     */
    private function youthFinalPayload(
        Application $application,
        CommissionMember $member,
        array $criteria,
        ?string $notes,
        int $seat,
    ): array {
        $payload = $this->finalScorePayload($application, $member, $criteria, $notes, $seat);
        $payload['final_score'] = null;

        return $payload;
    }

    private function markYouthEvaluatedIfThreeSeatsLocked(Application $application): void
    {
        if (! $this->applicationHasYouthCanonicalSeats($application->fresh(['evaluationScores.commissionMember.commission.members']))) {
            return;
        }

        $application->refresh();
        if ($application->status !== 'submitted') {
            return;
        }

        $application->forceFill([
            'status' => 'evaluated',
            'evaluated_at' => now(),
        ])->save();
    }

    private function applicationBelongsToYouthScoringCycle(Application $application): bool
    {
        $application->loadMissing([
            'eliminatoryCheck',
            'eliminatoryNotice',
            'prigovor',
            'evaluationScores.commissionMember.commission.members',
            'competition',
        ]);

        if ($application->eliminatoryCheck?->isConfirmed() !== true) {
            return false;
        }

        if ($this->youthSecondSessionGate->hasFinalRemainingReason($application)) {
            return false;
        }

        if ($application->prigovor?->isPodnesen() === true) {
            return false;
        }

        if ($this->youthSecondSessionGate->appealWindowIsOpen($application)) {
            return false;
        }

        $passPath = $application->eliminatoryCheck->isConfirmedPass();
        $liftPath = $application->prigovor?->liftsEliminatoryBar() === true;
        if (! $passPath && ! $liftPath) {
            return false;
        }

        return $this->applicationHasYouthCanonicalSeats($application);
    }

    private function assertYouthBonusGates(Application $application, CommissionMember $member): void
    {
        if (! $application->competition?->isOmladinskoProfile()) {
            abort(403, ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE);
        }

        $expectedCommissionId = (int) ($application->competition?->commission_id ?? 0);
        if ($expectedCommissionId === 0
            || (int) $member->commission_id !== $expectedCommissionId
            || $member->status !== 'active'
            || $member->position !== 'predsjednik') {
            abort(403, self::YOUTH_BONUS_CHAIRMAN_REQUIRED_MESSAGE);
        }

        if (! $application->competition->hasCompleteValidCommission()) {
            abort(403, ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE);
        }

        if (! $this->eliminatoryChecks->scoringIsAllowed($application)) {
            abort(403, $this->eliminatoryChecks->isConfirmedFail($application)
                ? ApplicationEliminatoryCheckService::CONFIRMED_FAIL_SCORING_MESSAGE
                : ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE);
        }

        if ($application->bonuses_confirmed_at !== null) {
            abort(403, self::YOUTH_BONUS_LOCKED_MESSAGE);
        }
    }

    /**
     * @param  array<string, mixed>  $flags
     * @return array{bonus_info_day: bool, bonus_training: bool, bonus_new_business: bool, bonus_green_innovative: bool}
     */
    private function normalizedYouthBonusFlags(Application $application, array $flags): array
    {
        $newBusiness = (bool) ($flags['bonus_new_business'] ?? false);
        if ($newBusiness && ! $this->youthQualifiesForPlannedRegistrationBonus($application)) {
            throw ValidationException::withMessages([
                'bonus_new_business' => self::YOUTH_BONUS_NEW_BUSINESS_INVALID_MESSAGE,
            ]);
        }

        return [
            'bonus_info_day' => (bool) ($flags['bonus_info_day'] ?? false),
            'bonus_training' => (bool) ($flags['bonus_training'] ?? false),
            'bonus_new_business' => $newBusiness,
            'bonus_green_innovative' => (bool) ($flags['bonus_green_innovative'] ?? false),
        ];
    }

    private function youthFullPrecisionFinalScore(Application $application): ?string
    {
        $application->loadMissing('competition');
        if (! $application->competition?->isOmladinskoProfile()) {
            return null;
        }

        if ($application->bonuses_confirmed_at === null) {
            return null;
        }

        $aggregate = $this->aggregateYouthApplication($application);

        return $aggregate['final_score_full'] ?? null;
    }

    private function bcRound(string $value, int $scale): string
    {
        $negative = str_starts_with($value, '-');
        $absolute = $negative ? substr($value, 1) : $value;
        $nudge = bcdiv('5', bcpow('10', (string) ($scale + 1), 0), $scale + 1);
        $rounded = bcadd($absolute, $nudge, $scale);

        return $negative ? '-'.$rounded : $rounded;
    }
}
