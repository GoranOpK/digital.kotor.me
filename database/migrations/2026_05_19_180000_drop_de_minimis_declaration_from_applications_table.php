<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('applications', 'de_minimis_declaration')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropColumn('de_minimis_declaration');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('applications', 'de_minimis_declaration')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->boolean('de_minimis_declaration')->default(false)->after('vat_number');
            });
        }
    }
};
