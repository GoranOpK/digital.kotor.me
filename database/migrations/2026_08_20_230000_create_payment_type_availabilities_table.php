<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EP Phase 3 — payment type availability rules.
 * Canonical user_type storage only. No 17/41 matrix rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_type_availabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_type_id');
            $table->string('user_type', 191);
            $table->string('residential_status', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->string('residential_unique_token', 32)->storedAs(
                "IFNULL(`residential_status`, '')"
            );

            $table->unique(
                ['payment_type_id', 'user_type', 'residential_unique_token'],
                'pta_parent_user_res_unique'
            );
            $table->index(
                ['payment_type_id', 'is_active', 'user_type'],
                'pta_lookup_idx'
            );

            $table->foreign('payment_type_id', 'pta_type_fk')
                ->references('id')->on('payment_types')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_type_availabilities');
    }
};
