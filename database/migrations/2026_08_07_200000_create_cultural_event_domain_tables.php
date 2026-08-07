<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TS-003 / TS-004 Korak 1 — kanonski domen Događaj + Održavanje (PO-EV-01).
 * Paralelno sa legacy cultural_events; bez migracije/backfill/dual-write.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cultural_event_entries', function (Blueprint $table) {
            $table->id();
            $table->string('naslov')->nullable();
            $table->text('opis')->nullable();
            $table->string('status', 32)->default('draft');
            $table->unsignedBigInteger('organizer_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('cover_media_id')->nullable();
            $table->boolean('featured')->default(false);
            $table->text('cancellation_reason')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('last_modified_by')->nullable();
            $table->timestamp('first_submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('organizer_id', 'cee_organizer_fk')
                ->references('id')->on('cultural_organizers')->nullOnDelete();
            $table->foreign('category_id', 'cee_category_fk')
                ->references('id')->on('cultural_categories')->nullOnDelete();
            $table->foreign('cover_media_id', 'cee_cover_media_fk')
                ->references('id')->on('cultural_media')->nullOnDelete();
            $table->foreign('created_by', 'cee_created_by_fk')
                ->references('id')->on('users')->restrictOnDelete();
            $table->foreign('last_modified_by', 'cee_last_modified_by_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->index('status');
            $table->index('organizer_id');
            $table->index('category_id');
            $table->index('featured');
        });

        Schema::create('cultural_event_entry_tag', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cultural_event_entry_id');
            $table->unsignedBigInteger('cultural_tag_id');
            $table->timestamps();

            $table->foreign('cultural_event_entry_id', 'ceet_entry_fk')
                ->references('id')->on('cultural_event_entries')->cascadeOnDelete();
            $table->foreign('cultural_tag_id', 'ceet_tag_fk')
                ->references('id')->on('cultural_tags')->restrictOnDelete();
            $table->unique(['cultural_event_entry_id', 'cultural_tag_id'], 'ceet_entry_tag_unique');
        });

        Schema::create('cultural_occurrences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_entry_id');
            $table->date('datum');
            $table->time('vrijeme_od')->nullable();
            $table->time('vrijeme_do')->nullable();
            $table->boolean('cjelodnevno')->default(false);
            $table->string('status', 32)->default('planned');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('location_manual_name')->nullable();
            $table->timestamps();

            $table->foreign('event_entry_id', 'co_event_entry_fk')
                ->references('id')->on('cultural_event_entries')->restrictOnDelete();
            $table->foreign('location_id', 'co_location_fk')
                ->references('id')->on('cultural_locations')->nullOnDelete();

            $table->index('event_entry_id');
            $table->index('status');
            $table->index('datum');
            $table->index('location_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cultural_occurrences');
        Schema::dropIfExists('cultural_event_entry_tag');
        Schema::dropIfExists('cultural_event_entries');
    }
};
