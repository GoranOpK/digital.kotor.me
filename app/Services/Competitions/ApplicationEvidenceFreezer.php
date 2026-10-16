<?php

namespace App\Services\Competitions;

use App\Identity\Runtime\CurrentIdentityResolver;
use App\Models\Application;
use App\Models\CanonicalApplicantForm;
use App\Models\CanonicalDocumentEvidence;
use App\Models\CanonicalInvestmentPlan;
use App\Security\JmbDualWrite;
use App\Support\CanonicalLegalForm;
use App\Support\KotorAddress;

/**
 * OTD-002 hybrid evidentiary freeze into existing Application / BusinessPlan columns.
 *
 * Freezes evaluation-relevant identity and plan evidence at successful FINAL.
 * Does not freeze whole User (email/session/roles stay LIVE for routing).
 * JMB uses JmbDualWrite — no plaintext-only insecure path.
 */
class ApplicationEvidenceFreezer
{
    public function freeze(Application $application): void
    {
        $application->loadMissing(['businessPlan', 'user', 'documents']);
        $user = $application->user;
        $identity = $user ? app(CurrentIdentityResolver::class)->viewFor($user) : null;

        if ($application->isCanonicalProfile()) {
            $this->freezeCanonical($application, $identity?->jmb);

            return;
        }

        $didAssignJmb = false;

        if (in_array($application->applicant_type, ['preduzetnica', 'preduzetnik', 'doo', 'ostalo', 'privredno_drustvo'], true)
            && ! $application->hasApplicantJmbgSnapshot()
            && filled($identity?->jmb)) {
            JmbDualWrite::assignLogical($application, 'applicant_jmbg', $identity->jmb);
            $didAssignJmb = true;
        }

        if ($application->applicant_type === 'fizicko_lice') {
            if (! $application->hasPhysicalPersonJmbgSnapshot() && filled($identity?->jmb)) {
                JmbDualWrite::assignLogical($application, 'physical_person_jmbg', $identity->jmb);
                $didAssignJmb = true;
            }
            if (! filled($application->physical_person_name) && filled($user?->name)) {
                $application->physical_person_name = $user->name;
            }
            // Freeze routing-independent contact snapshots already on the application;
            // never overwrite User.email (LIVE).
            if (! filled($application->physical_person_email) && filled($user?->email)) {
                $application->physical_person_email = $user->email;
            }
        }

        if (in_array($application->applicant_type, ['preduzetnica', 'preduzetnik'], true)
            && ! filled($application->founder_name)
            && filled($user?->name)) {
            $application->founder_name = $user->name;
        }

        if ($didAssignJmb || $application->isDirty()) {
            $application->save();
        }

        // Canonical P2 subsidy type + finansijski okvir: immutable from FINAL on.
        $investmentPlan = CanonicalInvestmentPlan::query()->where('application_id', $application->id)->first();
        if ($investmentPlan && ! $investmentPlan->isFrozen()) {
            $investmentPlan->forceFill(['frozen_at' => now()])->save();
        }
        $application->unsetRelation('canonicalInvestmentPlan');

        $plan = $application->businessPlan;
        if (! $plan) {
            return;
        }

        $planDidAssignJmb = false;

        if (! filled($plan->applicant_name) && filled($user?->name)) {
            $plan->applicant_name = $user->name;
        }

        if (! $plan->hasApplicantJmbgSnapshot()) {
            $jmbg = $application->applicantJmbgForRead()
                ?: $application->physicalPersonJmbgForRead()
                ?: $identity?->jmb;
            if (filled($jmbg)) {
                JmbDualWrite::assignLogical($plan, 'applicant_jmbg', $jmbg);
                $planDidAssignJmb = true;
            }
        }

        if (! filled($plan->applicant_address) && $identity) {
            $address = KotorAddress::formatStreetAndCity($identity->address, $identity->city);
            if ($address !== '') {
                $plan->applicant_address = $address;
            }
        }

        if (! filled($plan->applicant_phone) && filled($identity?->phone)) {
            $plan->applicant_phone = $identity->phone;
        }

        // Snapshot contact for evaluation; LIVE User.email remains for notifications.
        if (! filled($plan->applicant_email) && filled($user?->email)) {
            $plan->applicant_email = $user->email;
        }

        if (! filled($plan->pib) && filled($application->pib)) {
            $plan->pib = $application->pib;
        }
        if (! filled($plan->vat_number) && filled($application->vat_number)) {
            $plan->vat_number = $application->vat_number;
        }
        if (! filled($plan->bank_account) && filled($application->bank_account)) {
            $plan->bank_account = $application->bank_account;
        }
        if (! filled($plan->company_website) && filled($application->website)) {
            $plan->company_website = $application->website;
        }
        if (! filled($plan->company_name)
            && filled($application->business_plan_name)
            && in_array($application->applicant_type, ['doo', 'ostalo', 'privredno_drustvo'], true)) {
            $plan->company_name = $application->business_plan_name;
        }

        // Document references + metadata at FINAL (immutable after write-lock).
        // Stored as plan note only when no dedicated freeze column exists —
        // evaluation continues to read ApplicationDocument rows locked post-submit.
        if ($planDidAssignJmb || $plan->isDirty()) {
            $plan->save();
        }

        // Touch relation so callers see frozen document set under write-lock.
        $application->unsetRelation('documents');
        $application->load('documents');
    }

    /**
     * Canonical FINAL: JMBG of the P1a preduzetnik / P1b submitting account from verified identity
     * (encrypted dual-write only), then Obrazac P1, Obrazac P2 and čl.14 document evidence frozen.
     */
    private function freezeCanonical(Application $application, ?string $identityJmb): void
    {
        $form = CanonicalApplicantForm::query()->where('application_id', $application->id)->first();

        // čl.16: only Obrazac P1a (preduzetnik) carries a JMBG; P1b (društvo) identifies by PIB društva.
        if ($form?->legal_form === CanonicalLegalForm::PREDUZETNIK
            && ! $application->hasApplicantJmbgSnapshot()
            && filled($identityJmb)) {
            JmbDualWrite::assignLogical($application, 'applicant_jmbg', $identityJmb);
            $application->save();
        }

        $frozenAt = now();

        if ($form && ! $form->isFrozen()) {
            $form->forceFill(['frozen_at' => $frozenAt])->save();
        }

        $investmentPlan = CanonicalInvestmentPlan::query()->where('application_id', $application->id)->first();
        if ($investmentPlan && ! $investmentPlan->isFrozen()) {
            $investmentPlan->forceFill(['frozen_at' => $frozenAt])->save();
        }

        CanonicalDocumentEvidence::query()
            ->where('application_id', $application->id)
            ->whereNull('frozen_at')
            ->update(['frozen_at' => $frozenAt]);

        $application->unsetRelation('canonicalApplicantForm');
        $application->unsetRelation('canonicalInvestmentPlan');
        $application->unsetRelation('documents');
        $application->load('documents');
    }
}
