<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KN-SYNC-009 — canonical MMSP administrative finish line: Predlog finalization,
 * Secretariat handoff, formal Odluka record with signed copy, one-time publication
 * and close/archive audit.
 *
 * Additive, canonical-only. Women/Youth paths never write these tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('canonical_predlogs', function (Blueprint $table) {
            $table->timestamp('finalized_at')->nullable()->after('prepared_at');
            $table->foreignId('finalized_by_user_id')->nullable()->after('finalized_at')
                ->constrained('users')->restrictOnDelete();
        });

        Schema::create('canonical_secretariat_handoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->unique()->constrained('competitions')->restrictOnDelete();
            $table->foreignId('predlog_id')->unique()->constrained('canonical_predlogs')->restrictOnDelete();
            $table->string('recipient', 255);
            $table->string('reference', 255)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('handed_off_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('handed_off_at');
            $table->timestamps();
        });

        Schema::create('canonical_formal_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions')->restrictOnDelete();
            $table->string('publication_slot', 32)->default('formal_decision');
            $table->foreignId('predlog_id')->unique()->constrained('canonical_predlogs')->restrictOnDelete();
            $table->foreignId('handoff_id')->unique()->constrained('canonical_secretariat_handoffs')->restrictOnDelete();
            $table->string('decision_number', 128);
            $table->date('decision_date');
            $table->string('issuer', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('recorded_at');
            $table->foreignId('official_decision_copy_id')->nullable()->unique('canonical_formal_decisions_copy_unique')
                ->constrained('competition_official_decision_copies')->restrictOnDelete();
            $table->foreignId('attached_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('attached_at')->nullable();
            $table->foreignId('published_notice_id')->nullable()->unique('canonical_formal_decisions_notice_unique')
                ->constrained('notices')->restrictOnDelete();
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['competition_id', 'publication_slot'], 'canonical_formal_decisions_slot_unique');
        });

        Schema::create('canonical_administrative_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions')->restrictOnDelete();
            $table->string('action', 64);
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->string('subject_type', 64);
            $table->unsignedBigInteger('subject_id');
            $table->json('payload')->nullable();
            $table->timestamp('created_at');

            $table->index(['competition_id', 'action'], 'canonical_admin_events_competition_action_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canonical_administrative_events');
        Schema::dropIfExists('canonical_formal_decisions');
        Schema::dropIfExists('canonical_secretariat_handoffs');

        Schema::table('canonical_predlogs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('finalized_by_user_id');
            $table->dropColumn('finalized_at');
        });
    }
};
