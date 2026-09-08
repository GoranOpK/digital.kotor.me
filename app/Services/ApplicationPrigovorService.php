<?php

namespace App\Services;

use App\Mail\ApplicationPrigovorDecisionMail;
use App\Models\Application;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\ApplicationEliminatoryNotice;
use App\Models\ApplicationPrigovor;
use App\Models\CommissionMember;
use App\Models\User;
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

    public function applicantCanSubmit(Application $application, User $user): bool
    {
        if ((int) $application->user_id !== (int) $user->id) {
            return false;
        }

        $application->loadMissing(['eliminatoryNotice', 'prigovor', 'eliminatoryCheck']);

        if ($application->prigovor !== null) {
            return false;
        }

        $notice = $application->eliminatoryNotice;
        if ($notice === null || ! $notice->prigovorWindowIsOpen()) {
            return false;
        }

        return $application->eliminatoryCheck?->isConfirmedFail() === true;
    }

    public function submit(Application $application, User $applicant, string $obrazlozenje): ApplicationPrigovor
    {
        if ((int) $application->user_id !== (int) $applicant->id) {
            abort(403, 'Samo podnositeljka može podnijeti Prigovor za ovu prijavu.');
        }

        $obrazlozenje = trim($obrazlozenje);
        if ($obrazlozenje === '') {
            throw ValidationException::withMessages([
                'obrazlozenje' => 'Obrazloženje Prigovora je obavezno.',
            ]);
        }

        return DB::transaction(function () use ($application, $applicant, $obrazlozenje) {
            $application->loadMissing(['eliminatoryNotice', 'eliminatoryCheck']);

            $existing = ApplicationPrigovor::query()
                ->where('application_id', $application->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                abort(403, self::ALREADY_SUBMITTED_MESSAGE);
            }

            $notice = ApplicationEliminatoryNotice::query()
                ->where('application_id', $application->id)
                ->lockForUpdate()
                ->first();

            if ($notice === null || $application->eliminatoryCheck?->isConfirmedFail() !== true) {
                abort(403, 'Prigovor je moguć samo nakon odbijanja po eliminatornoj provjeri.');
            }

            if (! $notice->prigovorWindowIsOpen()) {
                abort(403, self::WINDOW_CLOSED_MESSAGE);
            }

            $prigovor = ApplicationPrigovor::create([
                'application_id' => $application->id,
                'obrazlozenje' => $obrazlozenje,
                'status' => ApplicationPrigovor::STATUS_PODNESEN,
                'submitted_at' => now(),
                'submitted_by_user_id' => $applicant->id,
                'eliminatory_reason_remaining' => true,
            ]);

            return $prigovor->fresh();
        });
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
