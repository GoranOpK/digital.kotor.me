<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cultural_manifestations', function (Blueprint $table) {
            $table->id();
            $table->string('naziv', 255);
            $table->text('opis')->nullable();
            $table->string('status', 32)->default('draft');
            $table->unsignedBigInteger('organizer_id')->nullable();
            $table->unsignedBigInteger('cover_media_id')->nullable();
            $table->string('web_stranica', 2048)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('last_modified_by')->nullable();
            $table->timestamp('first_submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->foreign('organizer_id', 'cm_organizer_fk')
                ->references('id')->on('cultural_organizers')->nullOnDelete();
            $table->foreign('cover_media_id', 'cm_cover_media_fk')
                ->references('id')->on('cultural_media')->nullOnDelete();
            $table->foreign('created_by', 'cm_created_by_fk')
                ->references('id')->on('users')->restrictOnDelete();
            $table->foreign('last_modified_by', 'cm_last_modified_by_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->index('naziv');
            $table->index('status');
            $table->index('organizer_id');
            $table->index('cover_media_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cultural_manifestations');
    }
};

