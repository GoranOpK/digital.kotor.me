<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive MMSP-only column. Existing completeness rows stay unchanged.
 * Women/Youth Obrazac 3 does not use this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('application_completeness_assessments')
            || Schema::hasColumn('application_completeness_assessments', 'criterion_answers')) {
            return;
        }

        Schema::table('application_completeness_assessments', function (Blueprint $table) {
            $table->json('criterion_answers')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('application_completeness_assessments')
            || ! Schema::hasColumn('application_completeness_assessments', 'criterion_answers')) {
            return;
        }

        Schema::table('application_completeness_assessments', function (Blueprint $table) {
            $table->dropColumn('criterion_answers');
        });
    }
};
