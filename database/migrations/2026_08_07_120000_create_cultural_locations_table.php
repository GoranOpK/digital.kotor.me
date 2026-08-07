<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive: katalog lokacija (TS-006 Korak 1).
     * Ne mijenja cultural_events ni postojeće kolone.
     */
    public function up(): void
    {
        Schema::create('cultural_locations', function (Blueprint $table) {
            $table->id();
            $table->string('naziv');
            $table->text('opis')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();

            $table->index('status');
            $table->index('naziv');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cultural_locations');
    }
};
