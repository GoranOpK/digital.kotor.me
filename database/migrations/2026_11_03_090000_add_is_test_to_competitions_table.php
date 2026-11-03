<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            if (! Schema::hasColumn('competitions', 'is_test')) {
                $table->boolean('is_test')->default(false)->after('is_test_preview');
            }
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            if (Schema::hasColumn('competitions', 'is_test')) {
                $table->dropColumn('is_test');
            }
        });
    }
};
