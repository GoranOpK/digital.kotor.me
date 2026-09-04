<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EP Phase 1 — catalog type foundation.
 * No 17/41 production rows. Purpose/model/payment-code columns are deferred.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('code', 'payment_types_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_types');
    }
};
