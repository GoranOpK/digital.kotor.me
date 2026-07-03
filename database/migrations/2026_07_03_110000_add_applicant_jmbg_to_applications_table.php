<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('applications', 'applicant_jmbg')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->string('applicant_jmbg', 13)->nullable()->after('company_seat');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('applications', 'applicant_jmbg')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropColumn('applicant_jmbg');
            });
        }
    }
};
