<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TS-010.3a — Prijedlog izmjene objavljenog Događaja (N-DG-04).
 * Bez proposal Održavanja (TS-010.3b).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cultural_event_change_proposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_entry_id');
            $table->unsignedBigInteger('organizer_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('last_modified_by')->nullable();
            $table->string('status', 32)->default('draft');

            $table->string('proposed_naslov')->nullable();
            $table->text('proposed_opis')->nullable();
            $table->unsignedBigInteger('proposed_category_id')->nullable();
            $table->unsignedBigInteger('proposed_cover_media_id')->nullable();

            $table->text('return_reason')->nullable();
            $table->unsignedBigInteger('decision_user_id')->nullable();
            $table->timestamp('decision_at')->nullable();
            $table->text('decision_note')->nullable();

            $table->timestamp('first_submitted_at')->nullable();
            $table->timestamp('last_submitted_at')->nullable();
            $table->timestamp('review_started_at')->nullable();
            $table->unsignedBigInteger('review_started_by')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('inoperable_at')->nullable();
            $table->string('inoperable_reason', 64)->nullable();

            /** BR-012: set to event_entry_id while draft|pending_review; NULL when terminal. */
            $table->unsignedBigInteger('active_for_event_id')->nullable();

            $table->timestamps();

            $table->foreign('event_entry_id', 'cecp_event_fk')
                ->references('id')->on('cultural_event_entries')->restrictOnDelete();
            $table->foreign('organizer_id', 'cecp_organizer_fk')
                ->references('id')->on('cultural_organizers')->nullOnDelete();
            $table->foreign('created_by', 'cecp_created_by_fk')
                ->references('id')->on('users')->restrictOnDelete();
            $table->foreign('last_modified_by', 'cecp_last_modified_by_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('proposed_category_id', 'cecp_category_fk')
                ->references('id')->on('cultural_categories')->nullOnDelete();
            $table->foreign('proposed_cover_media_id', 'cecp_cover_fk')
                ->references('id')->on('cultural_media')->nullOnDelete();
            $table->foreign('decision_user_id', 'cecp_decision_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('review_started_by', 'cecp_review_by_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->unique('active_for_event_id', 'cecp_active_for_event_unique');
            $table->index('status');
            $table->index(['event_entry_id', 'status']);
            $table->index('organizer_id');
        });

        Schema::create('cultural_event_change_proposal_tag', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proposal_id');
            $table->unsignedBigInteger('cultural_tag_id');
            $table->timestamps();

            $table->foreign('proposal_id', 'cecpt_proposal_fk')
                ->references('id')->on('cultural_event_change_proposals')->cascadeOnDelete();
            $table->foreign('cultural_tag_id', 'cecpt_tag_fk')
                ->references('id')->on('cultural_tags')->restrictOnDelete();
            $table->unique(['proposal_id', 'cultural_tag_id'], 'cecpt_proposal_tag_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cultural_event_change_proposal_tag');
        Schema::dropIfExists('cultural_event_change_proposals');
    }
};
