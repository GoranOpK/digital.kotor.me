<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('canonical_final_rankings', function (Blueprint $table) {
            $table->dropForeign(['final_decision_session_id']);
        });

        DB::statement('ALTER TABLE canonical_final_rankings MODIFY final_decision_session_id BIGINT UNSIGNED NULL');

        Schema::table('canonical_final_rankings', function (Blueprint $table) {
            $table->foreign('final_decision_session_id')
                ->references('id')
                ->on('commission_sessions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('canonical_final_rankings', function (Blueprint $table) {
            $table->dropForeign(['final_decision_session_id']);
        });

        DB::statement('ALTER TABLE canonical_final_rankings MODIFY final_decision_session_id BIGINT UNSIGNED NOT NULL');

        Schema::table('canonical_final_rankings', function (Blueprint $table) {
            $table->foreign('final_decision_session_id')
                ->references('id')
                ->on('commission_sessions')
                ->restrictOnDelete();
        });
    }
};
