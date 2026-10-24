<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            if (! Schema::hasColumn('competitions', 'canonical_test_applications_closed_at')) {
                $table->timestamp('canonical_test_applications_closed_at')->nullable()->after('is_test_preview');
            }
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            if (Schema::hasColumn('competitions', 'canonical_test_applications_closed_at')) {
                $table->dropColumn('canonical_test_applications_closed_at');
            }
        });
    }
};
