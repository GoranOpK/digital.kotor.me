<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KN-PO-FINAL-002 — canonical mail delivery bookkeeping.
 *
 * Additive, nullable only, no backfill. notice_sent_at / submitted_at / decision_due_at /
 * decided_at stay the legal events; these columns only record email delivery outcome.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_completeness_assessments', function (Blueprint $table) {
            $table->timestamp('mail_sent_at')->nullable()->after('notice_sent_at');
            $table->timestamp('mail_failed_at')->nullable()->after('mail_sent_at');
        });

        Schema::table('application_complaints', function (Blueprint $table) {
            $table->timestamp('mail_sent_at')->nullable()->after('compliance_warning');
            $table->timestamp('mail_failed_at')->nullable()->after('mail_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('application_complaints', function (Blueprint $table) {
            $table->dropColumn(['mail_sent_at', 'mail_failed_at']);
        });

        Schema::table('application_completeness_assessments', function (Blueprint $table) {
            $table->dropColumn(['mail_sent_at', 'mail_failed_at']);
        });
    }
};
