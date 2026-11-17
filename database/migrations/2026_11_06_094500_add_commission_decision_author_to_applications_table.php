<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trajni identitet predsjednika koji je evidentirao PM zaključak.
 * Postojeći zaključci ostaju bez autora i ne dobijaju naknadno ime.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (! Schema::hasColumn('applications', 'commission_decision_author_member_id')) {
                $table->unsignedBigInteger('commission_decision_author_member_id')
                    ->nullable()
                    ->after('commission_decision_date');
            }
            if (! Schema::hasColumn('applications', 'commission_decision_author_name')) {
                $table->string('commission_decision_author_name')
                    ->nullable()
                    ->after('commission_decision_author_member_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('applications', 'commission_decision_author_name')) {
                $drop[] = 'commission_decision_author_name';
            }
            if (Schema::hasColumn('applications', 'commission_decision_author_member_id')) {
                $drop[] = 'commission_decision_author_member_id';
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
