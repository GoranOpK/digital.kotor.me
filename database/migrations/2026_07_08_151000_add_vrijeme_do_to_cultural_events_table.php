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
        Schema::table('cultural_events', function (Blueprint $table) {
            $table->time('vrijeme_do')->nullable()->after('vrijeme');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cultural_events', function (Blueprint $table) {
            $table->dropColumn('vrijeme_do');
        });
    }
};

