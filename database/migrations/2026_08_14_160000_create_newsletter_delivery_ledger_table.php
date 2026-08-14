<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NL-04 — first_include delivery evidence (successful send only).
 * No backfill. Legacy newsletter_subscribers KEEP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_delivery_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('newsletter_subscription_id');
            $table->unsignedBigInteger('cultural_event_entry_id');
            $table->unsignedBigInteger('cultural_occurrence_id')->nullable();
            $table->string('entry_type', 32);
            $table->string('change_control_key', 191)->nullable();
            $table->uuid('delivery_cycle_id');
            $table->json('payload_snapshot')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unsignedBigInteger('first_include_event_id')->nullable()->storedAs(
                "CASE WHEN `entry_type` = 'first_include' THEN `cultural_event_entry_id` ELSE NULL END"
            );

            $table->unique(
                ['newsletter_subscription_id', 'first_include_event_id'],
                'ndl_first_include_unique'
            );
            $table->index(['newsletter_subscription_id', 'sent_at'], 'ndl_subscription_sent_idx');
            $table->index('cultural_event_entry_id', 'ndl_event_idx');
            $table->index('delivery_cycle_id', 'ndl_cycle_idx');

            $table->foreign('newsletter_subscription_id', 'ndl_subscription_fk')
                ->references('id')
                ->on('newsletter_subscriptions')
                ->cascadeOnDelete();

            $table->foreign('cultural_event_entry_id', 'ndl_event_fk')
                ->references('id')
                ->on('cultural_event_entries')
                ->restrictOnDelete();

            $table->foreign('cultural_occurrence_id', 'ndl_occurrence_fk')
                ->references('id')
                ->on('cultural_occurrences')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_delivery_ledger');
    }
};
