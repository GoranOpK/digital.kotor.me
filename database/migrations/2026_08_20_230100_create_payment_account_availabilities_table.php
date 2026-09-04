<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EP Phase 3 — payment account availability rules.
 * Canonical user_type storage only. No 17/41 matrix rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_account_availabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_account_id');
            $table->string('user_type', 191);
            $table->string('residential_status', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->string('residential_unique_token', 32)->storedAs(
                "IFNULL(`residential_status`, '')"
            );

            $table->unique(
                ['payment_account_id', 'user_type', 'residential_unique_token'],
                'paa_parent_user_res_unique'
            );
            $table->index(
                ['payment_account_id', 'is_active', 'user_type'],
                'paa_lookup_idx'
            );

            $table->foreign('payment_account_id', 'paa_account_fk')
                ->references('id')->on('payment_accounts')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_account_availabilities');
    }
};
