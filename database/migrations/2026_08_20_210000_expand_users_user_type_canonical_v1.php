<?php

use App\Support\UserType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->hasUserTypeColumn()) {
            return;
        }

        DB::statement('ALTER TABLE `users` MODIFY COLUMN `user_type` '.$this->enumSql(UserType::mysqlEnumValues()).' NULL');
    }

    public function down(): void
    {
        if (! $this->hasUserTypeColumn()) {
            return;
        }

        $newValues = [
            UserType::NGO_ASSOCIATION,
            UserType::SPORTS_ORGANIZATION,
        ];

        $blockingCount = (int) DB::table('users')->whereIn('user_type', $newValues)->count();

        if ($blockingCount > 0) {
            throw new \RuntimeException(
                "Cannot roll back users.user_type enum: {$blockingCount} row(s) use canonical V1 values added by this migration."
            );
        }

        $previous = [
            UserType::PHYSICAL_PERSON,
            UserType::ENTREPRENEUR,
            UserType::GENERAL_PARTNERSHIP,
            UserType::LIMITED_PARTNERSHIP,
            UserType::LIMITED_LIABILITY_COMPANY,
            UserType::JOINT_STOCK_COMPANY,
            UserType::LEGACY_FOREIGN_BRANCH,
            UserType::LEGACY_ASSOCIATION_BUNDLE,
            UserType::LEGACY_INSTITUTION_BUNDLE,
            UserType::LEGACY_OTHER_ORGANIZATIONS,
        ];

        DB::statement('ALTER TABLE `users` MODIFY COLUMN `user_type` '.$this->enumSql($previous).' NULL');
    }

    /**
     * @param  list<string>  $values
     */
    private function enumSql(array $values): string
    {
        $quoted = array_map(
            fn (string $value): string => "'".str_replace("'", "''", $value)."'",
            $values
        );

        return 'ENUM('.implode(',', $quoted).')';
    }

    private function hasUserTypeColumn(): bool
    {
        return Schema::hasTable('users') && Schema::hasColumn('users', 'user_type');
    }
};
