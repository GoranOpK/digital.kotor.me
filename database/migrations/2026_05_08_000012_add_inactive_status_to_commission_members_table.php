<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE commission_members
            MODIFY COLUMN status ENUM('active', 'inactive', 'resigned', 'dismissed') NOT NULL DEFAULT 'active'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pri rollback-u vrati privremeno neaktivne na dismissed da enum ostane validan.
        DB::table('commission_members')
            ->where('status', 'inactive')
            ->update(['status' => 'dismissed']);

        DB::statement("
            ALTER TABLE commission_members
            MODIFY COLUMN status ENUM('active', 'resigned', 'dismissed') NOT NULL DEFAULT 'active'
        ");
    }
};
