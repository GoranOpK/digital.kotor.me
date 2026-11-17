<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\BusinessPlan;
use Illuminate\Validation\ValidationException;

/** PM only. Serialize regular model writes with FINAL; never repair historical rows. */
final class PmIdentityWriteGuard
{
    private const APPLICATION_FIELDS = [
        'id', 'submitted_at', 'user_id', 'competition_id', 'applicant_type', 'is_registered', 'company_legal_form', 'registration_form',
        'business_plan_name', 'physical_person_name', 'preduzetnik_name', 'doo_name', 'founder_name', 'director_name',
        'physical_person_jmbg', 'physical_person_jmbg_encrypted', 'applicant_jmbg', 'applicant_jmbg_encrypted',
        'physical_person_address', 'physical_person_phone', 'physical_person_email', 'preduzetnik_address',
        'preduzetnik_phone', 'preduzetnik_email', 'doo_address', 'doo_phone', 'doo_email', 'company_seat', 'pib', 'crps_number',
    ];

    private const PLAN_FIELDS = [
        'id', 'application_id', 'applicant_name', 'applicant_jmbg', 'applicant_jmbg_encrypted', 'applicant_address',
        'applicant_phone', 'applicant_email', 'company_name', 'has_registered_business', 'registration_form', 'pib',
    ];

    public function applicationUpdate(Application $model, callable $save): bool
    {
        $db = $model->getConnection();
        $stored = $db->table('applications')->where('id', $model->getRawOriginal($model->getKeyName()))->first();
        if (! $stored || $db->table('competitions')->where('id', $stored->competition_id)->value('type') !== 'omladinsko') {
            return $save();
        }
        return $db->transaction(function () use ($db, $model, $save) {
            $stored = $db->table('applications')->where('id', $model->getRawOriginal($model->getKeyName()))->lockForUpdate()->first();
            if ($stored->status !== 'draft' && ($model->isDirty(self::APPLICATION_FIELDS)
                || ($model->isDirty('status') && $model->status === 'draft'))) {
                $this->deny();
            }
            return $save();
        });
    }

    public function planUpdate(BusinessPlan $model, callable $save, bool $targetChecked = false): bool
    {
        $db = $model->getConnection();
        if (! $targetChecked && $model->isDirty('application_id')) {
            return $db->transaction(function () use ($db, $model, $save) {
                $target = $db->table('applications')->where('id', $model->application_id)->lockForUpdate()->first();
                if ($target && $target->status !== 'draft'
                    && $db->table('competitions')->where('id', $target->competition_id)->value('type') === 'omladinsko') {
                    $this->deny();
                }
                return $this->planUpdate($model, $save, true);
            });
        }
        $applicationId = $db->table('business_plans')->where('id', $model->getRawOriginal($model->getKeyName()))->value('application_id');
        $application = $db->table('applications')->where('id', $applicationId)->first();
        if (! $application || $db->table('competitions')->where('id', $application->competition_id)->value('type') !== 'omladinsko') {
            return $save();
        }
        return $db->transaction(function () use ($db, $model, $save, $applicationId) {
            $application = $db->table('applications')->where('id', $applicationId)->lockForUpdate()->first();
            $db->table('business_plans')->where('id', $model->getRawOriginal($model->getKeyName()))->lockForUpdate()->first();
            if ($application->status !== 'draft' && $model->isDirty(self::PLAN_FIELDS)) {
                $this->deny();
            }
            return $save();
        });
    }

    public function planInsert(BusinessPlan $model, callable $save): bool
    {
        $db = $model->getConnection();
        $application = $db->table('applications')->where('id', $model->application_id)->first();
        if (! $application || $db->table('competitions')->where('id', $application->competition_id)->value('type') !== 'omladinsko') {
            return $save();
        }
        return $db->transaction(function () use ($db, $model, $save) {
            $application = $db->table('applications')->where('id', $model->application_id)->lockForUpdate()->first();
            $identityFields = array_diff(self::PLAN_FIELDS, ['id', 'application_id']);
            if ($application->status !== 'draft' && array_intersect(array_keys($model->getAttributes()), $identityFields) !== []) {
                $this->deny();
            }
            return $save();
        });
    }
    private function deny(): never
    {
        throw ValidationException::withMessages(['identity' => 'Identitet podnesene PM prijave je zakljucan. Ispravka zahtijeva poseban odobren postupak.']);
    }
}
