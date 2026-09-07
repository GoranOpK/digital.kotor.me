<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_prigovors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id');
            $table->text('obrazlozenje');
            $table->string('status', 32);
            $table->timestamp('submitted_at');
            $table->foreignId('submitted_by_user_id')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by_commission_member_id')->nullable();
            $table->foreignId('decided_by_user_id')->nullable();
            $table->string('decided_by_name')->nullable();
            $table->text('decision_note')->nullable();
            $table->boolean('eliminatory_reason_remaining')->default(true);
            $table->timestamps();

            $table->unique('application_id', 'apg_application_id_unique');
            $table->foreign('application_id', 'apg_application_id_fk')
                ->references('id')
                ->on('applications')
                ->cascadeOnDelete();
            $table->foreign('submitted_by_user_id', 'apg_submitted_by_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('decided_by_commission_member_id', 'apg_decided_by_cm_fk')
                ->references('id')
                ->on('commission_members')
                ->nullOnDelete();
            $table->foreign('decided_by_user_id', 'apg_decided_by_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_prigovors');
    }
};
