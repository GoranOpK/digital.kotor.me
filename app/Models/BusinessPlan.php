<?php

namespace App\Models;

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
        'products_services_table' => 'array',
        'target_customers' => 'array',
        'sales_locations' => 'array',
        'pricing_table' => 'array',
        'revenue_share_table' => 'array',
        'employment_structure' => 'array',
        'competition_analysis' => 'array',
        'business_history' => 'array',
        'required_resources' => 'array',
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

