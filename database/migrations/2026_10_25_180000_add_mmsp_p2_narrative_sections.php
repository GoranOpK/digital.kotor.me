<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Additional narrative sections of MMSP P2, Article 17. Additive: keep historic P2 rows intact. */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('canonical_investment_plans') && !Schema::hasColumn('canonical_investment_plans', 'narrative_sections')) {
            Schema::table('canonical_investment_plans', function (Blueprint $table) {
                $table->json('narrative_sections')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('canonical_investment_plans') && Schema::hasColumn('canonical_investment_plans', 'narrative_sections')) {
            Schema::table('canonical_investment_plans', fn (Blueprint $table) => $table->dropColumn('narrative_sections'));
        }
    }
};
