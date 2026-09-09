<?php

namespace App\Services;

use App\Models\Application;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Support\CommissionCanonicalSeat;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CanonicalIndividualScoringService
{
    public const SEAT_ALREADY_FINAL_MESSAGE = 'Kanonsko mjesto Komisije već ima konačnu ocjenu za ovu prijavu. Ponovno bodovanje nije dozvoljeno.';

    public const SCORE_IMMUTABLE_MESSAGE = 'Individualno bodovanje je završeno i ne može se mijenjati.';

    public const CONFIRMATION_REQUIRED_MESSAGE = 'Prije završetka bodovanja morate potvrditi da je ocjena konačna.';

    public const INVALID_SEAT_MESSAGE = 'Ne može se utvrditi kanonsko mjesto Komisije za bodovanje.';

    public const RANKING_LOCKED_MESSAGE = 'Rang lista i zbirni rezultati dostupni su tek kada svih pet kanonskih mjesta Komisije završi individualno bodovanje svih prijava koje su ušle u bodovanje.';

    public const PARTIAL_ROW_MESSAGE = 'Pronađen je nepotpun istorijski red ocjena za ovo kanonsko mjesto. Konačno bodovanje je zaustavljeno do pregleda. Red se ne smije prepisati niti dopuniti.';

    public const BONUS_LOCKED_MESSAGE = 'Dodatni bodovi su trajno zaključani. Izmjena nije dozvoljena nakon završetka cjelokupnog ciklusa individualnog bodovanja.';

    /** @var list<string> */
    public const BONUS_FLAG_KEYS = [
        'bonus_info_day',
        'bonus_new_business',
        'bonus_zavod_nezaposleni',
        'bonus_green_innovative',
    ];

    public function __construct(
        protected ApplicationEliminatoryCheckService $eliminatoryChecks,
    ) {}

    public function isFinalCompleted(?EvaluationScore $score): bool
    {
        if ($score === null) {
            return false;
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
     * @param  array<string, mixed>  $criteria
     */
    public function recordFinalScore(
        Application $application,
        CommissionMember $member,
        array $criteria,
        ?string $notes,
        bool $confirmed,
    ): EvaluationScore {
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

        $application->forceFill([
            'bonus_info_day' => (bool) ($flags['bonus_info_day'] ?? false),
            'bonus_new_business' => (bool) ($flags['bonus_new_business'] ?? false),
            'bonus_zavod_nezaposleni' => (bool) ($flags['bonus_zavod_nezaposleni'] ?? false),
            'bonus_green_innovative' => (bool) ($flags['bonus_green_innovative'] ?? false),
        ])->save();
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

    public function persistApplicationAggregatesIfCycleComplete(Competition $competition): void
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
     * @param  Collection<int, Application>  $eligible
     */
    private function assignAboveLineRankingPositions(Collection $eligible): void
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

        $position = 1;
        foreach ($aboveLine as $application) {
            if ((int) $application->ranking_position !== $position) {
                $application->forceFill(['ranking_position' => $position])->save();
            }
            $position++;
        }
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
}
