<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            if (! Schema::hasColumn('competitions', 'is_test_preview')) {
                $table->boolean('is_test_preview')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            if (Schema::hasColumn('competitions', 'is_test_preview')) {
                $table->dropColumn('is_test_preview');
            }
        });
    }
};
