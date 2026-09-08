<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_prigovors', function (Blueprint $table) {
            $table->boolean('criterion_1_remaining')->nullable()->after('eliminatory_reason_remaining');
            $table->boolean('criterion_2_remaining')->nullable()->after('criterion_1_remaining');
            $table->boolean('criterion_3_remaining')->nullable()->after('criterion_2_remaining');
        });
    }

    public function down(): void
    {
        Schema::table('application_prigovors', function (Blueprint $table) {
            $table->dropColumn([
                'criterion_1_remaining',
                'criterion_2_remaining',
                'criterion_3_remaining',
            ]);
        });
    }
};
