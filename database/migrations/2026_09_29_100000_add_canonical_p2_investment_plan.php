<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * KN-SYNC-008A — canonical P2 (čl.17) subsidy type + financial frame, and
 * profile-aware FINAL claims (PO decision: KN-FR-042 superseded for canonical MMSP only).
 *
 * claim_scope = COALESCE(subsidy_type, '*'):
 *  - canonical claims carry a subsidy type → one claim per (user, competition, type);
 *  - historical / untyped claims keep scope '*' → still one per (user, competition).
 * Beneficiary (normalized PIB) limits: one claim per type, at most slots 1 and 2.
 *
 * Additive. Historical claims are preserved as-is. Women/Youth never write either table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('canonical_investment_plans')) {
            Schema::create('canonical_investment_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->unique()->constrained('applications')->cascadeOnDelete();
                $table->string('subsidy_type', 64)->nullable();
                $table->decimal('total_investment', 14, 2)->nullable();
                $table->decimal('own_contribution', 14, 2)->nullable();
                $table->decimal('expected_subsidy', 14, 2)->nullable();
                $table->json('expense_lines')->nullable();
                $table->timestamp('frozen_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('final_application_claims', function (Blueprint $table) {
            $table->string('subsidy_type', 64)->nullable()->after('application_id');
            $table->char('beneficiary_pib', 8)->nullable()->after('subsidy_type');
            $table->unsignedTinyInteger('beneficiary_slot')->nullable()->after('beneficiary_pib');
            $table->string('claim_scope', 64)->storedAs("COALESCE(`subsidy_type`, '*')")->after('beneficiary_slot');
        });

        $hasCompetitionIndex = Schema::hasIndex('final_application_claims', 'final_claims_competition_idx');

        Schema::table('final_application_claims', function (Blueprint $table) use ($hasCompetitionIndex) {
            // Explicit FK support index (kept on rollback): MySQL may silently drop the implicit
            // one once competition-leading unique indexes exist, which would pin them forever.
            if (! $hasCompetitionIndex) {
                $table->index('competition_id', 'final_claims_competition_idx');
            }
            $table->unique(['user_id', 'competition_id', 'claim_scope'], 'final_claims_user_competition_scope_unique');
            $table->unique(['competition_id', 'beneficiary_pib', 'subsidy_type'], 'final_claims_competition_pib_type_unique');
            $table->unique(['competition_id', 'beneficiary_pib', 'beneficiary_slot'], 'final_claims_competition_pib_slot_unique');
        });

        Schema::table('final_application_claims', function (Blueprint $table) {
            $table->dropUnique('final_claims_user_competition_unique');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE final_application_claims ADD CONSTRAINT final_claims_beneficiary_slot_check CHECK (beneficiary_slot IS NULL OR beneficiary_slot IN (1, 2))');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE final_application_claims DROP CHECK final_claims_beneficiary_slot_check');
        }

        Schema::table('final_application_claims', function (Blueprint $table) {
            $table->unique(['user_id', 'competition_id'], 'final_claims_user_competition_unique');
        });

        Schema::table('final_application_claims', function (Blueprint $table) {
            $table->dropUnique('final_claims_user_competition_scope_unique');
            $table->dropUnique('final_claims_competition_pib_type_unique');
            $table->dropUnique('final_claims_competition_pib_slot_unique');
        });

        Schema::table('final_application_claims', function (Blueprint $table) {
            $table->dropColumn(['claim_scope', 'beneficiary_slot', 'beneficiary_pib', 'subsidy_type']);
        });

        Schema::dropIfExists('canonical_investment_plans');
    }
};
