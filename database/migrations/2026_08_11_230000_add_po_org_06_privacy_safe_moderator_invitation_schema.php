<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PO-ORG-06 Package 1 — schema foundation for privacy-safe Moderator invitation.
 *
 * Additive / backward-compatible:
 * - nullable proposed_moderator_user_id / target_user_id (FK KEEP restrictOnDelete)
 * - proposed_moderator_name / proposed_moderator_email (nullable DB; store normalized email)
 * - status remains string(32); awaiting_moderator_eligibility is application-level
 * - resolver indexes; no unique constraint on unfinished ADD (app-level / MySQL partial unique)
 *
 * Rollback:
 * - PRE-NEW-DATA (no NULL user bindings): down may restore NOT NULL
 * - POST-NEW-DATA (NULL bindings exist): restoring NOT NULL is destructive / fails
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cultural_organizer_creation_requests', function (Blueprint $table) {
            $table->dropForeign('cocr_proposed_mod_fk');
        });

        Schema::table('cultural_organizer_creation_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('proposed_moderator_user_id')->nullable()->change();

            $table->string('proposed_moderator_name')->nullable()->after('proposed_moderator_user_id');
            $table->string('proposed_moderator_email')->nullable()->after('proposed_moderator_name');

            $table->foreign('proposed_moderator_user_id', 'cocr_proposed_mod_fk')
                ->references('id')->on('users')->restrictOnDelete();

            $table->index(
                ['proposed_moderator_email', 'status'],
                'cocr_pmod_email_status_idx'
            );
        });

        Schema::table('cultural_moderator_requests', function (Blueprint $table) {
            $table->dropForeign('cmr_target_fk');
        });

        Schema::table('cultural_moderator_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('target_user_id')->nullable()->change();

            $table->string('proposed_moderator_name')->nullable()->after('target_user_id');
            $table->string('proposed_moderator_email')->nullable()->after('proposed_moderator_name');

            $table->foreign('target_user_id', 'cmr_target_fk')
                ->references('id')->on('users')->restrictOnDelete();

            $table->index(
                ['proposed_moderator_email', 'status'],
                'cmr_pmod_email_status_idx'
            );
            $table->index(
                ['organizer_id', 'proposed_moderator_email', 'status'],
                'cmr_org_pmod_email_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('cultural_moderator_requests', function (Blueprint $table) {
            $table->dropIndex('cmr_org_pmod_email_status_idx');
            $table->dropIndex('cmr_pmod_email_status_idx');
            $table->dropForeign('cmr_target_fk');
            $table->dropColumn(['proposed_moderator_name', 'proposed_moderator_email']);
        });

        if ($this->hasNullBindings('cultural_moderator_requests', 'target_user_id')) {
            throw new \RuntimeException(
                'PO-ORG-06 rollback blocked: cultural_moderator_requests.target_user_id has NULL rows (POST-NEW-DATA).'
            );
        }

        Schema::table('cultural_moderator_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('target_user_id')->nullable(false)->change();
            $table->foreign('target_user_id', 'cmr_target_fk')
                ->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('cultural_organizer_creation_requests', function (Blueprint $table) {
            $table->dropIndex('cocr_pmod_email_status_idx');
            $table->dropForeign('cocr_proposed_mod_fk');
            $table->dropColumn(['proposed_moderator_name', 'proposed_moderator_email']);
        });

        if ($this->hasNullBindings('cultural_organizer_creation_requests', 'proposed_moderator_user_id')) {
            throw new \RuntimeException(
                'PO-ORG-06 rollback blocked: cultural_organizer_creation_requests.proposed_moderator_user_id has NULL rows (POST-NEW-DATA).'
            );
        }

        Schema::table('cultural_organizer_creation_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('proposed_moderator_user_id')->nullable(false)->change();
            $table->foreign('proposed_moderator_user_id', 'cocr_proposed_mod_fk')
                ->references('id')->on('users')->restrictOnDelete();
        });
    }

    private function hasNullBindings(string $table, string $column): bool
    {
        return DB::table($table)->whereNull($column)->exists();
    }
};
