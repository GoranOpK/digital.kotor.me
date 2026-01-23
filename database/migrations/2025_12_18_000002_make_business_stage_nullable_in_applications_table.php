<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'business_stage')) {
                // Promeni kolonu business_stage da bude nullable za draft prijave
                DB::statement('ALTER TABLE `applications` MODIFY COLUMN `business_stage` VARCHAR(255) NULL');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'business_stage')) {
                // Vrati kolonu na NOT NULL (sa default vrednošću)
                DB::statement('ALTER TABLE `applications` MODIFY COLUMN `business_stage` VARCHAR(255) NOT NULL DEFAULT "započinjanje"');
            }
        });
    }
};

