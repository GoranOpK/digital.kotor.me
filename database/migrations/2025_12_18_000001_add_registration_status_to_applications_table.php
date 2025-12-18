<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'is_registered')) {
                $table->boolean('is_registered')->default(true)->after('business_stage');
            }
            if (!Schema::hasColumn('applications', 'accuracy_declaration')) {
                $table->boolean('accuracy_declaration')->default(false)->after('previous_support_declaration');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'is_registered')) {
                $table->dropColumn('is_registered');
            }
            if (Schema::hasColumn('applications', 'accuracy_declaration')) {
                $table->dropColumn('accuracy_declaration');
            }
        });
    }
};

