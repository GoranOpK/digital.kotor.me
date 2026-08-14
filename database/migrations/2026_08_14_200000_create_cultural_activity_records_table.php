<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F8-02 — TS-012 centralna Evidencija aktivnosti (store only).
 * Bez emitera. Bez Admin UI. Newsletter ledger ostaje odvojen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cultural_activity_records', function (Blueprint $table) {
            $table->id();
            $table->string('source_module', 16);
            $table->string('event_id', 191);
            $table->string('event_type', 64);
            $table->timestamp('occurred_at');
            $table->string('actor_type', 16);
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('target_type', 64);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->unsignedBigInteger('organizer_context_id')->nullable();
            $table->json('context')->nullable();
            // MySQL strict/NO_ZERO_DATE: TIMESTAMP NOT NULL without DEFAULT → 1067.
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['source_module', 'event_id'], 'car_source_event_unique');
            $table->index('occurred_at', 'car_occurred_idx');
            $table->index(['target_type', 'target_id'], 'car_target_idx');

            $table->foreign('actor_user_id', 'car_actor_user_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cultural_activity_records');
    }
};
