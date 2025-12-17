<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('category'); // Lični dokumenti, Finansijski, Tehnički, Poslovni, Ostali
            $table->string('name'); // Naziv dokumenta
            $table->string('file_path'); // Putanja do optimizovanog PDF fajla
            $table->string('original_filename')->nullable(); // Originalni naziv fajla
            $table->bigInteger('file_size'); // Veličina u bajtovima (optimizovanog fajla)
            $table->date('expires_at')->nullable(); // Datum isteka (ako ima)
            $table->enum('status', ['active', 'expired'])->default('active');
            $table->timestamps();

            $table->index(['user_id', 'category']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_documents');
    }
};

