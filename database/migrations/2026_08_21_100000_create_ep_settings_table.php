<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * EP Phase 4 — minimal module flag for new payments.
 * Not a general settings framework. No production seed of catalog data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ep_settings', function (Blueprint $table) {
            $table->string('key', 64);
            $table->text('value');
            $table->timestamps();

            $table->primary('key');
        });

        DB::table('ep_settings')->insert([
            'key' => 'new_payments_enabled',
            'value' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ep_settings');
    }
};
