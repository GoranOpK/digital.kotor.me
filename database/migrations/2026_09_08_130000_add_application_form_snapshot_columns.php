<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive Application snapshot columns for Obrazac 1a/1b contact and address.
     * Nullable for backward compatibility. No backfill. No identity schema change.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (! Schema::hasColumn('applications', 'physical_person_address')) {
                $table->string('physical_person_address', 500)->nullable()->after('physical_person_email');
            }
            if (! Schema::hasColumn('applications', 'preduzetnik_name')) {
                $table->string('preduzetnik_name')->nullable()->after('physical_person_address');
            }
            if (! Schema::hasColumn('applications', 'preduzetnik_phone')) {
                $table->string('preduzetnik_phone', 50)->nullable()->after('preduzetnik_name');
            }
            if (! Schema::hasColumn('applications', 'preduzetnik_email')) {
                $table->string('preduzetnik_email')->nullable()->after('preduzetnik_phone');
            }
            if (! Schema::hasColumn('applications', 'preduzetnik_address')) {
                $table->string('preduzetnik_address', 500)->nullable()->after('preduzetnik_email');
            }
            if (! Schema::hasColumn('applications', 'doo_name')) {
                $table->string('doo_name')->nullable()->after('preduzetnik_address');
            }
            if (! Schema::hasColumn('applications', 'doo_phone')) {
                $table->string('doo_phone', 50)->nullable()->after('doo_name');
            }
            if (! Schema::hasColumn('applications', 'doo_email')) {
                $table->string('doo_email')->nullable()->after('doo_phone');
            }
            if (! Schema::hasColumn('applications', 'doo_address')) {
                $table->string('doo_address', 500)->nullable()->after('doo_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $columns = [
                'physical_person_address',
                'preduzetnik_name',
                'preduzetnik_phone',
                'preduzetnik_email',
                'preduzetnik_address',
                'doo_name',
                'doo_phone',
                'doo_email',
                'doo_address',
            ];

            $existing = array_values(array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn('applications', $column)
            ));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
