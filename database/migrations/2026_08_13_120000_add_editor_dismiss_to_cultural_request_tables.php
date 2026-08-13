<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PO-ORG/MOD rejected request editor cleanup — dismiss metadata only.
 * Additive; nullable; no backfill; no SoftDeletes; no hard delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cultural_organizer_creation_requests', function (Blueprint $table) {
            $table->timestamp('editor_dismissed_at')->nullable()->after('decision_note');
            $table->unsignedBigInteger('editor_dismissed_by_user_id')->nullable()->after('editor_dismissed_at');

            $table->foreign('editor_dismissed_by_user_id', 'cocr_editor_dismissed_by_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->index('editor_dismissed_at', 'cocr_editor_dismissed_at_idx');
        });

        Schema::table('cultural_moderator_requests', function (Blueprint $table) {
            $table->timestamp('editor_dismissed_at')->nullable()->after('decision_note');
            $table->unsignedBigInteger('editor_dismissed_by_user_id')->nullable()->after('editor_dismissed_at');

            $table->foreign('editor_dismissed_by_user_id', 'cmr_editor_dismissed_by_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->index('editor_dismissed_at', 'cmr_editor_dismissed_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cultural_moderator_requests', function (Blueprint $table) {
            $table->dropIndex('cmr_editor_dismissed_at_idx');
            $table->dropForeign('cmr_editor_dismissed_by_fk');
            $table->dropColumn(['editor_dismissed_at', 'editor_dismissed_by_user_id']);
        });

        Schema::table('cultural_organizer_creation_requests', function (Blueprint $table) {
            $table->dropIndex('cocr_editor_dismissed_at_idx');
            $table->dropForeign('cocr_editor_dismissed_by_fk');
            $table->dropColumn(['editor_dismissed_at', 'editor_dismissed_by_user_id']);
        });
    }
};
