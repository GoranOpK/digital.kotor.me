<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TS-001 Korak 1 — additive domen Organizator / Moderator / zahtjevi (PO-ORG-01–04).
 * Bez FK na CulturalEvent; bez Role izmjena; bez TS-012.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cultural_organizer_creation_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submitter_user_id');
            $table->unsignedBigInteger('proposed_moderator_user_id');
            $table->boolean('proposed_moderator_is_submitter')->default(false);

            $table->string('proposed_naziv');
            $table->text('proposed_opis')->nullable();
            $table->string('proposed_contact_email')->nullable();
            $table->string('proposed_contact_phone')->nullable();
            $table->string('proposed_website')->nullable();

            $table->string('status', 32)->default('submitted');
            $table->unsignedBigInteger('decision_user_id')->nullable();
            $table->timestamp('decision_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();

            $table->foreign('submitter_user_id', 'cocr_submitter_fk')
                ->references('id')->on('users')->restrictOnDelete();
            $table->foreign('proposed_moderator_user_id', 'cocr_proposed_mod_fk')
                ->references('id')->on('users')->restrictOnDelete();
            $table->foreign('decision_user_id', 'cocr_decision_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->index('status');
            $table->index('submitter_user_id');
        });

        Schema::create('cultural_organizers', function (Blueprint $table) {
            $table->id();
            $table->string('naziv');
            $table->text('opis')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('website')->nullable();
            $table->string('status', 32)->default('active');
            $table->unsignedBigInteger('approved_creation_request_id');
            $table->timestamps();

            $table->foreign('approved_creation_request_id', 'corg_approved_req_fk')
                ->references('id')->on('cultural_organizer_creation_requests')->restrictOnDelete();
            $table->unique('approved_creation_request_id');
            $table->index('status');
            $table->index('naziv');
        });

        Schema::create('cultural_moderator_authorizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('organizer_id');
            $table->string('status', 32)->default('active');
            $table->string('source', 32);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id', 'cma_user_fk')
                ->references('id')->on('users')->restrictOnDelete();
            $table->foreign('organizer_id', 'cma_organizer_fk')
                ->references('id')->on('cultural_organizers')->restrictOnDelete();
            $table->unique(['user_id', 'organizer_id'], 'cma_user_org_unique');
            $table->index('status');
            $table->index(['organizer_id', 'status']);
        });

        Schema::create('cultural_moderator_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organizer_id');
            $table->unsignedBigInteger('submitter_user_id');
            $table->unsignedBigInteger('target_user_id');
            $table->string('type', 16);
            $table->string('status', 32)->default('submitted');
            $table->unsignedBigInteger('decision_user_id')->nullable();
            $table->timestamp('decision_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();

            $table->foreign('organizer_id', 'cmr_organizer_fk')
                ->references('id')->on('cultural_organizers')->restrictOnDelete();
            $table->foreign('submitter_user_id', 'cmr_submitter_fk')
                ->references('id')->on('users')->restrictOnDelete();
            $table->foreign('target_user_id', 'cmr_target_fk')
                ->references('id')->on('users')->restrictOnDelete();
            $table->foreign('decision_user_id', 'cmr_decision_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->index('status');
            $table->index(['organizer_id', 'status']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cultural_moderator_requests');
        Schema::dropIfExists('cultural_moderator_authorizations');
        Schema::dropIfExists('cultural_organizers');
        Schema::dropIfExists('cultural_organizer_creation_requests');
    }
};
