<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('business_plans', 'finances_notice_confirmed')) {
            Schema::table('business_plans', function (Blueprint $table) {
                $table->boolean('finances_notice_confirmed')->default(false)->after('requested_amount');
            });
        }

        DB::table('business_plans')
            ->where('requested_amount', '>', 0)
            ->update(['finances_notice_confirmed' => true]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('business_plans', 'finances_notice_confirmed')) {
            Schema::table('business_plans', function (Blueprint $table) {
                $table->dropColumn('finances_notice_confirmed');
            });
        }
    }
};
