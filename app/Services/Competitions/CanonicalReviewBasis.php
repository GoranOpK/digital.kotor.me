<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\CanonicalApplicantForm;
use App\Models\CanonicalDocumentEvidence;
use App\Models\CanonicalInvestmentPlan;

/**
 * Frozen FINAL evidence that canonical completeness review is recorded against (OTD-002).
 *
 * Contains no JMB (plaintext or encrypted) and no live User fields: User.email,
 * session and roles stay operational and are not part of the review basis.
 */
final class CanonicalReviewBasis
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(Application $application): array
    {
        $application->loadMissing(['businessPlan', 'finalClaim']);
        $plan = $application->businessPlan;
        $investmentPlan = CanonicalInvestmentPlan::query()->where('application_id', $application->id)->first();
        $applicantForm = CanonicalApplicantForm::query()->where('application_id', $application->id)->first();
        $evidence = CanonicalDocumentEvidence::query()
            ->where('application_id', $application->id)
            ->get()
            ->keyBy('application_document_id');

        return [
            'application' => [
                'id' => (int) $application->id,
                'redni_broj' => $application->redni_broj,
                'submitted_at' => $application->submitted_at?->toIso8601String(),
                'applicant_type' => $application->applicant_type,
                'business_stage' => $application->business_stage,
                'business_area' => $application->business_area,
                'business_plan_name' => $application->business_plan_name,
                'is_registered' => (bool) $application->is_registered,
                'founder_name' => $application->founder_name,
                'physical_person_name' => $application->physical_person_name,
                'preduzetnik_name' => $application->preduzetnik_name,
                'doo_name' => $application->doo_name,
                'company_seat' => $application->company_seat,
                'pib' => $application->pib,
            ],
            'business_plan' => $plan ? [
                'id' => (int) $plan->id,
                'business_idea_name' => $plan->business_idea_name,
                'applicant_name' => $plan->applicant_name,
                'applicant_address' => $plan->applicant_address,
                'company_name' => $plan->company_name,
                'requested_amount' => $plan->requested_amount,
                'required_amount' => $plan->required_amount,
                'finances_notice_confirmed' => (bool) $plan->finances_notice_confirmed,
            ] : null,
            'documents' => $application->documents()
                ->orderBy('id')
                ->get(['id', 'name', 'file_path', 'document_type', 'is_required'])
                ->map(fn ($document) => [
                    'id' => (int) $document->id,
                    'name' => $document->name,
                    'file_path' => $document->file_path,
                    'document_type' => $document->document_type,
                    'is_required' => (bool) $document->is_required,
                    'variant' => $evidence->get($document->id)?->variant,
                    'issued_on' => $evidence->get($document->id)?->issued_on?->toDateString(),
                ])
                ->all(),
            'canonical_p1' => $applicantForm?->isFrozen() ? $applicantForm->basis() : null,
            'canonical_p2' => $investmentPlan?->isFrozen() ? $investmentPlan->basis() : null,
            'final_claim_id' => $application->finalClaim?->id,
        ];
    }

    /**
     * Frozen canonical P2 facts (subsidy type, total investment, own contribution,
     * expected subsidy, expense lines); null until FINAL froze them. Never reads drafts.
     *
     * @return array<string, mixed>|null
     */
    public function frozenP2(Application $application): ?array
    {
        return $this->snapshot($application)['canonical_p2'];
    }

    /**
     * Frozen Obrazac P1a / P1b facts incl. legal form; null until FINAL froze them. Never reads drafts.
     *
     * @return array<string, mixed>|null
     */
    public function frozenP1(Application $application): ?array
    {
        return $this->snapshot($application)['canonical_p1'];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function fingerprint(array $snapshot): string
    {
        return hash('sha256', json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /**
     * Review basis plus lifecycle fields that completeness/complaint writes must not touch.
     *
     * @return array<string, mixed>
     */
    public function dossierGuard(Application $application): array
    {
        return [
            'basis' => $this->fingerprint($this->snapshot($application)),
            'status' => $application->status,
            'rejection_reason' => $application->rejection_reason,
            'final_score' => $application->final_score,
            'approved_amount' => $application->approved_amount,
        ];
    }
}
