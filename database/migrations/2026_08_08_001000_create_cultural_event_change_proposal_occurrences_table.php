<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TS-010.3b — snapshot / ops Održavanja unutar CulturalEventChangeProposal.
 * Statusne radnje (postpone/cancel) nisu ovdje — OccurrenceLifecycle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cultural_event_change_proposal_occurrences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proposal_id');
            $table->string('operation', 16);
            $table->unsignedBigInteger('source_occurrence_id')->nullable();

            $table->date('proposed_datum');
            $table->time('proposed_vrijeme_od')->nullable();
            $table->time('proposed_vrijeme_do')->nullable();
            $table->boolean('proposed_cjelodnevno')->default(false);
            $table->unsignedBigInteger('proposed_location_id')->nullable();
            $table->string('proposed_location_manual_name')->nullable();

            $table->timestamps();

            $table->foreign('proposal_id', 'cecpo_proposal_fk')
                ->references('id')->on('cultural_event_change_proposals')->cascadeOnDelete();
            $table->foreign('source_occurrence_id', 'cecpo_source_occ_fk')
                ->references('id')->on('cultural_occurrences')->restrictOnDelete();
            $table->foreign('proposed_location_id', 'cecpo_location_fk')
                ->references('id')->on('cultural_locations')->nullOnDelete();

            $table->index(['proposal_id', 'operation'], 'cecpo_proposal_op_idx');
            $table->unique(['proposal_id', 'source_occurrence_id'], 'cecpo_proposal_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cultural_event_change_proposal_occurrences');
    }
};
