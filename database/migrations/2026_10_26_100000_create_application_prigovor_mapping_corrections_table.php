<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_prigovor_mapping_corrections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('prigovor_id');
            $table->unsignedTinyInteger('from_criterion');
            $table->unsignedTinyInteger('to_criterion');
            $table->json('before_snapshot');
            $table->json('after_snapshot');
            $table->text('reason');
            $table->unsignedBigInteger('corrected_by_commission_member_id')->nullable();
            $table->unsignedBigInteger('corrected_by_user_id')->nullable();
            $table->string('corrected_by_name');
            $table->timestamp('created_at')->useCurrent();

            $table->unique('application_id', 'apmc_application_id_unique');
            $table->foreign('application_id', 'apmc_application_id_fk')
                ->references('id')
                ->on('applications')
                ->cascadeOnDelete();
            $table->foreign('prigovor_id', 'apmc_prigovor_id_fk')
                ->references('id')
                ->on('application_prigovors')
                ->cascadeOnDelete();
            $table->foreign('corrected_by_commission_member_id', 'apmc_actor_member_fk')
                ->references('id')
                ->on('commission_members')
                ->nullOnDelete();
            $table->foreign('corrected_by_user_id', 'apmc_actor_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_prigovor_mapping_corrections');
    }
};
