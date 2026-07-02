<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'applications'] as $table) {
            if (!Schema::hasColumn($table, 'pib')) {
                continue;
            }

            DB::statement("UPDATE `{$table}` SET `pib` = LEFT(`pib`, 8) WHERE `pib` IS NOT NULL AND LENGTH(`pib`) > 8");
            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `pib` VARCHAR(8) NULL");
        }

        if (Schema::hasColumn('business_plans', 'pib')) {
            DB::statement("UPDATE `business_plans` SET `pib` = LEFT(`pib`, 8) WHERE `pib` IS NOT NULL AND LENGTH(`pib`) > 8");
        }
    }

    public function down(): void
    {
        foreach (['users', 'applications'] as $table) {
            if (!Schema::hasColumn($table, 'pib')) {
                continue;
            }

            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `pib` VARCHAR(9) NULL");
        }
    }
};
