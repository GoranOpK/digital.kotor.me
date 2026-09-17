<?php

namespace App\Services;

use App\Mail\ApplicationPrigovorDecisionMail;
use App\Models\Application;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\ApplicationEliminatoryNotice;
use App\Models\ApplicationPrigovor;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\User;
use App\Support\EliminatoryProfileConfig;
use App\Support\YouthPrigovorObrazlozenje;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApplicationPrigovorService
{
    public const WINDOW_CLOSED_MESSAGE = 'Rok za podnošenje Prigovora je istekao.';

    public const ALREADY_SUBMITTED_MESSAGE = 'Prigovor za ovu prijavu je već podnesen.';

    public const FINISHED_MESSAGE = 'Završen Prigovor ne može se ponovo otvoriti.';

    public const NOT_PENDING_MESSAGE = 'Odluka po Prigovoru je moguća samo dok je Prigovor u stanju Podnesen.';

    public const DECISION_NOTE_REQUIRED_MESSAGE = 'Obrazloženje odluke Komisije je obavezno.';

    public const CONTESTED_REQUIRED_MESSAGE = 'Prigovor mora osporiti najmanje jedan aktivirani eliminatorni razlog.';

    public const INACTIVE_CONTESTED_MESSAGE = 'Prigovor može osporiti samo aktivirane eliminatorne razloge.';

    public const CONTESTED_EXPLANATION_REQUIRED_MESSAGE = 'Obrazloženje je obavezno za svaki osporeni kriterijum.';

    public const YOUTH_COMMISSION_INCOMPLETE_MESSAGE = 'Odluku po prigovoru može evidentirati samo predsjednik kompletne Komisije Poziva sa tačno tri aktivna mjesta.';

    public const YOUTH_CONTESTED_OUTCOME_REQUIRED_MESSAGE = 'Za svaki osporeni kriterijum mora se označiti Otklonjen ili Ostaje.';

    public const YOUTH_UNCONTESTED_CANNOT_LIFT_MESSAGE = 'Neosporeni aktivirani razlog ostaje. Ne može se otkloniti.';

    public const YOUTH_INACTIVE_OUTCOME_MESSAGE = 'Ishod se ne evidentira za kriterijum koji nije bio aktiviran.';

    public const NOT_SUBMITTED_MESSAGE = 'Prigovor je moguć samo dok je prijava u stanju submitted.';

    public function __construct(
        protected ApplicationYouthAppealWindowService $youthAppealWindows,
    ) {}

    public function applicantCanSubmit(Application $application, User $user): bool
    {
        if ((int) $application->user_id !== (int) $user->id) {
            return false;
        }

        $this->youthAppealWindows->finalizeExpiredWithoutPrigovor($application);

        $application->loadMissing(['eliminatoryNotice', 'prigovor', 'eliminatoryCheck', 'competition']);

        if ($application->prigovor !== null) {
            return false;
        }

        $notice = $application->eliminatoryNotice;
        if ($notice === null || ! $notice->prigovorWindowIsOpen()) {
            return false;
        }

        return $application->eliminatoryCheck?->isConfirmedFail() === true;
    }

    /**
     * @param  list<int|string>  $contestedNumbers
     * @param  array<int|string, string|null>  $explanationsByCriterion
     */
    public function submit(
        Application $application,
        User $applicant,
        string $obrazlozenje,
        array $contestedNumbers = [],
        array $explanationsByCriterion = [],
    ): ApplicationPrigovor {
        if ((int) $application->user_id !== (int) $applicant->id) {
            abort(403, 'Samo podnositeljka može podnijeti Prigovor za ovu prijavu.');
        }

        $windowClosed = false;

        try {
            $prigovor = DB::transaction(function () use (
                $application,
                $applicant,
                $obrazlozenje,
                $contestedNumbers,
                $explanationsByCriterion,
                &$windowClosed,
            ) {
                /** @var Application $locked */
                $locked = Application::query()
                    ->whereKey($application->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $locked->load('competition');

                if ((int) $locked->user_id !== (int) $applicant->id) {
                    abort(403, 'Samo podnositeljka može podnijeti Prigovor za ovu prijavu.');
                }

                $isYouth = $locked->competition?->isOmladinskoProfile() === true;
                if ($isYouth && $locked->status !== 'submitted') {
                    abort(403, self::NOT_SUBMITTED_MESSAGE);
                }

                $notice = ApplicationEliminatoryNotice::query()
                    ->where('application_id', $locked->id)
                    ->lockForUpdate()
                    ->first();

                $check = ApplicationEliminatoryCheck::query()
                    ->where('application_id', $locked->id)
                    ->lockForUpdate()
                    ->first();

                $existing = ApplicationPrigovor::query()
                    ->where('application_id', $locked->id)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    abort(403, self::ALREADY_SUBMITTED_MESSAGE);
                }

                if ($notice === null || $check?->isConfirmedFail() !== true) {
                    abort(403, 'Prigovor je moguć samo nakon odbijanja po eliminatornoj provjeri.');
                }

                if (! $notice->prigovorWindowIsOpen()) {
                    if ($isYouth) {
                        $this->youthAppealWindows->finalizeExpiredWithoutPrigovorLocked($locked->id);
                    }
                    $windowClosed = true;

                    return null;
                }

                $composedObrazlozenje = trim($obrazlozenje);
                $contestedByCriterion = [1 => null, 2 => null, 3 => null];

                if ($isYouth) {
                    $locked->setRelation('eliminatoryCheck', $check);
                    [$composedObrazlozenje, $contestedByCriterion] = $this->validatedYouthSubmission(
                        $locked,
                        $contestedNumbers,
                        $explanationsByCriterion,
                    );
                } elseif ($composedObrazlozenje === '') {
                    throw ValidationException::withMessages([
                        'obrazlozenje' => 'Obrazloženje Prigovora je obavezno.',
                    ]);
                }

                return ApplicationPrigovor::create([
                    'application_id' => $locked->id,
                    'obrazlozenje' => $composedObrazlozenje,
                    'status' => ApplicationPrigovor::STATUS_PODNESEN,
                    'submitted_at' => now(),
                    'submitted_by_user_id' => $applicant->id,
                    'eliminatory_reason_remaining' => true,
                    'criterion_1_contested' => $contestedByCriterion[1],
                    'criterion_2_contested' => $contestedByCriterion[2],
                    'criterion_3_contested' => $contestedByCriterion[3],
                ]);
            });
        } catch (QueryException $e) {
            if ($this->isDuplicatePrigovorConstraint($e)) {
                abort(403, self::ALREADY_SUBMITTED_MESSAGE);
            }

            throw $e;
        }

        if ($windowClosed) {
            abort(403, self::WINDOW_CLOSED_MESSAGE);
        }

        return $prigovor;
    }

    /**
     * @param  array<int|string, mixed>  $criterionOutcomes
     */
    public function decide(
        Application $application,
        CommissionMember $chairman,
        string $odluka,
        ?string $decisionNote,
        array $criterionOutcomes = [],
    ): ApplicationPrigovor {
        $application->loadMissing('competition');

        if ($application->competition?->isOmladinskoProfile()) {
            return $this->decideYouth($application, $chairman, $decisionNote, $criterionOutcomes);
        }

        if ($chairman->position !== 'predsjednik' || $chairman->status !== 'active') {
            abort(403, 'Samo predsjednik Komisije, u ime Komisije, može evidentirati odluku o Prigovoru.');
        }

        if ((int) $chairman->commission_id !== (int) $application->competition?->commission_id) {
            abort(403, 'Samo predsjednik Komisije konkretnog Konkursa može evidentirati odluku o Prigovoru.');
        }

        if (! in_array($odluka, [ApplicationPrigovor::STATUS_PRIHVACEN, ApplicationPrigovor::STATUS_ODBIJEN], true)) {
            throw ValidationException::withMessages([
                'odluka' => 'Odluka Komisije mora biti Prihvaćen ili Odbijen.',
            ]);
        }

        $decisionNote = $decisionNote !== null ? trim($decisionNote) : '';
        if ($decisionNote === '') {
            throw ValidationException::withMessages([
                'decision_note' => self::DECISION_NOTE_REQUIRED_MESSAGE,
            ]);
        }

        $prigovor = DB::transaction(function () use ($application, $chairman, $odluka, $decisionNote, $criterionOutcomes) {
            $prigovor = ApplicationPrigovor::query()
                ->where('application_id', $application->id)
                ->lockForUpdate()
                ->first();

            if ($prigovor === null) {
                abort(403, 'Ne postoji podneseni Prigovor za ovu prijavu.');
            }

            if ($prigovor->isFinished()) {
                abort(403, self::FINISHED_MESSAGE);
            }

            if (! $prigovor->isPodnesen()) {
                abort(403, self::NOT_PENDING_MESSAGE);
            }

            $check = ApplicationEliminatoryCheck::query()
                ->where('application_id', $application->id)
                ->lockForUpdate()
                ->first();

            if ($check === null || ! $check->isConfirmedFail()) {
                abort(403, 'Odluka po Prigovoru je moguća samo uz potvrđenu eliminatornu provjeru.');
            }

            $remainingByCriterion = $this->validatedRemainingByCriterion($check, $odluka, $criterionOutcomes);

            $prigovor->status = $odluka;
            $prigovor->decided_at = now();
            $prigovor->decided_by_commission_member_id = $chairman->id;
            $prigovor->decided_by_user_id = $chairman->user_id;
            $prigovor->decided_by_name = $chairman->name;
            $prigovor->decision_note = $decisionNote;
            $prigovor->criterion_1_remaining = $remainingByCriterion[1];
            $prigovor->criterion_2_remaining = $remainingByCriterion[2];
            $prigovor->criterion_3_remaining = $remainingByCriterion[3];
            $prigovor->eliminatory_reason_remaining = $this->aggregateRemaining($remainingByCriterion, $odluka);
            $prigovor->save();

            return $prigovor->fresh();
        });

        $this->deliverDecisionEmail($application, $prigovor);

        return $prigovor;
    }

    /**
     * @param  array<int|string, mixed>  $criterionOutcomes
     */
    private function decideYouth(
        Application $application,
        CommissionMember $chairman,
        ?string $decisionNote,
        array $criterionOutcomes,
    ): ApplicationPrigovor {
        if ($chairman->position !== 'predsjednik' || $chairman->status !== 'active') {
            abort(403, 'Samo predsjednik Komisije, u ime Komisije, može evidentirati odluku o Prigovoru.');
        }

        if ((int) $chairman->commission_id !== (int) $application->competition?->commission_id) {
            abort(403, 'Samo predsjednik Komisije konkretnog Konkursa može evidentirati odluku o Prigovoru.');
        }

        $decisionNote = $decisionNote !== null ? trim($decisionNote) : '';
        if ($decisionNote === '') {
            throw ValidationException::withMessages([
                'decision_note' => self::DECISION_NOTE_REQUIRED_MESSAGE,
            ]);
        }

        try {
            $prigovor = DB::transaction(function () use ($application, $chairman, $decisionNote, $criterionOutcomes) {
                /** @var Application $locked */
                $locked = Application::query()
                    ->whereKey($application->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $locked->load('competition');

                $prigovor = ApplicationPrigovor::query()
                    ->where('application_id', $locked->id)
                    ->lockForUpdate()
                    ->first();

                if ($prigovor === null) {
                    abort(403, 'Ne postoji podneseni Prigovor za ovu prijavu.');
                }

                if ($prigovor->isFinished()) {
                    abort(403, self::FINISHED_MESSAGE);
                }

                if (! $prigovor->isPodnesen()) {
                    abort(403, self::NOT_PENDING_MESSAGE);
                }

                $check = ApplicationEliminatoryCheck::query()
                    ->where('application_id', $locked->id)
                    ->lockForUpdate()
                    ->first();

                if ($check === null || ! $check->isConfirmedFail()) {
                    abort(403, 'Odluka po Prigovoru je moguća samo uz potvrđenu eliminatornu provjeru.');
                }

                if (! $prigovor->hasReadableContestedData()) {
                    abort(403, 'Odluka po prigovoru zahtijeva čitljive osporene kriterijume i obrazloženja.');
                }

                $this->assertYouthCommissionDecisionGate($locked, $chairman);

                $remainingByCriterion = $this->validatedYouthRemainingByCriterion($check, $prigovor, $criterionOutcomes);
                $anyRemaining = in_array(true, $remainingByCriterion, true);
                $odluka = $anyRemaining
                    ? ApplicationPrigovor::STATUS_ODBIJEN
                    : ApplicationPrigovor::STATUS_PRIHVACEN;

                $prigovor->status = $odluka;
                $prigovor->decided_at = now();
                $prigovor->decided_by_commission_member_id = $chairman->id;
                $prigovor->decided_by_user_id = $chairman->user_id;
                $prigovor->decided_by_name = $chairman->name;
                $prigovor->decision_note = $decisionNote;
                $prigovor->criterion_1_remaining = $remainingByCriterion[1];
                $prigovor->criterion_2_remaining = $remainingByCriterion[2];
                $prigovor->criterion_3_remaining = $remainingByCriterion[3];
                $prigovor->eliminatory_reason_remaining = $anyRemaining;
                $prigovor->save();

                if ($anyRemaining) {
                    $locked->status = 'rejected';
                    $locked->rejection_reason = $this->youthRemainingRejectionReason($locked, $remainingByCriterion);
                } else {
                    $locked->status = 'submitted';
                    if ($this->youthRejectionReasonBelongsToCycle($locked->rejection_reason, $check)) {
                        $locked->rejection_reason = null;
                    }
                }
                $locked->save();

                return $prigovor->fresh();
            });
        } catch (QueryException $e) {
            if ($this->isRecognizedLockOrUniqueFailure($e)) {
                $existing = ApplicationPrigovor::query()
                    ->where('application_id', $application->id)
                    ->first();
                if ($existing?->isFinished()) {
                    abort(403, self::FINISHED_MESSAGE);
                }
            }

            throw $e;
        }

        $this->deliverDecisionEmail($application, $prigovor);

        return $prigovor;
    }

    private function assertYouthCommissionDecisionGate(Application $application, CommissionMember $chairman): void
    {
        $commissionId = $application->competition?->commission_id;
        if (! $commissionId) {
            abort(403, self::YOUTH_COMMISSION_INCOMPLETE_MESSAGE);
        }

        Commission::query()
            ->whereKey($commissionId)
            ->lockForUpdate()
            ->first();

        $activeMembers = CommissionMember::query()
            ->where('commission_id', $commissionId)
            ->where('status', 'active')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $lockedChairman = $activeMembers->firstWhere('id', $chairman->id);
        if (
            $lockedChairman === null
            || $lockedChairman->position !== 'predsjednik'
            || $lockedChairman->status !== 'active'
            || (int) $lockedChairman->user_id !== (int) $chairman->user_id
        ) {
            abort(403, 'Samo predsjednik Komisije, u ime Komisije, može evidentirati odluku o Prigovoru.');
        }

        $presidents = $activeMembers->where('position', 'predsjednik');
        if ($presidents->count() !== 1 || (int) $presidents->first()->id !== (int) $chairman->id) {
            abort(403, self::YOUTH_COMMISSION_INCOMPLETE_MESSAGE);
        }

        $application->competition?->unsetRelation('commission');
        if ($application->competition?->hasCompleteValidCommission() !== true) {
            abort(403, self::YOUTH_COMMISSION_INCOMPLETE_MESSAGE);
        }
    }

    /**
     * @param  array<int|string, mixed>  $criterionOutcomes
     * @return array{1: ?bool, 2: ?bool, 3: ?bool}
     */
    private function validatedYouthRemainingByCriterion(
        ApplicationEliminatoryCheck $check,
        ApplicationPrigovor $prigovor,
        array $criterionOutcomes,
    ): array {
        $normalized = [];
        foreach ($criterionOutcomes as $key => $value) {
            $number = (int) $key;
            if ($number >= 1 && $number <= 3) {
                $normalized[$number] = is_string($value) ? trim($value) : $value;
            }
        }

        $remaining = [1 => null, 2 => null, 3 => null];
        $errors = [];

        foreach ([1, 2, 3] as $number) {
            $isActivated = $check->criterionIsFalse($check->{"criterion_{$number}"});
            $isContested = $prigovor->criterionIsContested($number);
            $hasPostedOutcome = array_key_exists($number, $normalized)
                && $normalized[$number] !== ''
                && $normalized[$number] !== null;

            if (! $isActivated) {
                if ($hasPostedOutcome) {
                    $errors["criterion_outcomes.{$number}"] = self::YOUTH_INACTIVE_OUTCOME_MESSAGE;
                }
                $remaining[$number] = null;

                continue;
            }

            if (! $isContested) {
                if ($hasPostedOutcome && $normalized[$number] === ApplicationPrigovor::OUTCOME_OTKLONJEN) {
                    $errors["criterion_outcomes.{$number}"] = self::YOUTH_UNCONTESTED_CANNOT_LIFT_MESSAGE;
                }
                $remaining[$number] = true;

                continue;
            }

            if (! $hasPostedOutcome) {
                $errors["criterion_outcomes.{$number}"] = self::YOUTH_CONTESTED_OUTCOME_REQUIRED_MESSAGE;

                continue;
            }

            $outcome = $normalized[$number];
            if (! in_array($outcome, [ApplicationPrigovor::OUTCOME_OTKLONJEN, ApplicationPrigovor::OUTCOME_OSTAJE], true)) {
                $errors["criterion_outcomes.{$number}"] = 'Ishod mora biti Otklonjen ili Ostaje.';

                continue;
            }

            $remaining[$number] = $outcome === ApplicationPrigovor::OUTCOME_OSTAJE;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $remaining;
    }

    /**
     * @param  array{1: ?bool, 2: ?bool, 3: ?bool}  $remainingByCriterion
     */
    private function youthRemainingRejectionReason(Application $application, array $remainingByCriterion): string
    {
        $profile = EliminatoryProfileConfig::for($application->competition?->type);
        $labels = [];
        foreach ([1, 2, 3] as $number) {
            if ($remainingByCriterion[$number] === true) {
                $labels[] = $profile->statement($number);
            }
        }

        return implode('; ', $labels);
    }

    private function youthRejectionReasonBelongsToCycle(?string $reason, ApplicationEliminatoryCheck $check): bool
    {
        if ($reason === null || trim($reason) === '') {
            return false;
        }

        if (str_starts_with($reason, 'Istekao je rok za prigovor')) {
            return true;
        }

        foreach ($check->failedCriterionLabels() as $statement) {
            if ($statement !== '' && str_contains($reason, $statement)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<int|string>  $contestedNumbers
     * @param  array<int|string, string|null>  $explanationsByCriterion
     * @return array{0: string, 1: array{1: ?bool, 2: ?bool, 3: ?bool}}
     */
    private function validatedYouthSubmission(
        Application $application,
        array $contestedNumbers,
        array $explanationsByCriterion,
    ): array {
        $check = $application->eliminatoryCheck;
        if ($check === null || $check->isConfirmedFail() !== true) {
            abort(403, 'Prigovor je moguć samo nakon odbijanja po eliminatornoj provjeri.');
        }

        $normalizedContested = [];
        foreach ($contestedNumbers as $value) {
            $number = (int) $value;
            if ($number >= 1 && $number <= 3) {
                $normalizedContested[$number] = $number;
            }
        }
        $normalizedContested = array_values($normalizedContested);

        if ($normalizedContested === []) {
            throw ValidationException::withMessages([
                'contested' => self::CONTESTED_REQUIRED_MESSAGE,
            ]);
        }

        $errors = [];
        $explanations = [1 => '', 2 => '', 3 => ''];
        $contestedByCriterion = [1 => null, 2 => null, 3 => null];

        foreach ([1, 2, 3] as $number) {
            $isActivated = $check->criterionIsFalse($check->{"criterion_{$number}"});
            $isContested = in_array($number, $normalizedContested, true);

            if ($isContested && ! $isActivated) {
                $errors['contested'] = self::INACTIVE_CONTESTED_MESSAGE;

                continue;
            }

            if (! $isActivated) {
                $contestedByCriterion[$number] = null;

                continue;
            }

            $contestedByCriterion[$number] = $isContested;
            if (! $isContested) {
                continue;
            }

            $text = trim((string) ($explanationsByCriterion[$number] ?? $explanationsByCriterion[(string) $number] ?? ''));
            if ($text === '') {
                $errors["criterion_obrazlozenja.{$number}"] = self::CONTESTED_EXPLANATION_REQUIRED_MESSAGE;

                continue;
            }

            $explanations[$number] = $text;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [YouthPrigovorObrazlozenje::compose($explanations), $contestedByCriterion];
    }

    /**
     * @param  array<int|string, mixed>  $criterionOutcomes
     * @return array{1: ?bool, 2: ?bool, 3: ?bool}
     */
    private function validatedRemainingByCriterion(
        ApplicationEliminatoryCheck $check,
        string $odluka,
        array $criterionOutcomes,
    ): array {
        $normalized = [];
        foreach ($criterionOutcomes as $key => $value) {
            $number = (int) $key;
            if ($number >= 1 && $number <= 3) {
                $normalized[$number] = is_string($value) ? trim($value) : $value;
            }
        }

        $remaining = [1 => null, 2 => null, 3 => null];

        if ($odluka === ApplicationPrigovor::STATUS_ODBIJEN) {
            return $remaining;
        }

        $errors = [];

        foreach ([1, 2, 3] as $number) {
            $isOriginalFail = $check->criterionIsFalse($check->{"criterion_{$number}"});
            $hasPostedOutcome = array_key_exists($number, $normalized) && $normalized[$number] !== '' && $normalized[$number] !== null;

            if (! $isOriginalFail) {
                if ($hasPostedOutcome) {
                    $errors["criterion_outcomes.{$number}"] = 'Ishod se evidentira samo za originalne eliminatorne razloge (Ne). Obrazac 3 se ne mijenja.';
                }

                continue;
            }

            if (! $hasPostedOutcome) {
                $errors["criterion_outcomes.{$number}"] = 'Za svaki originalni razlog Ne mora se označiti Otklonjen ili Ostaje.';

                continue;
            }

            $outcome = $normalized[$number];
            if (! in_array($outcome, [ApplicationPrigovor::OUTCOME_OTKLONJEN, ApplicationPrigovor::OUTCOME_OSTAJE], true)) {
                $errors["criterion_outcomes.{$number}"] = 'Ishod mora biti Otklonjen ili Ostaje.';

                continue;
            }

            $remaining[$number] = $outcome === ApplicationPrigovor::OUTCOME_OSTAJE;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $remaining;
    }

    /**
     * @param  array{1: ?bool, 2: ?bool, 3: ?bool}  $remainingByCriterion
     */
    private function aggregateRemaining(array $remainingByCriterion, string $odluka): bool
    {
        if ($odluka === ApplicationPrigovor::STATUS_ODBIJEN) {
            return true;
        }

        return in_array(true, $remainingByCriterion, true);
    }

    private function isDuplicatePrigovorConstraint(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (int) ($e->errorInfo[1] ?? 0);
        $message = strtolower($e->getMessage());

        if ($driverCode !== 1062 && $sqlState !== '23000') {
            return false;
        }

        return str_contains($message, 'apg_application_id_unique')
            || str_contains($message, 'application_prigovors.application_id');
    }

    private function isRecognizedLockOrUniqueFailure(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        if (in_array($driverCode, [1205, 1213], true) || $sqlState === '40001') {
            return true;
        }

        return $this->isDuplicatePrigovorConstraint($e);
    }

    private function deliverDecisionEmail(Application $application, ApplicationPrigovor $prigovor): void
    {
        $application->loadMissing('user');
        $recipient = $application->user?->email;

        if (! is_string($recipient) || trim($recipient) === '') {
            Log::warning('Prigovor decision mail skipped: missing registered email.', [
                'application_id' => $application->id,
                'prigovor_id' => $prigovor->id,
            ]);

            return;
        }

        try {
            Mail::to($recipient)->send(new ApplicationPrigovorDecisionMail($application, $prigovor));
        } catch (Throwable $e) {
            Log::warning('Prigovor decision mail failed.', [
                'application_id' => $application->id,
                'prigovor_id' => $prigovor->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
