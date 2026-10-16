<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KN-SYNC-006 — canonical Commission governance foundation.
 *
 * Additive and nullable only. Existing Women/Youth rows keep NULL in every new column,
 * so the canonical-only unique keys never collide with them. No backfill.
 * Runs after commission_sessions (2026_09_15_140100) and the latest Youth migrations (2026_09_22).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->string('profile_type', 64)->nullable()->after('status');
            $table->string('appointment_reference')->nullable()->after('profile_type');
            $table->timestamp('appointed_at')->nullable()->after('appointment_reference');
            $table->timestamp('mandate_starts_at')->nullable()->after('appointed_at');
            $table->timestamp('mandate_ends_at')->nullable()->after('mandate_starts_at');
        });

        Schema::table('commission_members', function (Blueprint $table) {
            $table->string('canonical_seat', 16)->nullable()->after('canonical_seat_no');
            $table->string('external_category', 32)->nullable()->after('canonical_seat');
            $table->char('represented_company_pib', 8)->nullable()->after('external_category');
            $table->string('appointment_reference')->nullable()->after('represented_company_pib');
            $table->timestamp('appointed_at')->nullable()->after('appointment_reference');
            $table->timestamp('replaced_at')->nullable()->after('appointed_at');
            $table->foreignId('replaced_by_member_id')->nullable()->after('replaced_at')
                ->constrained('commission_members')->nullOnDelete();

            // Maintained by CanonicalCommissionMembershipService: equal to the member value while the
            // canonical membership is active, NULL otherwise. MySQL UNIQUE ignores NULL.
            $table->unsignedBigInteger('canonical_active_user_id')->nullable()->after('replaced_by_member_id');
            $table->string('canonical_active_seat', 16)->nullable()->after('canonical_active_user_id');
            $table->unsignedTinyInteger('canonical_active_seat_no')->nullable()->after('canonical_active_seat');

            $table->unique(['commission_id', 'canonical_active_user_id'], 'commission_members_canonical_active_user_unique');
            $table->unique(['commission_id', 'canonical_active_seat'], 'commission_members_canonical_active_seat_unique');
            $table->unique(['commission_id', 'canonical_active_seat_no'], 'commission_members_canonical_active_seat_no_unique');
        });

        Schema::create('commission_member_replacements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_id')->constrained('commissions')->restrictOnDelete();
            $table->unsignedTinyInteger('seat_no');
            $table->string('canonical_seat', 16);
            $table->foreignId('vacated_member_id')->constrained('commission_members')->restrictOnDelete();
            $table->foreignId('replacement_member_id')->nullable()->constrained('commission_members')->restrictOnDelete();
            $table->string('vacancy_reason', 64);
            $table->timestamp('vacancy_triggered_at');
            $table->dateTime('due_at');
            $table->timestamp('replaced_at')->nullable();
            $table->boolean('late')->default(false);
            $table->text('compliance_warning')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('vacated_member_id', 'commission_member_replacements_vacated_unique');
        });

        Schema::create('commission_governance_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_id')->constrained('commissions')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('commission_member_id')->nullable()->constrained('commission_members')->nullOnDelete();
            $table->foreignId('competition_id')->nullable()->constrained('competitions')->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();
            $table->string('conflict_type', 64);
            $table->string('normalized_value', 32);
            $table->text('reason');
            $table->timestamp('detected_at');
            $table->foreignId('detected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['commission_id', 'conflict_type'], 'commission_governance_conflicts_type_idx');
        });

        Schema::table('commission_sessions', function (Blueprint $table) {
            $table->string('canonical_purpose', 32)->nullable()->after('session_type');
            $table->timestamp('due_at')->nullable()->after('completed_at');
            $table->boolean('late')->default(false)->after('due_at');
            $table->text('compliance_warning')->nullable()->after('late');
        });

        Schema::table('commission_session_attendances', function (Blueprint $table) {
            $table->string('attendance_mode', 16)->nullable()->after('present');
        });
    }

    public function down(): void
    {
        Schema::table('commission_session_attendances', function (Blueprint $table) {
            $table->dropColumn('attendance_mode');
        });

        Schema::table('commission_sessions', function (Blueprint $table) {
            $table->dropColumn(['canonical_purpose', 'due_at', 'late', 'compliance_warning']);
        });

        Schema::dropIfExists('commission_governance_conflicts');
        Schema::dropIfExists('commission_member_replacements');

        // MySQL drops the implicit commission_id FK index once a composite index can serve the FK,
        // so the FK needs a standalone index before the canonical uniques can be dropped.
        if (! $this->hasStandaloneCommissionIdIndex()) {
            Schema::table('commission_members', function (Blueprint $table) {
                $table->index('commission_id', 'commission_members_commission_id_foreign');
            });
        }

        Schema::table('commission_members', function (Blueprint $table) {
            $table->dropUnique('commission_members_canonical_active_user_unique');
            $table->dropUnique('commission_members_canonical_active_seat_unique');
            $table->dropUnique('commission_members_canonical_active_seat_no_unique');
            $table->dropConstrainedForeignId('replaced_by_member_id');
            $table->dropColumn([
                'canonical_seat',
                'external_category',
                'represented_company_pib',
                'appointment_reference',
                'appointed_at',
                'replaced_at',
                'canonical_active_user_id',
                'canonical_active_seat',
                'canonical_active_seat_no',
            ]);
        });

        Schema::table('commissions', function (Blueprint $table) {
            $table->dropColumn(['profile_type', 'appointment_reference', 'appointed_at', 'mandate_starts_at', 'mandate_ends_at']);
        });
    }

    private function hasStandaloneCommissionIdIndex(): bool
    {
        foreach (Schema::getIndexes('commission_members') as $index) {
            if ($index['columns'] === ['commission_id']) {
                return true;
            }
        }

        return false;
    }
};
