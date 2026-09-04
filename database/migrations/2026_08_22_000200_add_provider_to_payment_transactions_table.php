<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EP Phase 10 — historical provider identity on PaymentTransaction.
 * Nullable: legacy/unknown rows fail closed for inquiry. No fake backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('provider', 64)->nullable()->after('gateway_reference');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
};
