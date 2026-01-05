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
            if (!Schema::hasColumn('applications', 'commission_decision')) {
                $table->enum('commission_decision', ['podrzava_potpuno', 'podrzava_djelimicno', 'odbija'])->nullable()->after('final_score')->comment('Zaključak komisije');
            }
            if (!Schema::hasColumn('applications', 'commission_justification')) {
                $table->text('commission_justification')->nullable()->after('commission_decision')->comment('Obrazloženje zaključka komisije');
            }
            if (!Schema::hasColumn('applications', 'commission_notes')) {
                $table->text('commission_notes')->nullable()->after('commission_justification')->comment('Ostale napomene komisije');
            }
            if (!Schema::hasColumn('applications', 'commission_decision_date')) {
                $table->date('commission_decision_date')->nullable()->after('commission_notes')->comment('Datum donošenja zaključka');
            }
            if (!Schema::hasColumn('applications', 'signed_by_chairman')) {
                $table->boolean('signed_by_chairman')->default(false)->after('commission_decision_date')->comment('Potpisano od strane predsjednika');
            }
            if (!Schema::hasColumn('applications', 'signed_by_members')) {
                $table->json('signed_by_members')->nullable()->after('signed_by_chairman')->comment('Potpisano od strane članova (JSON sa ID-ovima članova)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'commission_decision')) {
                $table->dropColumn('commission_decision');
            }
            if (Schema::hasColumn('applications', 'commission_justification')) {
                $table->dropColumn('commission_justification');
            }
            if (Schema::hasColumn('applications', 'commission_notes')) {
                $table->dropColumn('commission_notes');
            }
            if (Schema::hasColumn('applications', 'commission_decision_date')) {
                $table->dropColumn('commission_decision_date');
            }
            if (Schema::hasColumn('applications', 'signed_by_chairman')) {
                $table->dropColumn('signed_by_chairman');
            }
            if (Schema::hasColumn('applications', 'signed_by_members')) {
                $table->dropColumn('signed_by_members');
            }
        });
    }
};
