<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrioritiesTable extends Migration
{
    /**
     * Kreira tabelu za prioritetne oblasti konkursa.
     */
    public function up()
    {
        Schema::create('priorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->onDelete('cascade'); // Veza sa konkursom
            $table->string('name'); // Naziv prioritetne oblasti
            $table->text('description')->nullable(); // Opis oblasti
            $table->timestamps();
        });
    }

    /**
     * Briše tabelu.
     */
    public function down()
    {
        Schema::dropIfExists('priorities');
    }
}