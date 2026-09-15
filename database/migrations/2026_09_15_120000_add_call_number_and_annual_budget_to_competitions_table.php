<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Minimal annual-instance columns on existing competitions.
 *
 * Nullable for existing zensko rows. No backfill. No remaining_amount.
 * No separate instance table. Does not rewrite existing data.
 */
return new class extends Migration
{
    public const UNIQUE_INDEX = 'competitions_type_year_call_number_unique';

    public const CHECK_CONSTRAINT = 'competitions_call_number_chk';

    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            if (! Schema::hasColumn('competitions', 'call_number')) {
                $table->unsignedTinyInteger('call_number')->nullable()->after('type');
            }

            if (! Schema::hasColumn('competitions', 'annual_budget')) {
                $table->decimal('annual_budget', 15, 2)->nullable()->after('budget');
            }
        });

        $this->dropUniqueIndexIfExists();
        $this->dropCheckConstraintIfExists();

        Schema::table('competitions', function (Blueprint $table) {
            $table->unique(['type', 'year', 'call_number'], self::UNIQUE_INDEX);
        });

        DB::statement(
            'ALTER TABLE competitions ADD CONSTRAINT '.self::CHECK_CONSTRAINT
            .' CHECK (call_number IS NULL OR call_number IN (1, 2))'
        );
    }

    public function down(): void
    {
        $this->dropCheckConstraintIfExists();
        $this->dropUniqueIndexIfExists();

        Schema::table('competitions', function (Blueprint $table) {
            $drop = [];

            if (Schema::hasColumn('competitions', 'call_number')) {
                $drop[] = 'call_number';
            }

            if (Schema::hasColumn('competitions', 'annual_budget')) {
                $drop[] = 'annual_budget';
            }

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }

    private function dropCheckConstraintIfExists(): void
    {
        $exists = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = ?',
            ['competitions', self::CHECK_CONSTRAINT, 'CHECK']
        );

        if ($exists) {
            DB::statement('ALTER TABLE competitions DROP CHECK '.self::CHECK_CONSTRAINT);
        }
    }

    private function dropUniqueIndexIfExists(): void
    {
        $exists = DB::selectOne(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?
             LIMIT 1',
            ['competitions', self::UNIQUE_INDEX]
        );

        if ($exists) {
            Schema::table('competitions', function (Blueprint $table) {
                $table->dropUnique(self::UNIQUE_INDEX);
            });
        }
    }
};
