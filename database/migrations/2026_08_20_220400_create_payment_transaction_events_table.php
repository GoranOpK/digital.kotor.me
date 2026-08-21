<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EP Phase 1 — append-only event storage foundation.
 * No callback/inquiry processing. No provider-event uniqueness (gateway contract OPEN).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transaction_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_transaction_id');
            $table->string('event_type', 64);
            $table->string('provider_event_id', 64)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->foreign('payment_transaction_id', 'payment_tx_events_transaction_fk')
                ->references('id')->on('payment_transactions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transaction_events');
    }
};
