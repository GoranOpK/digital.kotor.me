<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zensko_decision_proposal_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('competition_id');
            $table->string('storage_path');
            $table->char('content_sha256', 64);
            $table->unsignedBigInteger('closed_by');
            $table->timestamp('created_at')->useCurrent();

            $table->unique('competition_id', 'zdps_competition_id_unique');
            $table->foreign('competition_id', 'zdps_competition_fk')
                ->references('id')
                ->on('competitions')
                ->restrictOnDelete();
            $table->foreign('closed_by', 'zdps_closed_by_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->unsignedBigInteger('zensko_decision_proposal_snapshot_id')->nullable();
            $table->unique('zensko_decision_proposal_snapshot_id', 'competitions_zdps_id_unique');
            $table->foreign('zensko_decision_proposal_snapshot_id', 'competitions_zdps_fk')
                ->references('id')
                ->on('zensko_decision_proposal_snapshots')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropForeign('competitions_zdps_fk');
            $table->dropUnique('competitions_zdps_id_unique');
            $table->dropColumn('zensko_decision_proposal_snapshot_id');
        });

        Schema::dropIfExists('zensko_decision_proposal_snapshots');
    }
};
