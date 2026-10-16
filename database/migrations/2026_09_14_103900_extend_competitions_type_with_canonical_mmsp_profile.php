<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * KN-PO-TYPE-001 / KN-IMPL-002.
 *
 * Prospective extension of competitions.type. Existing zensko, omladinsko and
 * ostalo values stay valid. Default stays ostalo. No row is rewritten.
 */
return new class extends Migration
{
    private const PREVIOUS_ENUM = "ENUM('zensko', 'omladinsko', 'ostalo') NOT NULL DEFAULT 'ostalo'";

    private const EXTENDED_ENUM = "ENUM('zensko', 'omladinsko', 'ostalo', 'preduzetnici_mikro_mala_i_srednja_preduzeca') NOT NULL DEFAULT 'ostalo'";

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `competitions` MODIFY COLUMN `type` '.self::EXTENDED_ENUM);
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $usesCanonicalKey = DB::table('competitions')
            ->where('type', 'preduzetnici_mikro_mala_i_srednja_preduzeca')
            ->exists();

        if ($usesCanonicalKey) {
            throw new RuntimeException(
                'Cannot narrow competitions.type while canonical profile rows exist. This rollback does not rewrite historical types.'
            );
        }

        DB::statement('ALTER TABLE `competitions` MODIFY COLUMN `type` '.self::PREVIOUS_ENUM);
    }
};
