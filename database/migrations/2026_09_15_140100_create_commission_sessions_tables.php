<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commission_id')->constrained()->cascadeOnDelete();
            $table->string('session_type', 16);
            $table->dateTime('held_at');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['competition_id', 'session_type'], 'commission_sessions_competition_type_unique');
            $table->index(['commission_id', 'session_type']);
        });

        Schema::create('commission_session_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_session_id')->constrained('commission_sessions')->cascadeOnDelete();
            $table->foreignId('commission_member_id')->constrained('commission_members')->cascadeOnDelete();
            $table->boolean('present')->default(false);
            $table->timestamps();

            $table->unique(
                ['commission_session_id', 'commission_member_id'],
                'commission_session_attendances_session_member_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_session_attendances');
        Schema::dropIfExists('commission_sessions');
    }
};
