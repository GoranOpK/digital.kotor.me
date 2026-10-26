<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\ApplicationPrigovor;
use App\Models\ApplicationPrigovorMappingCorrection;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Support\YouthPrigovorObrazlozenje;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class YouthPrigovorMappingCorrectionService
{
    public const APPLICATION_ID = 107;

    public const FROM_CRITERION = 2;

    public const TO_CRITERION = 1;

    public const REASON_REQUIRED_MESSAGE = 'Obrazloženje predsjednika za korekciju mapiranja je obavezno.';

    public const NOT_ELIGIBLE_MESSAGE = 'Jednokratna korekcija mapiranja nije dostupna za ovo stanje prijave.';

    public const ALREADY_CORRECTED_MESSAGE = 'Korekcija mapiranja za ovu prijavu je već evidentirana.';

    public const PENDING_CORRECTION_MESSAGE = 'Najprije je potrebna jednokratna auditovana korekcija mapiranja Prigovora.';

    public const LATE_DOCUMENT_LIFT_MESSAGE = 'Dokument dodat nakon roka za prijave ne može se uzeti kao blagovremen dokaz za otklanjanje razloga nepotpune dokumentacije.';

    public const UNVERIFIABLE_TIMELINESS_MESSAGE = 'Odluka Otklonjen je blokirana jer se ne može pouzdano utvrditi rok za prijave niti blagovremenost priloga.';

    public const INCOMPLETE_COMMISSION_MESSAGE = ApplicationPrigovorService::YOUTH_COMMISSION_INCOMPLETE_MESSAGE;

    public function recordedCorrection(Application $application): ?ApplicationPrigovorMappingCorrection
    {
        return ApplicationPrigovorMappingCorrection::query()
            ->where('application_id', $application->id)
            ->first();
    }

    public function isPendingUncorrected(Application $application): bool
    {
        return $this->mismatchStateReason($application) === null
            && $this->recordedCorrection($application) === null;
    }

    public function chairmanCanCorrect(
        ?CommissionMember $commissionMember,
        Application $application,
        ?ApplicationPrigovor $prigovor,
    ): bool {
        if (
            ! $commissionMember
            || $commissionMember->position !== 'predsjednik'
            || $commissionMember->status !== 'active'
            || $prigovor?->isPodnesen() !== true
        ) {
            return false;
        }

        $application->loadMissing(['competition.commission.activeMembers', 'eliminatoryCheck', 'prigovor']);

        if ($application->competition?->hasCompleteValidCommission() !== true) {
            return false;
        }

        return $this->isPendingUncorrected($application);
    }

    /**
     * @return string|null Null when the locked 107 mismatch is present and uncorrected.
     */
    public function mismatchStateReason(Application $application): ?string
    {
        if ((int) $application->id !== self::APPLICATION_ID) {
            return 'Akcija je vezana isključivo za prijavu ID 107.';
        }

        $application->loadMissing(['competition', 'eliminatoryCheck', 'prigovor']);

        if ($application->competition?->isOmladinskoProfile() !== true) {
            return 'Prijava 107 nije omladinski konkurs.';
        }

        $check = $application->eliminatoryCheck;
        if ($check?->isConfirmedFail() !== true) {
            return 'Zaključani M3 nije potvrđeni pad.';
        }

        if ($check->activatedCriterionNumbers() !== [self::TO_CRITERION]) {
            return 'Zaključani M3 nije aktivirao samo kriterijum potpune dokumentacije.';
        }

        $prigovor = $application->prigovor;
        if ($prigovor === null || ! $prigovor->isPodnesen()) {
            return 'Prigovor nije u stanju Podnesen.';
        }

        if ($prigovor->isFinished()) {
            return 'Komisija je već donijela odluku po Prigovoru.';
        }

        if ($prigovor->criterionIsContested(self::FROM_CRITERION) !== true) {
            return 'Zapis ne označava kriterijum 2 (M4/M4a) kao osporen.';
        }

        if ($prigovor->criterionIsContested(self::TO_CRITERION)) {
            return 'Kriterijum dokumentacije je već označen kao osporen.';
        }

        return null;
    }

    public function correct(Application $application, CommissionMember $chairman, string $reason): ApplicationPrigovorMappingCorrection
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages([
                'correction_reason' => self::REASON_REQUIRED_MESSAGE,
            ]);
        }

        try {
            return DB::transaction(function () use ($application, $chairman, $reason) {
                /** @var Application $locked */
                $locked = Application::query()
                    ->whereKey($application->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $locked->load(['competition', 'eliminatoryCheck', 'prigovor']);

                $this->assertChairmanOfCompleteCommission($locked, $chairman);

                if ($this->recordedCorrection($locked) !== null) {
                    abort(403, self::ALREADY_CORRECTED_MESSAGE);
                }

                $mismatch = $this->mismatchStateReason($locked);
                if ($mismatch !== null) {
                    abort(403, self::NOT_ELIGIBLE_MESSAGE);
                }

                $prigovor = ApplicationPrigovor::query()
                    ->where('application_id', $locked->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $check = ApplicationEliminatoryCheck::query()
                    ->where('application_id', $locked->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $before = $this->snapshot($locked, $prigovor, $check);

                $prigovor->criterion_1_contested = true;
                $prigovor->criterion_2_contested = null;
                $prigovor->criterion_3_contested = null;
                $prigovor->save();

                $afterPrigovor = $prigovor->fresh();
                $after = $this->snapshot($locked->fresh(['eliminatoryCheck']), $afterPrigovor, $check->fresh());

                return ApplicationPrigovorMappingCorrection::create([
                    'application_id' => $locked->id,
                    'prigovor_id' => $prigovor->id,
                    'from_criterion' => self::FROM_CRITERION,
                    'to_criterion' => self::TO_CRITERION,
                    'before_snapshot' => $before,
                    'after_snapshot' => $after,
                    'reason' => $reason,
                    'corrected_by_commission_member_id' => $chairman->id,
                    'corrected_by_user_id' => $chairman->user_id,
                    'corrected_by_name' => $chairman->name,
                    'created_at' => now(),
                ]);
            });
        } catch (QueryException $e) {
            if ($this->isDuplicateCorrectionConstraint($e)) {
                abort(403, self::ALREADY_CORRECTED_MESSAGE);
            }

            throw $e;
        }
    }

    /**
     * @param  array<int|string, mixed>  $criterionOutcomes
     */
    public function assertDecisionAllowed(Application $application, ApplicationPrigovor $prigovor, array $criterionOutcomes): void
    {
        if ((int) $application->id !== self::APPLICATION_ID) {
            return;
        }

        if ($this->isPendingUncorrected($application)) {
            abort(403, self::PENDING_CORRECTION_MESSAGE);
        }

        $wantsLift = false;
        foreach ($criterionOutcomes as $key => $value) {
            if ((int) $key === self::TO_CRITERION && $value === ApplicationPrigovor::OUTCOME_OTKLONJEN) {
                $wantsLift = true;
                break;
            }
        }

        if (! $wantsLift) {
            return;
        }

        $timeliness = $this->documentationTimeliness($application);
        if ($timeliness['verifiable'] !== true) {
            throw ValidationException::withMessages([
                'criterion_outcomes.'.self::TO_CRITERION => self::UNVERIFIABLE_TIMELINESS_MESSAGE,
            ]);
        }

        if ($timeliness['untimely'] !== []) {
            throw ValidationException::withMessages([
                'criterion_outcomes.'.self::TO_CRITERION => self::LATE_DOCUMENT_LIFT_MESSAGE,
            ]);
        }
    }

    /**
     * @return array{verifiable: bool, deadline_at: ?string, timely: list<array{id: int, created_at: ?string}>, untimely: list<array{id: int, created_at: ?string}>}
     */
    public function documentationTimeliness(Application $application): array
    {
        $application->loadMissing(['competition', 'documents']);
        $deadline = $application->competition?->deadline;
        $timely = [];
        $untimely = [];

        if ($deadline === null) {
            return [
                'verifiable' => false,
                'deadline_at' => null,
                'timely' => [],
                'untimely' => [],
            ];
        }

        foreach ($application->documents as $document) {
            $row = [
                'id' => (int) $document->id,
                'created_at' => $document->created_at?->toIso8601String(),
            ];
            if ($document->created_at === null || $document->created_at->gt($deadline)) {
                $untimely[] = $row;
            } else {
                $timely[] = $row;
            }
        }

        return [
            'verifiable' => true,
            'deadline_at' => $deadline->toIso8601String(),
            'timely' => $timely,
            'untimely' => $untimely,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Application $application, ApplicationPrigovor $prigovor, ApplicationEliminatoryCheck $check): array
    {
        return [
            'application_id' => (int) $application->id,
            'application_status' => $application->status,
            'prigovor_status' => $prigovor->status,
            'submitted_at' => $prigovor->submitted_at?->toIso8601String(),
            'komisija_deadline_at' => $prigovor->submitted_at
                ? $prigovor->komisijaDeadlineAt()->toIso8601String()
                : null,
            'criterion_1_contested' => $prigovor->criterion_1_contested,
            'criterion_2_contested' => $prigovor->criterion_2_contested,
            'criterion_3_contested' => $prigovor->criterion_3_contested,
            'criterion_1_remaining' => $prigovor->criterion_1_remaining,
            'criterion_2_remaining' => $prigovor->criterion_2_remaining,
            'criterion_3_remaining' => $prigovor->criterion_3_remaining,
            'obrazlozenje' => $prigovor->obrazlozenje,
            'applicant_explanation_1' => YouthPrigovorObrazlozenje::explanation($prigovor->obrazlozenje, 1),
            'applicant_explanation_2' => YouthPrigovorObrazlozenje::explanation($prigovor->obrazlozenje, 2),
            'applicant_explanation_3' => YouthPrigovorObrazlozenje::explanation($prigovor->obrazlozenje, 3),
            'm3' => [
                'criterion_1' => $check->criterion_1,
                'criterion_2' => $check->criterion_2,
                'criterion_3' => $check->criterion_3,
                'confirmed_at' => $check->confirmed_at?->toIso8601String(),
                'note' => $check->note,
            ],
        ];
    }

    private function assertChairmanOfCompleteCommission(Application $application, CommissionMember $chairman): void
    {
        if ($chairman->position !== 'predsjednik' || $chairman->status !== 'active') {
            abort(403, 'Samo predsjednik Komisije, u ime Komisije, može evidentirati korekciju mapiranja.');
        }

        if ((int) $chairman->commission_id !== (int) $application->competition?->commission_id) {
            abort(403, 'Samo predsjednik Komisije konkretnog Konkursa može evidentirati korekciju mapiranja.');
        }

        $commissionId = $application->competition?->commission_id;
        if (! $commissionId) {
            abort(403, self::INCOMPLETE_COMMISSION_MESSAGE);
        }

        Commission::query()->whereKey($commissionId)->lockForUpdate()->first();

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
            || (int) $lockedChairman->user_id !== (int) $chairman->user_id
        ) {
            abort(403, 'Samo predsjednik Komisije, u ime Komisije, može evidentirati korekciju mapiranja.');
        }

        $presidents = $activeMembers->where('position', 'predsjednik');
        if ($presidents->count() !== 1 || (int) $presidents->first()->id !== (int) $chairman->id) {
            abort(403, self::INCOMPLETE_COMMISSION_MESSAGE);
        }

        $application->competition?->unsetRelation('commission');
        if ($application->competition?->hasCompleteValidCommission() !== true) {
            abort(403, self::INCOMPLETE_COMMISSION_MESSAGE);
        }
    }

    private function isDuplicateCorrectionConstraint(QueryException $e): bool
    {
        $driverCode = (int) ($e->errorInfo[1] ?? 0);
        $message = strtolower($e->getMessage());

        return $driverCode === 1062 && str_contains($message, 'apmc_application_id_unique');
    }
}
