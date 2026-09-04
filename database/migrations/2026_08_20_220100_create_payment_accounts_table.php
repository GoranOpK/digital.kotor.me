<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EP Phase 1 — catalog account foundation.
 * Account number change = deactivate old row, insert new row. No production accounts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_type_id');
            $table->string('account_number', 64);
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('account_number', 'payment_accounts_number_unique');
            $table->foreign('payment_type_id', 'payment_accounts_type_fk')
                ->references('id')->on('payment_types')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_accounts');
    }
};
