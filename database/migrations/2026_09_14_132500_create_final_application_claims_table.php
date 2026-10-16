<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KN-SYNC-004 / OTD-004 — prospective final claim table.
 *
 * Additive only. No backfill. No Women/Youth behavior rewrite.
 * Timestamp 132500 sits between canonical type (103900) and publication claims (140000).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_application_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('competition_id')->constrained('competitions')->restrictOnDelete();
            $table->foreignId('application_id')->constrained('applications')->restrictOnDelete();
            $table->timestamp('claimed_at');
            $table->timestamps();

            $table->unique(['user_id', 'competition_id'], 'final_claims_user_competition_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_application_claims');
    }
};
