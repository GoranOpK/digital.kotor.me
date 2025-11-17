<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    /**
     * Kreira tabelu za izvještaje o realizaciji biznis plana.
     */
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade'); // Veza sa prijavom
            $table->text('description')->nullable(); // Opis realizacije
            $table->string('document_file')->nullable(); // Putanja do dokaza (pdf, jpg...)
            $table->string('status')->default('predato'); // Status izvještaja (predato, odobreno, odbijeno)
            $table->timestamps();
        });
    }

    /**
     * Briše tabelu.
     */
    public function down()
    {
        Schema::dropIfExists('reports');
    }
}