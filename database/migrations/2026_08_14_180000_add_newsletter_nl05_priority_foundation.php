<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NL-05 — pending priority changes + additive priority_change ledger uniqueness.
 * Additive only. No backfill. Legacy newsletter_subscribers KEEP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_pending_priority_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cultural_event_entry_id');
            $table->unsignedBigInteger('cultural_occurrence_id')->nullable();
            $table->string('change_kind', 32);
            $table->string('change_control_key', 191);
            $table->json('effective_state')->nullable();
            $table->timestamp('detected_at');
            $table->string('status', 32);
            $table->timestamps();

            $table->string('pending_subject_key', 64)->nullable()->storedAs(
                "CASE WHEN `status` = 'pending' THEN CONCAT(`cultural_event_entry_id`, ':', IFNULL(`cultural_occurrence_id`, 0)) ELSE NULL END"
            );

            $table->unique('pending_subject_key', 'nppc_pending_subject_unique');
            $table->index(['status', 'detected_at'], 'nppc_status_detected_idx');
            $table->index('cultural_event_entry_id', 'nppc_event_idx');
            $table->index('change_control_key', 'nppc_change_key_idx');

            $table->foreign('cultural_event_entry_id', 'nppc_event_fk')
                ->references('id')
                ->on('cultural_event_entries')
                ->restrictOnDelete();

            $table->foreign('cultural_occurrence_id', 'nppc_occurrence_fk')
                ->references('id')
                ->on('cultural_occurrences')
                ->restrictOnDelete();
        });

        Schema::table('newsletter_delivery_ledger', function (Blueprint $table) {
            $table->string('priority_change_key', 191)->nullable()->storedAs(
                "CASE WHEN `entry_type` = 'priority_change' THEN `change_control_key` ELSE NULL END"
            );

            $table->unique(
                ['newsletter_subscription_id', 'priority_change_key'],
                'ndl_priority_change_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_delivery_ledger', function (Blueprint $table) {
            $table->dropUnique('ndl_priority_change_unique');
            $table->dropColumn('priority_change_key');
        });

        Schema::dropIfExists('newsletter_pending_priority_changes');
    }
};
