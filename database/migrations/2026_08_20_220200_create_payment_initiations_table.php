<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EP Phase 1 — user confirmation / future idempotency boundary.
 * Not a business PaymentTransaction. No fifth business status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_initiations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('payment_type_id');
            $table->unsignedBigInteger('payment_account_id');
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('EUR');
            $table->timestamps();

            $table->unique('uuid', 'payment_initiations_uuid_unique');
            $table->foreign('user_id', 'payment_initiations_user_fk')
                ->references('id')->on('users')->restrictOnDelete();
            $table->foreign('payment_type_id', 'payment_initiations_type_fk')
                ->references('id')->on('payment_types')->restrictOnDelete();
            $table->foreign('payment_account_id', 'payment_initiations_account_fk')
                ->references('id')->on('payment_accounts')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_initiations');
    }
};
