<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EP Phase 10 — catalog-admin audit (append-only).
 * Not PaymentTransactionEvent. Not KK CulturalActivityRecord.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ep_catalog_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_user_id');
            $table->string('action', 64);
            $table->string('entity_type', 64);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id'], 'ep_catalog_audits_entity_index');
            $table->index('created_at', 'ep_catalog_audits_created_at_index');
            $table->foreign('actor_user_id', 'ep_catalog_audits_actor_fk')
                ->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ep_catalog_audits');
    }
};
