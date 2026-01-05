<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('business_plans', function (Blueprint $table) {
            // I. OSNOVNI PODACI - dodatna polja
            if (!Schema::hasColumn('business_plans', 'applicant_name')) {
                $table->string('applicant_name')->nullable()->after('business_idea_name');
            }
            if (!Schema::hasColumn('business_plans', 'applicant_jmbg')) {
                $table->string('applicant_jmbg')->nullable()->after('applicant_name');
            }
            if (!Schema::hasColumn('business_plans', 'applicant_address')) {
                $table->text('applicant_address')->nullable()->after('applicant_jmbg');
            }
            if (!Schema::hasColumn('business_plans', 'applicant_phone')) {
                $table->string('applicant_phone')->nullable()->after('applicant_address');
            }
            if (!Schema::hasColumn('business_plans', 'applicant_email')) {
                $table->string('applicant_email')->nullable()->after('applicant_phone');
            }
            if (!Schema::hasColumn('business_plans', 'has_registered_business')) {
                $table->boolean('has_registered_business')->nullable()->after('applicant_email');
            }
            if (!Schema::hasColumn('business_plans', 'registration_form')) {
                $table->string('registration_form')->nullable()->after('has_registered_business');
            }
            if (!Schema::hasColumn('business_plans', 'company_name')) {
                $table->string('company_name')->nullable()->after('registration_form');
            }
            if (!Schema::hasColumn('business_plans', 'pib')) {
                $table->string('pib')->nullable()->after('company_name');
            }
            if (!Schema::hasColumn('business_plans', 'vat_number')) {
                $table->string('vat_number')->nullable()->after('pib');
            }
            if (!Schema::hasColumn('business_plans', 'company_address')) {
                $table->text('company_address')->nullable()->after('vat_number');
            }
            if (!Schema::hasColumn('business_plans', 'company_phone')) {
                $table->string('company_phone')->nullable()->after('company_address');
            }
            if (!Schema::hasColumn('business_plans', 'company_email')) {
                $table->string('company_email')->nullable()->after('company_phone');
            }
            if (!Schema::hasColumn('business_plans', 'company_website')) {
                $table->string('company_website')->nullable()->after('company_email');
            }
            if (!Schema::hasColumn('business_plans', 'bank_account')) {
                $table->string('bank_account')->nullable()->after('company_website');
            }
            
            // II. MARKETING - dodatna polja
            if (!Schema::hasColumn('business_plans', 'products_services_table')) {
                $table->text('products_services_table')->nullable()->after('product_service');
            }
            if (!Schema::hasColumn('business_plans', 'realization_type')) {
                $table->string('realization_type')->nullable()->after('products_services_table');
            }
            if (!Schema::hasColumn('business_plans', 'target_customers')) {
                $table->text('target_customers')->nullable()->after('realization_type');
            }
            if (!Schema::hasColumn('business_plans', 'sales_locations')) {
                $table->text('sales_locations')->nullable()->after('location');
            }
            if (!Schema::hasColumn('business_plans', 'has_business_space')) {
                $table->string('has_business_space')->nullable()->after('sales_locations');
            }
            if (!Schema::hasColumn('business_plans', 'pricing_table')) {
                $table->text('pricing_table')->nullable()->after('pricing');
            }
            if (!Schema::hasColumn('business_plans', 'annual_sales_volume')) {
                $table->decimal('annual_sales_volume', 15, 2)->nullable()->after('pricing_table');
            }
            if (!Schema::hasColumn('business_plans', 'revenue_share_table')) {
                $table->text('revenue_share_table')->nullable()->after('annual_sales_volume');
            }
            
            // II. MARKETING - LJUDI
            if (!Schema::hasColumn('business_plans', 'employment_structure')) {
                $table->text('employment_structure')->nullable()->after('people_marketing');
            }
            if (!Schema::hasColumn('business_plans', 'has_seasonal_workers')) {
                $table->boolean('has_seasonal_workers')->nullable()->after('employment_structure');
            }
            if (!Schema::hasColumn('business_plans', 'competition_analysis')) {
                $table->text('competition_analysis')->nullable()->after('has_seasonal_workers');
            }
            
            // III. POSLOVANJE - dodatna polja
            if (!Schema::hasColumn('business_plans', 'business_history')) {
                $table->text('business_history')->nullable()->after('business_analysis');
            }
            if (!Schema::hasColumn('business_plans', 'required_resources')) {
                $table->text('required_resources')->nullable()->after('supply_market');
            }
            if (!Schema::hasColumn('business_plans', 'suppliers_table')) {
                $table->text('suppliers_table')->nullable()->after('required_resources');
            }
            if (!Schema::hasColumn('business_plans', 'annual_purchases_volume')) {
                $table->decimal('annual_purchases_volume', 15, 2)->nullable()->after('suppliers_table');
            }
            
            // IV. FINANSIJE - dodatna polja
            if (!Schema::hasColumn('business_plans', 'required_amount')) {
                $table->decimal('required_amount', 15, 2)->nullable()->after('required_funds');
            }
            if (!Schema::hasColumn('business_plans', 'requested_amount')) {
                $table->decimal('requested_amount', 15, 2)->nullable()->after('required_amount');
            }
            if (!Schema::hasColumn('business_plans', 'funding_sources_table')) {
                $table->text('funding_sources_table')->nullable()->after('requested_amount');
            }
            if (!Schema::hasColumn('business_plans', 'funding_alternative')) {
                $table->string('funding_alternative')->nullable()->after('funding_sources_table');
            }
            if (!Schema::hasColumn('business_plans', 'revenue_projection')) {
                $table->text('revenue_projection')->nullable()->after('revenue_expense_projection');
            }
            if (!Schema::hasColumn('business_plans', 'expense_projection')) {
                $table->text('expense_projection')->nullable()->after('revenue_projection');
            }
            
            // V. LJUDI - dodatna polja
            if (!Schema::hasColumn('business_plans', 'work_experience')) {
                $table->text('work_experience')->nullable()->after('entrepreneur_data');
            }
            if (!Schema::hasColumn('business_plans', 'personal_strengths_weaknesses')) {
                $table->text('personal_strengths_weaknesses')->nullable()->after('work_experience');
            }
            if (!Schema::hasColumn('business_plans', 'biggest_support')) {
                $table->string('biggest_support')->nullable()->after('personal_strengths_weaknesses');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_plans', function (Blueprint $table) {
            $columnsToDrop = [
                'applicant_name', 'applicant_jmbg', 'applicant_address', 'applicant_phone', 'applicant_email',
                'has_registered_business', 'registration_form', 'company_name', 'pib', 'vat_number',
                'company_address', 'company_phone', 'company_email', 'company_website', 'bank_account',
                'products_services_table', 'realization_type', 'target_customers', 'sales_locations',
                'has_business_space', 'pricing_table', 'annual_sales_volume', 'revenue_share_table',
                'employment_structure', 'has_seasonal_workers', 'competition_analysis',
                'business_history', 'required_resources', 'suppliers_table', 'annual_purchases_volume',
                'required_amount', 'requested_amount', 'funding_sources_table', 'funding_alternative',
                'revenue_projection', 'expense_projection',
                'work_experience', 'personal_strengths_weaknesses', 'biggest_support'
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('business_plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
