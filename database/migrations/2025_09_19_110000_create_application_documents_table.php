<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicationDocumentsTable extends Migration
{
    /**
     * Kreira tabelu za dokumenta uz prijavu (aplikaciju).
     */
    public function up()
    {
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade'); // Veza sa prijavom
            $table->string('name'); // Naziv dokumenta (npr. izvod iz CRPS, dokaz o porezima...)
            $table->string('file_path'); // Putanja do fajla (pdf, jpg, itd.)
            $table->string('type')->nullable(); // Tip dokumenta (opciono)
            $table->timestamps();
        });
    }

    /**
     * Briše tabelu.
     */
    public function down()
    {
        Schema::dropIfExists('application_documents');
    }
}