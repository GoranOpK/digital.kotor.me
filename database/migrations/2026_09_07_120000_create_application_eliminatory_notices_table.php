<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_eliminatory_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id');
            $table->foreignId('eliminatory_check_id')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('portal_recorded_at')->useCurrent();
            $table->timestamp('mail_sent_at')->nullable();
            $table->timestamp('mail_failed_at')->nullable();
            $table->json('reasons_snapshot')->nullable();
            $table->text('note_snapshot')->nullable();
            $table->timestamps();

            $table->unique('application_id', 'aen_application_id_unique');
            $table->foreign('application_id', 'aen_application_id_fk')
                ->references('id')
                ->on('applications')
                ->cascadeOnDelete();
            $table->foreign('eliminatory_check_id', 'aen_eliminatory_check_id_fk')
                ->references('id')
                ->on('application_eliminatory_checks')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_eliminatory_notices');
    }
};
