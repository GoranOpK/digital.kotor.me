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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'pib')) {
                // Promeni dužinu kolone PIB sa 9 na 8 karaktera
                DB::statement('ALTER TABLE `users` MODIFY COLUMN `pib` VARCHAR(8) NULL');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'pib')) {
                // Vrati dužinu kolone PIB na 9 karaktera
                DB::statement('ALTER TABLE `users` MODIFY COLUMN `pib` VARCHAR(9) NULL');
            }
        });
    }
};

