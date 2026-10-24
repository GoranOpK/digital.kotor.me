<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\Competition;
use App\Models\FinalApplicationClaim;
use App\Models\User;
use App\Services\Competitions\Decision\CanonicalAdministrativeGate;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\Pib;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Canonical FINAL — one atomic transaction:
 * validate (P1a/P1b, Kotor address, P2, P1/P2 subsidy type, čl.14 documents, beneficiary PIB)
 * → freeze → claim → status → submitted_at → redni_broj. No Women business plan / Obrazac 1a/1b gate.
 *
 * Profile isolation: only CompetitionProfileResolver::TYPE_CANONICAL.
 * Women/Youth retain ApplicationController submit path (no claim).
 */
final class FinalSubmissionService
{
    public const CLAIM_CONFLICT_MESSAGE = 'Konačna predaja nije moguća: već postoji konačna prijava ovog podnosioca za istu vrstu subvencije ili je dostignut maksimum od dvije prijave.';

    public function __construct(
        private readonly ApplicationEvidenceFreezer $freezer,
        private readonly FinalKotorAddressCheck $kotorAddress,
        private readonly CanonicalInvestmentPlanService $investmentPlans,
        private readonly CanonicalBeneficiaryApplications $beneficiaries,
        private readonly CanonicalApplicantFormService $applicantForms,
        private readonly CanonicalDocumentService $documents,
    ) {
    }

    public function submit(Application $application, User $actor): FinalSubmissionResult
    {
        if ((int) $application->user_id !== (int) $actor->id) {
            return FinalSubmissionResult::failed('Nemate pristup ovoj prijavi.');
        }

        try {
            return DB::transaction(function () use ($application, $actor) {
                $locked = Application::query()->whereKey($application->id)->lockForUpdate()->first();
                if (! $locked || (int) $locked->user_id !== (int) $actor->id) {
                    return FinalSubmissionResult::failed('Nemate pristup ovoj prijavi.');
                }

                $locked->loadMissing(['competition', 'user']);

                $competition = $locked->competition;
                if (! $competition || $competition->type !== CompetitionProfileResolver::TYPE_CANONICAL) {
                    return FinalSubmissionResult::failed('Konačna predaja preko claim/freeze putanje je dostupna samo za kanonski MMSP profil.');
                }

                if (CanonicalAdministrativeGate::isClosed($competition)) {
                    return FinalSubmissionResult::failed(CanonicalAdministrativeGate::CLOSED_MESSAGE);
                }

                $ownClaim = FinalApplicationClaim::query()
                    ->where('application_id', $locked->id)
                    ->lockForUpdate()
                    ->first();

                if ($ownClaim && $locked->status !== 'draft') {
                    return FinalSubmissionResult::alreadyFinal();
                }

                if ($locked->status !== 'draft') {
                    return FinalSubmissionResult::failed('Prijava je već podnesena ili je u obradi.');
                }

                if ($ownClaim) {
                    return FinalSubmissionResult::failed(self::CLAIM_CONFLICT_MESSAGE);
                }

                if ($competition->canonicalTestApplicationWindowIsClosed()) {
                    return FinalSubmissionResult::failed(CanonicalTestApplicationPhaseService::WINDOW_CLOSED_MESSAGE);
                }

                if (! $competition->is_open) {
                    return FinalSubmissionResult::failed('Rok za prijave je istekao ili konkurs nije otvoren za prijave.');
                }

                $p1Error = $this->applicantForms->finalBlockReason($locked);
                if ($p1Error !== null) {
                    return FinalSubmissionResult::failed($p1Error);
                }

                $policy = CompetitionProfileResolver::resolve($competition->type);
                if ($policy->requiresKotorAddress()) {
                    $kotorError = $this->kotorAddress->errorFor($locked);
                    if ($kotorError !== null) {
                        return FinalSubmissionResult::failed($kotorError);
                    }
                }

                $p2Error = $this->investmentPlans->finalBlockReason($locked);
                if ($p2Error !== null) {
                    return FinalSubmissionResult::failed($p2Error);
                }

                $p1SubsidyType = $locked->canonicalApplicantForm()->value('subsidy_type');
                if ($p1SubsidyType !== $locked->canonicalInvestmentPlan()->value('subsidy_type')) {
                    return FinalSubmissionResult::failed(CanonicalApplicantFormService::SUBSIDY_MISMATCH_MESSAGE);
                }

                $documentError = $this->documents->finalBlockReason($locked, now());
                if ($documentError !== null) {
                    return FinalSubmissionResult::failed($documentError);
                }

                $beneficiaryPib = Pib::normalize($locked->pib);
                if ($beneficiaryPib === null) {
                    return FinalSubmissionResult::failed(CanonicalBeneficiaryApplications::PIB_REQUIRED_MESSAGE);
                }

                Competition::query()->whereKey($competition->id)->lockForUpdate()->first();

                $subsidyType = (string) $locked->canonicalInvestmentPlan()->value('subsidy_type');
                $assessment = $this->beneficiaries->assessFinalClaim(
                    $competition,
                    (int) $locked->user_id,
                    $beneficiaryPib,
                    $subsidyType,
                );
                if ($assessment['error'] !== null) {
                    return FinalSubmissionResult::failed($assessment['error']);
                }

                $this->freezer->freeze($locked);

                FinalApplicationClaim::query()->create([
                    'user_id' => $locked->user_id,
                    'competition_id' => $locked->competition_id,
                    'application_id' => $locked->id,
                    'subsidy_type' => $subsidyType,
                    'beneficiary_pib' => $beneficiaryPib,
                    'beneficiary_slot' => $assessment['slot'],
                    'claimed_at' => now(),
                ]);

                $maxRedni = Application::query()
                    ->where('competition_id', $locked->competition_id)
                    ->max('redni_broj');

                $locked->update([
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'redni_broj' => ($maxRedni ?? 0) + 1,
                ]);

                return FinalSubmissionResult::submitted();
            });
        } catch (QueryException $exception) {
            if ($this->isUniqueViolation($exception)) {
                return FinalSubmissionResult::failed(self::CLAIM_CONFLICT_MESSAGE);
            }

            throw $exception;
        }
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        $sqlState = (string) $exception->getCode();
        $message = $exception->getMessage();

        return $sqlState === '23000'
            || str_contains($message, 'final_claims_')
            || str_contains($message, 'Duplicate entry');
    }
}
