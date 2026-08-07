<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive: katalog Medija (TS-008 Korak 1).
     * Ne mijenja cultural_events.slika ni postojeći storage cultural-events/.
     */
    public function up(): void
    {
        Schema::create('cultural_media', function (Blueprint $table) {
            $table->id();
            $table->string('naziv');
            $table->string('namjena', 64);
            $table->string('status', 32)->default('active');
            $table->string('alt_tekst');
            $table->text('opis')->nullable();
            $table->string('autor')->nullable();
            $table->string('izvor')->nullable();
            $table->string('licenca')->nullable();
            $table->json('tagovi')->nullable();
            $table->string('originalni_naziv');
            $table->string('interni_naziv');
            $table->string('mime', 64);
            $table->string('format', 16);
            $table->unsignedInteger('sirina')->nullable();
            $table->unsignedInteger('visina')->nullable();
            $table->unsignedBigInteger('velicina');
            $table->string('storage_path');
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('namjena');
            $table->index('naziv');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cultural_media');
    }
};
