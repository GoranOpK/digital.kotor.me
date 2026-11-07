<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MMSP only: an existing TIMESTAMP column may have MySQL's implicit
 * ON UPDATE CURRENT_TIMESTAMP behavior (older server configurations).
 * That silently replaces the original complaint submission time when
 * the Commission records its decision.
 *
 * DATETIME stores the existing displayed wall-clock value unchanged and
 * has no implicit ON UPDATE behavior. No complaint records are rewritten
 * by application code; existing values are preserved during conversion.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)
            || ! Schema::hasTable('application_complaints')) {
            return;
        }

        $column = DB::selectOne(
            "SELECT DATA_TYPE AS data_type FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'application_complaints'
               AND COLUMN_NAME = 'submitted_at'"
        );

        if ($column && strtolower((string) $column->data_type) === 'timestamp') {
            DB::statement('ALTER TABLE `application_complaints` MODIFY COLUMN `submitted_at` DATETIME NOT NULL');
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: do not restore a column definition
        // that can silently overwrite the legally significant timestamp.
    }
};
