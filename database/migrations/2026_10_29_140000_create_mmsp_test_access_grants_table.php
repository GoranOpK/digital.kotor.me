<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only history of MMSP test-access grants and revocations.
 * user_id is indexed, not unique: one account can have many events.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mmsp_test_access_grants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('actor_user_id');
            $table->string('action', 16);
            $table->string('reason', 32);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'id'], 'mmsp_test_access_grants_user_latest_idx');
            $table->index('actor_user_id', 'mmsp_test_access_grants_actor_idx');
            $table->foreign('user_id', 'mmsp_test_access_grants_user_fk')
                ->references('id')->on('users')->restrictOnDelete();
            $table->foreign('actor_user_id', 'mmsp_test_access_grants_actor_fk')
                ->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mmsp_test_access_grants');
    }
};
