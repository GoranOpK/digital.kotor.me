<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive MySQL ENUM expansion for omladinsko applicant types.
 *
 * Keeps existing values: preduzetnica, doo, fizicko_lice, ostalo.
 * Adds: preduzetnik, privredno_drustvo.
 * Converts VARCHAR(255) from a fresh migration history to the same ENUM.
 * Does not convert ENUM to VARCHAR.
 * Does not rewrite existing zensko rows.
 *
 * Safe rollback: never shrinks applicant_type ENUM. If rows already use the
 * new values, down() refuses to run. company_legal_form may be dropped only
 * when no non-null values remain.
 */
return new class extends Migration
{
    public const APPLICANT_TYPE_ENUM_SQL = "ENUM('preduzetnica','doo','fizicko_lice','ostalo','preduzetnik','privredno_drustvo')";

    public const ALLOWED_APPLICANT_TYPES = [
        'preduzetnica',
        'doo',
        'fizicko_lice',
        'ostalo',
        'preduzetnik',
        'privredno_drustvo',
    ];

    public const NEW_APPLICANT_TYPES = ['preduzetnik', 'privredno_drustvo'];

    public const COMPANY_LEGAL_FORMS = ['doo', 'ad', 'od', 'kd'];

    public function up(): void
    {
        $this->normalizeApplicantTypeToCanonicalEnum();

        Schema::table('applications', function (Blueprint $table) {
            if (! Schema::hasColumn('applications', 'company_legal_form')) {
                $table->enum('company_legal_form', self::COMPANY_LEGAL_FORMS)
                    ->nullable()
                    ->after('applicant_type');
            }
        });
    }

    public function down(): void
    {
        if ($this->applicantTypeColumnIsEnum()) {
            $inUse = DB::table('applications')
                ->whereIn('applicant_type', self::NEW_APPLICANT_TYPES)
                ->exists();

            if ($inUse) {
                throw new RuntimeException(
                    'Cannot roll back applications.applicant_type ENUM: rows already use preduzetnik or privredno_drustvo. Rollback must not shrink the ENUM or remove those values.'
                );
            }

            // Even when unused, shrinking MySQL ENUM is unsafe for mixed environments.
            // Leave the additive values in place. Only drop company_legal_form below.
        }

        if (Schema::hasColumn('applications', 'company_legal_form')) {
            $legalFormInUse = DB::table('applications')
                ->whereNotNull('company_legal_form')
                ->exists();

            if ($legalFormInUse) {
                throw new RuntimeException(
                    'Cannot drop applications.company_legal_form: non-null values exist.'
                );
            }

            Schema::table('applications', function (Blueprint $table) {
                $table->dropColumn('company_legal_form');
            });
        }
    }

    private function normalizeApplicantTypeToCanonicalEnum(): void
    {
        $columnType = $this->applicantTypeColumnType();
        if ($columnType === null) {
            return;
        }

        $normalized = strtolower($columnType);
        $isEnum = str_starts_with($normalized, 'enum(');
        $isVarchar = str_starts_with($normalized, 'varchar');

        if (! $isEnum && ! $isVarchar) {
            throw new RuntimeException(
                'Cannot normalize applications.applicant_type: unexpected column type '.$columnType.'.'
            );
        }

        $this->assertExistingApplicantTypesAreAllowed();

        DB::statement(
            'ALTER TABLE applications MODIFY COLUMN applicant_type '.self::APPLICANT_TYPE_ENUM_SQL.' NULL'
        );
    }

    private function assertExistingApplicantTypesAreAllowed(): void
    {
        $existing = DB::table('applications')
            ->whereNotNull('applicant_type')
            ->distinct()
            ->pluck('applicant_type')
            ->map(static fn ($value): string => (string) $value)
            ->all();

        $unknown = array_values(array_diff($existing, self::ALLOWED_APPLICANT_TYPES));
        if ($unknown === []) {
            return;
        }

        throw new RuntimeException(
            'Cannot convert applications.applicant_type to ENUM: existing values are outside the allowed set: '.implode(', ', $unknown).'. Existing data was not changed.'
        );
    }

    private function applicantTypeColumnIsEnum(): bool
    {
        $columnType = $this->applicantTypeColumnType();

        return $columnType !== null && str_starts_with(strtolower($columnType), 'enum(');
    }

    private function applicantTypeColumnType(): ?string
    {
        $column = DB::selectOne(
            'SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['applications', 'applicant_type']
        );

        if ($column === null) {
            return null;
        }

        return (string) $column->COLUMN_TYPE;
    }
};
