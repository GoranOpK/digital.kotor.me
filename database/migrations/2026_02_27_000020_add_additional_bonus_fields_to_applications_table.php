<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'bonus_info_day')) {
                $table->boolean('bonus_info_day')
                    ->default(false)
                    ->after('final_score')
                    ->comment('Dodatni bod za prisustvovanje Info danu i radionici „Forma za biznis plan – Obrazac 2“');
            }

            if (!Schema::hasColumn('applications', 'bonus_new_business')) {
                $table->boolean('bonus_new_business')
                    ->default(false)
                    ->after('bonus_info_day')
                    ->comment('Dodatna 2 boda za novi biznis (nema već registrovanu djelatnost)');
            }

            if (!Schema::hasColumn('applications', 'bonus_green_innovative')) {
                $table->boolean('bonus_green_innovative')
                    ->default(false)
                    ->after('bonus_new_business')
                    ->comment('Dodatna 3 boda za inovativnu i/ili „zelenu“ biznis ideju');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'bonus_info_day')) {
                $table->dropColumn('bonus_info_day');
            }
            if (Schema::hasColumn('applications', 'bonus_new_business')) {
                $table->dropColumn('bonus_new_business');
            }
            if (Schema::hasColumn('applications', 'bonus_green_innovative')) {
                $table->dropColumn('bonus_green_innovative');
            }
        });
    }
};

