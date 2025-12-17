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
            // Učini kolonu business_plan nullable jer se sada koristi odvojeni model BusinessPlan
            if (Schema::hasColumn('applications', 'business_plan')) {
                DB::statement('ALTER TABLE `applications` MODIFY `business_plan` TEXT NULL');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // Vraćamo kolonu na NOT NULL
            if (Schema::hasColumn('applications', 'business_plan')) {
                DB::statement('ALTER TABLE `applications` MODIFY `business_plan` TEXT NOT NULL');
            }
        });
    }
};
