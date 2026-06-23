<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'email')) {
                $table->unsignedTinyInteger('email')
                    ->default(0)
                    ->after('rejection_reason')
                    ->comment('0 = obavještenje e-mailom nije poslato, 1 = poslato');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
