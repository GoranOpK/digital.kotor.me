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
        Schema::table('reports', function (Blueprint $table) {
            // Osnovni podaci
            if (!Schema::hasColumn('reports', 'entrepreneur_name')) {
                $table->string('entrepreneur_name')->nullable()->after('type');
            }
            if (!Schema::hasColumn('reports', 'legal_status')) {
                $table->string('legal_status')->nullable()->after('entrepreneur_name');
            }
            if (!Schema::hasColumn('reports', 'business_plan_name')) {
                $table->string('business_plan_name')->nullable()->after('legal_status');
            }
            if (!Schema::hasColumn('reports', 'approved_amount')) {
                $table->decimal('approved_amount', 15, 2)->nullable()->after('business_plan_name');
            }
            if (!Schema::hasColumn('reports', 'contract_number')) {
                $table->string('contract_number')->nullable()->after('approved_amount');
            }
            if (!Schema::hasColumn('reports', 'report_period_start')) {
                $table->date('report_period_start')->nullable()->after('contract_number');
            }
            if (!Schema::hasColumn('reports', 'report_period_end')) {
                $table->date('report_period_end')->nullable()->after('report_period_start');
            }
            
            // Polja za odgovore na pitanja
            if (!Schema::hasColumn('reports', 'activities_description')) {
                $table->text('activities_description')->nullable()->after('description');
            }
            if (!Schema::hasColumn('reports', 'problems_description')) {
                $table->text('problems_description')->nullable()->after('activities_description');
            }
            if (!Schema::hasColumn('reports', 'successes_description')) {
                $table->text('successes_description')->nullable()->after('problems_description');
            }
            if (!Schema::hasColumn('reports', 'new_employees')) {
                $table->text('new_employees')->nullable()->after('successes_description');
            }
            if (!Schema::hasColumn('reports', 'new_product_service')) {
                $table->text('new_product_service')->nullable()->after('new_employees');
            }
            if (!Schema::hasColumn('reports', 'purchases_description')) {
                $table->text('purchases_description')->nullable()->after('new_product_service');
            }
            if (!Schema::hasColumn('reports', 'deviations_description')) {
                $table->text('deviations_description')->nullable()->after('purchases_description');
            }
            if (!Schema::hasColumn('reports', 'satisfaction_with_cooperation')) {
                $table->string('satisfaction_with_cooperation')->nullable()->after('deviations_description');
            }
            if (!Schema::hasColumn('reports', 'recommendations')) {
                $table->text('recommendations')->nullable()->after('satisfaction_with_cooperation');
            }
            if (!Schema::hasColumn('reports', 'will_apply_again')) {
                $table->string('will_apply_again')->nullable()->after('recommendations');
            }
            
            // Prilozi
            if (!Schema::hasColumn('reports', 'financial_report_file')) {
                $table->string('financial_report_file')->nullable()->after('document_file');
            }
            if (!Schema::hasColumn('reports', 'invoices_file')) {
                $table->string('invoices_file')->nullable()->after('financial_report_file');
            }
            if (!Schema::hasColumn('reports', 'bank_statement_file')) {
                $table->string('bank_statement_file')->nullable()->after('invoices_file');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $columnsToDrop = [
                'entrepreneur_name', 'legal_status', 'business_plan_name', 'approved_amount',
                'contract_number', 'report_period_start', 'report_period_end',
                'activities_description', 'problems_description', 'successes_description',
                'new_employees', 'new_product_service', 'purchases_description',
                'deviations_description', 'satisfaction_with_cooperation', 'recommendations',
                'will_apply_again', 'financial_report_file', 'invoices_file', 'bank_statement_file'
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('reports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
