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
        // Koristimo raw SQL jer change() zahteva doctrine/dbal
        DB::statement('ALTER TABLE `competitions` MODIFY `start_date` DATE NULL');
        DB::statement('ALTER TABLE `competitions` MODIFY `end_date` DATE NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Vraćamo kolone na NOT NULL
        DB::statement('ALTER TABLE `competitions` MODIFY `start_date` DATE NOT NULL');
        DB::statement('ALTER TABLE `competitions` MODIFY `end_date` DATE NOT NULL');
    }
};
