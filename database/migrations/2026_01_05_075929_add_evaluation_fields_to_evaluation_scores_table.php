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
        Schema::table('evaluation_scores', function (Blueprint $table) {
            if (!Schema::hasColumn('evaluation_scores', 'documents_complete')) {
                $table->boolean('documents_complete')->default(true)->after('commission_member_id')->comment('Dostavljena su sva potrebna dokumenta');
            }
            if (!Schema::hasColumn('evaluation_scores', 'justification')) {
                $table->text('justification')->nullable()->after('notes')->comment('Obrazloženje');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation_scores', function (Blueprint $table) {
            if (Schema::hasColumn('evaluation_scores', 'documents_complete')) {
                $table->dropColumn('documents_complete');
            }
            if (Schema::hasColumn('evaluation_scores', 'justification')) {
                $table->dropColumn('justification');
            }
        });
    }
};
