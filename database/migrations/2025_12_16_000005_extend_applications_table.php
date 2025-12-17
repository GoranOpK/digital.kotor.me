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
        Schema::table('applications', function (Blueprint $table) {
            // Osnovni podaci iz Obrasca 1a/1b
            if (!Schema::hasColumn('applications', 'business_plan_name')) {
                $table->string('business_plan_name')->nullable()->after('user_id'); // Naziv biznis plana
            }
            if (!Schema::hasColumn('applications', 'applicant_type')) {
                $table->enum('applicant_type', ['preduzetnica', 'doo'])->nullable()->after('business_plan_name');
            }
            if (!Schema::hasColumn('applications', 'business_stage')) {
                $table->enum('business_stage', ['započinjanje', 'razvoj'])->nullable()->after('applicant_type');
            }
            
            // Podaci za DOO
            if (!Schema::hasColumn('applications', 'founder_name')) {
                $table->string('founder_name')->nullable()->after('business_stage'); // Ime osnivača/ice
            }
            if (!Schema::hasColumn('applications', 'director_name')) {
                $table->string('director_name')->nullable()->after('founder_name'); // Ime izvršnog direktora/ice
            }
            if (!Schema::hasColumn('applications', 'company_seat')) {
                $table->string('company_seat')->nullable()->after('director_name'); // Sjedište društva
            }
            
            // Finansijski podaci
            if (!Schema::hasColumn('applications', 'requested_amount')) {
                $table->decimal('requested_amount', 15, 2)->nullable()->after('company_seat'); // Traženi iznos
            }
            if (!Schema::hasColumn('applications', 'total_budget_needed')) {
                $table->decimal('total_budget_needed', 15, 2)->nullable()->after('requested_amount'); // Ukupan budžet potreban
            }
            if (!Schema::hasColumn('applications', 'approved_amount')) {
                $table->decimal('approved_amount', 15, 2)->nullable()->after('total_budget_needed'); // Odobreni iznos
            }
            
            // Dodatni podaci
            if (!Schema::hasColumn('applications', 'business_area')) {
                $table->string('business_area')->nullable()->after('approved_amount'); // Oblast biznisa
            }
            if (!Schema::hasColumn('applications', 'website')) {
                $table->string('website')->nullable()->after('business_area');
            }
            if (!Schema::hasColumn('applications', 'bank_account')) {
                $table->string('bank_account')->nullable()->after('website'); // Broj žiro računa
            }
            if (!Schema::hasColumn('applications', 'vat_number')) {
                $table->string('vat_number')->nullable()->after('bank_account'); // PDV broj
            }
            
            // Izjave i deklaracije
            if (!Schema::hasColumn('applications', 'de_minimis_declaration')) {
                $table->boolean('de_minimis_declaration')->default(false)->after('vat_number'); // De minimis izjava
            }
            if (!Schema::hasColumn('applications', 'previous_support_declaration')) {
                $table->boolean('previous_support_declaration')->default(false)->after('de_minimis_declaration'); // Prethodna podrška
            }
            
            // Status i ocjene
            // Napomena: Status kolona je string u originalnoj migraciji,
            // validacija će se vršiti na nivou modela
            if (!Schema::hasColumn('applications', 'final_score')) {
                $table->decimal('final_score', 5, 2)->nullable()->after('status'); // Konačna ocjena
            }
            if (!Schema::hasColumn('applications', 'ranking_position')) {
                $table->integer('ranking_position')->nullable()->after('final_score'); // Pozicija na rang listi
            }
            if (!Schema::hasColumn('applications', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('ranking_position'); // Razlog odbijanja
            }
            
            // Datumi
            if (!Schema::hasColumn('applications', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('applications', 'evaluated_at')) {
                $table->timestamp('evaluated_at')->nullable()->after('submitted_at');
            }
            if (!Schema::hasColumn('applications', 'interview_scheduled_at')) {
                $table->timestamp('interview_scheduled_at')->nullable()->after('evaluated_at'); // Usmeno obrazloženje
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $columns = [
                'business_plan_name',
                'founder_name',
                'director_name',
                'company_seat',
                'requested_amount',
                'total_budget_needed',
                'approved_amount',
                'business_area',
                'website',
                'bank_account',
                'vat_number',
                'de_minimis_declaration',
                'previous_support_declaration',
                'final_score',
                'ranking_position',
                'rejection_reason',
                'submitted_at',
                'evaluated_at',
                'interview_scheduled_at'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

