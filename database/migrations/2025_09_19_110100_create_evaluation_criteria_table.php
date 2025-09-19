<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationCriteriaTable extends Migration
{
    /**
     * Kreira tabelu za kriterijume ocjenjivanja konkursa.
     */
    public function up()
    {
        Schema::create('evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->onDelete('cascade'); // Veza sa konkursom
            $table->string('name'); // Naziv kriterijuma (npr. inovativnost, tržište...)
            $table->text('description')->nullable(); // Opis kriterijuma
            $table->integer('max_score')->default(5); // Maksimalan broj bodova (default 5)
            $table->timestamps();
        });
    }

    /**
     * Briše tabelu.
     */
    public function down()
    {
        Schema::dropIfExists('evaluation_criteria');
    }
}