<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_official_decision_lifecycle_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('competition_official_decision_copy_id');
            $table->unsignedBigInteger('competition_id');
            $table->string('action', 64);
            $table->unsignedBigInteger('actor_user_id');
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at', 'codc_lifecycle_events_created_at_index');
            $table->foreign('competition_official_decision_copy_id', 'codc_lifecycle_events_copy_fk')
                ->references('id')
                ->on('competition_official_decision_copies')
                ->restrictOnDelete();
            $table->foreign('competition_id', 'codc_lifecycle_events_competition_fk')
                ->references('id')
                ->on('competitions')
                ->restrictOnDelete();
            $table->foreign('actor_user_id', 'codc_lifecycle_events_actor_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_official_decision_lifecycle_events');
    }
};
