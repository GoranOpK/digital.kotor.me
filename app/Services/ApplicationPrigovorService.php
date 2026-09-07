<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationEliminatoryNotice;
use App\Models\ApplicationPrigovor;
use App\Models\CommissionMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationPrigovorService
{
    public const WINDOW_CLOSED_MESSAGE = 'Rok za podnošenje Prigovora je istekao.';

    public const ALREADY_SUBMITTED_MESSAGE = 'Prigovor za ovu prijavu je već podnesen.';

    public const FINISHED_MESSAGE = 'Završen Prigovor ne može se ponovo otvoriti.';

    public const NOT_PENDING_MESSAGE = 'Odluka po Prigovoru je moguća samo dok je Prigovor u stanju Podnesen.';

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

    public function decide(
        Application $application,
        CommissionMember $chairman,
        string $odluka,
        ?string $decisionNote,
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
        $decisionNote = $decisionNote === '' ? null : $decisionNote;

        return DB::transaction(function () use ($application, $chairman, $odluka, $decisionNote) {
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

            $prigovor->status = $odluka;
            $prigovor->decided_at = now();
            $prigovor->decided_by_commission_member_id = $chairman->id;
            $prigovor->decided_by_user_id = $chairman->user_id;
            $prigovor->decided_by_name = $chairman->name;
            $prigovor->decision_note = $decisionNote;
            $prigovor->eliminatory_reason_remaining = $odluka !== ApplicationPrigovor::STATUS_PRIHVACEN;
            $prigovor->save();

            return $prigovor->fresh();
        });
    }
}
