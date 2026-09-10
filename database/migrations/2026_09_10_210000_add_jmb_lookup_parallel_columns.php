<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public const USERS_UNIQUE = 'users_jmb_lookup_unique';

    public const PHYSICAL_PERSON_INDEX = 'physical_person_identities_jmb_lookup_index';

    /**
     * Additive nullable lookup columns for future JMB uniqueness.
     * Does not backfill, compute digests, or change runtime reads/writes.
     * Does not replace or drop users.jmb UNIQUE.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'jmb_lookup')) {
                $table->char('jmb_lookup', 64)->nullable()->after('jmb_encrypted');
                $table->unique('jmb_lookup', self::USERS_UNIQUE);
            }
        });

        Schema::table('physical_person_identities', function (Blueprint $table) {
            if (! Schema::hasColumn('physical_person_identities', 'jmb_lookup')) {
                $table->char('jmb_lookup', 64)->nullable()->after('jmb_encrypted');
                $table->index('jmb_lookup', self::PHYSICAL_PERSON_INDEX);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('physical_person_identities', 'jmb_lookup')) {
            Schema::table('physical_person_identities', function (Blueprint $table) {
                $table->dropIndex(self::PHYSICAL_PERSON_INDEX);
                $table->dropColumn('jmb_lookup');
            });
        }

        if (Schema::hasColumn('users', 'jmb_lookup')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(self::USERS_UNIQUE);
                $table->dropColumn('jmb_lookup');
            });
        }
    }
};
