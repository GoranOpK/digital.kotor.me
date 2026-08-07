<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 3A.3 — razlog vraćanja na doradu (BR-040 / TS-003 §4.5).
 * Additive; ne dira legacy cultural_events.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cultural_event_entries', function (Blueprint $table) {
            $table->text('return_reason')->nullable()->after('cancellation_reason');
        });
    }

    public function down(): void
    {
        Schema::table('cultural_event_entries', function (Blueprint $table) {
            $table->dropColumn('return_reason');
        });
    }
};
