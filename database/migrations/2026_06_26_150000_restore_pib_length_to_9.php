<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'pib')) {
            DB::statement('ALTER TABLE `users` MODIFY COLUMN `pib` VARCHAR(9) NULL');
        }

        if (Schema::hasColumn('applications', 'pib')) {
            DB::statement('ALTER TABLE `applications` MODIFY COLUMN `pib` VARCHAR(9) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'pib')) {
            DB::statement("UPDATE `users` SET `pib` = LEFT(`pib`, 8) WHERE LENGTH(`pib`) = 9 AND `pib` IS NOT NULL");
            DB::statement('ALTER TABLE `users` MODIFY COLUMN `pib` VARCHAR(8) NULL');
        }

        if (Schema::hasColumn('applications', 'pib')) {
            DB::statement("UPDATE `applications` SET `pib` = LEFT(`pib`, 8) WHERE LENGTH(`pib`) = 9 AND `pib` IS NOT NULL");
            DB::statement('ALTER TABLE `applications` MODIFY COLUMN `pib` VARCHAR(8) NULL');
        }
    }
};
