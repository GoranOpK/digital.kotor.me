<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NL-03 — first_include eligibility foundation.
 *
 * Additive only. No Event backfill. No subscriber backfill.
 * Legacy newsletter_subscribers KEEP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cultural_event_entries', function (Blueprint $table) {
            $table->timestamp('first_published_at')->nullable()->after('first_submitted_at');
            $table->index('first_published_at', 'cee_first_published_at_idx');
        });

        Schema::create('newsletter_subscription_source_coverages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('newsletter_subscription_id');
            $table->string('source_type', 32);
            $table->unsignedBigInteger('cultural_organizer_id')->nullable();
            $table->timestamp('covered_since');
            $table->timestamp('covered_until')->nullable();
            $table->timestamps();

            $table->index(
                ['newsletter_subscription_id', 'source_type', 'covered_until'],
                'nssc_subscription_type_until_idx'
            );
            $table->index(
                ['newsletter_subscription_id', 'cultural_organizer_id'],
                'nssc_subscription_organizer_idx'
            );

            $table->foreign('newsletter_subscription_id', 'nssc_subscription_fk')
                ->references('id')
                ->on('newsletter_subscriptions')
                ->cascadeOnDelete();

            $table->foreign('cultural_organizer_id', 'nssc_organizer_fk')
                ->references('id')
                ->on('cultural_organizers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscription_source_coverages');

        Schema::table('cultural_event_entries', function (Blueprint $table) {
            $table->dropIndex('cee_first_published_at_idx');
            $table->dropColumn('first_published_at');
        });
    }
};
