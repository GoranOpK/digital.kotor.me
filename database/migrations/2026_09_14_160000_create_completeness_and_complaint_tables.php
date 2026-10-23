<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KN-SYNC-005 — canonical completeness + complaint tables.
 *
 * Additive only. No backfill. No Women/Youth Obrazac 3 rewrite.
 * Timestamp 160000 sits after publication claims (140000) on 2026_09_14.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('application_completeness_assessments')) {
            Schema::create('application_completeness_assessments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->constrained('applications')->restrictOnDelete();
                $table->foreignId('competition_id')->constrained('competitions')->restrictOnDelete();
                $table->string('outcome', 32);
                $table->string('catalog_key')->nullable();
                $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->json('evidence_snapshot');
                $table->char('evidence_fingerprint', 64);
                $table->timestamp('recorded_at');
                $table->timestamp('notice_sent_at')->nullable();
                $table->timestamps();

                $table->index(['application_id', 'id'], 'completeness_assessments_application_idx');
            });
        }

        if (! Schema::hasTable('application_complaints')) {
            Schema::create('application_complaints', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->constrained('applications')->restrictOnDelete();
                $table->foreignId('competition_id')->constrained('competitions')->restrictOnDelete();
                $table->foreignId('completeness_assessment_id')->constrained('application_completeness_assessments')->restrictOnDelete();
                $table->foreignId('submitted_by_user_id')->constrained('users')->restrictOnDelete();
                $table->text('body');
                $table->timestamp('submitted_at');
                $table->dateTime('decision_due_at');
                $table->string('decision', 32)->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('decision_notes')->nullable();
                $table->boolean('decision_late')->default(false);
                $table->text('compliance_warning')->nullable();
                $table->timestamps();

                $table->unique('application_id', 'application_complaints_application_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('application_complaints');
        Schema::dropIfExists('application_completeness_assessments');
    }
};
