<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_prigovors', function (Blueprint $table) {
            $table->boolean('criterion_1_contested')->nullable()->after('criterion_3_remaining');
            $table->boolean('criterion_2_contested')->nullable()->after('criterion_1_contested');
            $table->boolean('criterion_3_contested')->nullable()->after('criterion_2_contested');
        });
    }

    public function down(): void
    {
        Schema::table('application_prigovors', function (Blueprint $table) {
            $table->dropColumn([
                'criterion_1_contested',
                'criterion_2_contested',
                'criterion_3_contested',
            ]);
        });
    }
};
