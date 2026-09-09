<?php

use App\Support\ScoringSeatBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // All fail-closed data checks that can run on the existing schema
        // must pass before the first ALTER TABLE. MySQL DDL is not transactional.
        ScoringSeatBackfill::assertPreflightAgainstExistingSchema();

        if (! Schema::hasColumn('commission_members', 'canonical_seat_no')) {
            Schema::table('commission_members', function (Blueprint $table) {
                $table->unsignedTinyInteger('canonical_seat_no')->nullable()->after('replaces_member_number');
            });
        }

        if (! Schema::hasColumn('evaluation_scores', 'canonical_seat_no')) {
            Schema::table('evaluation_scores', function (Blueprint $table) {
                $table->unsignedTinyInteger('canonical_seat_no')->nullable()->after('commission_member_id');
            });
        }

        if (! Schema::hasColumn('evaluation_scores', 'completed_at')) {
            Schema::table('evaluation_scores', function (Blueprint $table) {
                $table->timestamp('completed_at')->nullable()->after('justification');
                // completed_at is for NEW final submissions only.
                // Legacy completed rows keep completed_at NULL; completeness is 10 non-null criteria.
            });
        }

        ScoringSeatBackfill::backfillAfterColumnsExist();
        ScoringSeatBackfill::assertPostBackfillInvariants();

        // Residual MySQL risk: DDL is not transactional. If this UNIQUE step
        // fails after columns exist, the schema can remain without the index.
        // Operator recovery (NOT production): verify columns/index; if the
        // migration is not recorded, re-run up() — it is guarded and will not
        // re-add existing columns. Do not delete score rows or invent timestamps.
        if (! Schema::hasIndex('evaluation_scores', 'eval_scores_app_seat_unique')) {
            Schema::table('evaluation_scores', function (Blueprint $table) {
                $table->unique(['application_id', 'canonical_seat_no'], 'eval_scores_app_seat_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('evaluation_scores', 'eval_scores_app_seat_unique')) {
            Schema::table('evaluation_scores', function (Blueprint $table) {
                $table->dropUnique('eval_scores_app_seat_unique');
            });
        }

        Schema::table('evaluation_scores', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('evaluation_scores', 'canonical_seat_no')) {
                $drop[] = 'canonical_seat_no';
            }
            if (Schema::hasColumn('evaluation_scores', 'completed_at')) {
                $drop[] = 'completed_at';
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });

        if (Schema::hasColumn('commission_members', 'canonical_seat_no')) {
            Schema::table('commission_members', function (Blueprint $table) {
                $table->dropColumn('canonical_seat_no');
            });
        }
    }
};
