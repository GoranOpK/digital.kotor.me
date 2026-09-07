<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_official_decision_copies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('competition_id');
            $table->string('storage_path');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('competition_id', 'codc_competition_fk')
                ->references('id')
                ->on('competitions')
                ->restrictOnDelete();

            $table->foreign('uploaded_by', 'codc_uploaded_by_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_official_decision_copies');
    }
};
