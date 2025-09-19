<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicationScoresTable extends Migration
{
    /**
     * Kreira tabelu za bodove i komentare komisije po prijavi i kriterijumu.
     */
    public function up()
    {
        Schema::create('application_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade'); // Veza sa prijavom
            $table->foreignId('evaluator_id')->constrained('users')->onDelete('cascade'); // Član komisije
            $table->foreignId('criteria_id')->constrained('evaluation_criteria')->onDelete('cascade'); // Kriterijum ocjene
            $table->integer('score'); // Bodovi (1-5)
            $table->text('comment')->nullable(); // Komentar/obrazloženje
            $table->timestamps();

            // Jedinstven unos po evaluatoru i kriterijumu za prijavu (kratko ime za unique constraint zbog MySQL limita)
            $table->unique(['application_id', 'evaluator_id', 'criteria_id'], 'app_score_eval_crit_unique');
        });
    }

    /**
     * Briše tabelu.
     */
    public function down()
    {
        Schema::dropIfExists('application_scores');
    }
}