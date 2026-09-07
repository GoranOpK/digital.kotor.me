<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Schema only. Does not backfill or reinterpret documents_complete.
     */
    public function up(): void
    {
        Schema::create('application_eliminatory_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id');
            $table->boolean('criterion_1')->nullable()->default(true);
            $table->boolean('criterion_2')->nullable()->default(true);
            $table->boolean('criterion_3')->nullable()->default(true);
            $table->text('note')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by_commission_member_id')->nullable();
            $table->foreignId('confirmed_by_user_id')->nullable();
            $table->string('confirmed_by_name')->nullable();
            $table->timestamps();

            $table->unique('application_id', 'aec_application_id_unique');
            $table->foreign('application_id', 'aec_application_id_fk')
                ->references('id')
                ->on('applications')
                ->cascadeOnDelete();
            $table->foreign('confirmed_by_commission_member_id', 'aec_confirmed_by_cm_fk')
                ->references('id')
                ->on('commission_members')
                ->nullOnDelete();
            $table->foreign('confirmed_by_user_id', 'aec_confirmed_by_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_eliminatory_checks');
    }
};
