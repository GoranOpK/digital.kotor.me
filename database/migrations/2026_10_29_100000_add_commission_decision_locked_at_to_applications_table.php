<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vrijeme zaključavanja buduće odluke Ženskog preduzetništva.
 * Postojeći redovi ostaju null i ne dobijaju novi zaključak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (! Schema::hasColumn('applications', 'commission_decision_locked_at')) {
                $table->timestamp('commission_decision_locked_at')
                    ->nullable()
                    ->after('signed_by_chairman');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'commission_decision_locked_at')) {
                $table->dropColumn('commission_decision_locked_at');
            }
        });
    }
};
