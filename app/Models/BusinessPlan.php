<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        // I. OSNOVNI PODACI
        'business_idea_name',
        'applicant_name',
        'applicant_jmbg',
        'applicant_address',
        'applicant_phone',
        'applicant_email',
        'has_registered_business',
        'registration_form',
        'company_name',
        'pib',
        'vat_number',
        'company_address',
        'company_phone',
        'company_email',
        'company_website',
        'bank_account',
        'summary',
        // II. MARKETING
        'product_service',
        'products_services_table',
        'realization_type',
        'target_customers',
        'location',
        'sales_locations',
        'has_business_space',
        'pricing',
        'pricing_table',
        'annual_sales_volume',
        'revenue_share_table',
        'promotion',
        'people_marketing',
        'employment_structure',
        'has_seasonal_workers',
        'competition_analysis',
        // III. POSLOVANJE
        'business_analysis',
        'business_history',
        'supply_market',
        'required_resources',
        'suppliers_table',
        'annual_purchases_volume',
        // IV. FINANSIJE
        'required_funds',
        'required_amount',
        'requested_amount',
        'finances_notice_confirmed',
        'funding_sources_table',
        'funding_alternative',
        'revenue_expense_projection',
        'revenue_projection',
        'expense_projection',
        // V. LJUDI
        'entrepreneur_data',
        'work_experience',
        'personal_strengths_weaknesses',
        'biggest_support',
        'job_schedule',
        // VI. RIZICI
        'risk_matrix',
    ];

    protected $casts = [
        'has_registered_business' => 'boolean',
        'has_seasonal_workers' => 'boolean',
        'annual_sales_volume' => 'decimal:2',
        'annual_purchases_volume' => 'decimal:2',
        'required_amount' => 'decimal:2',
        'requested_amount' => 'decimal:2',
        'finances_notice_confirmed' => 'boolean',
        'products_services_table' => 'array',
        'target_customers' => 'array',
        'sales_locations' => 'array',
        'pricing_table' => 'array',
        'revenue_share_table' => 'array',
        'employment_structure' => 'array',
        // competition_analysis i required_resources su tekstualna polja (textarea), ne JSON nizovi
        'business_history' => 'array',
        'suppliers_table' => 'array',
        'funding_sources_table' => 'array',
        'revenue_projection' => 'array',
        'expense_projection' => 'array',
        'job_schedule' => 'array',
        'risk_matrix' => 'array',
    ];

    /**
     * Veza sa prijavom
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Ranije su ova polja pogrešno bila cast-ovana kao array, pa u bazi mogu biti
     * JSON stringovi ("tekst"). Accessor vraća čist tekst za formu.
     */
    protected function competitionAnalysis(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $this->normalizeStoredText($value),
        );
    }

    protected function requiredResources(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $this->normalizeStoredText($value),
        );
    }

    protected function normalizeStoredText(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_string($decoded)) {
            return $decoded;
        }

        return $value;
    }

    /**
     * Zbir projekcije rashoda (pitanje 25) za sve tri godine.
     */
    public function expenseProjectionGrandTotal(): float
    {
        $total = 0.0;

        if (!is_array($this->expense_projection)) {
            return $total;
        }

        foreach ($this->expense_projection as $row) {
            if (!is_array($row)) {
                continue;
            }

            $total += (float) ($row['year1'] ?? 0);
            $total += (float) ($row['year2'] ?? 0);
            $total += (float) ($row['year3'] ?? 0);
        }

        return round($total, 2);
    }

    /**
     * Zbir stavki iz tabele traženih sredstava (pitanje 22).
     */
    public function fundingSourcesGrandTotal(): float
    {
        $total = 0.0;

        if (!is_array($this->funding_sources_table)) {
            return $total;
        }

        foreach ($this->funding_sources_table as $row) {
            if (!is_array($row)) {
                continue;
            }

            $total += (float) ($row['price'] ?? 0);
        }

        return round($total, 2);
    }

    /**
     * Potrebna sredstva: pitanje 21 ili, ako nije popunjeno, zbir rashoda iz pitanja 25.
     */
    public function resolvedRequiredAmount(): ?float
    {
        if ($this->required_amount !== null && (float) $this->required_amount > 0) {
            return (float) $this->required_amount;
        }

        $fromExpenses = $this->expenseProjectionGrandTotal();

        return $fromExpenses > 0 ? $fromExpenses : null;
    }

    /**
     * Traženi iznos podrške: pitanje 22 ili zbir tabele namjene sredstava.
     */
    public function resolvedRequestedAmount(): ?float
    {
        if ($this->requested_amount !== null && (float) $this->requested_amount > 0) {
            return (float) $this->requested_amount;
        }

        $fromFunding = $this->fundingSourcesGrandTotal();

        return $fromFunding > 0 ? $fromFunding : null;
    }

    /**
     * Provjerava da li je biznis plan kompletan (ima sva obavezna polja popunjena)
     * Obavezna polja su: business_idea_name, applicant_name, applicant_jmbg, applicant_address, applicant_phone, applicant_email, summary
     */
    public function isComplete(): bool
    {
        $requiredFields = [
            'business_idea_name',
            'applicant_name',
            'applicant_jmbg',
            'applicant_address',
            'applicant_phone',
            'applicant_email',
            'summary',
        ];

        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Provjerava da li je biznis plan nekompletan (draft)
     */
    public function isDraft(): bool
    {
        return !$this->isComplete();
    }
}

