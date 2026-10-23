<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KN-SYNC-007 — canonical MMSP preliminary ranking facts.
 *
 * applications.final_score_calc keeps the full-precision CALCULATION VALUE (KN-BR-037);
 * applications.final_score stays the 2-decimal DISPLAY VALUE. The formation timestamp
 * freezes the preliminary ranking. Additive and nullable: Women/Youth rows are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('applications', 'final_score_calc')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->decimal('final_score_calc', 14, 10)->nullable()->after('final_score');
            });
        }

        if (! Schema::hasColumn('competitions', 'canonical_ranking_formed_at')) {
            Schema::table('competitions', function (Blueprint $table) {
                $table->timestamp('canonical_ranking_formed_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('competitions', 'canonical_ranking_formed_at')) {
            Schema::table('competitions', function (Blueprint $table) {
                $table->dropColumn('canonical_ranking_formed_at');
            });
        }

        if (Schema::hasColumn('applications', 'final_score_calc')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropColumn('final_score_calc');
            });
        }
    }
};
