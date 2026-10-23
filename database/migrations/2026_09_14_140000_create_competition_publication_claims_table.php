<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KN-SYNC-003 / OTD-003.
 *
 * Prospective slot claim. Does not backfill or rewrite historical publications.
 * Uniqueness is year + type + slot, not year + type, so profiles may have more than one slot.
 * Youth annual calls do not use this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_publication_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions')->restrictOnDelete();
            $table->unsignedSmallInteger('calendar_year');
            $table->string('competition_type');
            $table->string('publication_slot');
            $table->timestamp('claimed_at');
            $table->timestamps();

            $table->unique(
                ['calendar_year', 'competition_type', 'publication_slot'],
                'publication_claims_year_type_slot_unique'
            );
            $table->unique('competition_id', 'publication_claims_competition_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_publication_claims');
    }
};
