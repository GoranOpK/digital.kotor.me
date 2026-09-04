<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EP Phase 7 — email confirmation delivery ledger.
 * Idempotency for first processing → successful transition. Not a catalog audit.
 * No backfill of existing successful transactions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_confirmation_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_transaction_id');
            $table->string('channel', 32);
            $table->string('status', 32);
            $table->string('recipient_email', 255)->nullable();
            $table->string('error_class', 191)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['payment_transaction_id', 'channel'],
                'pcd_transaction_channel_unique'
            );
            $table->foreign('payment_transaction_id', 'pcd_transaction_fk')
                ->references('id')->on('payment_transactions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_confirmation_deliveries');
    }
};
