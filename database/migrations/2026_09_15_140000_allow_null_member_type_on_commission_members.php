<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE commission_members
            MODIFY COLUMN member_type ENUM('opstina', 'udruzenje', 'zene_mreza') NULL
        ");
    }

    public function down(): void
    {
        $nullCount = (int) DB::table('commission_members')
            ->whereNull('member_type')
            ->count();

        if ($nullCount > 0) {
            throw new \RuntimeException(
                "Cannot restore member_type NOT NULL: {$nullCount} row(s) have member_type IS NULL."
            );
        }

        DB::statement("
            ALTER TABLE commission_members
            MODIFY COLUMN member_type ENUM('opstina', 'udruzenje', 'zene_mreza') NOT NULL
        ");
    }
};
