<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KN-SYNC-008 — canonical MMSP third-session outcome, subsidy amounts, shared-rank
 * tie resolution, 3/3 final ranking signatures and the private Predlog snapshot.
 *
 * Additive, canonical-only tables. Women/Youth outcome paths never write them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canonical_final_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->unique()->constrained('competitions')->restrictOnDelete();
            $table->foreignId('commission_id')->constrained('commissions')->restrictOnDelete();
            $table->foreignId('final_decision_session_id')->constrained('commission_sessions')->restrictOnDelete();
            $table->foreignId('confirmed_by_member_id')->constrained('commission_members')->restrictOnDelete();
            $table->decimal('budget', 15, 2);
            $table->decimal('total_approved', 15, 2);
            $table->timestamp('confirmed_at');
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
        });

        Schema::create('canonical_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions')->restrictOnDelete();
            $table->foreignId('application_id')->unique()->constrained('applications')->restrictOnDelete();
            $table->string('decision', 16);
            $table->decimal('approved_amount', 14, 2)->nullable();
            $table->string('rejection_reason', 64)->nullable();
            $table->text('justification')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by_member_id')->constrained('commission_members')->restrictOnDelete();
            $table->foreignId('final_ranking_id')->nullable()->constrained('canonical_final_rankings')->restrictOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['competition_id', 'decision']);
        });

        Schema::create('canonical_tie_resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions')->restrictOnDelete();
            $table->unsignedInteger('shared_rank');
            $table->json('tied_application_ids');
            $table->json('selected_application_ids');
            $table->string('status', 16);
            $table->foreignId('proposed_by_member_id')->constrained('commission_members')->restrictOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->index(['competition_id', 'shared_rank', 'status'], 'canonical_ties_competition_rank_status_idx');
        });

        Schema::create('canonical_tie_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tie_resolution_id')->constrained('canonical_tie_resolutions')->restrictOnDelete();
            $table->foreignId('commission_member_id')->constrained('commission_members')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('canonical_seat_no');
            $table->boolean('in_favour');
            $table->timestamp('voted_at');
            $table->timestamps();

            $table->unique(['tie_resolution_id', 'canonical_seat_no'], 'canonical_tie_votes_seat_unique');
            $table->unique(['tie_resolution_id', 'commission_member_id'], 'canonical_tie_votes_member_unique');
        });

        Schema::create('canonical_final_ranking_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('final_ranking_id')->constrained('canonical_final_rankings')->restrictOnDelete();
            $table->foreignId('commission_member_id')->constrained('commission_members')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('canonical_seat_no');
            $table->string('canonical_seat', 32);
            $table->string('signer_name');
            $table->timestamp('signed_at');
            $table->timestamps();

            $table->unique(['final_ranking_id', 'canonical_seat_no'], 'canonical_ranking_signatures_seat_unique');
            $table->unique(['final_ranking_id', 'commission_member_id'], 'canonical_ranking_signatures_member_unique');
        });

        Schema::create('canonical_predlogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->unique()->constrained('competitions')->restrictOnDelete();
            $table->foreignId('final_ranking_id')->unique()->constrained('canonical_final_rankings')->restrictOnDelete();
            $table->json('payload');
            $table->char('fingerprint', 64);
            $table->timestamp('prepared_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canonical_predlogs');
        Schema::dropIfExists('canonical_final_ranking_signatures');
        Schema::dropIfExists('canonical_tie_votes');
        Schema::dropIfExists('canonical_tie_resolutions');
        Schema::dropIfExists('canonical_outcomes');
        Schema::dropIfExists('canonical_final_rankings');
    }
};
