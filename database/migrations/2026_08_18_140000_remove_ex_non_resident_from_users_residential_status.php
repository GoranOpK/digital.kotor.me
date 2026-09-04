<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_STATUS = 'ex-non-resident';

    private const NARROW_ENUM = "ENUM('resident','non-resident') NULL";

    private const WIDE_ENUM = "ENUM('resident','non-resident','ex-non-resident') NULL";

    public function up(): void
    {
        if (! $this->hasResidentialStatusColumn()) {
            return;
        }

        $legacyCount = (int) DB::table('users')
            ->where('residential_status', self::LEGACY_STATUS)
            ->count();

        if ($legacyCount > 0) {
            throw new \RuntimeException(
                "Cannot narrow users.residential_status: {$legacyCount} row(s) still use legacy value ex-non-resident. Product Owner mapping decision required."
            );
        }

        DB::statement('ALTER TABLE `users` MODIFY COLUMN `residential_status` '.self::NARROW_ENUM);
    }

    public function down(): void
    {
        if (! $this->hasResidentialStatusColumn()) {
            return;
        }

        DB::statement('ALTER TABLE `users` MODIFY COLUMN `residential_status` '.self::WIDE_ENUM);
    }

    private function hasResidentialStatusColumn(): bool
    {
        return Schema::hasTable('users') && Schema::hasColumn('users', 'residential_status');
    }
};
