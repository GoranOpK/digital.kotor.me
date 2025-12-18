<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migracija za kreiranje tabele feedback
 * Omogućava korisnicima da ostave povratnu informaciju o portalu
 */
return new class extends Migration
{
    /**
     * Pokreni migraciju
     */
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name')->nullable(); // Ime korisnika ako nije prijavljen
            $table->string('email')->nullable(); // Email korisnika ako nije prijavljen
            $table->string('subject'); // Naslov povratne informacije
            $table->text('message'); // Sadržaj povratne informacije
            $table->enum('status', ['new', 'in_progress', 'resolved', 'closed'])->default('new');
            $table->text('admin_response')->nullable(); // Odgovor administratora
            $table->timestamp('responded_at')->nullable(); // Vrijeme odgovora
            $table->timestamps();
        });
    }

    /**
     * Vrati migraciju
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
