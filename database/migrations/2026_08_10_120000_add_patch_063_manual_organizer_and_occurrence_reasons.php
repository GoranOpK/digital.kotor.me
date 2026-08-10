<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PATCH-063 Phase 1 — schema only (PO-U / BM PATCH-063 / TS-003 §6.6 / TS-004 §6.6).
 * Additive nullable columns; bez backfill-a, indexa i defaulta.
 * Entry cancellation_reason već postoji — ne dodaje se.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cultural_event_entries', function (Blueprint $table) {
            $table->string('organizer_manual_name')->nullable()->after('organizer_id');
        });

        Schema::table('cultural_occurrences', function (Blueprint $table) {
            $table->text('postponement_reason')->nullable()->after('location_manual_name');
            $table->text('cancellation_reason')->nullable()->after('postponement_reason');
        });
    }

    public function down(): void
    {
        Schema::table('cultural_occurrences', function (Blueprint $table) {
            $table->dropColumn(['postponement_reason', 'cancellation_reason']);
        });

        Schema::table('cultural_event_entries', function (Blueprint $table) {
            $table->dropColumn('organizer_manual_name');
        });
    }
};
