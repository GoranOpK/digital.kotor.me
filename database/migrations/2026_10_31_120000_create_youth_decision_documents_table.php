<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('youth_decision_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('competition_id');
            $table->unsignedBigInteger('generated_by');
            $table->timestamp('generated_at');
            $table->string('storage_path');
            $table->char('content_sha256', 64);
            $table->char('ranking_sha256', 64);

            $table->unique('storage_path', 'ydd_storage_path_unique');
            $table->index(['competition_id', 'generated_at'], 'ydd_competition_generated_at_idx');
            $table->foreign('competition_id', 'ydd_competition_fk')
                ->references('id')
                ->on('competitions')
                ->restrictOnDelete();
            $table->foreign('generated_by', 'ydd_generated_by_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('youth_decision_documents');
    }
};
