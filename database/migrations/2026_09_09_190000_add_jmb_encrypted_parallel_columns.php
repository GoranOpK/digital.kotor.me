<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase A: additive nullable parallel columns for future JMB/JMBG encryption.
     * Does not backfill, encrypt, or change plaintext columns.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'jmb_encrypted')) {
                $table->text('jmb_encrypted')->nullable()->after('jmb');
            }
        });

        Schema::table('physical_person_identities', function (Blueprint $table) {
            if (! Schema::hasColumn('physical_person_identities', 'jmb_encrypted')) {
                $table->text('jmb_encrypted')->nullable()->after('jmb');
            }
        });

        Schema::table('legal_entity_authorized_persons', function (Blueprint $table) {
            if (! Schema::hasColumn('legal_entity_authorized_persons', 'jmb_encrypted')) {
                $table->text('jmb_encrypted')->nullable()->after('jmb');
            }
        });

        Schema::table('foreign_branch_representatives', function (Blueprint $table) {
            if (! Schema::hasColumn('foreign_branch_representatives', 'jmb_encrypted')) {
                $table->text('jmb_encrypted')->nullable()->after('jmb');
            }
        });

        Schema::table('applications', function (Blueprint $table) {
            if (! Schema::hasColumn('applications', 'physical_person_jmbg_encrypted')) {
                $table->text('physical_person_jmbg_encrypted')->nullable()->after('physical_person_jmbg');
            }
            if (! Schema::hasColumn('applications', 'applicant_jmbg_encrypted')) {
                $table->text('applicant_jmbg_encrypted')->nullable()->after('applicant_jmbg');
            }
        });

        Schema::table('business_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('business_plans', 'applicant_jmbg_encrypted')) {
                $table->text('applicant_jmbg_encrypted')->nullable()->after('applicant_jmbg');
            }
        });
    }

    public function down(): void
    {
        $this->dropEncryptedColumn('users', 'jmb_encrypted');
        $this->dropEncryptedColumn('physical_person_identities', 'jmb_encrypted');
        $this->dropEncryptedColumn('legal_entity_authorized_persons', 'jmb_encrypted');
        $this->dropEncryptedColumn('foreign_branch_representatives', 'jmb_encrypted');
        $this->dropEncryptedColumn('applications', 'physical_person_jmbg_encrypted');
        $this->dropEncryptedColumn('applications', 'applicant_jmbg_encrypted');
        $this->dropEncryptedColumn('business_plans', 'applicant_jmbg_encrypted');
    }

    private function dropEncryptedColumn(string $table, string $column): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->dropColumn($column);
        });
    }
};
