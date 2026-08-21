<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EP Phase 1 — business PaymentTransaction structure only.
 * Rows are created in a later phase, after a gateway attempt is accepted/started.
 * Stub table `payments` is unchanged and coexists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('payment_initiation_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('payment_type_id');
            $table->unsignedBigInteger('payment_account_id');
            $table->string('status', 32);
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('EUR');
            $table->string('merchant_transaction_id', 64)->nullable();
            $table->string('gateway_reference', 64)->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->unique('uuid', 'payment_transactions_uuid_unique');
            $table->unique('payment_initiation_id', 'payment_transactions_initiation_unique');
            $table->unique('merchant_transaction_id', 'payment_transactions_merchant_tx_unique');
            $table->foreign('payment_initiation_id', 'payment_transactions_initiation_fk')
                ->references('id')->on('payment_initiations')->restrictOnDelete();
            $table->foreign('user_id', 'payment_transactions_user_fk')
                ->references('id')->on('users')->restrictOnDelete();
            $table->foreign('payment_type_id', 'payment_transactions_type_fk')
                ->references('id')->on('payment_types')->restrictOnDelete();
            $table->foreign('payment_account_id', 'payment_transactions_account_fk')
                ->references('id')->on('payment_accounts')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
