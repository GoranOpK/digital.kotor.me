<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'bonus_zavod_nezaposleni')) {
                $table->boolean('bonus_zavod_nezaposleni')
                    ->default(false)
                    ->after('bonus_new_business')
                    ->comment('Dodatna 2 boda – podnositeljka na evidenciji Zavoda za zapošljavanje duže od 12 mjeseci');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'bonus_zavod_nezaposleni')) {
                $table->dropColumn('bonus_zavod_nezaposleni');
            }
        });
    }
};
