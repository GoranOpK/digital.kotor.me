<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_oral_presentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_session_id')->constrained('commission_sessions')->cascadeOnDelete();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('held_at')->nullable();
            $table->boolean('applicant_attended')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('application_id', 'application_oral_presentations_application_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_oral_presentations');
    }
};
