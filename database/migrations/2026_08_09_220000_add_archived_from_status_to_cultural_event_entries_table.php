<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 6A-09 / PO-6A09-02 — očuvanje izvornog javnog statusa pri arhiviranju.
 * Additive; bez backfill-a. Postojeći archived ostaju null (fail-closed za Javnu Arhivu).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cultural_event_entries', function (Blueprint $table) {
            $table->string('archived_from_status', 32)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('cultural_event_entries', function (Blueprint $table) {
            $table->dropColumn('archived_from_status');
        });
    }
};
