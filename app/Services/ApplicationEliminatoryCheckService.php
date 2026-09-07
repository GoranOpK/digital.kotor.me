<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\CommissionMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationEliminatoryCheckService
{
    public function __construct(
        protected ApplicationEliminatoryNoticeService $notices,
    ) {}

    public const FAIL_CONFIRMATION_MESSAGE = 'Prijava ne ispunjava jedan ili više eliminatornih kriterijuma i biće odbijena. Da li želite da nastavite?';

    public const SCORING_LOCKED_MESSAGE = 'Individualno bodovanje je dostupno tek nakon što predsjednik Komisije potvrdi Obrazac 3 sa Da / Da / Da.';

    public const CONFIRMED_FAIL_SCORING_MESSAGE = 'Prijava ne prolazi eliminatornu provjeru. Individualno bodovanje nije dostupno.';

    public const IMMUTABLE_MESSAGE = 'Obrazac 3 je potvrđen i ne može se mijenjati.';

    public function scoringIsAllowed(Application $application): bool
    {
        $application->loadMissing(['eliminatoryCheck', 'prigovor']);

        if ($application->eliminatoryCheck?->isConfirmedPass() === true) {
            return true;
        }

        return $application->prigovor?->liftsEliminatoryBar() === true;
    }

    public function isConfirmedFail(Application $application): bool
    {
        $application->loadMissing('eliminatoryCheck');

        return $application->eliminatoryCheck?->isConfirmedFail() === true;
    }

    public function isUnconfirmed(Application $application): bool
    {
        $application->loadMissing('eliminatoryCheck');

        $check = $application->eliminatoryCheck;

        return $check === null || ! $check->isConfirmed();
    }

    /**
     * @param  array{criterion_1: bool, criterion_2: bool, criterion_3: bool, note: ?string}  $answers
     */
    public function saveDraft(Application $application, CommissionMember $chairman, array $answers): ApplicationEliminatoryCheck
    {
        $this->assertChairmanCanMutate($application, $chairman);

        return DB::transaction(function () use ($application, $answers) {
            $check = $this->lockOrNewCheck($application);

            if ($check->isConfirmed()) {
                abort(403, self::IMMUTABLE_MESSAGE);
            }

            $check->fill([
                'criterion_1' => $answers['criterion_1'],
                'criterion_2' => $answers['criterion_2'],
                'criterion_3' => $answers['criterion_3'],
                'note' => $answers['note'],
            ]);
            $check->save();

            return $check->fresh();
        });
    }

    /**
     * @param  array{criterion_1: bool, criterion_2: bool, criterion_3: bool, note: ?string}  $answers
     */
    public function confirm(
        Application $application,
        CommissionMember $chairman,
        array $answers,
        bool $acknowledgement,
    ): ApplicationEliminatoryCheck {
        $this->assertChairmanCanMutate($application, $chairman);

        $check = DB::transaction(function () use ($application, $chairman, $answers, $acknowledgement) {
            $check = $this->lockOrNewCheck($application);

            if ($check->isConfirmed()) {
                abort(403, self::IMMUTABLE_MESSAGE);
            }

            $check->fill([
                'criterion_1' => $answers['criterion_1'],
                'criterion_2' => $answers['criterion_2'],
                'criterion_3' => $answers['criterion_3'],
                'note' => $answers['note'],
            ]);

            if ($check->hasAnyFailCriterion()) {
                if (! $acknowledgement) {
                    throw ValidationException::withMessages([
                        'confirmation_acknowledged' => self::FAIL_CONFIRMATION_MESSAGE,
                    ]);
                }

                if (trim((string) $check->note) === '') {
                    throw ValidationException::withMessages([
                        'note' => 'Napomena je obavezna kada postoji najmanje jedan odgovor Ne*.',
                    ]);
                }
            }

            $check->confirmed_at = now();
            $check->confirmed_by_commission_member_id = $chairman->id;
            $check->confirmed_by_user_id = $chairman->user_id;
            $check->confirmed_by_name = $chairman->name;
            $check->save();

            $check = $check->fresh();

            if ($check->isConfirmedFail()) {
                $this->notices->persistForFailedCheck($application, $check);
            }

            return $check;
        });

        if ($check->isConfirmedFail()) {
            $this->notices->deliverRegisteredEmail($application);
        }

        return $check;
    }

    private function assertChairmanCanMutate(Application $application, CommissionMember $chairman): void
    {
        $application->loadMissing('competition');

        if ($chairman->position !== 'predsjednik' || $chairman->status !== 'active') {
            abort(403, 'Samo predsjednik Komisije može uređivati Obrazac 3.');
        }

        if ((int) $chairman->commission_id !== (int) $application->competition?->commission_id) {
            abort(403, 'Samo predsjednik Komisije konkretnog Konkursa može uređivati Obrazac 3.');
        }
    }

    private function lockOrNewCheck(Application $application): ApplicationEliminatoryCheck
    {
        $existing = ApplicationEliminatoryCheck::query()
            ->where('application_id', $application->id)
            ->lockForUpdate()
            ->first();

        if ($existing) {
            return $existing;
        }

        $check = new ApplicationEliminatoryCheck([
            'application_id' => $application->id,
            'criterion_1' => true,
            'criterion_2' => true,
            'criterion_3' => true,
        ]);
        $check->save();

        return $check;
    }
}
