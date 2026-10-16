<?php

namespace Tests\Feature\Kn\Concerns;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\CanonicalApplicantForm;
use App\Models\CanonicalDocumentEvidence;
use App\Models\CanonicalInvestmentPlan;
use App\Models\Competition;
use App\Models\User;
use App\Support\CanonicalDocumentCatalog;
use App\Support\CanonicalLegalForm;
use App\Support\CanonicalSubsidyType;

/**
 * KN-SYNC-015 fixture: a FINAL-ready canonical MMSP draft (Obrazac P1a/P1b + P2 + čl.14 prilozi).
 */
trait BuildsCanonicalApplicantDraft
{
    /**
     * Options:
     *  - legal_form: CanonicalLegalForm::PREDUZETNIK (default) | DRUSTVO
     *  - subsidy_type: P2 type (default INNOVATIVE); p1_subsidy_type defaults to the same, null = untyped P1
     *  - with_p2 (true), with_documents (true), without_documents (list of types left out)
     *  - pib (default 7 + zero-padded user id), employee_count (2), registration_date, address
     *  - variants: [document_type => variant], issued_on: date for 30-day documents (today)
     *
     * @param  array<string, mixed>  $options
     */
    protected function canonicalDraft(User $owner, Competition $competition, array $options = []): Application
    {
        $legalForm = $options['legal_form'] ?? CanonicalLegalForm::PREDUZETNIK;
        $subsidyType = $options['subsidy_type'] ?? CanonicalSubsidyType::INNOVATIVE;
        $p1SubsidyType = array_key_exists('p1_subsidy_type', $options) ? $options['p1_subsidy_type'] : $subsidyType;
        $isEntrepreneur = $legalForm === CanonicalLegalForm::PREDUZETNIK;

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'applicant_type' => CanonicalLegalForm::applicantType($legalForm),
            'status' => 'draft',
            'pib' => array_key_exists('pib', $options) ? $options['pib'] : $this->canonicalPibFor($owner),
        ]);

        CanonicalApplicantForm::create([
            'application_id' => $application->id,
            'legal_form' => $legalForm,
            'subsidy_type' => $p1SubsidyType,
            'person_name' => $isEntrepreneur ? 'Marko Marković' : 'Odgovorno Lice',
            'company_name' => $isEntrepreneur ? null : 'Primjer d.o.o.',
            'address' => $options['address'] ?? 'Njegoševa 1, 85330 Kotor',
            'activity_code' => '56.10',
            'employee_count' => $options['employee_count'] ?? 2,
            'bank_account' => '510-0000000000001-11',
            'phone' => '067000000',
            'email' => 'p1-'.$application->id.'@example.com',
            'website' => 'primjer.me',
            'registration_date' => $options['registration_date'] ?? '2020-03-15',
            'accuracy_confirmed_at' => now(),
        ]);

        if ($options['with_p2'] ?? true) {
            CanonicalInvestmentPlan::create([
                'application_id' => $application->id,
                'subsidy_type' => $subsidyType,
                'total_investment' => '1000.00',
                'own_contribution' => '200.00',
                'expected_subsidy' => '500.00',
                'expense_lines' => [['cost_type' => 'Oprema', 'description' => 'Mašina za preradu', 'amount' => '1000.00']],
            ]);
        }

        if ($options['with_documents'] ?? true) {
            $this->attachCanonicalDocuments($application, $options);
        }

        return $application->fresh(['competition']);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function attachCanonicalDocuments(Application $application, array $options = []): void
    {
        $form = CanonicalApplicantForm::query()->where('application_id', $application->id)->firstOrFail();
        $catalog = CanonicalDocumentCatalog::forLegalForm($form->legal_form);
        $skip = $options['without_documents'] ?? [];
        $variants = $options['variants'] ?? [];
        $issuedOn = $options['issued_on'] ?? now()->toDateString();

        $defaults = [
            CanonicalDocumentCatalog::TYPE_VAT => CanonicalDocumentCatalog::VARIANT_VAT_REGISTERED,
            CanonicalDocumentCatalog::TYPE_IOPPD => (int) $form->employee_count > 0
                ? CanonicalDocumentCatalog::VARIANT_IOPPD
                : CanonicalDocumentCatalog::VARIANT_NO_EMPLOYEES,
            CanonicalDocumentCatalog::TYPE_ANNUAL_ACCOUNTS => CanonicalDocumentCatalog::VARIANT_WITH_CUSTOMER_ANALYTICS,
        ];

        foreach ($catalog->uploadTypes() as $type) {
            if (in_array($type, $skip, true)) {
                continue;
            }
            $variant = $catalog->variantsFor($type) !== [] ? ($variants[$type] ?? $defaults[$type] ?? null) : null;
            if ($type === CanonicalDocumentCatalog::TYPE_CASH_REGISTER
                && ($variants[CanonicalDocumentCatalog::TYPE_ANNUAL_ACCOUNTS] ?? null) !== CanonicalDocumentCatalog::VARIANT_WITHOUT_CUSTOMER_ANALYTICS) {
                continue;
            }

            $this->attachCanonicalDocument($application, $type, $variant, $catalog->requiresIssueDate($type) ? $issuedOn : null);
        }
    }

    protected function attachCanonicalDocument(Application $application, string $type, ?string $variant = null, ?string $issuedOn = null): ApplicationDocument
    {
        $document = ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => $type.'.pdf',
            'file_path' => 'applications/'.$application->id.'/'.$type.'.pdf',
            'document_type' => $type,
            'is_required' => true,
        ]);

        CanonicalDocumentEvidence::create([
            'application_document_id' => $document->id,
            'application_id' => $application->id,
            'document_type' => $type,
            'variant' => $variant,
            'issued_on' => $issuedOn,
        ]);

        return $document;
    }

    /**
     * Full Obrazac P1 payload from the stored form, with overrides.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function canonicalP1Payload(Application $application, array $overrides = []): array
    {
        $form = CanonicalApplicantForm::query()->where('application_id', $application->id)->firstOrFail();

        return array_merge([
            'subsidy_type' => $form->subsidy_type,
            'person_name' => $form->person_name,
            'company_name' => $form->company_name,
            'pib' => $application->fresh()->pib,
            'address' => $form->address,
            'activity_code' => $form->activity_code,
            'employee_count' => (string) $form->employee_count,
            'bank_account' => $form->bank_account,
            'phone' => $form->phone,
            'email' => $form->email,
            'website' => $form->website,
            'registration_date' => $form->registration_date?->toDateString(),
            'accuracy_confirmed' => $form->accuracy_confirmed_at ? '1' : '',
        ], $overrides);
    }

    protected function canonicalPibFor(User $owner): string
    {
        return '7'.str_pad((string) $owner->id, 7, '0', STR_PAD_LEFT);
    }
}
